-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2024 at 06:42 AM
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
(1, 'Phetchaburi Rd.', 'Ratchathewi', 'Bangkok', '10400', '2024-10-03 06:07:40'),
(16, 'Phahonyothin Rd', 'Chatuchak', 'Bangkok ', '10900', '2024-10-05 18:51:47'),
(18, 'Rama I Rd', 'Pathum Wan', 'Bangkok ', '10330', '2024-10-12 07:06:51');

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

--
-- Dumping data for table `damaged_products`
--

INSERT INTO `damaged_products` (`deproduct_id`, `product_id`, `store_id`, `deproduct_type`, `created_at`) VALUES
(1, 424, 13, 'reject', '2024-11-05 12:34:27'),
(2, 423, 13, 'expire', '2024-11-05 12:37:45');

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
(164, 120, 14, 2, 1.00),
(165, 120, 18, 1, 450.00),
(166, 121, 18, 2, 450.00),
(167, 121, 21, 1, 450.00),
(168, 122, 14, 2, 1.00),
(169, 122, 18, 3, 450.00);

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
(18, 424, 'quantity_issue', 'aa', '2024-11-05 12:18:10', 0x363732396161393261356635372e706e67);

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

--
-- Dumping data for table `notiflyproduct`
--

INSERT INTO `notiflyproduct` (`notiflyproduct_id`, `listproduct_id`, `product_id`, `alert_type`, `status`, `created_at`, `read_at`, `store_id`) VALUES
(7, 18, 423, 'low_stock', 'unread', '2024-11-05 04:25:09', NULL, 13),
(8, 18, 423, 'near_exp', 'unread', '2024-11-05 04:25:09', NULL, 13),
(9, 18, 424, 'near_exp', 'read', '2024-11-05 04:25:09', '2024-11-05 05:03:07', 13),
(10, 21, 425, 'low_stock', 'unread', '2024-11-05 04:25:09', NULL, 13),
(11, 21, 425, 'near_exp', 'unread', '2024-11-05 04:25:09', NULL, 13),
(12, 18, 423, 'expired', 'unread', '2024-11-05 04:31:22', '2024-11-05 05:03:11', 13),
(13, 18, 423, 'expired', 'read', '2024-11-05 04:33:10', '2024-11-05 05:03:11', 13),
(14, 18, 423, 'expired', 'read', '2024-11-05 04:33:10', '2024-11-05 05:03:10', 13),
(15, 18, 423, 'expired', 'read', '2024-11-05 04:34:35', '2024-11-05 05:03:10', 13),
(16, 18, 423, 'expired', 'read', '2024-11-05 04:34:35', '2024-11-05 05:03:10', 13),
(17, 18, 423, 'expired', 'read', '2024-11-05 04:39:55', '2024-11-05 05:03:10', 13),
(18, 18, 423, 'expired', 'read', '2024-11-05 04:41:21', '2024-11-05 05:03:10', 13),
(19, 18, 423, 'expired', 'read', '2024-11-05 04:43:17', '2024-11-05 05:03:10', 13),
(20, 18, 423, 'near_exp', 'read', '2024-11-05 04:44:53', '2024-11-05 05:03:09', 13),
(21, 18, 423, 'expired', 'read', '2024-11-05 04:51:52', '2024-11-05 05:03:08', 13),
(22, 18, 423, 'expired', 'read', '2024-11-05 04:53:15', '2024-11-05 05:03:09', 13),
(23, 18, 423, 'expired', 'read', '2024-11-05 05:00:43', '2024-11-05 05:03:09', 13),
(24, 18, 424, 'near_exp', 'unread', '2024-11-05 05:08:28', NULL, 13),
(25, 18, 423, 'expired', 'unread', '2024-11-05 05:37:45', NULL, 13);

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
(37, 1, 122, NULL, 'ship_order', 'read', '2024-11-04 19:15:40', NULL, 13),
(38, 53, 122, NULL, 'deli_order', 'unread', '2024-11-04 19:16:10', NULL, 13),
(39, 53, 122, NULL, 'add_product', 'unread', '2024-11-04 19:16:22', NULL, 13),
(40, 1, 120, NULL, 'ship_order', 'read', '2024-11-04 21:01:26', NULL, 13),
(41, 53, 120, NULL, 'deli_order', 'unread', '2024-11-04 21:01:42', NULL, 13),
(42, 53, 120, NULL, 'add_product', 'unread', '2024-11-04 21:01:51', NULL, 13),
(43, 53, 121, NULL, 'deli_order', 'unread', '2024-11-05 03:23:15', NULL, 13),
(44, 1, 121, NULL, 'ship_order', 'unread', '2024-11-05 03:23:59', NULL, 13),
(45, 1, 121, NULL, 'ship_order', 'unread', '2024-11-05 03:23:59', NULL, 13),
(46, 53, 121, NULL, 'deli_order', 'unread', '2024-11-05 03:24:27', NULL, 13),
(47, 53, 121, NULL, 'add_product', 'unread', '2024-11-05 03:24:35', NULL, 13),
(48, 1, 121, NULL, 'ship_order', 'unread', '2024-11-05 04:17:58', NULL, 13),
(49, 53, 121, NULL, 'deli_order', 'unread', '2024-11-05 04:18:32', NULL, 13),
(50, 53, 121, NULL, 'add_product', 'unread', '2024-11-05 04:18:40', NULL, 13),
(51, 53, NULL, 424, 'issue_product', 'unread', '2024-11-05 05:18:10', NULL, 13),
(52, 1, NULL, 424, 'resolve_product', 'unread', '2024-11-05 05:22:02', NULL, 13),
(53, 1, NULL, 424, 'resolve_product', 'unread', '2024-11-05 05:26:32', NULL, 13),
(54, 1, NULL, 424, 'resolve_product', 'unread', '2024-11-05 05:34:27', NULL, 13);

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
(120, 13, 'completed', 452.00, '2024-11-04 09:55:35', '2024-11-04 21:01:26', '2024-11-04 21:01:42', NULL, NULL, '67293626a66044673', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373239333632366136363034343637332e706e67),
(121, 13, 'completed', 1350.00, '2024-11-04 09:56:23', '2024-11-05 04:17:58', '2024-11-05 04:18:32', NULL, NULL, '67299c76b39d37449', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373239396337366233396433373434392e706e67),
(122, 13, 'completed', 1352.00, '2024-11-04 16:09:40', '2024-11-04 19:15:40', '2024-11-04 19:16:10', 'a', '', '67291d5c2ad262914', 0x2e2e2f2e2e2f75706c6f61642f626172636f6465732f36373239316435633261643236323931342e706e67);

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
(102, 120, '2024-11-04 09:55:35', 'credit_card', 452.00, NULL),
(103, 121, '2024-11-04 09:56:23', 'credit_card', 1350.00, NULL),
(104, 122, '2024-11-04 16:09:40', 'credit_card', 1352.00, NULL);

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
(423, 18, 13, '2024-11-05', 'expired', 5, 'Aa', '2024-10-30', '2024-11-05 05:37:45', 166, '2024-11-05', 121),
(424, 18, 13, '2024-11-08', 'nearing_expiration', 5, 'Aa', '2024-10-30', '2024-11-05 05:37:45', 166, '2024-11-05', 121),
(425, 21, 13, '2024-11-08', 'nearing_expiration', 5, 'Aa', '2024-10-30', '2024-11-05 05:35:30', 167, '2024-11-05', 121);

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
(14, 'กาแฟ Gold Blend', 'กาแฟ', 1.00, 'อะไรสักอย่างลืม', 25, 0x4466645f636f6666656573746f636b2de0b881e0b8b2e0b8a3e0b981e0b888e0b989e0b887e0b980e0b895e0b8b7e0b8ade0b899202831292e6a7067, 1, '2024-10-18 13:29:20'),
(15, 'กาแฟเมล็ด Arabica', 'กาแฟ', 500.00, 'เมล็ดกาแฟคั่วกลาง 100% สำหรับดริปและเอสเพรสโซ', 5, NULL, 1, '2024-10-08 03:23:44'),
(16, 'กาแฟเมล็ด Robusta', 'กาแฟ', 350.00, 'เมล็ดกาแฟคั่วเข้ม สำหรับเมนูเครื่องดื่มเข้มข้น', 10, NULL, 1, '2024-10-08 03:23:44'),
(17, 'กาแฟดริปสำเร็จรูป', 'กาแฟ', 200.00, 'กาแฟดริปพร้อมชง ในซองเล็ก', 50, NULL, 1, '2024-10-08 03:23:44'),
(18, 'กาแฟ Decaf', 'กาแฟ', 450.00, 'เมล็ดกาแฟไม่มีคาเฟอีน เหมาะสำหรับลูกค้าที่ไม่ต้องการคาเฟอีน', 5, NULL, 1, '2024-10-08 03:23:44'),
(19, 'กาแฟผสม Moka', 'กาแฟ', 550.00, 'เมล็ดกาแฟผสม Arabica และ Robusta สำหรับ Moka', 10, NULL, 1, '2024-10-08 03:23:44'),
(20, 'กาแฟคั่วเข้ม Dark Roast', 'กาแฟ', 400.00, 'กาแฟคั่วเข้มสำหรับผู้ชอบรสขม เข้มและกลิ่นหอม', 7, NULL, 1, '2024-10-08 03:23:44'),
(21, 'กาแฟ Cold Brew', 'กาแฟ', 450.00, 'เมล็ดกาแฟสำหรับชงแบบ Cold Brew', 5, NULL, 1, '2024-10-08 03:23:44'),
(22, 'กาแฟ Single Origin', 'กาแฟ', 600.00, 'กาแฟจากแหล่งปลูกเฉพาะถิ่น สำหรับกาแฟคุณภาพสูง', 8, NULL, 1, '2024-10-08 03:23:44'),
(23, 'กาแฟแบบบด', 'กาแฟ', 380.00, 'กาแฟบดละเอียดพร้อมชง', 5, NULL, 1, '2024-10-08 03:23:44'),
(24, 'กาแฟผงสำเร็จรูป', 'กาแฟ', 250.00, 'กาแฟสำเร็จรูปพร้อมชงสะดวก สำหรับลูกค้าที่ต้องการความเร็ว', 30, NULL, 1, '2024-10-08 03:23:44'),
(25, 'นมสดพาสเจอร์ไรส์', 'นมและครีม', 45.00, 'นมสดจากฟาร์มโคนม เหมาะสำหรับลาเต้และคาปูชิโน่', 30, NULL, 1, '2024-10-08 03:23:44'),
(26, 'นมข้นจืด', 'นมและครีม', 60.00, 'นมข้นสำหรับใช้ในกาแฟและขนมหวาน', 24, NULL, 1, '2024-10-08 03:23:44'),
(27, 'ครีมสด', 'นมและครีม', 90.00, 'ครีมสดสำหรับทำวิปครีมและกาแฟเย็น', 15, NULL, 1, '2024-10-08 03:23:44'),
(28, 'นมถั่วเหลือง', 'นมและครีม', 55.00, 'นมถั่วเหลืองออร์แกนิค สำหรับลูกค้าที่แพ้นมวัว', 20, NULL, 1, '2024-10-08 03:23:44'),
(29, 'นมอัลมอนด์', 'นมและครีม', 80.00, 'นมอัลมอนด์สุขภาพ สำหรับกาแฟสุขภาพ', 10, NULL, 1, '2024-10-08 03:23:44'),
(30, 'ไซรัปวานิลลา', 'ไซรัปและน้ำเชื่อม', 150.00, 'ไซรัปวานิลลาสำหรับเพิ่มรสชาติหวานหอม', 10, NULL, 1, '2024-10-08 03:23:44'),
(31, 'น้ำเชื่อม Maple', 'ไซรัปและน้ำเชื่อม', 450.00, 'น้ำเชื่อมเมเปิ้ลจากแคนาดา สำหรับเครื่องดื่มและขนม', 5, NULL, 1, '2024-10-08 03:23:44'),
(32, 'ไซรัปคาราเมล', 'ไซรัปและน้ำเชื่อม', 180.00, 'ไซรัปคาราเมลสำหรับกาแฟและขนมหวาน', 12, NULL, 1, '2024-10-08 03:23:44'),
(33, 'ผงโกโก้', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 300.00, 'ผงโกโก้เกรดพรีเมียมสำหรับทำเครื่องดื่มโกโก้', 5, NULL, 1, '2024-10-08 03:58:28'),
(34, 'ผงมัทฉะ', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 600.00, 'ผงชาเขียวมัทฉะเกรดพรีเมียมจากญี่ปุ่น', 3, NULL, 1, '2024-10-08 03:58:30'),
(35, 'ผงชินนามอน', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 150.00, 'ผงอบเชยสำหรับเพิ่มรสชาติในเครื่องดื่มและขนม', 10, NULL, 1, '2024-10-08 03:58:33'),
(36, 'ผงชาไทย', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 180.00, 'ผงชาไทยสำเร็จรูปสำหรับทำชาเย็นและชานม', 5, NULL, 1, '2024-10-08 03:58:37'),
(37, 'ผงวานิลลา', 'ผงเครื่องดื่มฯ', 350.00, 'ผงวานิลลาธรรมชาติสำหรับเพิ่มรสหวานในกาแฟและขนม', 10, NULL, 1, '2024-10-08 03:23:44'),
(38, 'ผงคาราเมล', 'ผงเครื่องดื่มฯ', 280.00, 'ผงคาราเมลสำหรับเครื่องดื่มและขนมหวาน', 5, NULL, 1, '2024-10-08 03:23:44'),
(39, 'ผงมอลต์', 'ผงเครื่องดื่มฯ', 250.00, 'ผงมอลต์สำหรับเพิ่มรสชาติในเครื่องดื่ม', 5, NULL, 1, '2024-10-08 03:23:44'),
(40, 'ผงคาเฟอีน', 'ผงเครื่องดื่มฯ', 400.00, 'ผงคาเฟอีนสำหรับทำเครื่องดื่มสูตรพิเศษ', 2, NULL, 1, '2024-10-08 03:23:44'),
(41, 'ช็อกโกแลตขูด', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 250.00, 'ช็อกโกแลตขูดสำหรับตกแต่งเครื่องดื่มและขนมหวาน', 10, NULL, 1, '2024-10-08 03:59:16'),
(42, 'ผงกาแฟสำเร็จรูป', 'ผงเครื่องดื่มและส่วนผสมอื่นๆ', 200.00, 'ผงกาแฟพร้อมชงสำเร็จรูปสำหรับเมนูด่วน', 30, NULL, 1, '2024-10-08 03:59:19'),
(43, 'ครัวซองต์', 'ขนมและของว่าง', 35.00, 'ครัวซองต์กรอบนอกนุ่มใน ใช้เนยแท้สำหรับกาแฟและชา', 50, NULL, 1, '2024-10-08 03:23:44'),
(44, 'คุกกี้ช็อกโกแลตชิพ', 'ขนมและของว่าง', 30.00, 'คุกกี้ช็อกโกแลตชิพหอมหวาน กรอบนุ่ม', 50, NULL, 1, '2024-10-08 03:23:44'),
(45, 'มัฟฟินบลูเบอร์รี่', 'ขนมและของว่าง', 50.00, 'มัฟฟินบลูเบอร์รี่รสชาติหอมหวาน เหมาะสำหรับเสิร์ฟคู่กาแฟ', 30, NULL, 1, '2024-10-08 03:23:44'),
(46, 'เค้กมะพร้าว', 'ขนมและของว่าง', 70.00, 'เค้กมะพร้าวหอมหวาน เนื้อเค้กนุ่มละมุน', 20, NULL, 1, '2024-10-08 03:23:44'),
(47, 'ขนมปังกรอบเนยสด', 'ขนมและของว่าง', 25.00, 'ขนมปังกรอบเคลือบเนยสำหรับเสิร์ฟคู่กับเครื่องดื่มร้อน', 100, NULL, 1, '2024-10-08 03:23:44'),
(48, 'เครื่องชงกาแฟเอสเพรสโซ', 'อุปกรณ์การชงกาแฟ', 25000.00, 'เครื่องชงกาแฟเอสเพรสโซแบบอัตโนมัติ', 5, NULL, 1, '2024-10-08 04:00:03'),
(49, 'เครื่องบดกาแฟมือ', 'อุปกรณ์การชงกาแฟ', 1200.00, 'เครื่องบดกาแฟแบบมือหมุน ขนาดพกพา', 10, NULL, 1, '2024-10-08 04:00:08'),
(50, 'ที่ตีฟองนมไฟฟ้า', 'อุปกรณ์การชงกาแฟ', 1000.00, 'อุปกรณ์ตีฟองนมไฟฟ้าสำหรับทำคาปูชิโน่และลาเต้', 10, NULL, 1, '2024-10-08 04:00:12'),
(51, 'กระดาษกรองกาแฟ', 'อุปกรณ์การชงกาแฟ', 150.00, 'กระดาษกรองกาแฟสำหรับใช้ในเครื่องดริปกาแฟ', 30, NULL, 1, '2024-10-08 04:00:16'),
(52, 'ช้อนตวงกาแฟ', 'อุปกรณ์การชงกาแฟ', 85.00, 'ช้อนตวงสแตนเลสสำหรับตวงเมล็ดกาแฟ 10 กรัม', 30, NULL, 1, '2024-10-08 04:00:18'),
(53, 'แก้วกระดาษ', 'แก้วและภาชนะบรรจุ', 120.00, 'แก้วกระดาษขนาด 12 ออนซ์ พร้อมฝาปิด', 50, NULL, 1, '2024-10-08 04:00:29'),
(54, 'แก้วพลาสติกใส', 'แก้วและภาชนะบรรจุ', 90.00, 'แก้วพลาสติกใสขนาด 16 ออนซ์ พร้อมฝาปิดแบบดัน', 50, 0x466c6f772de0b89ae0b8b1e0b899e0b897e0b8b6e0b881e0b898e0b8b8e0b8a3e0b881e0b8a3e0b8a3e0b8a12e6a7067, 1, '2024-10-11 21:37:47'),
(55, 'แก้วเซรามิค', 'แก้วและภาชนะ', 150.00, 'แก้วเซรามิคขนาด 8 ออนซ์ สำหรับเสิร์ฟเครื่องดื่มร้อน', 30, NULL, 1, '2024-10-08 03:23:44'),
(56, 'หลอดกระดาษ', 'แก้วและภาชนะ', 80.00, 'หลอดกระดาษย่อยสลายได้ สำหรับเครื่องดื่มเย็น', 100, NULL, 1, '2024-10-08 03:23:44'),
(57, 'ฟางข้าวสาลี', 'แก้วและภาชนะบรรจุ', 100.00, 'ฟางข้าวสาลีสำหรับเสิร์ฟกาแฟเย็น ลดการใช้พลาสติก', 100, NULL, 1, '2024-10-08 04:00:40'),
(58, 'น้ำตาลทราย', 'สารให้ความหวานและสารแต่งกลิ่นรส', 50.00, 'น้ำตาลทรายขาวบริสุทธิ์ สำหรับชงกาแฟ', 20, NULL, 1, '2024-10-08 03:59:37'),
(59, 'หญ้าหวาน', 'สารให้ความหวานและสารแต่งกลิ่นรส', 180.00, 'สารให้ความหวานธรรมชาติจากหญ้าหวาน ไม่มีแคลอรี', 5, NULL, 1, '2024-10-08 03:59:40'),
(60, 'ซอสคาราเมล', 'สารให้ความหวานและสารแต่งกลิ่นรส', 250.00, 'ซอสคาราเมลเพิ่มความหวานและหอมสำหรับเครื่องดื่มและขนม', 12, NULL, 1, '2024-10-08 03:59:44'),
(61, 'ซอสช็อกโกแลต', 'สารให้ความหวานและสารแต่งกลิ่นรส', 280.00, 'ซอสช็อกโกแลตเพิ่มความหวานในกาแฟและขนม', 10, NULL, 1, '2024-10-08 03:59:46'),
(62, 'น้ำตาลมะพร้าว', 'สารให้ความหวานและสารแต่งกลิ่นรส', 120.00, 'น้ำตาลจากมะพร้าว รสชาติหอมหวานธรรมชาติ', 10, NULL, 1, '2024-10-08 03:59:49'),
(63, 'น้ำตาลทรายแดง', 'สารให้ความหวานและสารแต่งกลิ่นรส', 70.00, 'น้ำตาลทรายแดงสำหรับเครื่องดื่มร้อน', 20, NULL, 1, '2024-10-08 03:59:53'),
(64, 'น้ำตาลไอซิ่ง', 'สารให้ความหวานและสารแต่งกลิ่นรส', 90.00, 'น้ำตาลไอซิ่งสำหรับตกแต่งขนมและเครื่องดื่ม', 10, NULL, 1, '2024-10-08 03:59:56'),
(65, 'อัลมอนด์อบ', 'ผลิตภัณฑ์เพิ่มมูลค่า', 350.00, 'อัลมอนด์อบกรอบ เพิ่มรสชาติในเครื่องดื่มและขนมหวาน', 10, NULL, 1, '2024-10-08 03:23:44'),
(66, 'เมล็ดเจีย', 'ผลิตภัณฑ์เพิ่มมูลค่า', 280.00, 'เมล็ดเจียออร์แกนิคสำหรับเครื่องดื่มสุขภาพ', 5, NULL, 1, '2024-10-08 03:23:44'),
(67, 'ผลไม้อบแห้ง', 'ผลิตภัณฑ์เพิ่มมูลค่า', 250.00, 'ผลไม้อบแห้งเพิ่มรสชาติในขนมและเครื่องดื่ม', 5, NULL, 1, '2024-10-08 03:23:44'),
(68, 'มะพร้าวขูดอบแห้ง', 'ผลิตภัณฑ์เพิ่มมูลค่า', 150.00, 'มะพร้าวขูดอบแห้งสำหรับเครื่องดื่มหรือขนมหวาน', 10, NULL, 1, '2024-10-08 03:23:44'),
(69, 'น้ำเชื่อมรสผลไม้', 'ผลิตภัณฑ์เพิ่มมูลค่า', 180.00, 'น้ำเชื่อมรสผลไม้สำหรับทำเครื่องดื่มและขนม', 10, NULL, 1, '2024-10-08 03:23:44');

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
(13, 18, 25, 5, '2024-11-05 11:11:58'),
(14, 21, 25, 5, '2024-11-05 10:53:48');

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
(9, 424, 'reject', 'aa', '2024-11-05 12:22:02', 0x2e2e2f2e2e2f75706c6f61642f7265736f6c7574696f6e5f696d616765732f363732396162376131633133375f36373237346563313263306433343236322e706e67),
(10, 424, 'reject', 'aa', '2024-11-05 12:26:32', NULL),
(11, 424, 'reject', 'aa', '2024-11-05 12:34:27', NULL);

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
(1, 'Central World Branch', 1, '0251234567', '2024-10-05 17:53:51'),
(13, 'Central Ladprao Branch', 16, '0825712345', '2024-10-05 18:51:47'),
(15, 'Mega Bangna Branch', 18, '0892987655', '2024-10-12 07:06:51');

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

