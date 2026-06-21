-- =====================================================
-- Hotel Management System — DEMO SEED DATA
-- Populates dashboard + reports for a presentation.
-- Reference date assumed: 2026-06-19 (current month June 2026).
-- Idempotent: deletes prior DEMO rows first, then re-inserts.
-- All demo records are marked:
--   users.email        LIKE '%@demo.local'
--   guests.email       LIKE '%@demo.local'
--   rooms.description  = 'DEMO_ROOM'
--   checked.ref_no     LIKE 'DEMO-%'
--   invoices.invoice_no LIKE 'DEMO-INV-%'
--   notifications.reference_type = 'demo'
-- Run:  mysql -u root hotel_db < database/demo_seed.sql
-- =====================================================
USE hotel_db;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- CLEANUP PRIOR DEMO DATA ----------
DELETE FROM users          WHERE email LIKE '%@demo.local';
DELETE FROM payments       WHERE invoice_id IN (SELECT id FROM invoices WHERE invoice_no LIKE 'DEMO-INV-%');
DELETE FROM invoice_items  WHERE invoice_id IN (SELECT id FROM invoices WHERE invoice_no LIKE 'DEMO-INV-%');
DELETE FROM invoices       WHERE invoice_no LIKE 'DEMO-INV-%';
DELETE FROM housekeeping_tasks WHERE checked_id IN (SELECT id FROM checked WHERE ref_no LIKE 'DEMO-%') OR notes LIKE 'DEMO%';
DELETE FROM guest_requests  WHERE checked_id IN (SELECT id FROM checked WHERE ref_no LIKE 'DEMO-%');
DELETE FROM checked         WHERE ref_no LIKE 'DEMO-%';
DELETE FROM notifications   WHERE reference_type = 'demo';
DELETE FROM rooms           WHERE description = 'DEMO_ROOM';
DELETE FROM guests          WHERE email LIKE '%@demo.local';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DEMO USERS (staff portal logins)
-- Passwords are stored as the plaintext seed value and are
-- transparently upgraded to a bcrypt hash on first login
-- (same mechanism as the default `admin` account).
--   demo / demo123       -> active staff, can log in immediately
--   applicant / —        -> pending signup, shows in the
--                            Admin > Users "Pending Approvals" queue
--                            so the approval flow can be demoed
-- =====================================================
INSERT INTO users (name, email, phone, username, password, type, is_active, status)
VALUES ('Demo Staff','demo@demo.local','+254700100200','demo','demo123',2,1,'active');

INSERT INTO users (name, email, phone, username, password, type, is_active, status)
VALUES ('Pending Applicant','applicant@demo.local','+254700100201','applicant','applicant123',2,0,'pending');

