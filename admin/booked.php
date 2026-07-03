<?php include('db_connect.php');

// Category lookup (used to name bookings that have a category but no room yet)
$cat_arr = [];
$cat = $conn->query("SELECT id, name FROM room_categories");
while ($row = $cat->fetch_assoc()) { $cat_arr[$row['id']] = $row['name']; }

// Summary: bookings + paid/unpaid across all reservations that have an invoice
$booked_count = (int)$conn->query("SELECT COUNT(*) c FROM checked WHERE status=0")->fetch_assoc()['c'];
$paid_count   = (int)$conn->query("SELECT COUNT(*) c FROM invoices WHERE status='paid'")->fetch_assoc()['c'];
$unpaid       = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(balance),0) b FROM invoices WHERE status IN ('issued','partial')")->fetch_assoc();
?>
<div class="container-fluid py-3">

  <div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="page-title mb-2"><i class="fa fa-calendar-check mr-2"></i>Bookings</h5>
    <div class="mb-2">
      <button class="btn btn-primary btn-sm" onclick="uni_modal('New Booking','manage_booking.php')">
        <i class="fa fa-calendar-plus mr-1"></i>New Booking
      </button>
      <button class="btn btn-outline-primary btn-sm" onclick="uni_modal('New Guest','manage_guest.php')">
        <i class="fa fa-user-plus mr-1"></i>New Guest
      </button>
    </div>
  </div>

  <!-- Summary -->
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left:3px solid #f59e0b !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#fbbf24"><?php echo $booked_count; ?></div>
          <small class="text-muted">Pending Bookings</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left:3px solid #22c55e !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#4ade80"><?php echo $paid_count; ?></div>
          <small class="text-muted">Paid Invoices</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left:3px solid #ef4444 !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#fca5a5"><?php echo (int)$unpaid['c']; ?></div>
          <small class="text-muted">Unpaid / Partial</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left:3px solid #f97316 !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#fdba74">KES <?php echo number_format($unpaid['b'], 2); ?></div>
          <small class="text-muted">Outstanding</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="form-row align-items-end">
        <div class="col-md-3 mb-1">
          <label class="small mb-0">Reservation status</label>
          <select id="f-status" class="custom-select custom-select-sm">
            <option value="">All</option>
            <option value="bs0">Booked (pending)</option>
            <option value="bs1">Checked-In</option>
            <option value="bs2">Checked-Out</option>
          </select>
        </div>
        <div class="col-md-3 mb-1">
          <label class="small mb-0">Payment</label>
          <select id="f-pay" class="custom-select custom-select-sm">
            <option value="">All</option>
            <option value="pay_paid">Paid</option>
            <option value="pay_partial">Partial</option>
            <option value="pay_unpaid">Unpaid</option>
            <option value="pay_none">No invoice yet</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="booked-table">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Guest</th>
            <th>Room / Category</th>
            <th>Reference</th>
            <th>Check-In</th>
            <th class="text-center">Status</th>
            <th class="text-center">Payment</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $rows = $conn->query(
              "SELECT c.id, c.ref_no, c.name AS guest_raw, c.status AS bstatus, c.date_in, c.booked_cid,
                      g.full_name AS guest_name, g.is_vip,
                      r.room, rc.name AS room_cat,
                      inv.id AS invoice_id, inv.status AS inv_status, inv.balance
               FROM checked c
               LEFT JOIN guests g ON c.guest_id = g.id
               LEFT JOIN rooms r ON c.room_id = r.id
               LEFT JOIN room_categories rc ON r.category_id = rc.id
               LEFT JOIN invoices inv ON inv.checked_id = c.id
               ORDER BY c.status ASC, c.id DESC"
          );
          while ($row = $rows->fetch_assoc()):
              $gname = $row['guest_name'] ?: ($row['guest_raw'] ?: '—');

              // Reservation status badge
              if ($row['bstatus'] == 0)      { $bs_tok='bs0'; $bs_lbl='Booked';       $bs_cls='warning'; }
              elseif ($row['bstatus'] == 1)  { $bs_tok='bs1'; $bs_lbl='Checked-In';   $bs_cls='info'; }
              else                           { $bs_tok='bs2'; $bs_lbl='Checked-Out';  $bs_cls='success'; }

              // Payment badge
              if (empty($row['invoice_id'])) {
                  $pay_tok='pay_none'; $pay_lbl='No invoice yet'; $pay_cls='secondary';
              } elseif ($row['inv_status'] === 'paid') {
                  $pay_tok='pay_paid'; $pay_lbl='Paid'; $pay_cls='success';
              } elseif ($row['inv_status'] === 'partial') {
                  $pay_tok='pay_partial'; $pay_lbl='Partial'; $pay_cls='info';
              } elseif (in_array($row['inv_status'], ['issued','draft'])) {
                  $pay_tok='pay_unpaid'; $pay_lbl='Unpaid'; $pay_cls='danger';
              } else {
                  $pay_tok='pay_none'; $pay_lbl=ucfirst($row['inv_status']); $pay_cls='secondary';
              }

              // Room / category label
              $room_lbl = $row['room'] ? ($row['room'] . ' — ' . $row['room_cat']) : ('— ' . ($cat_arr[$row['booked_cid']] ?? 'No category'));
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td>
              <?php if ($row['is_vip']): ?><span class="badge" style="background:#ffc107;color:#000">VIP</span> <?php endif; ?>
              <?php echo htmlspecialchars($gname); ?>
            </td>
            <td><?php echo htmlspecialchars($room_lbl); ?></td>
            <td><code><?php echo htmlspecialchars(trim($row['ref_no'])); ?></code></td>
            <td><small><?php echo $row['date_in'] ? date('M d, Y', strtotime($row['date_in'])) : '—'; ?></small></td>
            <td class="text-center">
              <span class="badge badge-<?php echo $bs_cls; ?>"><?php echo $bs_lbl; ?></span>
              <span class="d-none"><?php echo $bs_tok; ?></span>
            </td>
            <td class="text-center">
              <span class="badge badge-<?php echo $pay_cls; ?>"><?php echo htmlspecialchars($pay_lbl); ?></span>
              <?php if (!empty($row['invoice_id']) && $row['inv_status'] !== 'paid' && (float)$row['balance'] > 0): ?>
                <div class="small text-danger">KES <?php echo number_format($row['balance'], 2); ?> due</div>
              <?php endif; ?>
              <span class="d-none"><?php echo $pay_tok; ?></span>
            </td>
            <td class="text-center">
              <?php if (!empty($row['invoice_id'])): ?>
              <button class="btn btn-xs btn-outline-success" style="font-size:.72rem;padding:2px 8px"
                onclick="uni_modal('Invoice','manage_billing.php?id=<?php echo $row['invoice_id']; ?>')" title="View invoice / record payment">
                <i class="fa fa-file-invoice-dollar"></i>
              </button>
              <?php endif; ?>
              <button class="btn btn-xs btn-outline-secondary view_res" style="font-size:.72rem;padding:2px 8px"
                data-id="<?php echo $row['id']; ?>" title="View">
                <i class="fa fa-eye"></i>
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  var bookedTable = $('#booked-table').DataTable({ order: [] });

  $('#f-status').on('change', function() {
    bookedTable.column(5).search(this.value).draw();
  });
  $('#f-pay').on('change', function() {
    bookedTable.column(6).search(this.value).draw();
  });

  $('#booked-table').on('click', '.view_res', function() {
    uni_modal("Reservation", "manage_check_out.php?id=" + $(this).attr("data-id"));
  });
</script>
