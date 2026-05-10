-- Smart Inventory - Relevant Database Schema
-- Optimized for pharmacy inventory management
-- Single-tenant focused (simpler than multi-tenant)
-- Database: inventory_management
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in correct order (child tables first)
DROP TABLE IF EXISTS `traffic_logs`;
DROP TABLE IF EXISTS `wastage`;
DROP TABLE IF EXISTS `disposals`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `goods_receipt`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Core Tables
-- ============================================================================

-- Users table (simplified - no multi-tenant)
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','pharmacist','staff','cashier') NOT NULL DEFAULT 'staff',
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories for medicines
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `parent_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suppliers
CREATE TABLE `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `tax_id` varchar(50) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `notes` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_name` (`company_name`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers (for sales tracking)
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `customer_type` enum('retail','wholesale','hospital','clinic') NOT NULL DEFAULT 'retail',
  `tax_id` varchar(50) DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `phone` (`phone`),
  KEY `customer_type` (`customer_type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Inventory Management Tables
-- ============================================================================

-- Medicines (main inventory table)
CREATE TABLE `medicines` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `medicine_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `pack_size` int NOT NULL DEFAULT 1,
  `quantity` int NOT NULL DEFAULT 0,
  `reorder_level` int DEFAULT NULL,
  `min_stock` int DEFAULT NULL,
  `max_stock` int DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_code` (`medicine_code`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  KEY `name` (`name`),
  KEY `generic_name` (`generic_name`),
  KEY `is_active` (`is_active`),
  KEY `quantity` (`quantity`),
  CONSTRAINT `medicines_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medicine batches/lots (for expiry tracking)
CREATE TABLE `medicine_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `medicine_id` int unsigned NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_id` int unsigned DEFAULT NULL,
  `received_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_batch` (`medicine_id`, `batch_number`),
  KEY `medicine_id` (`medicine_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `expiry_date` (`expiry_date`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `batches_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batches_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goods Receipt (stock intake)
CREATE TABLE `goods_receipt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,
  `supplier_id` int unsigned NOT NULL,
  `receipt_date` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `received_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `receipt_date` (`receipt_date`),
  KEY `received_by` (`received_by`),
  KEY `payment_status` (`payment_status`),
  CONSTRAINT `gr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `gr_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goods Receipt Items
CREATE TABLE `goods_receipt_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_id` (`goods_receipt_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `gri_receipt` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gri_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Sales & Transactions
-- ============================================================================

-- Sales (invoices)
CREATE TABLE `sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `sale_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','bank_transfer','credit') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sold_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `customer_id` (`customer_id`),
  KEY `sale_date` (`sale_date`),
  KEY `sold_by` (`sold_by`),
  KEY `payment_status` (`payment_status`),
  CONSTRAINT `sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_sold_by` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sale Items
CREATE TABLE `sale_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `batch_id` int unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `batch_id` (`batch_id`),
  CONSTRAINT `si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `si_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  CONSTRAINT `si_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Stock Management
-- ============================================================================

-- Wastage/Disposals
CREATE TABLE `wastage` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `wastage_number` varchar(50) NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `batch_id` int unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `reason` enum('expired','damaged','broken','spoiled','recalled','other') NOT NULL,
  `reason_details` text,
  `recorded_by` int unsigned DEFAULT NULL,
  `wastage_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wastage_number` (`wastage_number`),
  KEY `medicine_id` (`medicine_id`),
  KEY `batch_id` (`batch_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `wastage_date` (`wastage_date`),
  CONSTRAINT `wastage_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  CONSTRAINT `wastage_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wastage_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Adjustments (for corrections)
CREATE TABLE `stock_adjustments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `batch_id` int unsigned DEFAULT NULL,
  `adjustment_type` enum('increase','decrease') NOT NULL,
  `quantity` int NOT NULL,
  `reason` enum('correction','found','lost','transfer','other') NOT NULL,
  `reason_details` text,
  `adjusted_by` int unsigned DEFAULT NULL,
  `adjustment_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `medicine_id` (`medicine_id`),
  KEY `batch_id` (`batch_id`),
  KEY `adjusted_by` (`adjusted_by`),
  KEY `adjustment_date` (`adjustment_date`),
  CONSTRAINT `adjustments_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  CONSTRAINT `adjustments_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `adjustments_adjusted_by` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Audit & Logging
-- ============================================================================

-- Audit Logs
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `table_name` (`table_name`),
  KEY `record_id` (`record_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Sample Data
-- ============================================================================

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@pharmacy.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('pharmacist1', 'pharmacist@pharmacy.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Pharmacist', 'pharmacist'),
('cashier1', 'cashier@pharmacy.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Cashier', 'cashier');

-- Insert medicine categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Analgesics', 'Pain relievers'),
('Antibiotics', 'Anti-bacterial medications'),
('Antipyretics', 'Fever reducers'),
('Antihistamines', 'Allergy medications'),
('Cardiovascular', 'Heart and blood pressure medications'),
('Gastrointestinal', 'Stomach and digestive medications'),
('Vitamins & Supplements', 'Nutritional supplements'),
('Dermatological', 'Skin medications'),
('Respiratory', 'Lung and breathing medications'),
('Diabetes', 'Diabetes management medications');

-- Insert sample suppliers
INSERT INTO `suppliers` (`company_name`, `contact_person`, `phone`, `email`, `address`) VALUES
('Pharma Distributors Ltd.', 'Mr. David Chen', '+95-123456789', 'david@pharmadist.com', '123 Business Street, Yangon'),
('Medical Supplies Co.', 'Ms. Sarah Johnson', '+95-987654321', 'sarah@medsupply.com', '456 Health Avenue, Mandalay'),
('Global Pharma Inc.', 'Dr. Robert Kim', '+95-555123456', 'robert@globalpharma.com', '789 Pharma Road, Naypyidaw');

-- Insert sample customers
INSERT INTO `customers` (`customer_code`, `full_name`, `phone`, `customer_type`) VALUES
('CUST001', 'General Public (Walk-in)', NULL, 'retail'),
('CUST002', 'City General Hospital', '+95-111222333', 'hospital'),
('CUST003', 'Sunshine Clinic', '+95-444555666', 'clinic'),
('CUST004', 'MediWholesale Distributors', '+95-777888999', 'wholesale');

-- Insert sample medicines
INSERT INTO `medicines` (`medicine_code`, `name`, `generic_name`, `category_id`, `dosage_form`, `strength`, `unit`, `quantity`, `reorder_level`, `cost_price`, `selling_price`, `requires_prescription`) VALUES
('MED001', 'Paracetamol', 'Acetaminophen', 1, 'Tablet', '500mg', 'tablet', 1000, 100, 5.00, 10.00, 0),
('MED002', 'Amoxicillin', 'Amoxicillin', 2, 'Capsule', '500mg', 'capsule', 500, 50, 15.00, 30.00, 1),
('MED003', 'Ibuprofen', 'Ibuprofen', 1, 'Tablet', '400mg', 'tablet', 800, 80, 8.00, 16.00, 0),
('MED004', 'Cetirizine', 'Cetirizine HCl', 4, 'Tablet', '10mg', 'tablet', 600, 60, 3.00, 6.00, 0),
('MED005', 'Vitamin C', 'Ascorbic Acid', 7, 'Tablet', '1000mg', 'tablet', 1200, 120, 2.00, 4.00, 0);

-- Insert sample medicine batches
INSERT INTO `medicine_batches` (`medicine_id`, `batch_number`, `expiry_date`, `quantity`, `purchase_price`, `selling_price`, `supplier_id`, `received_date`) VALUES
(1, 'BATCH001', '2025-12-31', 500, 4.50, 9.00, 1, '2024-01-15'),
(1, 'BATCH002', '2026-06-30', 500, 5.00, 10.00, 2, '2024-02-20'),
(2, 'BATCH003', '2025-09-30', 500, 14.00, 28.00, 1, '2024-01-10'),
(3, 'BATCH004', '2025-11-30', 800, 7.50, 15.00, 3, '2024-03-05'),
(4, 'BATCH005', '2026-03-31', 600, 2.80, 5.60, 2, '2024-02-15');

-- ============================================================================
-- Views for Reporting
-- ============================================================================

-- View for low stock alert
CREATE VIEW `v_low_stock` AS
SELECT 
  m.id,
  m.medicine_code,
  m.name,
  m.generic_name,
  m.quantity,
  m.reorder_level,
  m.min_stock,
  c.name as category_name,
  CASE 
    WHEN m.quantity <= m.reorder_level THEN 'CRITICAL'
    WHEN m.quantity <= m.reorder_level * 1.5 THEN 'WARNING'
    ELSE 'OK'
  END as stock_status
FROM medicines m
LEFT JOIN categories c ON m.category_id = c.id
WHERE m.is_active = 1 AND m.quantity <= m.reorder_level * 1.5
ORDER BY m.quantity ASC;

-- View for expiring medicines
CREATE VIEW `v_expiring_medicines` AS
SELECT 
  mb.id,
  m.medicine_code,
  m.name,
  mb.batch_number,
  mb.expiry_date,
  mb.quantity,
  DATEDIFF(mb.expiry_date, CURDATE()) as days_until_expiry,
  s.company_name as supplier_name,
  CASE 
    WHEN DATEDIFF(mb.expiry_date, CURDATE()) <= 30 THEN 'IMMEDIATE'
    WHEN DATEDIFF(mb.expiry_date, CURDATE()) <= 90 THEN 'SOON'
    ELSE 'SAFE'
  END as expiry_status
FROM medicine_batches mb
JOIN medicines m ON mb.medicine_id = m.id
LEFT JOIN suppliers s ON mb.supplier_id = s.id
WHERE mb.is_active = 1 AND mb.expiry_date >= CURDATE()
ORDER BY mb.expiry_date ASC;

-- View for daily sales summary
CREATE VIEW `v_daily_sales` AS
SELECT 
  DATE(s.created_at) as sale_date,
  COUNT(DISTINCT s.id) as invoice_count,
  COUNT(si.id) as item_count,
  SUM(si.quantity) as total_quantity,
  SUM(s.grand_total) as total_sales,
  SUM(s.paid_amount) as total_paid,
  SUM(s.balance_due) as total_balance
FROM sales s
LEFT JOIN sale_items si ON s.id = si.sale_id
GROUP BY DATE(s.created_at)
ORDER BY sale_date DESC;

-- ============================================================================
-- Stored Procedures
-- ============================================================================

-- Procedure to update medicine quantity after sale
DELIMITER //
CREATE PROCEDURE `sp_update_stock_after_sale`(
  IN p_medicine_id INT,
  IN p_batch_id INT,
  IN p_quantity INT
)
BEGIN
  DECLARE v_current_quantity INT;
  
  -- Update medicine total quantity
  UPDATE medicines 
  SET quantity = quantity - p_quantity,
      updated_at = NOW()
  WHERE id = p_medicine_id;
  
  -- Update batch quantity if batch_id provided
  IF p_batch_id IS NOT NULL THEN
    UPDATE medicine_batches
    SET quantity = quantity - p_quantity,
        updated_at = NOW()
    WHERE id = p_batch_id;
  END IF;
  
  -- Get current quantity for verification
  SELECT quantity INTO v_current_quantity
  FROM medicines 
  WHERE id = p_medicine_id;
  
  -- Return success with current quantity
  SELECT 1 as success, v_current_quantity as current_quantity;
END//
DELIMITER ;

-- Procedure to check expiry alerts
DELIMITER //
CREATE PROCEDURE `sp_check_expiry_alerts`(
  IN p_days_threshold INT
)
BEGIN
  SELECT 
    m.medicine_code,
    m.name,
    mb.batch_number,
    mb.expiry_date,
    mb.quantity,
    DATEDIFF(mb.expiry_date, CURDATE()) as days_remaining,
    CASE 
      WHEN DATEDIFF(mb.expiry_date, CURDATE()) <= 0 THEN 'EXPIRED'
      WHEN DATEDIFF(mb.expiry_date, CURDATE()) <= p_days_threshold THEN 'ALERT'
      ELSE 'OK'
    END as alert_status
  FROM medicine_batches mb
  JOIN medicines m ON mb.medicine_id = m.id
  WHERE mb.is_active = 1
    AND mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL p_days_threshold DAY)
  ORDER BY mb.expiry_date ASC;
END//
DELIMITER ;

-- ============================================================================
-- Triggers for Data Integrity
-- ============================================================================

-- Trigger to update medicine quantity when batch quantity changes
DELIMITER //
CREATE TRIGGER `tr_batch_quantity_update`
AFTER UPDATE ON `medicine_batches`
FOR EACH ROW
BEGIN
  IF OLD.quantity != NEW.quantity THEN
    UPDATE medicines 
    SET quantity = (
      SELECT SUM(quantity) 
      FROM medicine_batches 
      WHERE medicine_id = NEW.medicine_id AND is_active = 1
    ),
    updated_at = NOW()
    WHERE id = NEW.medicine_id;
  END IF;
END//
DELIMITER ;

-- Trigger to log medicine quantity changes
DELIMITER //
CREATE TRIGGER `tr_medicine_quantity_audit`
AFTER UPDATE ON `medicines`
FOR EACH ROW
BEGIN
  IF OLD.quantity != NEW.quantity THEN
    INSERT INTO audit_logs (
      user_id, 
      action, 
      table_name, 
      record_id, 
      old_values, 
      new_values
    ) VALUES (
      NULL, -- System change
      'QUANTITY_UPDATE',
      'medicines',
      NEW.id,
      JSON_OBJECT('quantity', OLD.quantity),
      JSON_OBJECT('quantity', NEW.quantity)
    );
  END IF;
END//
DELIMITER ;

-- ============================================================================
-- Index Optimization
-- ============================================================================

-- Additional indexes for performance
CREATE INDEX `idx_medicines_search` ON `medicines` (`name`, `generic_name`, `medicine_code`);
CREATE INDEX `idx_sales_date_customer` ON `sales` (`sale_date`, `customer_id`, `payment_status`);
CREATE INDEX `idx_batches_expiry` ON `medicine_batches` (`expiry_date`, `is_active`);
CREATE INDEX `idx_goods_receipt_date` ON `goods_receipt` (`receipt_date`, `supplier_id`);

-- ============================================================================
-- Database Comments
-- ============================================================================

-- Table comments
ALTER TABLE `medicines` COMMENT = 'Main medicine inventory table';
ALTER TABLE `medicine_batches` COMMENT = 'Medicine batch/lot tracking for expiry management';
ALTER TABLE `sales` COMMENT = 'Customer sales/invoices';
ALTER TABLE `goods_receipt` COMMENT = 'Stock intake from suppliers';
ALTER TABLE `users` COMMENT = 'System users with role-based access';

-- Column comments
ALTER TABLE `medicines` 
  MODIFY `requires_prescription` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=requires prescription, 0=OTC';

ALTER TABLE `medicine_batches`
  MODIFY `expiry_date` date NOT NULL COMMENT 'Medicine expiry date (MM/DD/YYYY)';

ALTER TABLE `sales`
  MODIFY `payment_status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending' COMMENT 'Invoice payment status';

-- ============================================================================
-- Final Setup
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Display completion message
SELECT 'Database schema created successfully!' as message;

-- Show table count
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';