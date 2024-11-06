-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2024 at 04:11 AM
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
-- Database: `dbcon`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `location_id` int(11) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`location_id`, `street`, `district`, `province`, `postal_code`, `update_at`) VALUES
(1, 'Phetchaburi Rd.', 'Ratchathewi', 'Bangkok', '10400', '2024-11-06 00:32:37'),
(2, '88 Sukhumvit 19 Alley', 'Watthana', 'Bangkok', '10330', '2024-11-06 00:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `damaged_products`
--

CREATE TABLE `damaged_products` (
  `deproduct_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `deproduct_type` enum('expire','reject') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_orders`
--

CREATE TABLE `detail_orders` (
  `detail_order_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `listproduct_id` int(11) DEFAULT NULL,
  `quantity_set` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_orders`
--

INSERT INTO `detail_orders` (`detail_order_id`, `order_id`, `listproduct_id`, `quantity_set`, `price`) VALUES
(1, 1, 4, 3, 200.00),
(2, 1, 7, 2, 45.00),
(3, 1, 9, 2, 350.00),
(4, 1, 11, 5, 300.00),
(5, 2, 4, 2, 200.00),
(6, 2, 5, 5, 35.00),
(7, 2, 7, 2, 45.00),
(8, 2, 13, 3, 350.00),
(9, 3, 2, 2, 500.00),
(10, 3, 4, 2, 200.00),
(11, 3, 7, 2, 45.00),
(12, 3, 10, 3, 280.00),
(13, 4, 4, 1, 200.00),
(14, 4, 8, 2, 60.00),
(15, 4, 9, 3, 350.00),
(16, 4, 13, 2, 350.00),
(17, 5, 5, 2, 35.00),
(18, 5, 9, 5, 350.00),
(19, 5, 12, 5, 600.00),
(20, 5, 14, 2, 280.00),
(21, 5, 15, 2, 180.00),
(22, 5, 23, 1, 150.00),
(23, 6, 11, 2, 300.00),
(24, 6, 16, 2, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `issue_orders`
--

CREATE TABLE `issue_orders` (
  `issue_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `issue_type` enum('missing_item','damaged_item','incorrect_item','Expired or Quality Issue','Damaged Packaging') NOT NULL,
  `issue_description` text NOT NULL,
  `report_date` datetime NOT NULL,
  `issue_image` blob DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_orders`
--

INSERT INTO `issue_orders` (`issue_id`, `order_id`, `issue_type`, `issue_description`, `report_date`, `issue_image`, `product_id`) VALUES
(1, 6, 'missing_item', 'ไม่ได้รับสินค้าตัวนี้', '2024-11-06 09:40:50', NULL, 35),
(2, 6, 'damaged_item', 'สินค้าได้รับความเสียหาย', '2024-11-06 09:41:34', NULL, 36);

-- --------------------------------------------------------

--
-- Table structure for table `issue_product`
--

CREATE TABLE `issue_product` (
  `issueproduct_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `issue_type` enum('quality_issue','quantity_issue','damaged_issue') DEFAULT NULL,
  `issue_description` varchar(255) DEFAULT NULL,
  `report_date` datetime DEFAULT NULL,
  `issue_image` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_product`
--

INSERT INTO `issue_product` (`issueproduct_id`, `product_id`, `issue_type`, `issue_description`, `report_date`, `issue_image`) VALUES
(1, 5, 'quality_issue', 'สินค้าหมดอายุก่อนเวลา', '2024-11-06 09:54:14', NULL),
(2, 1, 'quality_issue', 'จำนวนสินค้าไม่ครบ', '2024-11-06 09:54:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notiflyproduct`
--

CREATE TABLE `notiflyproduct` (
  `notiflyproduct_id` int(11) NOT NULL,
  `listproduct_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `alert_type` enum('low_stock','near_exp','expired') NOT NULL,
  `status` enum('read','unread') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notiflyreport`
--

CREATE TABLE `notiflyreport` (
  `notiflyreport_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `notiflyreport_type` enum('issue_order','resolve_order','issue_product','resolve_product','add_product','order_product','con_order','can_order','ship_order','deli_order') DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notiflyreport`
--

INSERT INTO `notiflyreport` (`notiflyreport_id`, `user_id`, `order_id`, `product_id`, `notiflyreport_type`, `status`, `created_at`, `read_at`, `store_id`) VALUES
(1, 2, 1, NULL, 'order_product', 'unread', '2024-11-06 01:58:49', NULL, 1),
(2, 2, 2, NULL, 'order_product', 'unread', '2024-11-06 01:59:26', NULL, 1),
(3, 4, 3, NULL, 'order_product', 'unread', '2024-11-06 02:08:23', NULL, 2),
(4, 4, 4, NULL, 'order_product', 'unread', '2024-11-06 02:09:04', NULL, 2),
(5, 1, 4, NULL, 'can_order', 'unread', '2024-11-06 02:16:17', NULL, 2),
(6, 1, 3, NULL, 'con_order', 'unread', '2024-11-06 02:21:05', NULL, 2),
(7, 1, 2, NULL, 'con_order', 'unread', '2024-11-06 02:22:23', NULL, 1),
(8, 1, 1, NULL, 'con_order', 'unread', '2024-11-06 02:22:26', NULL, 1),
(9, 1, 2, NULL, 'ship_order', 'unread', '2024-11-06 02:22:51', NULL, 1),
(10, 2, 2, NULL, 'deli_order', 'unread', '2024-11-06 02:24:13', NULL, 1),
(11, 2, 2, NULL, 'add_product', 'unread', '2024-11-06 02:25:38', NULL, 1),
(12, 1, 3, NULL, 'ship_order', 'unread', '2024-11-06 02:30:13', NULL, 2),
(13, 1, 1, NULL, 'ship_order', 'unread', '2024-11-06 02:31:02', NULL, 1),
(14, 2, 5, NULL, 'order_product', 'unread', '2024-11-06 02:37:00', NULL, 1),
(15, 2, 6, NULL, 'order_product', 'unread', '2024-11-06 02:37:46', NULL, 1),
(16, 1, 6, NULL, 'con_order', 'unread', '2024-11-06 02:38:41', NULL, 1),
(17, 1, 5, NULL, 'con_order', 'unread', '2024-11-06 02:38:44', NULL, 1),
(18, 1, 6, NULL, 'ship_order', 'unread', '2024-11-06 02:38:58', NULL, 1),
(19, 1, 5, NULL, 'ship_order', 'unread', '2024-11-06 02:39:44', NULL, 1),
(20, 2, 6, NULL, 'deli_order', 'unread', '2024-11-06 02:40:16', NULL, 1),
(21, 2, 6, NULL, 'issue_order', 'unread', '2024-11-06 02:41:35', NULL, 1),
(22, 1, 6, NULL, 'resolve_order', 'unread', '2024-11-06 02:50:32', NULL, 1),
(23, 2, NULL, 5, 'issue_product', 'unread', '2024-11-06 02:54:14', NULL, 1),
(24, 2, NULL, 1, 'issue_product', 'unread', '2024-11-06 02:54:29', NULL, 1),
(25, 1, NULL, 1, 'resolve_product', 'unread', '2024-11-06 02:57:13', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `order_status` enum('paid','confirm','cancel','shipped','delivered','issue','refund','return_shipped','completed') DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipping_date` timestamp NULL DEFAULT NULL,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `cancel_info` text DEFAULT NULL,
  `cancel_pic` blob DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `barcode_pic` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `store_id`, `order_status`, `total_amount`, `order_date`, `shipping_date`, `delivered_date`, `cancel_info`, `cancel_pic`, `barcode`, `barcode_pic`) VALUES
(1, 1, 'shipped', 2890.00, '2024-11-06 01:58:49', '2024-11-06 02:31:02', NULL, NULL, NULL, '672ad4e6125666813', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373261643465363132353636363831332e706e67),
(2, 1, 'completed', 1715.00, '2024-11-06 01:59:26', '2024-11-06 02:22:51', '2024-11-06 02:24:13', NULL, NULL, '672ad2fbc07ab4906', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373261643266626330376162343930362e706e67),
(3, 2, 'shipped', 2330.00, '2024-11-06 02:08:23', '2024-11-06 02:30:13', NULL, NULL, NULL, '672ad4b53e5b56512', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373261643462353365356235363531322e706e67),
(4, 2, 'cancel', 2070.00, '2024-11-06 02:09:04', NULL, NULL, 'ไม่สามารถตรวจสอบรายการได้', '', NULL, NULL),
(5, 1, 'shipped', 5890.00, '2024-11-06 02:37:00', '2024-11-06 02:39:44', NULL, NULL, NULL, '672ad6f01fcac3629', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373261643666303166636163333632392e706e67),
(6, 1, 'return_shipped', 700.00, '2024-11-06 02:37:46', '2024-11-06 02:38:58', '2024-11-06 02:40:16', NULL, NULL, '672ad6c2bea7a9637', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373261643663326265613761393633372e706e67);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('credit_card','promptpay') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_pic` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_date`, `payment_method`, `amount`, `payment_pic`) VALUES
(1, 1, '2024-11-06 01:58:49', 'credit_card', 2890.00, NULL),
(2, 2, '2024-11-06 01:59:26', 'promptpay', 1715.00, 0x54584e5f3230323430313038385646653453396c6971675474714735742e6a7067),
(3, 3, '2024-11-06 02:08:23', 'credit_card', 2330.00, NULL),
(4, 4, '2024-11-06 02:09:04', 'promptpay', 2070.00, 0x54584e5f323032333038323238665148415465326733494e524d4d4f382e6a7067),
(5, 5, '2024-11-06 02:37:00', 'credit_card', 5890.00, NULL),
(6, 6, '2024-11-06 02:37:46', 'credit_card', 700.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `listproduct_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `status` enum('check','in_stock','expired','nearing_expiration','issue','cancel','unusable','replace','empty') NOT NULL,
  `quantity` int(11) NOT NULL,
  `location` varchar(50) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `detail_order_id` int(11) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `listproduct_id`, `store_id`, `expiration_date`, `status`, `quantity`, `location`, `manufacture_date`, `updated_at`, `detail_order_id`, `receipt_date`, `order_id`) VALUES
(1, 4, 1, '2024-11-27', 'replace', 50, 'ชั้นเก็บของ A1', '2024-10-30', '2024-11-06 02:57:13', 5, '2024-11-06', 2),
(2, 4, 1, '2024-11-27', 'in_stock', 50, 'ชั้นเก็บของ A1', '2024-10-30', '2024-11-06 02:25:38', 5, '2024-11-06', 2),
(3, 5, 1, '2024-11-27', 'in_stock', 50, 'ชั้นเก็บของ C1', '2024-10-30', '2024-11-06 02:25:38', 6, '2024-11-06', 2),
(4, 5, 1, '2024-11-27', 'in_stock', 50, 'ชั้นเก็บของ C1', '2024-10-30', '2024-11-06 02:25:38', 6, '2024-11-06', 2),
(5, 5, 1, '2024-11-27', 'issue', 50, 'ชั้นเก็บของ C1', '2024-10-30', '2024-11-06 02:54:14', 6, '2024-11-06', 2),
(6, 5, 1, '2024-11-27', 'in_stock', 50, 'ชั้นเก็บของ A2', '2024-10-30', '2024-11-06 02:25:38', 6, '2024-11-06', 2),
(7, 5, 1, '2024-11-27', 'in_stock', 50, 'ชั้นเก็บของ A2', '2024-10-30', '2024-11-06 02:25:38', 6, '2024-11-06', 2),
(8, 7, 1, '2024-11-27', 'in_stock', 30, 'ชั้นเก็บของ Z5', '2024-10-30', '2024-11-06 02:25:38', 7, '2024-11-06', 2),
(9, 7, 1, '2024-11-27', 'in_stock', 30, 'ชั้นเก็บของ Z5', '2024-10-30', '2024-11-06 02:25:38', 7, '2024-11-06', 2),
(10, 13, 1, '2024-11-27', 'in_stock', 10, 'ชั้นเก็บของ A2', '2024-10-30', '2024-11-06 02:25:38', 8, '2024-11-06', 2),
(11, 13, 1, '2024-11-27', 'in_stock', 10, 'ชั้นเก็บของ A2', '2024-10-30', '2024-11-06 02:25:38', 8, '2024-11-06', 2),
(12, 13, 1, '2024-11-27', 'in_stock', 10, 'ชั้นเก็บของ A4', '2024-10-30', '2024-11-06 02:25:38', 8, '2024-11-06', 2),
(13, 2, 2, '2024-11-27', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:30:13', 9, NULL, 3),
(14, 2, 2, '2024-11-27', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:30:13', 9, NULL, 3),
(15, 4, 2, '2024-11-27', 'check', 50, NULL, '2024-10-28', '2024-11-06 02:30:13', 10, NULL, 3),
(16, 4, 2, '2024-11-27', 'check', 50, NULL, '2024-10-28', '2024-11-06 02:30:13', 10, NULL, 3),
(17, 7, 2, '2024-11-28', 'check', 30, NULL, '2024-10-28', '2024-11-06 02:30:13', 11, NULL, 3),
(18, 7, 2, '2024-11-28', 'check', 30, NULL, '2024-10-28', '2024-11-06 02:30:13', 11, NULL, 3),
(19, 10, 2, '2024-11-24', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:30:13', 12, NULL, 3),
(20, 10, 2, '2024-11-24', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:30:13', 12, NULL, 3),
(21, 10, 2, '2024-11-24', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:30:13', 12, NULL, 3),
(22, 4, 1, '2024-12-01', 'check', 50, NULL, '2024-10-28', '2024-11-06 02:31:02', 1, NULL, 1),
(23, 4, 1, '2024-12-01', 'check', 50, NULL, '2024-10-28', '2024-11-06 02:31:02', 1, NULL, 1),
(24, 4, 1, '2024-12-01', 'check', 50, NULL, '2024-10-28', '2024-11-06 02:31:02', 1, NULL, 1),
(25, 7, 1, '2024-12-01', 'check', 30, NULL, '2024-10-28', '2024-11-06 02:31:02', 2, NULL, 1),
(26, 7, 1, '2024-12-01', 'check', 30, NULL, '2024-10-28', '2024-11-06 02:31:02', 2, NULL, 1),
(27, 9, 1, '2024-12-08', 'check', 10, NULL, '2024-09-30', '2024-11-06 02:31:02', 3, NULL, 1),
(28, 9, 1, '2024-12-08', 'check', 10, NULL, '2024-09-30', '2024-11-06 02:31:02', 3, NULL, 1),
(29, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:31:02', 4, NULL, 1),
(30, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:31:02', 4, NULL, 1),
(31, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:31:02', 4, NULL, 1),
(32, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:31:02', 4, NULL, 1),
(33, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:31:02', 4, NULL, 1),
(34, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:38:58', 23, NULL, 6),
(35, 11, 1, '2024-12-08', 'check', 5, NULL, '2024-10-28', '2024-11-06 02:50:32', 23, NULL, 6),
(36, 16, 1, '2024-12-08', 'check', 20, NULL, '2024-10-28', '2024-11-06 02:50:32', 24, NULL, 6),
(37, 16, 1, '2024-12-08', 'check', 20, NULL, '2024-10-28', '2024-11-06 02:38:58', 24, NULL, 6),
(38, 5, 1, '2024-12-08', 'check', 50, NULL, '2024-10-29', '2024-11-06 02:39:44', 17, NULL, 5),
(39, 5, 1, '2024-12-08', 'check', 50, NULL, '2024-10-29', '2024-11-06 02:39:44', 17, NULL, 5),
(40, 9, 1, '2024-12-01', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 18, NULL, 5),
(41, 9, 1, '2024-12-01', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 18, NULL, 5),
(42, 9, 1, '2024-12-01', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 18, NULL, 5),
(43, 9, 1, '2024-12-01', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 18, NULL, 5),
(44, 9, 1, '2024-12-01', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 18, NULL, 5),
(45, 12, 1, '2024-12-08', 'check', 3, NULL, '2024-10-29', '2024-11-06 02:39:44', 19, NULL, 5),
(46, 12, 1, '2024-12-08', 'check', 3, NULL, '2024-10-29', '2024-11-06 02:39:44', 19, NULL, 5),
(47, 12, 1, '2024-12-08', 'check', 3, NULL, '2024-10-29', '2024-11-06 02:39:44', 19, NULL, 5),
(48, 12, 1, '2024-12-08', 'check', 3, NULL, '2024-10-29', '2024-11-06 02:39:44', 19, NULL, 5),
(49, 12, 1, '2024-12-08', 'check', 3, NULL, '2024-10-29', '2024-11-06 02:39:44', 19, NULL, 5),
(50, 14, 1, '2024-12-08', 'check', 5, NULL, '2024-10-29', '2024-11-06 02:39:44', 20, NULL, 5),
(51, 14, 1, '2024-12-08', 'check', 5, NULL, '2024-10-29', '2024-11-06 02:39:44', 20, NULL, 5),
(52, 15, 1, '2024-12-08', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 21, NULL, 5),
(53, 15, 1, '2024-12-08', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 21, NULL, 5),
(54, 23, 1, '2024-12-08', 'check', 10, NULL, '2024-10-29', '2024-11-06 02:39:44', 22, NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `products_info`
--

CREATE TABLE `products_info` (
  `listproduct_id` int(11) NOT NULL,
  `product_name` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price_set` decimal(10,2) NOT NULL,
  `product_info` text DEFAULT NULL,
  `quantity_set` int(11) NOT NULL,
  `product_pic` blob DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products_info`
--

INSERT INTO `products_info` (`listproduct_id`, `product_name`, `category`, `price_set`, `product_info`, `quantity_set`, `product_pic`, `visible`, `updated_at`) VALUES
(1, 'กาแฟ Gold Blend', 'กาแฟ', 500.00, 'เมล็ดกาแฟไม่มีคาเฟอีน เหมาะสำหรับลูกค้าที่ไม่ต้องการ', 25, 0x6361666530342e706e67, 1, '2024-11-06 08:10:01'),
(2, 'กาแฟเมล็ด Arabica', 'กาแฟ', 500.00, 'เมล็ดกาแฟคั่วกลาง 100% สำหรับดริปและเอสเพรสโซ', 5, 0x6361666530332e706e67, 1, '2024-11-06 08:08:43'),
(3, 'กาแฟเมล็ด Robusta', 'กาแฟ', 350.00, 'เมล็ดกาแฟคั่วเข้ม สำหรับเมนูเครื่องดื่มเข้มข้น', 10, 0x6361666530322e706e67, 1, '2024-11-06 08:08:32'),
(4, 'กาแฟดริปสำเร็จรูป', 'กาแฟ', 200.00, 'กาแฟดริปพร้อมชง ในซองเล็ก', 50, 0x6361666530312e6a7067, 1, '2024-11-06 08:07:47'),
(5, 'ครัวซองต์', 'ขนมและของว่าง', 35.00, 'ครัวซองต์กรอบนอกนุ่มใน ใช้เนยแท้สำหรับกาแฟและชา', 50, 0xe0b884e0b8a3e0b8b1e0b8a7e0b88ae0b8ade0b8872e6a7067, 1, '2024-11-06 08:21:09'),
(6, 'คุกกี้ช็อกโกแลตชิพ', 'ขนมและของว่าง', 30.00, 'คุกกี้ช็อกโกแลตชิพหอมหวาน กรอบนุ่ม', 50, 0xe0b884e0b8b8e0b881e0b881e0b8b5e0b989e0b88ae0b987e0b8ade0b881e0b982e0b881e0b981e0b8a5e0b895e0b88ae0b8b4e0b89e2e6a7067, 1, '2024-11-06 08:21:03'),
(7, 'นมสดพาสเจอร์ไรส์', 'นมและครีม', 45.00, 'นมสดจากฟาร์มโคนม เหมาะสำหรับลาเต้และคาปูชิโน่', 30, 0xe0b899e0b8a1e0b8aae0b894e0b89ee0b8b2e0b8aae0b980e0b888e0b8ade0b8a3e0b98ce0b984e0b8a3e0b8aae0b98c2e6a7067, 1, '2024-11-06 08:16:45'),
(8, 'นมข้นจืด', 'นมและครีม', 60.00, 'นมข้นสำหรับใช้ในกาแฟและขนมหวาน', 24, 0xe0b899e0b8a1e0b882e0b989e0b8992e6a7067, 1, '2024-11-06 08:15:56'),
(9, 'ผงวานิลลา', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 350.00, 'ผงวานิลลาธรรมชาติสำหรับเพิ่มรสหวานในกาแฟและขนม', 10, 0xe0b884e0b8b2e0b8a3e0b8b2e0b980e0b8a1e0b8a52e6a7067, 1, '2024-11-06 08:31:22'),
(10, 'ผงคาราเมล', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 280.00, 'ผงคาราเมลสำหรับเครื่องดื่มและขนมหวาน', 5, 0xe0b8a7e0b8a5e0b8b4e0b8a5e0b8b22e6a7067, 1, '2024-11-06 08:30:59'),
(11, 'ผงโกโก้', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 300.00, 'ผงโกโก้เกรดพรีเมียมสำหรับทำเครื่องดื่มโกโก้', 5, 0xe0b89ce0b887e0b982e0b881e0b982e0b881e0b9892e6a7067, 1, '2024-11-06 08:19:04'),
(12, 'ผงมัทฉะ', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 600.00, 'ผงชาเขียวมัทฉะเกรดพรีเมียมจากญี่ปุ่น', 3, 0xe0b89ce0b887e0b8a1e0b8b1e0b897e0b889e0b8b02e6a7067, 1, '2024-11-06 08:18:27'),
(13, 'อัลมอนด์อบ', 'ผลิตภัณฑ์เพิ่มมูลค่า', 350.00, 'อัลมอนด์อบกรอบ เพิ่มรสชาติในเครื่องดื่มและขนมหวาน', 10, 0xe0b8ade0b8b1e0b8a5e0b8a1e0b8a1e0b8ade0b8a52e6a7067, 1, '2024-11-06 08:29:06'),
(14, 'เมล็ดเจีย', 'ผลิตภัณฑ์เพิ่มมูลค่า', 280.00, 'เมล็ดเจียออร์แกนิคสำหรับเครื่องดื่มสุขภาพ', 5, 0xe0b980e0b8a1e0b8a5e0b987e0b894e0b980e0b888e0b8b5e0b8a22e6a7067, 1, '2024-11-06 08:28:26'),
(15, 'น้ำเชื่อมรสผลไม้', 'ผลิตภัณฑ์เพิ่มมูลค่า', 180.00, 'น้ำเชื่อมรสผลไม้สำหรับทำเครื่องดื่มและขนม', 10, 0xe0b899e0b989e0b8b3e0b980e0b88ae0b8b7e0b988e0b8ade0b8a1e0b89ce0b8a5e0b984e0b8a1e0b9892e6a7067, 1, '2024-11-06 08:28:20'),
(16, 'น้ำตาลทราย', 'สารให้ความหวานและสารแต่งกลิ่นรส', 50.00, 'น้ำตาลทรายขาวบริสุทธิ์ สำหรับชงกาแฟ', 20, 0xe0b899e0b989e0b8b3e0b895e0b8b2e0b8a52e6a7067, 1, '2024-11-06 08:26:35'),
(17, 'หญ้าหวาน', 'สารให้ความหวานและสารแต่งกลิ่นรส', 180.00, 'สารให้ความหวานธรรมชาติจากหญ้าหวาน ไม่มีแคลอรี', 5, 0xe0b8abe0b88de0b989e0b8b2e0b8abe0b8a7e0b8b2e0b8992e6a7067, 1, '2024-11-06 08:26:21'),
(23, 'ไซรัปวานิลลา', 'ไซรัปและน้ำเชื่อม', 150.00, 'ไซรัปวานิลลาสำหรับเพิ่มรสชาติหวานหอม', 10, 0xe0b984e0b88be0b8a3e0b8b1e0b89be0b8a7e0b8b2e0b899e0b8b4e0b8a5e0b8a5e0b8b22e6a7067, 1, '2024-11-06 08:17:33');

-- --------------------------------------------------------

--
-- Table structure for table `product_alert_settings`
--

CREATE TABLE `product_alert_settings` (
  `alert_id` int(11) NOT NULL,
  `listproduct_id` int(11) NOT NULL,
  `low_stock_threshold` int(11) DEFAULT 10,
  `expiry_alert_days` int(11) DEFAULT 7,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_alert_settings`
--

INSERT INTO `product_alert_settings` (`alert_id`, `listproduct_id`, `low_stock_threshold`, `expiry_alert_days`, `updated_at`) VALUES
(1, 1, 10, 5, '2024-11-06 08:48:39'),
(2, 2, 10, 5, '2024-11-06 08:48:44'),
(3, 3, 10, 5, '2024-11-06 08:48:48'),
(4, 4, 10, 5, '2024-11-06 08:48:52');

-- --------------------------------------------------------

--
-- Table structure for table `resolution_orders`
--

CREATE TABLE `resolution_orders` (
  `resolution_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `resolution_type` enum('refund','return_item') DEFAULT NULL,
  `resolution_info` varchar(255) DEFAULT NULL,
  `resolution_image` blob DEFAULT NULL,
  `resolution_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resolution_orders`
--

INSERT INTO `resolution_orders` (`resolution_id`, `order_id`, `resolution_type`, `resolution_info`, `resolution_image`, `resolution_date`) VALUES
(1, 6, 'return_item', 'กำลังจัดส่งสินค้าคืน', 0x363732616439373861343764362e6a7067, '2024-11-06 09:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `resolution_product`
--

CREATE TABLE `resolution_product` (
  `resolutionproduct_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `resolution_type` enum('replace','reject') DEFAULT NULL,
  `resolution_description` varchar(255) DEFAULT NULL,
  `resolution_date` datetime DEFAULT NULL,
  `resolution_image` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resolution_product`
--

INSERT INTO `resolution_product` (`resolutionproduct_id`, `product_id`, `resolution_type`, `resolution_description`, `resolution_date`, `resolution_image`) VALUES
(1, 1, 'replace', 'กำลังจัดส่งสินค้าคืน', '2024-11-06 09:57:13', 0x2e2e2f2e2e2f75706c6f61642f7265736f6c7574696f6e5f696d616765732f363732616462303965343164365fe0b882e0b899e0b8aae0b988e0b8872e6a7067);

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `location_id` int(11) NOT NULL,
  `tel_store` char(10) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`store_id`, `store_name`, `location_id`, `tel_store`, `update_at`) VALUES
(1, 'Bokoto Royal', 1, '0825712345', '2024-11-06 00:32:49'),
(2, 'Bokoto Town', 2, '0892987655', '2024-11-06 00:33:07');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_manage`
--

CREATE TABLE `transaction_manage` (
  `transactionm_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `transaction_type` enum('add_u','edit_u','del_u','add_s','edit_s','del_s','add_p','edit_p','del_p') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tel_user` char(10) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `reset_password` tinyint(1) DEFAULT 0,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `surname`, `email`, `password`, `tel_user`, `role`, `store_id`, `reset_password`, `update_at`) VALUES
(1, 'Chatchai', 'Phongpanich', 'chatchai.p@example.com', '$2y$10$.mWmPC/1986stGwDuZ5ooOmvL6lcid1H1qgbeum7q54YSOuCgD4My', '0821112345', 'admin', NULL, 0, '2024-10-06 15:56:14'),
(2, 'Patricia', 'Harris', 'patricia.h@example.com', '$2y$10$UcebXNtmr/npBGu6WJDHfegibQd0Nv1ytJFMPXE0wRxrQQB9ykgYC', '0859123456', 'manager', 1, 0, '2024-11-05 14:27:04'),
(3, 'Christopher', 'Clark', 'christopher.c@example.com', '$2y$10$ggkH9TIU2AkxXnHm86x3bO4N/p774S0aL/7Peg6E3X6GcbT9nxVua', '0987456134', 'staff', 1, 0, '2024-11-05 14:38:26'),
(4, 'Charles', 'White', 'charles.w@example.com', '$2y$10$Te0B4DdL45eQdKyuNTWupOKYPeyH5S2XaW/D1YeYaXGw5HEV9FtOm', '0654321789', 'manager', 2, 0, '2024-11-06 02:04:29'),
(5, 'Laura', 'Martinez', 'laura.m@example.com', '$2y$10$0uO7/b1yZ462GxJq92agNOMup.H1CCv01okomsQAxNG84f0zKJqKa', '0567894321', 'staff', 2, 1, '2024-11-05 14:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawreport`
--

CREATE TABLE `withdrawreport` (
  `withdraw_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `withdraw_quantity` int(11) NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `damaged_products`
--
ALTER TABLE `damaged_products`
  ADD PRIMARY KEY (`deproduct_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `detail_orders`
--
ALTER TABLE `detail_orders`
  ADD PRIMARY KEY (`detail_order_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `listproduct_id` (`listproduct_id`);

--
-- Indexes for table `issue_orders`
--
ALTER TABLE `issue_orders`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_issue_orders_product` (`product_id`);

--
-- Indexes for table `issue_product`
--
ALTER TABLE `issue_product`
  ADD PRIMARY KEY (`issueproduct_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notiflyproduct`
--
ALTER TABLE `notiflyproduct`
  ADD PRIMARY KEY (`notiflyproduct_id`),
  ADD KEY `listproduct_id` (`listproduct_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `notiflyreport`
--
ALTER TABLE `notiflyreport`
  ADD PRIMARY KEY (`notiflyreport_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_store_id` (`store_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `listproduct_id` (`listproduct_id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `fk_detail_orders_product` (`detail_order_id`),
  ADD KEY `fk_order` (`order_id`);

--
-- Indexes for table `products_info`
--
ALTER TABLE `products_info`
  ADD PRIMARY KEY (`listproduct_id`);

--
-- Indexes for table `product_alert_settings`
--
ALTER TABLE `product_alert_settings`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `listproduct_id` (`listproduct_id`);

--
-- Indexes for table `resolution_orders`
--
ALTER TABLE `resolution_orders`
  ADD PRIMARY KEY (`resolution_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `resolution_product`
--
ALTER TABLE `resolution_product`
  ADD PRIMARY KEY (`resolutionproduct_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`store_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `transaction_manage`
--
ALTER TABLE `transaction_manage`
  ADD PRIMARY KEY (`transactionm_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `withdrawreport`
--
ALTER TABLE `withdrawreport`
  ADD PRIMARY KEY (`withdraw_id`),
  ADD KEY `fk_users_id` (`user_id`),
  ADD KEY `fk_products_id` (`product_id`),
  ADD KEY `fk_stores_id` (`store_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `damaged_products`
--
ALTER TABLE `damaged_products`
  MODIFY `deproduct_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_orders`
--
ALTER TABLE `detail_orders`
  MODIFY `detail_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `issue_orders`
--
ALTER TABLE `issue_orders`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `issue_product`
--
ALTER TABLE `issue_product`
  MODIFY `issueproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notiflyproduct`
--
ALTER TABLE `notiflyproduct`
  MODIFY `notiflyproduct_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notiflyreport`
--
ALTER TABLE `notiflyreport`
  MODIFY `notiflyreport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `products_info`
--
ALTER TABLE `products_info`
  MODIFY `listproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_alert_settings`
--
ALTER TABLE `product_alert_settings`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resolution_orders`
--
ALTER TABLE `resolution_orders`
  MODIFY `resolution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resolution_product`
--
ALTER TABLE `resolution_product`
  MODIFY `resolutionproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `store_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaction_manage`
--
ALTER TABLE `transaction_manage`
  MODIFY `transactionm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `withdrawreport`
--
ALTER TABLE `withdrawreport`
  MODIFY `withdraw_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `damaged_products`
--
ALTER TABLE `damaged_products`
  ADD CONSTRAINT `damaged_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `damaged_products_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`);

--
-- Constraints for table `detail_orders`
--
ALTER TABLE `detail_orders`
  ADD CONSTRAINT `detail_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `detail_orders_ibfk_2` FOREIGN KEY (`listproduct_id`) REFERENCES `products_info` (`listproduct_id`);

--
-- Constraints for table `issue_orders`
--
ALTER TABLE `issue_orders`
  ADD CONSTRAINT `fk_issue_orders_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `issue_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `issue_product`
--
ALTER TABLE `issue_product`
  ADD CONSTRAINT `issue_product_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `notiflyproduct`
--
ALTER TABLE `notiflyproduct`
  ADD CONSTRAINT `notiflyproduct_ibfk_1` FOREIGN KEY (`listproduct_id`) REFERENCES `products_info` (`listproduct_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notiflyproduct_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notiflyproduct_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notiflyreport`
--
ALTER TABLE `notiflyreport`
  ADD CONSTRAINT `fk_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  ADD CONSTRAINT `notiflyreport_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `notiflyreport_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `notiflyreport_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_detail_orders_product` FOREIGN KEY (`detail_order_id`) REFERENCES `detail_orders` (`detail_order_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`listproduct_id`) REFERENCES `products_info` (`listproduct_id`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`);

--
-- Constraints for table `product_alert_settings`
--
ALTER TABLE `product_alert_settings`
  ADD CONSTRAINT `product_alert_settings_ibfk_1` FOREIGN KEY (`listproduct_id`) REFERENCES `products_info` (`listproduct_id`) ON DELETE CASCADE;

--
-- Constraints for table `resolution_orders`
--
ALTER TABLE `resolution_orders`
  ADD CONSTRAINT `resolution_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `resolution_product`
--
ALTER TABLE `resolution_product`
  ADD CONSTRAINT `resolution_product_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `stores`
--
ALTER TABLE `stores`
  ADD CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `address` (`location_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_manage`
--
ALTER TABLE `transaction_manage`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawreport`
--
ALTER TABLE `withdrawreport`
  ADD CONSTRAINT `fk_products_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `fk_stores_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  ADD CONSTRAINT `fk_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
