-- =====================================================
-- Hotel Management System — Migration v3
-- Adds signup/auth fields to users table
-- Run AFTER hotel_db.sql and migration_v2.sql
-- =====================================================

USE hotel_db;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email`               VARCHAR(200) DEFAULT NULL      AFTER `name`,
  ADD COLUMN IF NOT EXISTS `phone`               VARCHAR(30)  DEFAULT NULL      AFTER `email`,
  ADD COLUMN IF NOT EXISTS `status`              ENUM('pending','active','rejected') NOT NULL DEFAULT 'active' AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `reset_token`         VARCHAR(100) DEFAULT NULL      AFTER `status`,
  ADD COLUMN IF NOT EXISTS `reset_token_expiry`  DATETIME     DEFAULT NULL      AFTER `reset_token`;

-- Ensure admin account stays active
UPDATE `users` SET `status` = 'active' WHERE `username` = 'admin';
