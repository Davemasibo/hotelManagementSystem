-- =====================================================
-- Hotel Management System v2.0 — Migration Script
-- Run via phpMyAdmin: http://localhost/phpmyadmin
-- Select database: hotel_db, then import this file
-- =====================================================

USE hotel_db;

-- =====================================================
-- EXTEND EXISTING TABLES
-- =====================================================

ALTER TABLE `checked`
  ADD COLUMN `guest_id` INT DEFAULT NULL AFTER `id`,
  ADD COLUMN `special_requests` TEXT DEFAULT NULL AFTER `contact_no`,
  ADD COLUMN `total_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `special_requests`;

ALTER TABLE `rooms`
  ADD COLUMN `housekeeping_status` ENUM('clean','dirty','in_progress','inspection','maintenance','out_of_order') DEFAULT 'clean' AFTER `status`,
  ADD COLUMN `floor` TINYINT UNSIGNED DEFAULT 1 AFTER `housekeeping_status`,
  ADD COLUMN `max_occupancy` TINYINT UNSIGNED DEFAULT 2 AFTER `floor`,
  ADD COLUMN `description` TEXT DEFAULT NULL AFTER `max_occupancy`;

ALTER TABLE `users`
  ADD COLUMN `last_login` DATETIME DEFAULT NULL AFTER `type`,
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `last_login`;

-- =====================================================
-- 1. GUEST PROFILES
-- =====================================================
CREATE TABLE IF NOT EXISTS `guests` (
  `id`            INT(30)      NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(200) NOT NULL,
  `email`         VARCHAR(200) DEFAULT NULL,
  `phone`         VARCHAR(30)  DEFAULT NULL,
  `id_type`       ENUM('passport','national_id','drivers_license','other') DEFAULT 'passport',
  `id_number`     VARCHAR(100) DEFAULT NULL,
  `nationality`   VARCHAR(100) DEFAULT NULL,
  `date_of_birth` DATE         DEFAULT NULL,
  `address`       TEXT         DEFAULT NULL,
  `city`          VARCHAR(100) DEFAULT NULL,
  `country`       VARCHAR(100) DEFAULT NULL,
  `is_vip`        TINYINT(1)   NOT NULL DEFAULT 0,
  `vip_note`      VARCHAR(300) DEFAULT NULL,
  `loyalty_points` INT         NOT NULL DEFAULT 0,
  `total_stays`   INT          NOT NULL DEFAULT 0,
  `notes`         TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email`  (`email`),
  KEY `idx_phone`  (`phone`),
  KEY `idx_name`   (`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. GUEST PREFERENCES
-- =====================================================
CREATE TABLE IF NOT EXISTS `guest_preferences` (
  `id`         INT(30)      NOT NULL AUTO_INCREMENT,
  `guest_id`   INT(30)      NOT NULL,
  `pref_key`   VARCHAR(100) NOT NULL,
  `pref_value` VARCHAR(300) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. GUEST REQUESTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `guest_requests` (
  `id`              INT(30)   NOT NULL AUTO_INCREMENT,
  `checked_id`      INT(30)   NOT NULL,
  `guest_id`        INT(30)   DEFAULT NULL,
  `request_type`    ENUM('maintenance','housekeeping','room_service','amenity','complaint','transport','other') DEFAULT 'other',
  `description`     TEXT      NOT NULL,
  `priority`        ENUM('low','normal','high','urgent') DEFAULT 'normal',
  `status`          ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `resolved_by`     INT(30)   DEFAULT NULL,
  `resolved_at`     DATETIME  DEFAULT NULL,
  `resolution_note` TEXT      DEFAULT NULL,
  `created_at`      DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`checked_id`) REFERENCES `checked`(`id`) ON DELETE CASCADE,
  KEY `idx_status`   (`status`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. HOUSEKEEPING TASKS
-- =====================================================
CREATE TABLE IF NOT EXISTS `housekeeping_tasks` (
  `id`             INT(30)  NOT NULL AUTO_INCREMENT,
  `room_id`        INT(30)  NOT NULL,
  `checked_id`     INT(30)  DEFAULT NULL,
  `assigned_to`    INT(30)  DEFAULT NULL,
  `task_type`      ENUM('standard_clean','deep_clean','turndown','inspection','maintenance','prepare','linen_change') DEFAULT 'standard_clean',
  `status`         ENUM('pending','in_progress','completed','verified','skipped') DEFAULT 'pending',
  `priority`       ENUM('low','normal','high') DEFAULT 'normal',
  `notes`          TEXT     DEFAULT NULL,
  `scheduled_date` DATE     NOT NULL,
  `started_at`     DATETIME DEFAULT NULL,
  `completed_at`   DATETIME DEFAULT NULL,
  `verified_at`    DATETIME DEFAULT NULL,
  `verified_by`    INT(30)  DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`),
  KEY `idx_status` (`status`),
  KEY `idx_date`   (`scheduled_date`),
  KEY `idx_room`   (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. INVOICES
-- =====================================================
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              INT(30)       NOT NULL AUTO_INCREMENT,
  `invoice_no`      VARCHAR(50)   NOT NULL,
  `checked_id`      INT(30)       NOT NULL,
  `guest_id`        INT(30)       DEFAULT NULL,
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate`        DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `tax_amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`          ENUM('draft','issued','partial','paid','cancelled','refunded') DEFAULT 'draft',
  `notes`           TEXT          DEFAULT NULL,
  `issued_at`       DATETIME      DEFAULT NULL,
  `due_date`        DATE          DEFAULT NULL,
  `paid_at`         DATETIME      DEFAULT NULL,
  `created_by`      INT(30)       DEFAULT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  FOREIGN KEY (`checked_id`) REFERENCES `checked`(`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. INVOICE ITEMS
-- =====================================================
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          INT(30)       NOT NULL AUTO_INCREMENT,
  `invoice_id`  INT(30)       NOT NULL,
  `description` VARCHAR(200)  NOT NULL,
  `item_type`   ENUM('room_charge','service','food_beverage','laundry','tax','discount','other') DEFAULT 'room_charge',
  `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. PAYMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id`             INT(30)       NOT NULL AUTO_INCREMENT,
  `invoice_id`     INT(30)       NOT NULL,
  `payment_method` ENUM('cash','credit_card','debit_card','bank_transfer','online','cheque','other') DEFAULT 'cash',
  `amount`         DECIMAL(10,2) NOT NULL,
  `reference`      VARCHAR(100)  DEFAULT NULL,
  `notes`          TEXT          DEFAULT NULL,
  `processed_by`   INT(30)       DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. NOTIFICATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`             INT(30)      NOT NULL AUTO_INCREMENT,
  `user_id`        INT(30)      DEFAULT NULL,
  `type`           ENUM('checkin','checkout','booking','housekeeping','payment','alert','vip','overbooking','request','maintenance') DEFAULT 'alert',
  `title`          VARCHAR(200) NOT NULL,
  `message`        TEXT         NOT NULL,
  `reference_id`   INT(30)      DEFAULT NULL,
  `reference_type` VARCHAR(50)  DEFAULT NULL,
  `priority`       ENUM('low','normal','high','critical') DEFAULT 'normal',
  `is_read`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  KEY `idx_type`      (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. AUDIT LOGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`          INT(30)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(30)      DEFAULT NULL,
  `user_name`   VARCHAR(200) DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `module`      VARCHAR(100) DEFAULT NULL,
  `record_id`   INT(30)      DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_module`  (`module`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
