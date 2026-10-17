-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2026 at 10:26 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `switchnex_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT 'Administrator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `created_at`) VALUES
(1, 'anmol', '$2y$10$jhj8NgTKV326rZj7v8Ba.OWhI5vOceumU5/J2QQlaEOEeaVQujwCm', 'SwitchNex Admin', '2026-10-17 07:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_type` varchar(100) NOT NULL,
  `size_code` varchar(20) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `product_type`, `size_code`, `model_name`, `created_at`) VALUES
(1, 'LED TV', '32 Inch', '32', 'SN3200', '2026-10-17 07:31:09'),
(2, 'LED TV', '43 Inch', '43', 'SN4300', '2026-10-17 07:31:09'),
(3, 'LED TV', '52 Inch', '52', 'SN5200', '2026-10-17 07:31:09');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `serial_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_mobile` varchar(30) NOT NULL,
  `dealer_name` varchar(150) NOT NULL,
  `sale_date` date NOT NULL,
  `warranty_months` int(11) NOT NULL DEFAULT 12,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `serial_numbers`
--

CREATE TABLE `serial_numbers` (
  `id` int(11) NOT NULL,
  `batch_code` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_no` varchar(100) NOT NULL,
  `is_printed` tinyint(1) NOT NULL DEFAULT 0,
  `printed_at` datetime DEFAULT NULL,
  `is_sold` tinyint(1) NOT NULL DEFAULT 0,
  `sold_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sn_formats`
--

CREATE TABLE `sn_formats` (
  `id` int(11) NOT NULL,
  `format_name` varchar(100) NOT NULL,
  `prefix` varchar(20) DEFAULT 'SN',
  `pattern` varchar(150) NOT NULL DEFAULT '{PREFIX}{SIZE}{MM}{YY}{RAND5}',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sn_formats`
--

INSERT INTO `sn_formats` (`id`, `format_name`, `prefix`, `pattern`, `updated_at`) VALUES
(1, 'SwitchNex Default', 'SN', '{PREFIX}{SIZE}{MM}{YY}{RAND5}', '2026-10-17 07:31:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_linkage` (`product_name`,`product_type`,`model_name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_id` (`serial_id`),
  ADD KEY `idx_customer` (`customer_name`),
  ADD KEY `idx_mobile` (`customer_mobile`),
  ADD KEY `idx_dealer` (`dealer_name`);

--
-- Indexes for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_no` (`serial_no`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_serial` (`serial_no`),
  ADD KEY `idx_batch` (`batch_code`),
  ADD KEY `idx_status` (`is_printed`,`is_sold`);

--
-- Indexes for table `sn_formats`
--
ALTER TABLE `sn_formats`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sn_formats`
--
ALTER TABLE `sn_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`serial_id`) REFERENCES `serial_numbers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  ADD CONSTRAINT `serial_numbers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
