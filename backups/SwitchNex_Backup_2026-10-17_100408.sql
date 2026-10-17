-- =========================================================
-- SwitchNex Database Backup
-- Database: `switchnex_db`
-- Generated: 2026-10-17 10:04:08
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ---------------------------------------------------------
-- Table structure for `admins`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT 'Administrator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `admins` (1 rows)
INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `created_at`) VALUES 
('1', 'anmol', '$2y$10$jhj8NgTKV326rZj7v8Ba.OWhI5vOceumU5/J2QQlaEOEeaVQujwCm', 'SwitchNex Admin', '2026-10-17 13:12:03');

-- ---------------------------------------------------------
-- Table structure for `products`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `product_type` varchar(100) NOT NULL,
  `size_code` varchar(20) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_linkage` (`product_name`,`product_type`,`model_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `products` (3 rows)
INSERT INTO `products` (`id`, `product_name`, `product_type`, `size_code`, `model_name`, `created_at`) VALUES 
('1', 'LED TV', '32 Inch', '32', 'SN3200', '2026-10-17 13:01:09'),
('2', 'LED TV', '43 Inch', '43', 'SN4300', '2026-10-17 13:01:09'),
('3', 'LED TV', '52 Inch', '52', 'SN5200', '2026-10-17 13:01:09');

-- ---------------------------------------------------------
-- Table structure for `sn_formats`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `sn_formats`;
CREATE TABLE `sn_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `format_name` varchar(100) NOT NULL,
  `prefix` varchar(20) DEFAULT 'SN',
  `pattern` varchar(150) NOT NULL DEFAULT '{PREFIX}{SIZE}{MM}{YY}{RAND5}',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `sn_formats` (1 rows)
INSERT INTO `sn_formats` (`id`, `format_name`, `prefix`, `pattern`, `updated_at`) VALUES 
('1', 'SwitchNex Default', 'SN', '{PREFIX}{SIZE}{MM}{YY}{RAND5}', '2026-10-17 13:01:09');

-- ---------------------------------------------------------
-- Table structure for `serial_numbers`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `serial_numbers`;
CREATE TABLE `serial_numbers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_no` varchar(100) NOT NULL,
  `is_printed` tinyint(1) NOT NULL DEFAULT 0,
  `printed_at` datetime DEFAULT NULL,
  `is_sold` tinyint(1) NOT NULL DEFAULT 0,
  `sold_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_no` (`serial_no`),
  KEY `product_id` (`product_id`),
  KEY `idx_serial` (`serial_no`),
  KEY `idx_batch` (`batch_code`),
  KEY `idx_status` (`is_printed`,`is_sold`),
  CONSTRAINT `serial_numbers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Table structure for `sales`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_mobile` varchar(30) NOT NULL,
  `dealer_name` varchar(150) NOT NULL,
  `sale_date` date NOT NULL,
  `warranty_months` int(11) NOT NULL DEFAULT 12,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_id` (`serial_id`),
  KEY `idx_customer` (`customer_name`),
  KEY `idx_mobile` (`customer_mobile`),
  KEY `idx_dealer` (`dealer_name`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`serial_id`) REFERENCES `serial_numbers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
