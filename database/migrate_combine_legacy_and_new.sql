-- ============================================================================
-- Smart Inventory: combine LEGACY schema + NEW multi-tenant features
-- WITHOUT dropping tables or deleting rows.
--
-- What this does:
--   • Creates new tables if missing: tenants, subscription_plans,
--     tenant_subscriptions, usage_monthly, traffic_logs
--   • Adds new columns (tenant_id, supplier_id, email, etc.) to existing tables
--   • Keeps legacy columns: users.username, users.password, goods_receipts, …
--   • Backfills tenant_id for all existing business rows into one workspace
--   • Migrates legacy users → email + password_hash + role (data copied, not removed)
--   • Ensures subscription row + optional usage counters from historical sales
--
-- Before running: BACK UP your database (mysqldump / phpMyAdmin export).
-- Target database: inventory_management
--   In phpMyAdmin: select DB `inventory_management` then import.
--   CLI: mysql -u ... inventory_management < migrate_combine_legacy_and_new.sql
-- Safe to run multiple times (uses INFORMATION_SCHEMA checks).
--
-- Optional (CLI / empty server): create DB, then run this file against it.
-- CREATE DATABASE IF NOT EXISTS `inventory_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `inventory_management`;

-- Charset
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1) New infrastructure tables (no DROP)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL,
  `max_medicine_skus` int unsigned NOT NULL DEFAULT 50,
  `max_sales_per_month` int unsigned NOT NULL DEFAULT 500,
  `max_goods_receipts_per_month` int unsigned NOT NULL DEFAULT 200,
  `max_team_members` int unsigned NOT NULL DEFAULT 3,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `subscription_plans` (`code`, `name`, `max_medicine_skus`, `max_sales_per_month`, `max_goods_receipts_per_month`, `max_team_members`, `sort_order`) VALUES
('free', 'Free', 30, 100, 50, 2, 0),
('starter', 'Starter', 200, 2000, 1000, 5, 1),
('pro', 'Professional', 2000, 50000, 20000, 25, 2);

CREATE TABLE IF NOT EXISTS `tenant_subscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `plan_id` int unsigned NOT NULL,
  `status` enum('trialing','active','past_due','canceled') NOT NULL DEFAULT 'active',
  `current_period_start` date NOT NULL,
  `current_period_end` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `ts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ts_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usage_monthly` (
  `tenant_id` int unsigned NOT NULL,
  `year_month` char(7) NOT NULL COMMENT 'YYYY-MM',
  `sales_count` int unsigned NOT NULL DEFAULT 0,
  `goods_receipts_count` int unsigned NOT NULL DEFAULT 0,
  `api_calls` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`tenant_id`, `year_month`),
  CONSTRAINT `usage_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `traffic_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `http_method` varchar(8) NOT NULL,
  `route` varchar(512) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `response_status` smallint unsigned DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_tenant_created` (`tenant_id`, `created_at`),
  KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2) Default workspace tenant for all legacy rows
-- ============================================================================

INSERT INTO `tenants` (`name`, `slug`, `status`)
SELECT 'Migrated workspace', 'migrated-workspace', 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tenants` WHERE `slug` = 'migrated-workspace' LIMIT 1);

SET @legacy_tenant_id := (SELECT `id` FROM `tenants` WHERE `slug` = 'migrated-workspace' LIMIT 1);

-- Subscription: Pro plan for migrated workspace so existing volume is unlikely to hit caps
INSERT INTO `tenant_subscriptions` (`tenant_id`, `plan_id`, `status`, `current_period_start`, `current_period_end`)
SELECT @legacy_tenant_id, `id`, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 YEAR)
FROM `subscription_plans` WHERE `code` = 'pro' LIMIT 1
AND NOT EXISTS (SELECT 1 FROM `tenant_subscriptions` WHERE `tenant_id` = @legacy_tenant_id LIMIT 1);

-- ============================================================================
-- 3) medicines: add tenant_id + FK (preserve all rows)
-- ============================================================================

SET @db := DATABASE();

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'medicines' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `medicines` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s1 FROM @sql; EXECUTE s1; DEALLOCATE PREPARE s1;

UPDATE `medicines` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

