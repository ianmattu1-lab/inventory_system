-- ============================================================================
--  UNIFIED INVENTORY MANAGEMENT SYSTEM  |  Database: inventory_system
--  Centro Escolar México Junior College - IT Department - Semester 3
--  Engine: InnoDB | Charset: utf8mb4 | Compatible: MySQL 5.7+/8.x, MariaDB 10.3+
--
--  HOW TO IMPORT (phpMyAdmin):
--    Open phpMyAdmin -> click "Import" (top tab) -> choose this file -> Go.
--    (No need to create the database first; the script creates it.)
--
--  DEFAULT LOGIN ACCOUNTS (passwords are stored as PHP password_hash() bcrypt):
--    admin@inventory.test   /  Admin@123   (role: admin)
--    maria@inventory.test   /  Staff@123   (role: staff)
--    carlos@inventory.test  /  Staff@123   (role: staff)
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `inventory_system`;
CREATE DATABASE `inventory_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `inventory_system`;

-- ----------------------------------------------------------------------------
-- 1. USERS
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Created with PHP password_hash()',
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. CATEGORIES
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. PRODUCTS
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`         INT UNSIGNED NOT NULL,
  `sku`                 VARCHAR(50)  NOT NULL,
  `name`                VARCHAR(150) NOT NULL,
  `description`         TEXT NULL,
  `unit_price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity`            INT NOT NULL DEFAULT 0,
  `min_stock_threshold` INT NOT NULL DEFAULT 5 COMMENT 'Low-stock alert when quantity < this value',
  `image_path`          VARCHAR(255) NULL COMMENT 'Relative path e.g. uploads/products/item_101.jpg',
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_stock` (`quantity`, `min_stock_threshold`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_products_price`    CHECK (`unit_price` >= 0),
  CONSTRAINT `chk_products_quantity` CHECK (`quantity` >= 0),
  CONSTRAINT `chk_products_min`      CHECK (`min_stock_threshold` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. STOCK_LOGS (Audit Trail)
-- ----------------------------------------------------------------------------
CREATE TABLE `stock_logs` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`       INT UNSIGNED NOT NULL,
  `user_id`          INT UNSIGNED NOT NULL,
  `action_type`      ENUM('CREATED','STOCK_IN','STOCK_OUT','UPDATED','DELETED') NOT NULL,
  `quantity_changed` INT NOT NULL DEFAULT 0,
  `platform`         ENUM('WEB','MOBILE') NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_product` (`product_id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
--  SEED DATA
-- ============================================================================

-- Users (bcrypt hashes generated in the PHP password_hash() $2y$ format)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@inventory.test',  '$2y$10$aezyIzAE7B4Y9obXf3Bslu67Eri.JezHEbGl1fJDb1egU.YLHMXby', 'admin', '2026-08-25 08:00:00'),
(2, 'Maria Gonzalez',       'maria@inventory.test',  '$2y$10$zxtn/MWdHqj7rh9ysUciXO3XSchirNafLI.b9hOngdqSqXq/L5kvm', 'staff', '2026-08-25 08:10:00'),
(3, 'Carlos Pech',          'carlos@inventory.test', '$2y$10$zxtn/MWdHqj7rh9ysUciXO3XSchirNafLI.b9hOngdqSqXq/L5kvm', 'staff', '2026-08-25 08:15:00');

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Electronics',     'Computer peripherals, cables, monitors and audio equipment.'),
(2, 'Office Supplies', 'Paper, stationery and everyday desk consumables.'),
(3, 'Hardware',        'Hand tools, power tools and workplace safety gear.');

-- Products (15 items across 3 categories; 4 start below their minimum threshold)
INSERT INTO `products`
(`id`, `category_id`, `sku`, `name`, `description`, `unit_price`, `quantity`, `min_stock_threshold`, `image_path`) VALUES
(1,  1, 'ELEC-001', 'Wireless Optical Mouse',        '2.4GHz wireless mouse with ergonomic grip and 12-month battery life.',        24.99,  45, 10, 'uploads/products/elec-001.jpg'),
(2,  1, 'ELEC-002', 'Mechanical Keyboard',           'Full-size mechanical keyboard with blue switches and backlighting.',          79.50,  18,  8, 'uploads/products/elec-002.jpg'),
(3,  1, 'ELEC-003', 'USB-C Charging Cable 2m',       'Braided fast-charge USB-C to USB-C cable, 2 metres.',                         12.75, 120, 30, 'uploads/products/elec-003.jpg'),
(4,  1, 'ELEC-004', '27-inch LED Monitor',           'Full HD IPS monitor with HDMI and DisplayPort inputs.',                      219.00,   6,  8, 'uploads/products/elec-004.jpg'),
(5,  1, 'ELEC-005', 'Bluetooth Headset',             'Over-ear wireless headset with noise-cancelling microphone.',                 54.90,  22, 10, 'uploads/products/elec-005.jpg'),
(6,  2, 'OFFC-001', 'A4 Copy Paper Ream (500 sheets)','80gsm white A4 multipurpose paper.',                                          6.50, 200, 50, 'uploads/products/offc-001.jpg'),
(7,  2, 'OFFC-002', 'Ballpoint Pens (Box of 50)',    'Blue ink ballpoint pens, medium tip.',                                         9.25,   4, 15, 'uploads/products/offc-002.jpg'),
(8,  2, 'OFFC-003', 'Heavy Duty Stapler',            'Metal stapler, 100-sheet capacity.',                                          15.80,  30, 10, 'uploads/products/offc-003.jpg'),
(9,  2, 'OFFC-004', 'Sticky Notes (12-pack)',        'Assorted colour 3x3 inch sticky note pads.',                                   7.40,  75, 20, 'uploads/products/offc-004.jpg'),
(10, 2, 'OFFC-005', 'Thermal Receipt Paper Roll',    '80mm x 80mm thermal roll for POS printers.',                                   1.95,  18, 25, 'uploads/products/offc-005.jpg'),
(11, 3, 'HARD-001', 'Claw Hammer 16oz',              'Fibreglass handle claw hammer with anti-slip grip.',                          14.99,  25,  8, 'uploads/products/hard-001.jpg'),
(12, 3, 'HARD-002', 'Cordless Drill 18V',            '18V lithium-ion drill/driver kit with two batteries and charger.',           129.00,   9,  5, 'uploads/products/hard-002.jpg'),
(13, 3, 'HARD-003', 'Screwdriver Set 32pc',          'Precision and standard screwdriver set in a carry case.',                     22.40,   3,  6, 'uploads/products/hard-003.jpg'),
(14, 3, 'HARD-004', 'Safety Gloves (pair)',          'Cut-resistant work gloves, size L.',                                           4.75,  60, 20, 'uploads/products/hard-004.jpg'),
(15, 3, 'HARD-005', 'Measuring Tape 8m',             'Auto-lock steel tape measure with metric and imperial markings.',              8.90,  40, 12, 'uploads/products/hard-005.jpg');

-- Audit trail: initial creation of every product (from the web portal by the admin)
INSERT INTO `stock_logs` (`product_id`, `user_id`, `action_type`, `quantity_changed`, `platform`, `created_at`)
SELECT `id`, 1, 'CREATED', `quantity`, 'WEB', '2026-08-26 09:00:00' FROM `products`;

-- Audit trail: sample day-to-day activity from both platforms
INSERT INTO `stock_logs` (`product_id`, `user_id`, `action_type`, `quantity_changed`, `platform`, `created_at`) VALUES
(1,  2, 'STOCK_OUT',  5, 'MOBILE', '2026-09-02 10:15:00'),
(3,  3, 'STOCK_IN',  40, 'MOBILE', '2026-09-03 08:40:00'),
(4,  2, 'STOCK_OUT',  2, 'MOBILE', '2026-09-04 14:05:00'),
(7,  3, 'STOCK_OUT', 11, 'MOBILE', '2026-09-05 11:30:00'),
(10, 2, 'STOCK_OUT',  7, 'MOBILE', '2026-09-06 16:20:00'),
(13, 3, 'STOCK_OUT',  2, 'MOBILE', '2026-09-07 09:50:00'),
(6,  1, 'STOCK_IN',  50, 'WEB',    '2026-09-08 13:00:00'),
(12, 1, 'UPDATED',    0, 'WEB',    '2026-09-09 15:45:00'),
(2,  2, 'STOCK_IN',  10, 'MOBILE', '2026-09-10 10:00:00'),
(14, 3, 'STOCK_OUT',  5, 'MOBILE', '2026-09-11 12:10:00');

-- ============================================================================
--  BONUS: VIEWS (handy for the dashboard, API and PDF/Excel reports)
-- ============================================================================

-- Products joined with category + computed low-stock flag and line value
CREATE OR REPLACE VIEW `v_inventory_valuation` AS
SELECT
  p.`id`, p.`sku`, p.`name`, c.`name` AS `category_name`,
  p.`unit_price`, p.`quantity`, p.`min_stock_threshold`, p.`image_path`,
  (p.`quantity` < p.`min_stock_threshold`) AS `is_low_stock`,
  ROUND(p.`unit_price` * p.`quantity`, 2)  AS `total_value`
FROM `products` p
JOIN `categories` c ON c.`id` = p.`category_id`;

-- Only items currently under their safe minimum
CREATE OR REPLACE VIEW `v_low_stock_products` AS
SELECT * FROM `v_inventory_valuation` WHERE `is_low_stock` = 1;

-- Audit log with readable user and product names
CREATE OR REPLACE VIEW `v_audit_log` AS
SELECT
  l.`id`, l.`created_at`, u.`full_name` AS `user_name`, u.`role`,
  p.`sku`, p.`name` AS `product_name`,
  l.`action_type`, l.`quantity_changed`, l.`platform`
FROM `stock_logs` l
JOIN `users`    u ON u.`id` = l.`user_id`
JOIN `products` p ON p.`id` = l.`product_id`;

-- Dashboard KPIs in one row: Total Products, Total Valuation, Low-Stock Count
CREATE OR REPLACE VIEW `v_dashboard_kpis` AS
SELECT
  COUNT(*)                                            AS `total_products`,
  COALESCE(SUM(`total_value`), 0)                     AS `total_valuation`,
  COALESCE(SUM(`is_low_stock`), 0)                    AS `low_stock_count`
FROM `v_inventory_valuation`;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================ END OF SCRIPT ================================