-- ---------- GUESTS ----------
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, vip_note, total_stays, notes)
VALUES ('Amara Okeke','amara@demo.local','+254700100101','national_id','DEMO-ID-101','Kenyan',1,'Repeat platinum guest — prefers high floor, late checkout',6,'DEMO guest');
SET @g1 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Brian Otieno','brian@demo.local','+254700100102','national_id','DEMO-ID-102','Kenyan',0,2,'DEMO guest');
SET @g2 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Carol Mwangi','carol@demo.local','+254700100103','passport','DEMO-ID-103','Kenyan',0,1,'DEMO guest');
SET @g3 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('David Kimani','david@demo.local','+254700100104','national_id','DEMO-ID-104','Kenyan',0,3,'DEMO guest');
SET @g4 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, vip_note, total_stays, notes)
VALUES ('Elena Petrova','elena@demo.local','+254700100105','passport','DEMO-ID-105','Russian',1,'VIP — corporate account, allergy: nuts',4,'DEMO guest');
SET @g5 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Frank Mueller','frank@demo.local','+254700100106','passport','DEMO-ID-106','German',0,1,'DEMO guest');
SET @g6 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Grace Akinyi','grace@demo.local','+254700100107','national_id','DEMO-ID-107','Kenyan',0,2,'DEMO guest');
SET @g7 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Henry Wanjala','henry@demo.local','+254700100108','national_id','DEMO-ID-108','Kenyan',0,1,'DEMO guest');
SET @g8 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Irene Njoroge','irene@demo.local','+254700100109','national_id','DEMO-ID-109','Kenyan',0,0,'DEMO guest');
SET @g9 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('James Karanja','james@demo.local','+254700100110','national_id','DEMO-ID-110','Kenyan',0,1,'DEMO guest');
SET @g10 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Kevin Omondi','kevin@demo.local','+254700100111','national_id','DEMO-ID-111','Kenyan',0,0,'DEMO guest');
SET @g11 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Linda Achieng','linda@demo.local','+254700100112','national_id','DEMO-ID-112','Kenyan',0,0,'DEMO guest');
SET @g12 = LAST_INSERT_ID();
INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, is_vip, total_stays, notes)
VALUES ('Moses Kiprop','moses@demo.local','+254700100113','national_id','DEMO-ID-113','Kenyan',0,0,'DEMO guest');
SET @g13 = LAST_INSERT_ID();

-- ---------- ROOMS (category ids: 2=Deluxe 500, 3=Single 120, 4=Family 350, 6=Twin 199, 7=Bedsitter 400) ----------
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R201',2,1,'clean',2,2,'DEMO_ROOM'); SET @r201 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R202',2,0,'clean',2,2,'DEMO_ROOM'); SET @r202 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R203',4,0,'clean',2,4,'DEMO_ROOM'); SET @r203 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R204',4,1,'clean',2,4,'DEMO_ROOM'); SET @r204 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R205',6,0,'clean',3,2,'DEMO_ROOM'); SET @r205 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R206',6,1,'clean',3,2,'DEMO_ROOM'); SET @r206 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R207',3,0,'dirty',3,1,'DEMO_ROOM'); SET @r207 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R208',7,0,'maintenance',1,2,'DEMO_ROOM'); SET @r208 = LAST_INSERT_ID();
INSERT INTO rooms (room, category_id, status, housekeeping_status, floor, max_occupancy, description)
VALUES ('R209',3,0,'dirty',3,1,'DEMO_ROOM'); SET @r209 = LAST_INSERT_ID();

-- =====================================================
-- STAYS  (status: 0=booking, 1=checked-in, 2=checked-out)
-- Each completed/active stay -> invoice + room_charge item + payment(s)
-- =====================================================

-- ---- S1: Brian, Deluxe R201, 06-02..06-05, 3 nights @500 = 1500, PAID ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g2,'DEMO-0001',@r201,'Brian Otieno','+254700100102',1500,'2026-06-02 14:00:00','2026-06-05 11:00:00',0,2,'2026-06-05 11:05:00');
SET @c1 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0001',@c1,@g2,1500,0,0,0,1500,1500,0,'paid','2026-06-02 14:00:00','2026-06-05','2026-06-05 10:30:00',1,'2026-06-02 14:00:00');
SET @i1 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i1,'Room charge — Deluxe Room (3 nights)','room_charge',3,500,1500);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i1,'cash',1500,'DEMO-PAY-0001',1,'2026-06-05 10:30:00');

-- ---- S2: Carol, Family R203, 06-04..06-07, 3 @350 = 1050, PAID ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g3,'DEMO-0002',@r203,'Carol Mwangi','+254700100103',1050,'2026-06-04 14:00:00','2026-06-07 11:00:00',0,2,'2026-06-07 11:05:00');
SET @c2 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0002',@c2,@g3,1050,0,0,0,1050,1050,0,'paid','2026-06-04 14:00:00','2026-06-07','2026-06-07 10:40:00',1,'2026-06-04 14:00:00');
SET @i2 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i2,'Room charge — Family Room (3 nights)','room_charge',3,350,1050);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i2,'credit_card',1050,'DEMO-PAY-0002',1,'2026-06-07 10:40:00');