--
-- Dumping data for table `transaction_manage`
--

INSERT INTO `transaction_manage` (`transactionm_id`, `user_id`, `transaction_type`, `created_at`) VALUES
(1, 1, 'edit_u', '2024-11-05 08:41:26'),
(2, 1, 'edit_u', '2024-11-05 08:41:32'),
(3, 1, 'edit_u', '2024-11-05 08:41:35'),
(4, 1, 'edit_s', '2024-11-05 08:45:28'),
(5, 1, 'edit_p', '2024-11-05 08:49:51'),
(6, 1, 'del_p', '2024-11-05 08:50:55');

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
(53, 'Preecha ', 'Jitjaroen', 'preecha.j@example.com', '$2y$10$D7EPaoM536hUgfOWbHfiruw6s9tFY8.5zarK0cGNESBjGMC6tVaG.', '0821112324', 'manager', 13, 0, '2024-11-05 01:41:31'),
(55, 'suthida', 'Maksee', 'suthida.m@example.com', '$2y$10$VQ/rxAsb778T05pepaaLAuAxowswP0w86NjzFRcMJGf/BC..kj8pC', '0821112345', 'staff', 13, 0, '2024-11-03 23:19:04');

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
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `damaged_products`
--
ALTER TABLE `damaged_products`
  MODIFY `deproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `detail_orders`
--
ALTER TABLE `detail_orders`
  MODIFY `detail_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `issue_orders`
--
ALTER TABLE `issue_orders`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `issue_product`
--
ALTER TABLE `issue_product`
  MODIFY `issueproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notiflyproduct`
--
ALTER TABLE `notiflyproduct`
  MODIFY `notiflyproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `notiflyreport`
--
ALTER TABLE `notiflyreport`
  MODIFY `notiflyreport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=426;

--
-- AUTO_INCREMENT for table `products_info`
--
ALTER TABLE `products_info`
  MODIFY `listproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `product_alert_settings`
--
ALTER TABLE `product_alert_settings`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `resolution_orders`
--
ALTER TABLE `resolution_orders`
  MODIFY `resolution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `resolution_product`
--
ALTER TABLE `resolution_product`
  MODIFY `resolutionproduct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `store_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transaction_manage`
--
ALTER TABLE `transaction_manage`
  MODIFY `transactionm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `withdrawreport`
--
ALTER TABLE `withdrawreport`
  MODIFY `withdraw_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
