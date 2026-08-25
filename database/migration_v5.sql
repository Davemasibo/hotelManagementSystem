-- =====================================================
-- Hotel Management System — Migration v5
-- 1. Adds guest ID/passport number to reservations
-- 2. Backfills ID numbers from linked guest profiles
-- Run AFTER hotel_db.sql, migration_v2.sql, v3 and v4
-- =====================================================

USE hotel_db;

-- Guest identification captured at booking / check-in time.
ALTER TABLE `checked`
  ADD COLUMN IF NOT EXISTS `id_number` VARCHAR(100) DEFAULT NULL AFTER `contact_no`;

-- Pull the ID number across for reservations already linked to a guest profile.
UPDATE `checked` c
  JOIN `guests` g ON c.guest_id = g.id
  SET c.id_number = g.id_number
WHERE (c.id_number IS NULL OR c.id_number = '')
  AND g.id_number IS NOT NULL AND g.id_number <> '';
