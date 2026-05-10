-- Smart Inventory v2 — GREENFIELD ONLY (drops existing tables).
-- Database: inventory_management (create + select before import).
-- If you already have legacy data, use instead:
--   database/migrate_combine_legacy_and_new.sql
-- Charset utf8mb4.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `traffic_logs`;
DROP TABLE IF EXISTS `usage_monthly`;
DROP TABLE IF EXISTS `tenant_subscriptions`;
DROP TABLE IF EXISTS `subscription_plans`;
DROP TABLE IF EXISTS `wastage`;
DROP TABLE IF EXISTS `disposals`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `goods_receipt`;
DROP TABLE IF EXISTS `goods_receipts`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `tenants`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `tenants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_plans` (
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

INSERT INTO `subscription_plans` (`code`, `name`, `max_medicine_skus`, `max_sales_per_month`, `max_goods_receipts_per_month`, `max_team_members`, `sort_order`) VALUES
('free', 'Free', 30, 100, 50, 2, 0),
('starter', 'Starter', 200, 2000, 1000, 5, 1),
('pro', 'Professional', 2000, 50000, 20000, 25, 2);

CREATE TABLE `tenant_subscriptions` (
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

CREATE TABLE `usage_monthly` (
  `tenant_id` int unsigned NOT NULL,
  `year_month` char(7) NOT NULL COMMENT 'YYYY-MM',
  `sales_count` int unsigned NOT NULL DEFAULT 0,
  `goods_receipts_count` int unsigned NOT NULL DEFAULT 0,
  `api_calls` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`tenant_id`, `year_month`),
  CONSTRAINT `usage_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned DEFAULT NULL COMMENT 'NULL = platform admin',
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` enum('owner','staff','platform_admin') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `suppliers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicines` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `sell_price` decimal(10,2) DEFAULT NULL,
  `reorder_level` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `tenant_name_lot` (`tenant_id`, `name`, `lot_number`),
  CONSTRAINT `medicines_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `goods_receipt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `supplier_id` int unsigned DEFAULT NULL,
  `lot_number` varchar(100) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `receipt_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `gr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gr_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `lot_number` varchar(255) NOT NULL,
  `quantity` int NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `sold_by` varchar(255) DEFAULT NULL,
  `sold_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `sales_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wastage` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `reason` varchar(255) NOT NULL,
  `recorded_by` varchar(100) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `wastage_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wastage_med` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `disposals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `reason` varchar(100) NOT NULL,
  `recorded_by` varchar(100) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `disposals_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `traffic_logs` (
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

-- Seed platform admin (password: ChangeMe!Admin1) — change immediately in production
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `display_name`, `role`, `is_active`) VALUES
(NULL, 'admin@platform.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'platform_admin', 1);