-- ---- S3: David, Twin R205, 06-08..06-10, 2 @199 = 398, PAID ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g4,'DEMO-0003',@r205,'David Kimani','+254700100104',398,'2026-06-08 14:00:00','2026-06-10 11:00:00',0,2,'2026-06-10 11:05:00');
SET @c3 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0003',@c3,@g4,398,0,0,0,398,398,0,'paid','2026-06-08 14:00:00','2026-06-10','2026-06-10 10:20:00',1,'2026-06-08 14:00:00');
SET @i3 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i3,'Room charge — Twin Bed Room (2 nights)','room_charge',2,199,398);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i3,'online',398,'DEMO-PAY-0003',1,'2026-06-10 10:20:00');

-- ---- S4: Frank, Single R207, 06-10..06-13, 3 @120 = 360, PAID ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g6,'DEMO-0004',@r207,'Frank Mueller','+254700100106',360,'2026-06-10 14:00:00','2026-06-13 11:00:00',0,2,'2026-06-13 11:05:00');
SET @c4 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0004',@c4,@g6,360,0,0,0,360,360,0,'paid','2026-06-10 14:00:00','2026-06-13','2026-06-13 10:15:00',1,'2026-06-10 14:00:00');
SET @i4 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i4,'Room charge — Single Room (3 nights)','room_charge',3,120,360);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i4,'cash',360,'DEMO-PAY-0004',1,'2026-06-13 10:15:00');

-- ---- S5: Elena (VIP), Deluxe R202, 06-12..06-16, 4 @500 = 2000, PARTIAL (paid 1000) ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, special_requests, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g5,'DEMO-0005',@r202,'Elena Petrova','+254700100105','Airport transfer; nut-free minibar',2000,'2026-06-12 14:00:00','2026-06-16 11:00:00',0,2,'2026-06-16 11:05:00');
SET @c5 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0005',@c5,@g5,2000,0,0,0,2000,1000,1000,'partial','2026-06-12 14:00:00','2026-06-16',NULL,1,'2026-06-12 14:00:00');
SET @i5 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i5,'Room charge — Deluxe Room (4 nights)','room_charge',4,500,2000);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i5,'bank_transfer',1000,'DEMO-PAY-0005',1,'2026-06-16 09:50:00');

-- ---- S6: Grace, Bedsitter R208, 06-15..06-18, 3 @400 = 1200, PAID ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g7,'DEMO-0006',@r208,'Grace Akinyi','+254700100107',1200,'2026-06-15 14:00:00','2026-06-18 11:00:00',0,2,'2026-06-18 11:05:00');
SET @c6 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0006',@c6,@g7,1200,0,0,0,1200,1200,0,'paid','2026-06-15 14:00:00','2026-06-18','2026-06-18 10:30:00',1,'2026-06-15 14:00:00');
SET @i6 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i6,'Room charge — Bedsitter (3 nights)','room_charge',3,400,1200);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i6,'credit_card',1200,'DEMO-PAY-0006',1,'2026-06-18 10:30:00');

-- ---- S7: Amara (VIP) IN-HOUSE, Deluxe R201, 06-17..06-21, 4 @500 = 2000, ISSUED (unpaid) ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, special_requests, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g1,'DEMO-0007',@r201,'Amara Okeke','+254700100101','High floor, late checkout, champagne on arrival',2000,'2026-06-17 14:00:00','2026-06-21 11:00:00',0,1,'2026-06-17 14:05:00');
SET @c7 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, created_by, created_at)
VALUES ('DEMO-INV-0007',@c7,@g1,2000,0,0,0,2000,0,2000,'issued','2026-06-17 14:00:00','2026-06-21',1,'2026-06-17 14:00:00');
SET @i7 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i7,'Room charge — Deluxe Room (4 nights)','room_charge',4,500,2000);

