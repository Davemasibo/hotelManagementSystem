<?php
session_start();

class Action {
    private $db;

    public function __construct() {
        ob_start();
        include 'db_connect.php';
        $this->db = $conn;
    }

    public function __destruct() {
        if ($this->db) $this->db->close();
        // The JSON endpoint may have already drained the buffers before exit;
        // only flush if one is still open to avoid a stray "no buffer" notice.
        if (ob_get_level() > 0) ob_end_flush();
    }

    // =====================================================
    // PRIVATE HELPERS
    // =====================================================

    private function clean($str) {
        return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Whether a column exists, so the app keeps working on a database where the
     * newest migration has not been imported yet. Cached for the request.
     */
    private function has_column($table, $column) {
        static $cache = [];
        $key = "$table.$column";
        if (!array_key_exists($key, $cache)) {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1"
            );
            $stmt->bind_param("ss", $table, $column);
            $stmt->execute();
            $cache[$key] = $stmt->get_result()->num_rows > 0;
            $stmt->close();
        }
        return $cache[$key];
    }

    /**
     * Shared validation for booking / check-in submissions. Returns an error
     * string, or null when the reservation details are acceptable.
     *
     * Phone and ID are mandatory, and a stay may not start in the past — the
     * browser is told the same thing via `required` and `min`, but a form can
     * always be bypassed, so the rule is enforced here as well.
     */
    private function validate_reservation($name, $contact, $id_number, $date_in, $allow_past = false) {
        if ($name === '')      return 'Guest name is required';
        if ($contact === '')   return 'Phone number is required';
        if ($id_number === '') return 'ID / passport number is required';

        // Strip formatting (spaces, dashes, brackets, leading +) before counting digits.
        $digits = preg_replace('/\D/', '', $contact);
        if (strlen($digits) < 7) return 'Enter a valid phone number (at least 7 digits)';

        if (!$allow_past) {
            $in = strtotime($date_in);
            if ($in === false) return 'Enter a valid check-in date';
            if (date('Y-m-d', $in) < date('Y-m-d')) {
                return 'Check-in date cannot be in the past';
            }
        }
        return null;
    }

    /**
     * Build the check-out datetime: check-in date + N nights, at the hotel's
     * check-out time (defaults to 11:00 when the form does not supply one).
     */
    private function build_date_out($datetime_in, $days, $date_out_time) {
        $date_out_time = preg_match('/^\d{2}:\d{2}$/', $date_out_time) ? $date_out_time : '11:00';
        $out_date = date('Y-m-d', strtotime($datetime_in . " +{$days} days"));
        return $out_date . ' ' . $date_out_time;
    }

    private function log_action($action, $module, $record_id = null, $description = '') {
        $user_id   = $_SESSION['login_id']   ?? null;
        $user_name = $_SESSION['login_name'] ?? 'System';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (user_id,user_name,action,module,record_id,description,ip_address)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param("isssiis", $user_id, $user_name, $action, $module, $record_id, $description, $ip);
        $stmt->execute();
        $stmt->close();
    }