-- NOT NULL + index + FK (ignore if already applied)
SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'medicines' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `medicines` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s2 FROM @sql; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'medicines' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `medicines` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s3 FROM @sql; EXECUTE s3; DEALLOCATE PREPARE s3;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'medicines' AND CONSTRAINT_NAME = 'medicines_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `medicines` ADD CONSTRAINT `medicines_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s4 FROM @sql; EXECUTE s4; DEALLOCATE PREPARE s4;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'medicines' AND INDEX_NAME = 'tenant_name_lot'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `medicines` ADD KEY `tenant_name_lot` (`tenant_id`, `name`, `lot_number`)',
  'SELECT 1');
PREPARE s5 FROM @sql; EXECUTE s5; DEALLOCATE PREPARE s5;

-- ============================================================================
-- 4) suppliers: add tenant_id
-- ============================================================================

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `suppliers` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `suppliers` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `suppliers` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `suppliers` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers' AND CONSTRAINT_NAME = 'suppliers_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `suppliers` ADD CONSTRAINT `suppliers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 5) sales: add tenant_id
-- ============================================================================

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `sales` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `sales` s
INNER JOIN `medicines` m ON m.`id` = s.`medicine_id`
SET s.`tenant_id` = m.`tenant_id`
WHERE s.`tenant_id` IS NULL;

UPDATE `sales` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `sales` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `sales` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales' AND CONSTRAINT_NAME = 'sales_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `sales` ADD CONSTRAINT `sales_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 6) goods_receipt: add tenant_id + supplier_id (legacy had neither supplier_id)
-- ============================================================================

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `goods_receipt` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND COLUMN_NAME = 'supplier_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `goods_receipt` ADD COLUMN `supplier_id` INT UNSIGNED NULL AFTER `medicine_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `goods_receipt` gr
INNER JOIN `medicines` m ON m.`id` = gr.`medicine_id`
SET gr.`tenant_id` = m.`tenant_id`
WHERE gr.`tenant_id` IS NULL;

UPDATE `goods_receipt` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `goods_receipt` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `goods_receipt` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND CONSTRAINT_NAME = 'gr_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `goods_receipt` ADD CONSTRAINT `gr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipt' AND CONSTRAINT_NAME = 'gr_supplier'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `goods_receipt` ADD CONSTRAINT `gr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 7) goods_receipts (legacy parallel table): add tenant_id, keep all columns
-- ============================================================================

SET @tbl := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipts'
);
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipts' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@tbl > 0 AND @col = 0,
  'ALTER TABLE `goods_receipts` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `goods_receipts` gr
INNER JOIN `medicines` m ON m.`id` = gr.`medicine_id`
SET gr.`tenant_id` = m.`tenant_id`
WHERE gr.`tenant_id` IS NULL;

UPDATE `goods_receipts` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipts' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@tbl > 0 AND @col = 'YES',
  'ALTER TABLE `goods_receipts` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipts' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@tbl > 0 AND @idx = 0,
  'ALTER TABLE `goods_receipts` ADD KEY `tenant_id` (`tenant_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'goods_receipts' AND CONSTRAINT_NAME = 'goods_receipts_tenant'
);
SET @sql := IF(@tbl > 0 AND @fk = 0,
  'ALTER TABLE `goods_receipts` ADD CONSTRAINT `goods_receipts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 8) wastage: add tenant_id
-- ============================================================================

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wastage' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `wastage` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `wastage` w
INNER JOIN `medicines` m ON m.`id` = w.`medicine_id`
SET w.`tenant_id` = m.`tenant_id`
WHERE w.`tenant_id` IS NULL;

UPDATE `wastage` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wastage' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `wastage` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wastage' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `wastage` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wastage' AND CONSTRAINT_NAME = 'wastage_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `wastage` ADD CONSTRAINT `wastage_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 9) disposals: add tenant_id
-- ============================================================================

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'disposals' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `disposals` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `disposals` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL;

SET @col := (
  SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'disposals' AND COLUMN_NAME = 'tenant_id' LIMIT 1
);
SET @sql := IF(@col = 'YES',
  'ALTER TABLE `disposals` MODIFY `tenant_id` INT UNSIGNED NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'disposals' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `disposals` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'disposals' AND CONSTRAINT_NAME = 'disposals_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `disposals` ADD CONSTRAINT `disposals_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- 10) users: extend legacy table (keep username + password columns)
-- ============================================================================

SET @has_username := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username'
);
SET @has_password := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'
);