-- ---- S8: Henry IN-HOUSE, Family R204, CHECKED IN TODAY 06-19..06-22, 3 @350 = 1050, PARTIAL (paid 500 today) ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g8,'DEMO-0008',@r204,'Henry Wanjala','+254700100108',1050,'2026-06-19 14:00:00','2026-06-22 11:00:00',0,1,'2026-06-19 14:05:00');
SET @c8 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, created_by, created_at)
VALUES ('DEMO-INV-0008',@c8,@g8,1050,0,0,0,1050,500,550,'partial','2026-06-19 14:00:00','2026-06-22',1,'2026-06-19 14:00:00');
SET @i8 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i8,'Room charge — Family Room (3 nights)','room_charge',3,350,1050);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i8,'cash',500,'DEMO-PAY-0008',1,'2026-06-19 14:10:00');

-- ---- S9: Irene IN-HOUSE, Twin R206, CHECKED IN TODAY 06-19..06-21, 2 @199 = 398, PAID today ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g9,'DEMO-0009',@r206,'Irene Njoroge','+254700100109',398,'2026-06-19 15:00:00','2026-06-21 11:00:00',0,1,'2026-06-19 15:05:00');
SET @c9 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0009',@c9,@g9,398,0,0,0,398,398,0,'paid','2026-06-19 15:00:00','2026-06-21','2026-06-19 15:10:00',1,'2026-06-19 15:00:00');
SET @i9 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i9,'Room charge — Twin Bed Room (2 nights)','room_charge',2,199,398);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i9,'credit_card',398,'DEMO-PAY-0009',1,'2026-06-19 15:10:00');

-- ---- S10: James CHECKED OUT TODAY, Single R209, 06-16..06-19, 3 @120 = 360, PAID today ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g10,'DEMO-0010',@r209,'James Karanja','+254700100110',360,'2026-06-16 14:00:00','2026-06-19 11:00:00',0,2,'2026-06-19 11:00:00');
SET @c10 = LAST_INSERT_ID();
INSERT INTO invoices (invoice_no, checked_id, guest_id, subtotal, tax_rate, tax_amount, discount_amount, total, amount_paid, balance, status, issued_at, due_date, paid_at, created_by, created_at)
VALUES ('DEMO-INV-0010',@c10,@g10,360,0,0,0,360,360,0,'paid','2026-06-16 14:00:00','2026-06-19','2026-06-19 10:55:00',1,'2026-06-16 14:00:00');
SET @i10 = LAST_INSERT_ID();
INSERT INTO invoice_items (invoice_id, description, item_type, quantity, unit_price, amount)
VALUES (@i10,'Room charge — Single Room (3 nights)','room_charge',3,120,360);
INSERT INTO payments (invoice_id, payment_method, amount, reference, processed_by, created_at)
VALUES (@i10,'cash',360,'DEMO-PAY-0010',1,'2026-06-19 10:55:00');

-- ---- S11: Kevin BOOKING (upcoming tomorrow), Deluxe R202, 06-20..06-23 ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g11,'DEMO-0011',@r202,'Kevin Omondi','+254700100111',1500,'2026-06-20 14:00:00','2026-06-23 11:00:00',0,0,'2026-06-18 09:00:00');

-- ---- S12: Linda BOOKING (arriving today evening), Family R203, 06-19..06-21 ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g12,'DEMO-0012',@r203,'Linda Achieng','+254700100112',700,'2026-06-19 20:00:00','2026-06-21 11:00:00',0,0,'2026-06-18 16:00:00');

-- ---- S13: Moses BOOKING (future), Twin R205, 06-25..06-28 ----
INSERT INTO checked (guest_id, ref_no, room_id, name, contact_no, total_amount, date_in, date_out, booked_cid, status, date_updated)
VALUES (@g13,'DEMO-0013',@r205,'Moses Kiprop','+254700100113',597,'2026-06-25 14:00:00','2026-06-28 11:00:00',0,0,'2026-06-17 12:00:00');