    private function push_notification($type, $title, $message, $ref_id = null, $ref_type = null, $priority = 'normal') {
        // Broadcast to all users (user_id = NULL means everyone)
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id,type,title,message,reference_id,reference_type,priority)
             VALUES (NULL,?,?,?,?,?,?)"
        );
        $stmt->bind_param("sssiis", $type, $title, $message, $ref_id, $ref_type, $priority);
        $stmt->execute();
        $stmt->close();
    }

    private function generate_invoice_no() {
        $year  = date('Y');
        $month = date('m');
        $res = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM invoices
             WHERE YEAR(created_at)='$year' AND MONTH(created_at)='$month'"
        );
        $cnt = ($res ? (int)$res->fetch_assoc()['cnt'] : 0) + 1;
        return 'INV-' . $year . $month . '-' . str_pad($cnt, 4, '0', STR_PAD_LEFT);
    }

    private function recalculate_invoice($invoice_id) {
        $id = (int)$invoice_id;

        $r1 = $this->db->query("SELECT COALESCE(SUM(amount),0) AS s FROM invoice_items WHERE invoice_id=$id AND item_type != 'discount'");
        $subtotal = (float)$r1->fetch_assoc()['s'];

        $r2 = $this->db->query("SELECT COALESCE(SUM(amount),0) AS d FROM invoice_items WHERE invoice_id=$id AND item_type = 'discount'");
        $discount = (float)$r2->fetch_assoc()['d'];

        $r3 = $this->db->query("SELECT tax_rate FROM invoices WHERE id=$id");
        $tax_rate = (float)$r3->fetch_assoc()['tax_rate'];

        $taxable    = $subtotal - $discount;
        $tax_amount = round($taxable * ($tax_rate / 100), 2);
        $total      = round($taxable + $tax_amount, 2);

        $r4 = $this->db->query("SELECT COALESCE(SUM(amount),0) AS p FROM payments WHERE invoice_id=$id");
        $paid    = (float)$r4->fetch_assoc()['p'];
        $balance = round($total - $paid, 2);

        if ($paid <= 0)              $status = 'issued';
        elseif ($balance <= 0.001)   $status = 'paid';
        else                         $status = 'partial';

        $paid_at = ($status === 'paid') ? 'NOW()' : 'NULL';
        $this->db->query(
            "UPDATE invoices SET subtotal=$subtotal, tax_amount=$tax_amount,
             discount_amount=$discount, total=$total, amount_paid=$paid, balance=$balance,
             status='$status', paid_at=" . ($status === 'paid' ? 'NOW()' : 'NULL') . "
             WHERE id=$id"
        );
        return compact('subtotal', 'tax_amount', 'discount', 'total', 'paid', 'balance', 'status');
    }

    private function create_invoice_for_checkin($checked_id, $room_id, $nights, $guest_id) {
        $room_id    = (int)$room_id;
        $checked_id = (int)$checked_id;
        $nights     = max(1, (int)$nights);

        $stmt = $this->db->prepare(
            "SELECT rc.price, rc.name AS cat_name, r.room
             FROM rooms r JOIN room_categories rc ON r.category_id = rc.id
             WHERE r.id = ?"
        );
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $price_per_night = (float)($room['price'] ?? 0);
        $room_charge     = round($price_per_night * $nights, 2);
        $desc            = ($room['room'] ?? 'Room') . ' - ' . ($room['cat_name'] ?? '') . " x {$nights} night(s)";

        // Editing a stay re-runs check-in. Reuse the invoice already raised for
        // this reservation — refreshing its room charge for the new dates —
        // instead of issuing a second invoice for the same guest.
        $find = $this->db->prepare("SELECT id FROM invoices WHERE checked_id=? ORDER BY id LIMIT 1");
        $find->bind_param("i", $checked_id);
        $find->execute();
        $existing = $find->get_result()->fetch_assoc();
        $find->close();

        if ($existing) {
            $invoice_id = (int)$existing['id'];
            $upd = $this->db->prepare(
                "UPDATE invoice_items SET description=?, quantity=?, unit_price=?, amount=?
                 WHERE invoice_id=? AND item_type='room_charge'"
            );
            $qty = (float)$nights;
            $upd->bind_param("sdddi", $desc, $qty, $price_per_night, $room_charge, $invoice_id);
            $upd->execute();
            $touched = $upd->affected_rows;
            $upd->close();

            // No room-charge line yet (hand-built invoice) — add one.
            if ($touched === 0) {
                $item_type = 'room_charge';
                $ins = $this->db->prepare(
                    "INSERT INTO invoice_items (invoice_id,description,item_type,quantity,unit_price,amount)
                     VALUES (?,?,?,?,?,?)"
                );
                $ins->bind_param("issddd", $invoice_id, $desc, $item_type, $qty, $price_per_night, $room_charge);
                $ins->execute();
                $ins->close();
            }

            $this->recalculate_invoice($invoice_id);
            return $invoice_id;
        }

        $invoice_no = $this->generate_invoice_no();
        $user_id    = $_SESSION['login_id'] ?? null;
        $due_date   = date('Y-m-d', strtotime('+7 days'));

        $stmt = $this->db->prepare(
            "INSERT INTO invoices (invoice_no,checked_id,guest_id,subtotal,total,balance,status,due_date,created_by,issued_at)
             VALUES (?,?,?,?,?,?,'issued',?,?,NOW())"
        );
        $stmt->bind_param("siidddsi", $invoice_no, $checked_id, $guest_id,
            $room_charge, $room_charge, $room_charge, $due_date, $user_id);
        $stmt->execute();
        $invoice_id = $this->db->insert_id;
        $stmt->close();

        $item_type  = 'room_charge';
        $qty        = (float)$nights;
        $unit_price = $price_per_night;
        $amount     = $room_charge;
        $stmt = $this->db->prepare(
            "INSERT INTO invoice_items (invoice_id,description,item_type,quantity,unit_price,amount)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("issddd", $invoice_id, $desc, $item_type, $qty, $unit_price, $amount);
        $stmt->execute();
        $stmt->close();

        return $invoice_id;
    }

    // =====================================================
    // AUTHENTICATION
    // =====================================================

    function login() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) return 3;

        // Fetch by username regardless of status so we can return specific codes
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $user  = $result->fetch_assoc();

            // Return code 4 for pending accounts
            if (($user['status'] ?? 'active') === 'pending') return 4;
            // Return code 3 for inactive/rejected
            if (!$user['is_active'] || ($user['status'] ?? 'active') === 'rejected') return 3;

            $valid = false;
            // Detect bcrypt hash vs. legacy plaintext
            if (strlen($user['password']) === 60 && substr($user['password'], 0, 4) === '$2y$') {
                $valid = password_verify($password, $user['password']);
            } else {
                if ($user['password'] === $password) {
                    $valid = true;
                    // Transparently upgrade to bcrypt
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $up = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $up->bind_param("si", $hashed, $user['id']);
                    $up->execute();
                    $up->close();
                }
            }

            if ($valid) {
                foreach ($user as $key => $value) {
                    if ($key !== 'password' && !is_numeric($key))
                        $_SESSION['login_' . $key] = $value;
                }
                $uid = $user['id'];
                $up = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $up->bind_param("i", $uid);
                $up->execute();
                $up->close();

                $this->log_action('login', 'users', $uid, 'User logged in');
                return ($user['type'] == 1) ? 1 : 2;
            }
        }
        return 3;
    }

    function logout() {
        $uid = $_SESSION['login_id'] ?? null;
        $this->log_action('logout', 'users', $uid, 'User logged out');
        foreach ($_SESSION as $key => $value) unset($_SESSION[$key]);
        session_destroy();
        header('location:login.php');
    }

    // =====================================================
    // USERS
    // =====================================================

    function save_user() {
        $id        = (int)($_POST['id'] ?? 0);
        $name      = $this->clean($_POST['name'] ?? '');
        $username  = $this->clean($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $type      = (int)($_POST['type'] ?? 2);
        $email     = $this->clean($_POST['email'] ?? '');
        $phone     = $this->clean($_POST['phone'] ?? '');
        $status    = in_array($_POST['status'] ?? '', ['active','pending','rejected']) ? $_POST['status'] : 'active';
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($name) || empty($username)) return 0;

        if (empty($id)) {
            if (empty($password)) return 0;
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $this->db->prepare("INSERT INTO users (name,username,password,type,email,phone,status,is_active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssissi", $name, $username, $hashed, $type, $email, $phone, $status, $is_active);
            $ok = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) { $this->log_action('create', 'users', $nid, "Created user: $name"); return 1; }
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt   = $this->db->prepare("UPDATE users SET name=?,username=?,password=?,type=?,email=?,phone=?,status=?,is_active=? WHERE id=?");
                $stmt->bind_param("ssssissii", $name, $username, $hashed, $type, $email, $phone, $status, $is_active, $id);
            } else {
                $stmt = $this->db->prepare("UPDATE users SET name=?,username=?,type=?,email=?,phone=?,status=?,is_active=? WHERE id=?");
                $stmt->bind_param("sssissii", $name, $username, $type, $email, $phone, $status, $is_active, $id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) { $this->log_action('update', 'users', $id, "Updated user: $name"); return 1; }
        }
        return 0;
    }

    function approve_user() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        $stmt = $this->db->prepare("UPDATE users SET status = 'active', is_active = 1 WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected > 0) {
            $this->log_action('approve', 'users', $id, 'Approved pending user');
            return json_encode(['status' => 'ok']);
        }
        return json_encode(['status' => 'error', 'message' => 'User not found or already processed']);
    }

    function reject_user() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected > 0) {
            $this->log_action('reject', 'users', $id, 'Rejected pending user signup');
            return json_encode(['status' => 'ok']);
        }
        return json_encode(['status' => 'error', 'message' => 'User not found or already processed']);
    }

    // =====================================================
    // SETTINGS
    // =====================================================

    function save_settings() {
        $name          = $this->clean($_POST['name']               ?? '');
        $email         = $this->clean($_POST['email']              ?? '');
        $contact       = $this->clean($_POST['contact']            ?? '');
        $tagline       = $this->clean($_POST['tagline']            ?? '');
        $address       = $this->clean($_POST['address']            ?? '');
        $currency      = $this->clean($_POST['currency']           ?? 'KES');
        $checkin_time  = $this->clean($_POST['checkin_time']       ?? '14:00');
        $checkout_time = $this->clean($_POST['checkout_time']      ?? '11:00');
        $default_tax   = (float)($_POST['default_tax']             ?? 0);
        $max_rooms     = (int)($_POST['max_rooms_per_floor']       ?? 20);
        $inv_prefix    = $this->clean($_POST['invoice_prefix']     ?? 'INV');
        $about         = htmlentities(str_replace("'", "&#x2019;", $_POST['about'] ?? ''));

        $img = '';
        if (!empty($_FILES['img']['tmp_name'])) {
            $img = strtotime(date('y-m-d H:i')) . '_' . basename($_FILES['img']['name']);
            move_uploaded_file($_FILES['img']['tmp_name'], '../assets/img/' . $img);
        }

        $chk = $this->db->query("SELECT id FROM system_settings LIMIT 1");
        if ($chk->num_rows > 0) {
            $eid = (int)$chk->fetch_assoc()['id'];
            if ($img) {
                $stmt = $this->db->prepare(
                    "UPDATE system_settings SET hotel_name=?,email=?,contact=?,about_content=?,cover_img=?,
                     tagline=?,address=?,currency=?,checkin_time=?,checkout_time=?,default_tax=?,max_rooms_per_floor=?,invoice_prefix=?
                     WHERE id=?"
                );
                // 10×s + d + i + s + i = 14
                $stmt->bind_param("ssssssssssdisi", $name, $email, $contact, $about, $img,
                    $tagline, $address, $currency, $checkin_time, $checkout_time, $default_tax, $max_rooms, $inv_prefix, $eid);
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE system_settings SET hotel_name=?,email=?,contact=?,about_content=?,
                     tagline=?,address=?,currency=?,checkin_time=?,checkout_time=?,default_tax=?,max_rooms_per_floor=?,invoice_prefix=?
                     WHERE id=?"
                );
                // 9×s + d + i + s + i = 13
                $stmt->bind_param("sssssssssdisi", $name, $email, $contact, $about,
                    $tagline, $address, $currency, $checkin_time, $checkout_time, $default_tax, $max_rooms, $inv_prefix, $eid);
            }
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO system_settings (hotel_name,email,contact,about_content,cover_img,tagline,address,currency,checkin_time,checkout_time,default_tax,max_rooms_per_floor,invoice_prefix)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param("ssssssssssdis", $name, $email, $contact, $about, $img,
                $tagline, $address, $currency, $checkin_time, $checkout_time, $default_tax, $max_rooms, $inv_prefix);
        }
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            $q = $this->db->query("SELECT * FROM system_settings LIMIT 1")->fetch_array();
            foreach ($q as $k => $v) { if (!is_numeric($k)) $_SESSION['setting_' . $k] = $v; }
            return 1;
        }
        return 0;
    }

    // =====================================================
    // ROOM CATEGORIES
    // =====================================================

    function save_category() {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = $this->clean($_POST['name']  ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $img   = '';
        if (!empty($_FILES['img']['tmp_name'])) {
            $img = strtotime(date('y-m-d H:i')) . '_' . basename($_FILES['img']['name']);
            move_uploaded_file($_FILES['img']['tmp_name'], '../assets/img/' . $img);
        }
        if (empty($id)) {
            $stmt = $this->db->prepare("INSERT INTO room_categories (name,price,cover_img) VALUES (?,?,?)");
            $stmt->bind_param("sds", $name, $price, $img);
            $ok  = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) { $this->log_action('create', 'room_categories', $nid, "Created category: $name"); return 1; }
        } else {
            if ($img) {
                $stmt = $this->db->prepare("UPDATE room_categories SET name=?,price=?,cover_img=? WHERE id=?");
                $stmt->bind_param("sdsi", $name, $price, $img, $id);
            } else {
                $stmt = $this->db->prepare("UPDATE room_categories SET name=?,price=? WHERE id=?");
                $stmt->bind_param("sdi", $name, $price, $id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) { $this->log_action('update', 'room_categories', $id, "Updated category: $name"); return 1; }
        }
        return 0;
    }

    function delete_category() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return json_encode(['status' => 'error', 'message' => 'Invalid category']);

        // Guard against orphaning rooms: a room pointing at a deleted category
        // loses its price, which silently produces KES 0 invoices at check-in.
        $chk = $this->db->prepare("SELECT COUNT(*) AS c FROM rooms WHERE category_id=?");
        $chk->bind_param("i", $id);
        $chk->execute();
        $count = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();
        if ($count > 0) {
            return json_encode([
                'status'  => 'error',
                'message' => "Cannot delete: {$count} room(s) still use this category. Reassign those rooms to another category first."
            ]);
        }

        $stmt = $this->db->prepare("DELETE FROM room_categories WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) { $this->log_action('delete', 'room_categories', $id, "Deleted category ID: $id"); return json_encode(['status' => 'ok']); }
        return json_encode(['status' => 'error', 'message' => 'Delete failed']);
    }

    // =====================================================
    // ROOMS
    // =====================================================

    function save_room() {
        $id             = (int)($_POST['id']           ?? 0);
        $room           = $this->clean($_POST['room']  ?? '');
        $category_id    = (int)($_POST['category_id']  ?? 0);
        $status         = (int)($_POST['status']        ?? 0);
        $floor          = (int)($_POST['floor']         ?? 1);
        $max_occupancy  = (int)($_POST['max_occupancy'] ?? 2);
        $description    = $this->clean($_POST['description'] ?? '');

        if (empty($id)) {
            $stmt = $this->db->prepare(
                "INSERT INTO rooms (room,category_id,status,floor,max_occupancy,description) VALUES (?,?,?,?,?,?)"
            );
            $stmt->bind_param("siiiis", $room, $category_id, $status, $floor, $max_occupancy, $description);
            $ok  = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) { $this->log_action('create', 'rooms', $nid, "Created room: $room"); return 1; }
        } else {
            $stmt = $this->db->prepare(
                "UPDATE rooms SET room=?,category_id=?,status=?,floor=?,max_occupancy=?,description=? WHERE id=?"
            );
            $stmt->bind_param("siiiisi", $room, $category_id, $status, $floor, $max_occupancy, $description, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) { $this->log_action('update', 'rooms', $id, "Updated room: $room"); return 1; }
        }
        return 0;
    }

    function delete_room() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return 0;
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) { $this->log_action('delete', 'rooms', $id, "Deleted room ID: $id"); return 1; }
        return 0;
    }

    // =====================================================
    // CHECK-IN / CHECK-OUT / BOOKING
    // =====================================================

    function save_check_in() {
        $id            = (int)($_POST['id']       ?? 0);
        $rid           = (int)($_POST['rid']       ?? 0);
        $guest_id      = (int)($_POST['guest_id']  ?? 0) ?: null;
        $name          = $this->clean($_POST['name']         ?? '');
        $contact       = $this->clean($_POST['contact']      ?? '');
        $id_number     = $this->clean($_POST['id_number']    ?? '');
        $date_in       = $this->clean($_POST['date_in']      ?? date('Y-m-d'));
        $date_in_time  = $this->clean($_POST['date_in_time'] ?? '12:00');
        $date_out_time = $this->clean($_POST['date_out_time'] ?? '11:00');
        $days          = max(1, (int)($_POST['days'] ?? 1));
        $requests      = $this->clean($_POST['special_requests'] ?? '');

        // Editing an existing stay may legitimately keep a past check-in date;
        // only a brand-new check-in is held to "today or later".
        $err = $this->validate_reservation($name, $contact, $id_number, $date_in, $id > 0);
        if ($err) return json_encode(['status' => 'error', 'message' => $err]);

        $datetime_in  = $date_in . ' ' . $date_in_time;
        $datetime_out = $this->build_date_out($datetime_in, $days, $date_out_time);

        // Generate unique ref_no
        do {
            $ref = sprintf('%010d', mt_rand(1, 9999999999));
            $chk = $this->db->prepare("SELECT id FROM checked WHERE ref_no=? LIMIT 1");
            $chk->bind_param("s", $ref);
            $chk->execute();
            $found = $chk->get_result()->num_rows;
            $chk->close();
        } while ($found > 0);

        $this->db->begin_transaction();
        try {
            // Verify room still available (race-condition guard)
            $rchk = $this->db->prepare("SELECT id, status FROM rooms WHERE id=? FOR UPDATE");
            $rchk->bind_param("i", $rid);
            $rchk->execute();
            $rrow = $rchk->get_result()->fetch_assoc();
            $rchk->close();

            // Source-of-truth guard: a room with an active (status=1) stay must be
            // checked out before it can be checked in again. This catches cases
            // where rooms.status has drifted out of sync with the `checked` table.
            if (empty($id)) {
                $act = $this->db->prepare("SELECT id FROM checked WHERE room_id=? AND status=1 LIMIT 1");
                $act->bind_param("i", $rid);
                $act->execute();
                $active = $act->get_result()->fetch_assoc();
                $act->close();
                if ($active) {
                    // Self-heal: make sure the room flag reflects the active stay.
                    $this->db->query("UPDATE rooms SET status=1 WHERE id=" . (int)$rid);
                    $this->db->commit();
                    return json_encode(['status' => 'error', 'message' => 'Room is already checked in — check it out first.']);
                }
            }

            if (!$rrow || ($rrow['status'] == 1 && empty($id))) {
                $this->db->rollback();
                return json_encode(['status' => 'error', 'message' => 'Room is no longer available']);
            }

            $has_id_col = $this->has_column('checked', 'id_number');

            if (empty($id)) {
                if ($has_id_col) {
                    $stmt = $this->db->prepare(
                        "INSERT INTO checked (guest_id,ref_no,room_id,name,contact_no,id_number,special_requests,date_in,date_out,status)
                         VALUES (?,?,?,?,?,?,?,?,?,1)"
                    );
                    // guest_id=i, ref=s, rid=i, name=s, contact=s, id_number=s, requests=s, date_in=s, date_out=s
                    $stmt->bind_param("isissssss", $guest_id, $ref, $rid, $name, $contact, $id_number, $requests, $datetime_in, $datetime_out);
                } else {
                    $stmt = $this->db->prepare(
                        "INSERT INTO checked (guest_id,ref_no,room_id,name,contact_no,special_requests,date_in,date_out,status)
                         VALUES (?,?,?,?,?,?,?,?,1)"
                    );
                    // guest_id=i, ref=s, rid=i, name=s, contact=s, requests=s, date_in=s, date_out=s
                    $stmt->bind_param("isisssss", $guest_id, $ref, $rid, $name, $contact, $requests, $datetime_in, $datetime_out);
                }
                $stmt->execute();
                $cid = $this->db->insert_id;
                $stmt->close();
            } else {
                $cid = $id;
                if ($has_id_col) {
                    $stmt = $this->db->prepare(
                        "UPDATE checked SET guest_id=?,room_id=?,name=?,contact_no=?,id_number=?,special_requests=?,
                         date_in=?,date_out=?,ref_no=?,status=1 WHERE id=?"
                    );
                    // guest_id=i, room_id=i, name=s, contact=s, id_number=s, requests=s, date_in=s, date_out=s, ref=s, id=i
                    $stmt->bind_param("iisssssssi", $guest_id, $rid, $name, $contact, $id_number, $requests, $datetime_in, $datetime_out, $ref, $cid);
                } else {
                    $stmt = $this->db->prepare(
                        "UPDATE checked SET guest_id=?,room_id=?,name=?,contact_no=?,special_requests=?,
                         date_in=?,date_out=?,ref_no=?,status=1 WHERE id=?"
                    );
                    // guest_id=i, room_id=i, name=s, contact=s, requests=s, date_in=s, date_out=s, ref=s, id=i
                    $stmt->bind_param("iissssssi", $guest_id, $rid, $name, $contact, $requests, $datetime_in, $datetime_out, $ref, $cid);
                }
                $stmt->execute();
                $stmt->close();
            }

            // Mark room occupied + clean housekeeping status
            $up = $this->db->prepare("UPDATE rooms SET status=1, housekeeping_status='clean' WHERE id=?");
            $up->bind_param("i", $rid);
            $up->execute();
            $up->close();

            // Auto-generate invoice
            $invoice_id = $this->create_invoice_for_checkin($cid, $rid, $days, $guest_id);

            // Notifications
            $guest_label = $name;
            if ($guest_id) {
                $g = $this->db->prepare("SELECT full_name, is_vip FROM guests WHERE id=?");
                $g->bind_param("i", $guest_id);
                $g->execute();
                $gdata = $g->get_result()->fetch_assoc();
                $g->close();
                $guest_label = $gdata['full_name'] ?? $name;
                if (!empty($gdata['is_vip'])) {
                    $this->push_notification('vip', 'VIP Guest Check-In',
                        "VIP guest {$guest_label} has checked into room.", $cid, 'checked', 'critical');
                }
            }
            $this->push_notification('checkin', 'New Check-In',
                "{$guest_label} checked in. Ref: {$ref}", $cid, 'checked', 'normal');

            // Update guest stats
            if ($guest_id) {
                $upg = $this->db->prepare("UPDATE guests SET total_stays = total_stays + 1 WHERE id=?");
                $upg->bind_param("i", $guest_id);
                $upg->execute();
                $upg->close();
            }

            $this->db->commit();
            $this->log_action('checkin', 'checked', $cid, "Check-in: $name, Room ID: $rid");
            return json_encode(['status' => 'ok', 'id' => $cid, 'invoice_id' => $invoice_id]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('save_check_in error: ' . $e->getMessage());
            return json_encode(['status' => 'error', 'message' => 'Check-in failed']);
        }
    }

    function save_checkout() {
        $id  = (int)($_POST['id']  ?? 0);
        $rid = (int)($_POST['rid'] ?? 0);
        if ($id <= 0 || $rid <= 0) return json_encode(['status' => 'error', 'message' => 'Invalid parameters']);

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("UPDATE checked SET status=2 WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Mark room dirty and available
            $stmt = $this->db->prepare("UPDATE rooms SET status=0, housekeeping_status='dirty' WHERE id=?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();

            // Mark any draft/issued invoice as issued (trigger payment)
            $this->db->query("UPDATE invoices SET status='issued', issued_at=NOW() WHERE checked_id=$id AND status='draft'");

            // Auto-create housekeeping task
            $today = date('Y-m-d');
            $task_type = 'standard_clean';
            $priority  = 'high';
            $tstmt = $this->db->prepare(
                "INSERT INTO housekeeping_tasks (room_id,checked_id,task_type,priority,scheduled_date) VALUES (?,?,?,?,?)"
            );
            $tstmt->bind_param("iisss", $rid, $id, $task_type, $priority, $today);
            $tstmt->execute();
            $tstmt->close();

            // Get invoice for response
            $inv = $this->db->query("SELECT id FROM invoices WHERE checked_id=$id LIMIT 1")->fetch_assoc();
            $invoice_id = $inv['id'] ?? null;

            $this->db->commit();
            $this->push_notification('checkout', 'Check-Out', "Guest checked out from room ID $rid", $id, 'checked');
            $this->push_notification('housekeeping', 'Room Needs Cleaning', "Room ID $rid requires cleaning after check-out", $rid, 'rooms', 'high');
            $this->log_action('checkout', 'checked', $id, "Checkout: checked ID $id, Room ID $rid");
            return json_encode(['status' => 'ok', 'invoice_id' => $invoice_id]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('save_checkout error: ' . $e->getMessage());
            return json_encode(['status' => 'error', 'message' => 'Checkout failed']);
        }
    }

    function save_book() {
        $cid           = (int)($_POST['cid']          ?? 0);
        $guest_id      = (int)($_POST['guest_id']      ?? 0) ?: null;
        $name          = $this->clean($_POST['name']         ?? '');
        $contact       = $this->clean($_POST['contact']      ?? '');
        $id_number     = $this->clean($_POST['id_number']    ?? '');
        $date_in       = $this->clean($_POST['date_in']      ?? date('Y-m-d'));
        $date_in_time  = $this->clean($_POST['date_in_time'] ?? '14:00');
        $date_out_time = $this->clean($_POST['date_out_time'] ?? '11:00');
        $days          = max(1, (int)($_POST['days'] ?? 1));
        $requests      = $this->clean($_POST['special_requests'] ?? '');

        $err = $this->validate_reservation($name, $contact, $id_number, $date_in);
        if ($err) return json_encode(['status' => 'error', 'message' => $err]);

        $datetime_in  = $date_in . ' ' . $date_in_time;
        $datetime_out = $this->build_date_out($datetime_in, $days, $date_out_time);

        do {
            $ref = sprintf('%010d', mt_rand(1, 9999999999));
            $chk = $this->db->prepare("SELECT id FROM checked WHERE ref_no=? LIMIT 1");
            $chk->bind_param("s", $ref);
            $chk->execute();
            $found = $chk->get_result()->num_rows;
            $chk->close();
        } while ($found > 0);

        if ($this->has_column('checked', 'id_number')) {
            $stmt = $this->db->prepare(
                "INSERT INTO checked (guest_id,booked_cid,ref_no,name,contact_no,id_number,special_requests,date_in,date_out,status)
                 VALUES (?,?,?,?,?,?,?,?,?,0)"
            );
            $stmt->bind_param("iisssssss", $guest_id, $cid, $ref, $name, $contact, $id_number, $requests, $datetime_in, $datetime_out);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO checked (guest_id,booked_cid,ref_no,name,contact_no,special_requests,date_in,date_out,status)
                 VALUES (?,?,?,?,?,?,?,?,0)"
            );
            $stmt->bind_param("iissssss", $guest_id, $cid, $ref, $name, $contact, $requests, $datetime_in, $datetime_out);
        }
        $stmt->execute();
        $new_id = $this->db->insert_id;
        $stmt->close();

        if ($new_id) {
            $this->push_notification('booking', 'New Booking', "$name booked. Ref: $ref. Check-in: $date_in", $new_id, 'checked');
            $this->log_action('booking', 'checked', $new_id, "Booking: $name, Ref: $ref");
            return json_encode(['status' => 'ok', 'id' => $new_id]);
        }
        return json_encode(['status' => 'error', 'message' => 'Booking failed']);
    }

    // =====================================================
    // GUESTS
    // =====================================================

    function save_guest() {
        $id            = (int)($_POST['id']            ?? 0);
        $full_name     = $this->clean($_POST['full_name']     ?? '');
        $email         = $this->clean($_POST['email']         ?? '');
        $phone         = $this->clean($_POST['phone']         ?? '');
        $id_type       = $this->clean($_POST['id_type']       ?? 'passport');
        $id_number     = $this->clean($_POST['id_number']     ?? '');
        $nationality   = $this->clean($_POST['nationality']   ?? '');
        $date_of_birth = $this->clean($_POST['date_of_birth'] ?? '');
        $address       = $this->clean($_POST['address']       ?? '');
        $city          = $this->clean($_POST['city']          ?? '');
        $country       = $this->clean($_POST['country']       ?? '');
        $is_vip        = (int)($_POST['is_vip']               ?? 0);
        $vip_note      = $this->clean($_POST['vip_note']      ?? '');
        $notes         = $this->clean($_POST['notes']         ?? '');
        $dob           = !empty($date_of_birth) ? $date_of_birth : null;

        if (empty($full_name)) return json_encode(['status' => 'error', 'message' => 'Name required']);
        if (empty($phone))     return json_encode(['status' => 'error', 'message' => 'Phone number is required']);
        if (empty($id_number)) return json_encode(['status' => 'error', 'message' => 'ID / passport number is required']);
        if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
            return json_encode(['status' => 'error', 'message' => 'Enter a valid phone number (at least 7 digits)']);
        }

        if (empty($id)) {
            $stmt = $this->db->prepare(
                "INSERT INTO guests (full_name,email,phone,id_type,id_number,nationality,date_of_birth,
                 address,city,country,is_vip,vip_note,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param("ssssssssssiss",
                $full_name, $email, $phone, $id_type, $id_number, $nationality,
                $dob, $address, $city, $country, $is_vip, $vip_note, $notes);
            $ok  = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) {
                $this->log_action('create', 'guests', $nid, "Created guest: $full_name");
                return json_encode(['status' => 'ok', 'id' => $nid]);
            }
        } else {
            $stmt = $this->db->prepare(
                "UPDATE guests SET full_name=?,email=?,phone=?,id_type=?,id_number=?,nationality=?,
                 date_of_birth=?,address=?,city=?,country=?,is_vip=?,vip_note=?,notes=? WHERE id=?"
            );
            $stmt->bind_param("ssssssssssissi",
                $full_name, $email, $phone, $id_type, $id_number, $nationality,
                $dob, $address, $city, $country, $is_vip, $vip_note, $notes, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                $this->log_action('update', 'guests', $id, "Updated guest: $full_name");
                return json_encode(['status' => 'ok', 'id' => $id]);
            }
        }
        return json_encode(['status' => 'error', 'message' => 'Failed to save guest']);
    }

    function delete_guest() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return json_encode(['status' => 'error']);
        $stmt = $this->db->prepare("DELETE FROM guests WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) { $this->log_action('delete', 'guests', $id, "Deleted guest ID: $id"); return 1; }
        return 0;
    }

    function search_guests() {
        $q = '%' . $this->clean($_GET['q'] ?? '') . '%';
        $stmt = $this->db->prepare(
            "SELECT id, full_name, email, phone, id_number, is_vip FROM guests
             WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR id_number LIKE ?
             ORDER BY full_name LIMIT 20"
        );
        $stmt->bind_param("ssss", $q, $q, $q, $q);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return json_encode($rows);
    }

    // =====================================================
    // GUEST REQUESTS
    // =====================================================

    function save_guest_request() {
        $id          = (int)($_POST['id']          ?? 0);
        $checked_id  = (int)($_POST['checked_id']  ?? 0);
        $guest_id    = (int)($_POST['guest_id']     ?? 0) ?: null;
        $req_type    = $this->clean($_POST['request_type'] ?? 'other');
        $description = $this->clean($_POST['description']  ?? '');
        $priority    = $this->clean($_POST['priority']     ?? 'normal');

        if (empty($checked_id) || empty($description)) return json_encode(['status' => 'error', 'message' => 'Missing fields']);

        if (empty($id)) {
            $stmt = $this->db->prepare(
                "INSERT INTO guest_requests (checked_id,guest_id,request_type,description,priority) VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("iisss", $checked_id, $guest_id, $req_type, $description, $priority);
            $ok  = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) {
                $this->push_notification('request', 'New Guest Request',
                    "[$priority] $req_type: $description", $nid, 'guest_requests',
                    ($priority === 'urgent' ? 'high' : 'normal'));
                return json_encode(['status' => 'ok', 'id' => $nid]);
            }
        } else {
            $status = $this->clean($_POST['status'] ?? 'pending');
            $note   = $this->clean($_POST['resolution_note'] ?? '');
            $uid    = $_SESSION['login_id'] ?? null;
            $resolved_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
            $stmt = $this->db->prepare(
                "UPDATE guest_requests SET request_type=?,description=?,priority=?,status=?,
                 resolution_note=?,resolved_by=?,resolved_at=? WHERE id=?"
            );
            $stmt->bind_param("sssssisi", $req_type, $description, $priority, $status, $note, $uid, $resolved_at, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) return json_encode(['status' => 'ok']);
        }
        return json_encode(['status' => 'error', 'message' => 'Failed to save request']);
    }

    // =====================================================
    // HOUSEKEEPING
    // =====================================================

    function save_housekeeping_task() {
        $id            = (int)($_POST['id']             ?? 0);
        $room_id       = (int)($_POST['room_id']         ?? 0);
        $checked_id    = (int)($_POST['checked_id']      ?? 0) ?: null;
        $assigned_to   = (int)($_POST['assigned_to']     ?? 0) ?: null;
        $task_type     = $this->clean($_POST['task_type']     ?? 'standard_clean');
        $priority      = $this->clean($_POST['priority']      ?? 'normal');
        $notes         = $this->clean($_POST['notes']         ?? '');
        $sched_date    = $this->clean($_POST['scheduled_date'] ?? date('Y-m-d'));

        if ($room_id <= 0) return json_encode(['status' => 'error', 'message' => 'Room required']);

        if (empty($id)) {
            $stmt = $this->db->prepare(
                "INSERT INTO housekeeping_tasks (room_id,checked_id,assigned_to,task_type,priority,notes,scheduled_date)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->bind_param("iiissss", $room_id, $checked_id, $assigned_to, $task_type, $priority, $notes, $sched_date);
            $ok  = $stmt->execute();
            $nid = $this->db->insert_id;
            $stmt->close();
            if ($ok) { $this->log_action('create', 'housekeeping_tasks', $nid, "Task for room $room_id"); return json_encode(['status'=>'ok','id'=>$nid]); }
        } else {
            $stmt = $this->db->prepare(
                "UPDATE housekeeping_tasks SET room_id=?,assigned_to=?,task_type=?,priority=?,notes=?,scheduled_date=? WHERE id=?"
            );
            $stmt->bind_param("iissssi", $room_id, $assigned_to, $task_type, $priority, $notes, $sched_date, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) return json_encode(['status' => 'ok']);
        }
        return json_encode(['status' => 'error', 'message' => 'Failed to save task']);
    }

    function update_task_status() {
        $id     = (int)($_POST['id']     ?? 0);
        $status = $this->clean($_POST['status'] ?? '');
        if ($id <= 0 || empty($status)) return json_encode(['status' => 'error']);

        $uid = $_SESSION['login_id'] ?? null;
        $now = date('Y-m-d H:i:s');

        if ($status === 'in_progress') {
            $stmt = $this->db->prepare("UPDATE housekeeping_tasks SET status=?,started_at=? WHERE id=?");
            $stmt->bind_param("ssi", $status, $now, $id);
        } elseif ($status === 'completed') {
            $stmt = $this->db->prepare("UPDATE housekeeping_tasks SET status=?,completed_at=? WHERE id=?");
            $stmt->bind_param("ssi", $status, $now, $id);
        } elseif ($status === 'verified') {
            $stmt = $this->db->prepare("UPDATE housekeeping_tasks SET status=?,verified_at=?,verified_by=? WHERE id=?");
            $stmt->bind_param("ssii", $status, $now, $uid, $id);
            // Mark room clean
            $t = $this->db->prepare("SELECT room_id FROM housekeeping_tasks WHERE id=?");
            $t->bind_param("i", $id);
            $t->execute();
            $r = $t->get_result()->fetch_assoc();
            $t->close();
            if ($r) {
                $rid = $r['room_id'];
                $rc = $this->db->prepare("UPDATE rooms SET housekeeping_status='clean' WHERE id=?");
                $rc->bind_param("i", $rid);
                $rc->execute();
                $rc->close();
            }
        } else {
            $stmt = $this->db->prepare("UPDATE housekeeping_tasks SET status=? WHERE id=?");
            $stmt->bind_param("si", $status, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();
        $this->log_action('update', 'housekeeping_tasks', $id, "Status changed to: $status");
        return json_encode(['status' => $ok ? 'ok' : 'error']);
    }

    // =====================================================
    // BILLING & INVOICES
    // =====================================================

    function add_invoice_item() {
        $invoice_id = (int)($_POST['invoice_id'] ?? 0);
        $desc       = $this->clean($_POST['description'] ?? '');
        $item_type  = $this->clean($_POST['item_type']   ?? 'service');
        $qty        = (float)($_POST['quantity']   ?? 1);
        $unit_price = (float)($_POST['unit_price'] ?? 0);
        $amount     = round($qty * $unit_price, 2);
        if ($item_type === 'discount') $amount = abs($amount);

        if ($invoice_id <= 0 || empty($desc)) return json_encode(['status' => 'error', 'message' => 'Missing fields']);

        $stmt = $this->db->prepare(
            "INSERT INTO invoice_items (invoice_id,description,item_type,quantity,unit_price,amount) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("issddd", $invoice_id, $desc, $item_type, $qty, $unit_price, $amount);
        $ok  = $stmt->execute();
        $nid = $this->db->insert_id;
        $stmt->close();

        if ($ok) {
            $totals = $this->recalculate_invoice($invoice_id);
            return json_encode(['status' => 'ok', 'id' => $nid, 'totals' => $totals]);
        }
        return json_encode(['status' => 'error', 'message' => 'Failed to add item']);
    }

    function delete_invoice_item() {
        $id         = (int)($_POST['id']         ?? 0);
        $invoice_id = (int)($_POST['invoice_id'] ?? 0);
        $stmt = $this->db->prepare("DELETE FROM invoice_items WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok && $invoice_id) {
            $totals = $this->recalculate_invoice($invoice_id);
            return json_encode(['status' => 'ok', 'totals' => $totals]);
        }
        return json_encode(['status' => $ok ? 'ok' : 'error']);
    }

    function update_invoice() {
        $id       = (int)($_POST['id']       ?? 0);
        $tax_rate = (float)($_POST['tax_rate'] ?? 0);
        $notes    = $this->clean($_POST['notes'] ?? '');
        $due_date = $this->clean($_POST['due_date'] ?? '');

        $stmt = $this->db->prepare("UPDATE invoices SET tax_rate=?,notes=?,due_date=? WHERE id=?");
        $stmt->bind_param("dssi", $tax_rate, $notes, $due_date, $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $totals = $this->recalculate_invoice($id);
            return json_encode(['status' => 'ok', 'totals' => $totals]);
        }
        return json_encode(['status' => 'error']);
    }

    function save_payment() {
        $invoice_id = (int)($_POST['invoice_id']    ?? 0);
        $method     = $this->clean($_POST['payment_method'] ?? 'cash');
        $amount     = (float)($_POST['amount']       ?? 0);
        $reference  = $this->clean($_POST['reference']     ?? '');
        $notes      = $this->clean($_POST['notes']         ?? '');
        $uid        = $_SESSION['login_id'] ?? null;

        if ($invoice_id <= 0 || $amount <= 0) return json_encode(['status' => 'error', 'message' => 'Invalid amount']);

        $stmt = $this->db->prepare(
            "INSERT INTO payments (invoice_id,payment_method,amount,reference,notes,processed_by) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("isdssi", $invoice_id, $method, $amount, $reference, $notes, $uid);
        $ok  = $stmt->execute();
        $nid = $this->db->insert_id;
        $stmt->close();

        if ($ok) {
            $totals = $this->recalculate_invoice($invoice_id);
            $this->push_notification('payment', 'Payment Received', "Payment of \$$amount received via $method", $invoice_id, 'invoices');
            $this->log_action('payment', 'payments', $nid, "Payment $amount via $method for invoice $invoice_id");
            return json_encode(['status' => 'ok', 'id' => $nid, 'totals' => $totals]);
        }
        return json_encode(['status' => 'error', 'message' => 'Failed to record payment']);
    }

    function update_room_hk_status() {
        $room_id   = (int)($_POST['room_id']  ?? 0);
        $hk_status = $this->clean($_POST['hk_status'] ?? '');
        $allowed   = ['clean','dirty','in_progress','inspection','maintenance','out_of_order'];
        if ($room_id <= 0 || !in_array($hk_status, $allowed)) return json_encode(['status'=>'error']);
        $stmt = $this->db->prepare("UPDATE rooms SET housekeeping_status=? WHERE id=?");
        $stmt->bind_param("si", $hk_status, $room_id);
        $ok = $stmt->execute();
        $stmt->close();
        $this->log_action('update', 'rooms', $room_id, "HK status set to: $hk_status");
        return json_encode(['status' => $ok ? 'ok' : 'error']);
    }

    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    function get_notifications() {
        $uid   = (int)($_SESSION['login_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 10);

        $res = $this->db->query(
            "SELECT * FROM notifications WHERE (user_id IS NULL OR user_id=$uid)
             AND is_read=0 ORDER BY created_at DESC LIMIT $limit"
        );
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        $cnt = $this->db->query(
            "SELECT COUNT(*) AS c FROM notifications WHERE (user_id IS NULL OR user_id=$uid) AND is_read=0"
        )->fetch_assoc()['c'];

        return json_encode(['unread_count' => (int)$cnt, 'notifications' => $rows]);
    }

    function mark_notification_read() {
        $id  = (int)($_POST['id']  ?? 0);
        $all = (int)($_POST['all'] ?? 0);
        $uid = (int)($_SESSION['login_id'] ?? 0);

        if ($all) {
            $this->db->query("UPDATE notifications SET is_read=1 WHERE user_id IS NULL OR user_id=$uid");
        } else {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read=1 WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        return json_encode(['status' => 'ok']);
    }

    // =====================================================
    // DASHBOARD STATS
    // =====================================================

    function get_dashboard_stats() {
        $stats = [];

        // Room stats
        $r = $this->db->query("SELECT COUNT(*) AS total, SUM(status=1) AS occupied, SUM(status=0) AS available FROM rooms");
        $room_stats = $r->fetch_assoc();
        $stats['rooms'] = $room_stats;
        $stats['occupancy_rate'] = $room_stats['total'] > 0
            ? round(($room_stats['occupied'] / $room_stats['total']) * 100, 1) : 0;

        // Today's check-ins/outs/bookings
        $today = date('Y-m-d');
        $stats['todays_checkins']  = (int)$this->db->query("SELECT COUNT(*) AS c FROM checked WHERE status=1 AND DATE(date_in)='$today'")->fetch_assoc()['c'];
        $stats['todays_checkouts'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM checked WHERE status=2 AND DATE(date_updated)='$today'")->fetch_assoc()['c'];
        $stats['pending_bookings'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM checked WHERE status=0")->fetch_assoc()['c'];

        // Revenue today
        $stats['todays_revenue'] = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount),0) AS r FROM payments WHERE DATE(created_at)='$today'"
        )->fetch_assoc()['r'];

        // Revenue this month
        $ym = date('Y-m');
        $stats['monthly_revenue'] = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount),0) AS r FROM payments WHERE DATE_FORMAT(created_at,'%Y-%m')='$ym'"
        )->fetch_assoc()['r'];

        // Housekeeping
        $stats['hk_pending']  = (int)$this->db->query("SELECT COUNT(*) AS c FROM housekeeping_tasks WHERE status='pending' AND scheduled_date='$today'")->fetch_assoc()['c'];
        $stats['hk_progress'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM housekeeping_tasks WHERE status='in_progress'")->fetch_assoc()['c'];

        // Pending payments
        $stats['pending_payments'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM invoices WHERE status IN ('issued','partial')")->fetch_assoc()['c'];

        // Upcoming check-ins (next 24h)
        $tomorrow = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stats['upcoming_checkins'] = $this->db->query(
            "SELECT c.*, g.full_name AS guest_name, g.is_vip, rc.name AS category, r.room
             FROM checked c
             LEFT JOIN guests g ON c.guest_id = g.id
             LEFT JOIN rooms r ON c.room_id = r.id
             LEFT JOIN room_categories rc ON r.category_id = rc.id
             WHERE c.status=0 AND c.date_in <= '$tomorrow'
             ORDER BY c.date_in ASC LIMIT 5"
        )->fetch_all(MYSQLI_ASSOC);

        // Current guests
        $stats['current_guests'] = $this->db->query(
            "SELECT c.*, g.full_name AS guest_name, g.is_vip, r.room, rc.name AS category
             FROM checked c
             LEFT JOIN guests g ON c.guest_id = g.id
             LEFT JOIN rooms r ON c.room_id = r.id
             LEFT JOIN room_categories rc ON r.category_id = rc.id
             WHERE c.status=1
             ORDER BY c.date_out ASC LIMIT 10"
        )->fetch_all(MYSQLI_ASSOC);

        return json_encode($stats);
    }

    // =====================================================
    // REPORTS
    // =====================================================

    function get_reports() {
        $from  = $this->clean($_GET['from']   ?? date('Y-m-01'));
        $to    = $this->clean($_GET['to']     ?? date('Y-m-d'));
        $from_dt = $from . ' 00:00:00';
        $to_dt   = $to   . ' 23:59:59';

        $total_rooms = (int)$this->db->query("SELECT COUNT(*) AS c FROM rooms")->fetch_assoc()['c'];
        $days_in_period = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
        $available_room_nights = $total_rooms * $days_in_period;

        // Revenue
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount),0) AS revenue FROM payments WHERE created_at BETWEEN ? AND ?"
        );
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $revenue = (float)$stmt->get_result()->fetch_assoc()['revenue'];
        $stmt->close();

        // Room nights sold (overlapping stays in period)
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(DATEDIFF(LEAST(date_out,?),GREATEST(date_in,?))),0) AS nights
             FROM checked WHERE status IN (1,2) AND date_in < ? AND date_out > ?"
        );
        $stmt->bind_param("ssss", $to_dt, $from_dt, $to_dt, $from_dt);
        $stmt->execute();
        $nights_sold = max(0, (int)$stmt->get_result()->fetch_assoc()['nights']);
        $stmt->close();

        $adr    = $nights_sold > 0 ? round($revenue / $nights_sold, 2) : 0;
        $revpar = $available_room_nights > 0 ? round($revenue / $available_room_nights, 2) : 0;
        $occ    = $available_room_nights > 0 ? round(($nights_sold / $available_room_nights) * 100, 1) : 0;

        // Daily revenue series (for chart)
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day, SUM(amount) AS revenue
             FROM payments WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at) ORDER BY day"
        );
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $daily_revenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Daily occupancy series
        $stmt = $this->db->prepare(
            "SELECT DATE(date_in) AS day, COUNT(*) AS checkins
             FROM checked WHERE status IN (1,2) AND date_in BETWEEN ? AND ?
             GROUP BY DATE(date_in) ORDER BY day"
        );
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $daily_checkins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Revenue by category
        $stmt = $this->db->prepare(
            "SELECT rc.name, COALESCE(SUM(ii.amount),0) AS revenue
             FROM invoice_items ii
             JOIN invoices inv ON ii.invoice_id = inv.id
             JOIN checked c ON inv.checked_id = c.id
             JOIN rooms r ON c.room_id = r.id
             JOIN room_categories rc ON r.category_id = rc.id
             WHERE ii.item_type = 'room_charge' AND inv.created_at BETWEEN ? AND ?
             GROUP BY rc.name ORDER BY revenue DESC"
        );
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $by_category = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Bookings/checkins/checkouts count for period
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM checked WHERE status=0 AND date_in BETWEEN ? AND ?");
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $bookings_count = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM checked WHERE status IN (1,2) AND date_in BETWEEN ? AND ?");
        $stmt->bind_param("ss", $from_dt, $to_dt);
        $stmt->execute();
        $checkins_count = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        return json_encode([
            'period'               => compact('from', 'to'),
            'total_rooms'          => $total_rooms,
            'days_in_period'       => $days_in_period,
            'available_room_nights'=> $available_room_nights,
            'revenue'              => $revenue,
            'nights_sold'          => $nights_sold,
            'adr'                  => $adr,
            'revpar'               => $revpar,
            'occupancy_rate'       => $occ,
            'bookings_count'       => $bookings_count,
            'checkins_count'       => $checkins_count,
            'daily_revenue'        => $daily_revenue,
            'daily_checkins'       => $daily_checkins,
            'by_category'          => $by_category,
        ]);
    }
}
