-- Migration v4: Extended system_settings and users columns
-- Run once against hotel_db

ALTER TABLE `system_settings`
  ADD COLUMN IF NOT EXISTS `tagline`            VARCHAR(300)   DEFAULT '',
  ADD COLUMN IF NOT EXISTS `address`            VARCHAR(300)   DEFAULT '',
  ADD COLUMN IF NOT EXISTS `currency`           VARCHAR(10)    DEFAULT 'KES',
  ADD COLUMN IF NOT EXISTS `checkin_time`       VARCHAR(5)     DEFAULT '14:00',
  ADD COLUMN IF NOT EXISTS `checkout_time`      VARCHAR(5)     DEFAULT '11:00',
  ADD COLUMN IF NOT EXISTS `default_tax`        DECIMAL(5,2)   DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `max_rooms_per_floor` INT           DEFAULT 20,
  ADD COLUMN IF NOT EXISTS `invoice_prefix`     VARCHAR(10)    DEFAULT 'INV';

-- Ensure users table has all required columns (idempotent)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email`              VARCHAR(200)   DEFAULT '',
  ADD COLUMN IF NOT EXISTS `phone`              VARCHAR(30)    DEFAULT '',
  ADD COLUMN IF NOT EXISTS `status`             ENUM('pending','active','rejected') DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS `is_active`          TINYINT(1)     DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `reset_token`        VARCHAR(100)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reset_token_expiry` DATETIME       DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `last_login`         DATETIME       DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `date_added`         DATETIME       DEFAULT CURRENT_TIMESTAMP;