-- =====================================================
-- HOUSEKEEPING TASKS
-- =====================================================
INSERT INTO housekeeping_tasks (room_id, checked_id, task_type, status, priority, notes, scheduled_date)
VALUES (@r209,@c10,'standard_clean','pending','high','DEMO: Post-checkout turnover — guest departed 11:00','2026-06-19');
INSERT INTO housekeeping_tasks (room_id, task_type, status, priority, notes, scheduled_date)
VALUES (@r207,'standard_clean','pending','normal','DEMO: Daily refresh','2026-06-19');
INSERT INTO housekeeping_tasks (room_id, task_type, status, priority, notes, scheduled_date, started_at, assigned_to)
VALUES (@r208,'maintenance','in_progress','high','DEMO: AC unit repair in progress','2026-06-19','2026-06-19 09:30:00',1);
INSERT INTO housekeeping_tasks (room_id, checked_id, task_type, status, priority, notes, scheduled_date, started_at)
VALUES (@r204,@c8,'prepare','in_progress','normal','DEMO: Prep family room for arriving guest','2026-06-19','2026-06-19 12:00:00');
INSERT INTO housekeeping_tasks (room_id, task_type, status, priority, notes, scheduled_date, started_at, completed_at, verified_at, verified_by)
VALUES (@r206,'standard_clean','verified','normal','DEMO: Completed and inspected','2026-06-19','2026-06-19 11:30:00','2026-06-19 12:15:00','2026-06-19 12:30:00',1);

-- =====================================================
-- GUEST REQUESTS (for in-house guests)
-- =====================================================
INSERT INTO guest_requests (checked_id, guest_id, request_type, description, priority, status)
VALUES (@c7,@g1,'room_service','DEMO: Champagne and fruit platter to the room','high','pending');
INSERT INTO guest_requests (checked_id, guest_id, request_type, description, priority, status, resolved_by, resolved_at, resolution_note)
VALUES (@c7,@g1,'amenity','DEMO: Extra pillows and bathrobe','normal','completed',1,'2026-06-18 08:30:00','Delivered by housekeeping');
INSERT INTO guest_requests (checked_id, guest_id, request_type, description, priority, status)
VALUES (@c8,@g8,'maintenance','DEMO: TV remote not working','normal','in_progress');
INSERT INTO guest_requests (checked_id, guest_id, request_type, description, priority, status)
VALUES (@c9,@g9,'transport','DEMO: Taxi to city centre at 6pm','low','pending');

-- =====================================================
-- NOTIFICATIONS (broadcast, unread for the bell)
-- =====================================================
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'vip','VIP Guest In-House','Amara Okeke (Platinum) is checked into Deluxe R201 — champagne requested.',@c7,'demo','critical',0,'2026-06-17 14:05:00');
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'checkin','New Check-In','Henry Wanjala checked into Family Room R204.',@c8,'demo','normal',0,'2026-06-19 14:05:00');
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'payment','Payment Received','KES 398 received from Irene Njoroge (INV DEMO-INV-0009).',@i9,'demo','normal',0,'2026-06-19 15:10:00');
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'request','Urgent Guest Request','Room service: champagne for VIP Amara Okeke (R201).',@c7,'demo','high',0,'2026-06-18 19:00:00');
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'housekeeping','Room Needs Cleaning','R209 vacated — turnover scheduled (high priority).',@c10,'demo','high',0,'2026-06-19 11:05:00');
INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, priority, is_read, created_at)
VALUES (NULL,'booking','New Booking','Kevin Omondi booked Deluxe R202 for 2026-06-20.',NULL,'demo','normal',1,'2026-06-18 09:00:00');

SELECT 'DEMO SEED COMPLETE' AS result;