-- Add new columns when migrating from classic users(username, password)
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'tenant_id'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `tenant_id` INT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(191) NULL AFTER `tenant_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_hash'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `email`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'display_name'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `display_name` VARCHAR(100) NULL AFTER `password_hash`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `users` ADD COLUMN `role` ENUM(\'owner\',\'staff\',\'platform_admin\') NOT NULL DEFAULT \'staff\' AFTER `display_name`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'created_at'
);
SET @sql := IF(@col = 0, 'ALTER TABLE `users` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_active`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Legacy → modern fields (original username + password columns kept for audit / rollback)
UPDATE `users` u
SET
  u.`display_name` = COALESCE(NULLIF(TRIM(u.`display_name`), ''), u.`username`),
  u.`password_hash` = COALESCE(
    NULLIF(TRIM(REPLACE(REPLACE(COALESCE(u.`password_hash`, ''), '\r', ''), '\n', '')), ''),
    IF(@has_password > 0, TRIM(REPLACE(REPLACE(COALESCE(u.`password`, ''), '\r', ''), '\n', '')), NULL)
  ),
  u.`email` = COALESCE(
    NULLIF(TRIM(u.`email`), ''),
    CONCAT('user-', u.`id`, '@migrated.local')
  ),
  u.`tenant_id` = COALESCE(u.`tenant_id`, @legacy_tenant_id),
  u.`role` = IF(u.`role` = 'platform_admin', 'platform_admin', 'owner')
WHERE @has_username > 0 AND u.`username` IS NOT NULL;

-- If already had email-only rows, ensure tenant
UPDATE `users` SET `tenant_id` = @legacy_tenant_id WHERE `tenant_id` IS NULL AND `role` <> 'platform_admin';

-- Last resort for broken legacy hashes only (bcrypt for password "password") — change after login
UPDATE `users` SET `password_hash` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE @has_username > 0
  AND (`password_hash` IS NULL OR `password_hash` = '')
  AND `role` <> 'platform_admin';

-- Drop old unique on username if present (email will be unique; keep username for reads)
SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'username' AND NON_UNIQUE = 0
);
SET @sql := IF(@idx > 0, 'ALTER TABLE `users` DROP INDEX `username`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Unique email (may fail if duplicate emails — fix data then re-run)
SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'email' AND NON_UNIQUE = 0
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'tenant_id'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE `users` ADD KEY `tenant_id` (`tenant_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'users_tenant'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE `users` ADD CONSTRAINT `users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Platform admin (optional; INSERT IGNORE)
INSERT IGNORE INTO `users` (`tenant_id`, `email`, `password_hash`, `display_name`, `role`, `is_active`)
VALUES (NULL, 'admin@platform.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'platform_admin', 1);

-- Platform admins must not hold tenant_id
UPDATE `users` SET `tenant_id` = NULL WHERE `role` = 'platform_admin';

-- ============================================================================
-- 11) Backfill usage_monthly from existing sales (per month counts)
-- ============================================================================

INSERT INTO `usage_monthly` (`tenant_id`, `year_month`, `sales_count`, `goods_receipts_count`, `api_calls`)
SELECT s.`tenant_id`, DATE_FORMAT(s.`sold_at`, '%Y-%m') AS ym, COUNT(*), 0, 0
FROM `sales` s
GROUP BY s.`tenant_id`, ym
ON DUPLICATE KEY UPDATE `sales_count` = VALUES(`sales_count`);

INSERT INTO `usage_monthly` (`tenant_id`, `year_month`, `sales_count`, `goods_receipts_count`, `api_calls`)
SELECT gr.`tenant_id`, DATE_FORMAT(gr.`receipt_date`, '%Y-%m') AS ym, 0, COUNT(*), 0
FROM `goods_receipt` gr
GROUP BY gr.`tenant_id`, ym
ON DUPLICATE KEY UPDATE `goods_receipts_count` = VALUES(`goods_receipts_count`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Done. Login hints after migration:
--   Legacy users: email is user-{old_id}@migrated.local (stable, unique)
--   Password: same as before (hash copied from users.password into password_hash, trimmed)
--   If hash was still empty, placeholder password "password" was set — change it immediately.
--   Platform admin: admin@platform.local / password (default bcrypt demo hash)
--   Original columns users.username and users.password are left intact for reference.
-- ============================================================================
