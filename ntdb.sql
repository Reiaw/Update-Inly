-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2025 at 10:13 AM
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
-- Database: `ntdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `id_address` int(11) NOT NULL,
  `info_address` text DEFAULT NULL,
  `id_amphures` int(11) DEFAULT NULL,
  `id_tambons` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`id_address`, `info_address`, `id_amphures`, `id_tambons`) VALUES
(42, '', 7101, 710103),
(45, '1234', 7113, 711301);

-- --------------------------------------------------------

--
-- Table structure for table `amphures`
--

CREATE TABLE `amphures` (
  `id_amphures` int(11) NOT NULL,
  `name_amphures` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amphures`
--

INSERT INTO `amphures` (`id_amphures`, `name_amphures`) VALUES
(7101, 'เมืองกาญจนบุรี'),
(7102, 'ไทรโยค'),
(7103, 'บ่อพลอย'),
(7104, 'ศรีสวัสดิ์'),
(7105, 'ท่ามะกา'),
(7106, 'ท่าม่วง'),
(7107, 'ทองผาภูมิ'),
(7108, 'สังขละบุรี'),
(7109, 'พนมทวน'),
(7110, 'เลาขวัญ'),
(7111, 'ด่านมะขามเตี้ย'),
(7112, 'หนองปรือ'),
(7113, 'ห้วยกระเจา');

-- --------------------------------------------------------

--
-- Table structure for table `bill_customer`
--

CREATE TABLE `bill_customer` (
  `id_bill` int(11) NOT NULL,
  `number_bill` int(15) DEFAULT NULL,
  `type_bill` enum('CIP+','Special Bill','Nt1') DEFAULT NULL,
  `status_bill` enum('ใช้งาน','ยกเลิกใช้งาน') DEFAULT NULL,
  `id_customer` int(11) DEFAULT NULL,
  `create_at` date DEFAULT NULL,
  `update_at` date DEFAULT NULL,
  `date_count` int(11) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `contact_count` int(11) DEFAULT 0,
  `contact_status` enum('ยังไม่ได้เลือก','ต่อสัญญา','ยกเลิกสัญญา') DEFAULT 'ยังไม่ได้เลือก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_customer`
--

INSERT INTO `bill_customer` (`id_bill`, `number_bill`, `type_bill`, `status_bill`, `id_customer`, `create_at`, `update_at`, `date_count`, `end_date`, `contact_count`, `contact_status`) VALUES
(13, 214748364, 'CIP+', 'ใช้งาน', 38, '2025-01-21', '2025-01-24', 120, '2035-01-21', 1, 'ต่อสัญญา'),
(14, 101, 'Special Bill', 'ใช้งาน', 38, '2025-01-17', '2025-01-21', 0, '2025-02-07', 2, 'ต่อสัญญา'),
(15, 2147483647, 'CIP+', 'ใช้งาน', 38, '2025-01-17', '2025-01-17', 20, '2026-10-03', 3, 'ต่อสัญญา'),
(16, 555555, 'CIP+', 'ใช้งาน', 38, '2025-01-20', '2025-01-20', 200, '2025-08-19', 2, 'ต่อสัญญา'),
(17, 2147483647, 'CIP+', 'ยกเลิกใช้งาน', 40, '2025-01-20', '2025-01-20', 365, '2026-02-19', 2, 'ต่อสัญญา'),
(34, 254687, 'CIP+', 'ใช้งาน', 38, '2025-01-19', '2025-01-19', 100, '2025-01-30', 3, 'ต่อสัญญา'),
(40, 2147483647, 'CIP+', 'ยกเลิกใช้งาน', 40, '2025-01-15', '2025-01-20', 5, '2025-01-25', 3, 'ต่อสัญญา'),
(41, 2147483647, 'CIP+', 'ใช้งาน', 40, '2025-01-21', '2025-01-22', 5, '2025-08-21', 3, 'ต่อสัญญา'),
(42, 254687, 'Special Bill', 'ใช้งาน', 40, '2025-01-20', '2025-01-20', 725, '2027-01-30', 0, 'ยังไม่ได้เลือก');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id_customer` int(11) NOT NULL,
  `name_customer` varchar(100) DEFAULT NULL,
  `type_customer` enum('อบต','อบจ','เทศบาล','โรงแรม') DEFAULT NULL,
  `phone_customer` varchar(50) DEFAULT NULL,
  `status_customer` enum('ใช้งาน','ไม่ได้ใช้งาน') DEFAULT NULL,
  `id_address` int(11) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id_customer`, `name_customer`, `type_customer`, `phone_customer`, `status_customer`, `id_address`, `create_at`, `update_at`) VALUES
(38, 'ปากแพรก', 'เทศบาล', '0874652345 คุณต้น', 'ใช้งาน', 42, '2025-01-17 15:20:56', '2025-01-17 15:20:56'),
(40, 'Rtop', 'อบต', '0898081659', 'ใช้งาน', 45, '2025-01-20 12:29:04', '2025-01-20 12:29:04');

-- --------------------------------------------------------

--
-- Table structure for table `gedget`
--

CREATE TABLE `gedget` (
  `id_gedget` int(11) NOT NULL,
  `name_gedget` varchar(100) DEFAULT NULL,
  `id_bill` int(11) NOT NULL,
  `create_at` date DEFAULT NULL,
  `status_gedget` enum('ใช้งาน','ยกเลิก') DEFAULT 'ใช้งาน',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gedget`
--

INSERT INTO `gedget` (`id_gedget`, `name_gedget`, `id_bill`, `create_at`, `status_gedget`, `note`) VALUES
(17, 'ubiquibi รุ่น uapaclr', 13, NULL, 'ใช้งาน', NULL),
(18, 'syndome รุ่น 1E00YA', 13, NULL, 'ใช้งาน', NULL),
(19, 'ZTE รุ่น F620', 13, NULL, 'ใช้งาน', NULL),
(20, 'Mikrotik รุ่น rb3011', 13, NULL, 'ใช้งาน', NULL),
(21, 'ubiquibi รุ่น us-8', 13, NULL, 'ใช้งาน', NULL),
(22, 'ubiquibi รุ่น llap ac lr', 13, NULL, 'ใช้งาน', NULL),
(23, 'syndome 1000vA', 13, NULL, 'ใช้งาน', NULL),
(24, 'Mikrotik Router RB3011', 15, NULL, 'ใช้งาน', NULL),
(25, 'Access point UAP ACLLR ', 15, NULL, 'ใช้งาน', NULL),
(26, 'UPS Poe Injecter', 15, NULL, 'ใช้งาน', NULL),
(29, 'Mikrotik รุ่น rb3011', 14, '2025-01-15', 'ใช้งาน', NULL),
(31, 'asd', 13, '2025-01-15', 'ใช้งาน', NULL),
(32, 'asd', 41, '2025-01-01', 'ใช้งาน', NULL),
(36, 'ฺฺBBc ตัวที่ 1', 15, '2025-01-23', 'ใช้งาน', ''),
(37, 'ฺฺBBc ตัวที่ 2', 15, '2025-01-23', 'ใช้งาน', ''),
(38, 'A ตัวที่ 1', 15, '2025-01-23', 'ใช้งาน', ''),
(39, 'A ตัวที่ 1', 15, '2025-01-23', 'ใช้งาน', ''),
(41, 'A ตัวที่ 2', 15, '2025-01-16', 'ใช้งาน', ''),
(42, 'A ตัวที่ 3', 15, '2025-01-16', 'ใช้งาน', ''),
(45, 'ZTE รุ่น F620 ตัวที่ 1', 15, '2025-01-23', 'ยกเลิก', 'ขี้เกียจใช้');

-- --------------------------------------------------------

--
-- Table structure for table `group_service`
--

CREATE TABLE `group_service` (
  `id_group` int(11) NOT NULL,
  `id_bill` int(11) NOT NULL,
  `group_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_service`
--

INSERT INTO `group_service` (`id_group`, `id_bill`, `group_name`) VALUES
(55, 13, 'สำนักปลัด'),
(56, 13, 'ICT กองคลัง'),
(57, 13, 'ICT สำนักปลัด'),
(58, 13, 'อื่นๆ'),
(61, 14, 'A'),
(62, 14, 'B'),
(63, 13, '5'),
(64, 14, 'A'),
(65, 14, 'AXXXX'),
(66, 15, 'A'),
(67, 15, 'C');

-- --------------------------------------------------------

--
-- Table structure for table `group_servicedetail`
--

CREATE TABLE `group_servicedetail` (
  `id_group_detail` int(11) NOT NULL,
  `id_group` int(11) NOT NULL,
  `id_service` int(11) DEFAULT NULL,
  `id_gedget` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_servicedetail`
--

INSERT INTO `group_servicedetail` (`id_group_detail`, `id_group`, `id_service`, `id_gedget`) VALUES
(58, 55, 36, NULL),
(59, 55, NULL, 19),
(60, 55, NULL, 20),
(61, 55, NULL, 21),
(62, 55, NULL, 22),
(63, 55, NULL, 23),
(69, 57, 34, NULL),
(70, 57, 36, NULL),
(71, 57, 38, NULL),
(72, 58, 32, NULL),
(73, 58, 33, NULL),
(74, 56, 31, NULL),
(75, 56, 35, NULL),
(76, 56, 37, NULL),
(94, 62, NULL, 29),
(95, 61, 39, NULL),
(96, 61, NULL, 29),
(97, 63, 31, NULL),
(98, 63, NULL, 31),
(99, 64, 39, NULL),
(100, 65, 39, NULL),
(101, 65, NULL, 29),
(102, 66, 40, NULL),
(103, 67, 42, NULL),
(104, 67, NULL, 41);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id_notifications` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_bill` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id_notifications`, `id_user`, `id_bill`, `message`, `is_read`, `created_at`, `task_id`) VALUES
(52, 15, 34, 'ลูกค้า: ปากแพรก\nหมายเลขบิล: 254687', 1, '2025-01-29 14:57:11', NULL),
(53, 15, 14, 'ลูกค้า: ปากแพรก\nหมายเลขบิล: 101', 0, '2025-01-29 14:57:11', NULL),
(56, 15, NULL, 'หัวข้องาน: ตรวจงาน\nเพิ่มโดย: Chatchai', 0, '2025-01-29 14:57:11', 3),
(61, 15, NULL, 'หัวข้องาน: ฺCG\nเพิ่มโดย: Chatchai', 0, '2025-01-29 16:00:30', 4),
(62, 16, NULL, 'หัวข้องาน: ฺCG\nเพิ่มโดย: John Doe', 0, '2025-01-29 16:00:30', 4),
(63, 15, NULL, 'หัวข้องาน: A\nเพิ่มโดย: Chatchai', 0, '2025-01-29 16:00:38', 2),
(64, 15, NULL, 'หัวข้องาน: A\nเพิ่มโดย: Chatchai', 0, '2025-01-29 16:00:38', 2);

-- --------------------------------------------------------

--
-- Table structure for table `overide`
--

CREATE TABLE `overide` (
  `id_overide` int(11) NOT NULL,
  `mainpackage_price` float DEFAULT NULL,
  `ict_price` float DEFAULT NULL,
  `all_price` float DEFAULT NULL,
  `info_overide` text DEFAULT NULL,
  `id_product` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overide`
--

INSERT INTO `overide` (`id_overide`, `mainpackage_price`, `ict_price`, `all_price`, `info_overide`, `id_product`) VALUES
(27, 100, 100, 200, NULL, 34),
(28, 200, 100, 300, NULL, 35),
(29, 105, 200, 300, NULL, 36),
(30, 100, 100, 200, NULL, 37),
(31, 100, 100, 200, NULL, 38),
(33, 500, 100, 600, NULL, 40);

-- --------------------------------------------------------

--
-- Table structure for table `package_list`
--

CREATE TABLE `package_list` (
  `id_package` int(11) NOT NULL,
  `name_package` varchar(255) DEFAULT NULL,
  `info_package` text DEFAULT NULL,
  `id_service` int(11) DEFAULT NULL,
  `create_at` date DEFAULT NULL,
  `update_at` date DEFAULT NULL,
  `status_package` enum('ใช้งาน','ยกเลิก') DEFAULT 'ใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_list`
--

INSERT INTO `package_list` (`id_package`, `name_package`, `info_package`, `id_service`, `create_at`, `update_at`, `status_package`) VALUES
(15, ' Smart Help Call Center', 'AB', 39, '0000-00-00', '2025-01-21', 'ยกเลิก'),
(18, ' Smart Help Call Center', '-', 39, '2025-01-16', '2025-01-21', 'ใช้งาน'),
(19, 'A', '', 39, '2025-01-09', '2025-01-21', 'ยกเลิก'),
(20, 'AA', '', 39, '2025-01-15', '2025-01-21', 'ใช้งาน'),
(24, ' Smart Help Call Center', '', 39, '2025-01-16', '2025-01-21', 'ใช้งาน'),
(26, 'CC', '', 57, '2025-01-16', '2025-01-24', 'ใช้งาน');

-- --------------------------------------------------------

--
-- Table structure for table `product_list`
--

CREATE TABLE `product_list` (
  `id_product` int(11) NOT NULL,
  `name_product` varchar(255) DEFAULT NULL,
  `info_product` text DEFAULT NULL,
  `id_package` int(11) DEFAULT NULL,
  `create_at` date DEFAULT NULL,
  `update_at` date DEFAULT NULL,
  `status_product` enum('ใช้งาน','ยกเลิก') DEFAULT 'ใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_list`
--

INSERT INTO `product_list` (`id_product`, `name_product`, `info_product`, `id_package`, `create_at`, `update_at`, `status_product`) VALUES
(34, 'AA', '', 18, '2025-01-16', '2025-01-21', 'ใช้งาน'),
(35, 'AA', '', 19, '2025-01-09', '2025-01-21', 'ยกเลิก'),
(36, 'AA', '', 20, '2025-01-15', '2025-01-21', 'ใช้งาน'),
(37, '0', '', 15, '0000-00-00', '2025-01-21', 'ใช้งาน'),
(38, '100', '', 24, '2025-01-16', '2025-01-21', 'ใช้งาน'),
(40, 'ค่าบริการ Smart Help Call Center', 'A', 26, '2025-01-16', '2025-01-24', 'ใช้งาน');

-- --------------------------------------------------------

--
-- Table structure for table `service_customer`
--

CREATE TABLE `service_customer` (
  `id_service` int(11) NOT NULL,
  `code_service` varchar(20) DEFAULT NULL,
  `type_service` enum('Fttx','Fttx+ICT solution','Fttx 2+ICT solution','SI service','วงจเช่า','IP phone','Smart City','WiFi','อื่นๆ') DEFAULT NULL,
  `type_gadget` enum('เช่า','ขาย','เช่าและขาย') DEFAULT NULL,
  `status_service` enum('ใช้งาน','ยกเลิก') DEFAULT NULL,
  `id_bill` int(11) DEFAULT NULL,
  `create_at` date DEFAULT NULL,
  `update_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_customer`
--

INSERT INTO `service_customer` (`id_service`, `code_service`, `type_service`, `type_gadget`, `status_service`, `id_bill`, `create_at`, `update_at`) VALUES
(31, '034510824a', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(32, '034510825', 'Fttx+ICT solution', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(33, '034510990', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(34, '034510991', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(35, '3451J0998', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(36, '3451J1157', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(37, 'C010003095', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(38, 'C020003096', 'Fttx', 'เช่า', 'ใช้งาน', 13, '2025-01-17', '2025-01-17'),
(39, '6711002934a', 'Smart City', 'ขาย', 'ใช้งาน', 14, '2025-01-17', '2025-01-21'),
(40, '1235', 'วงจเช่า', 'เช่า', 'ใช้งาน', 15, '2025-01-17', '2025-01-23'),
(42, '034510533', 'Fttx', 'เช่าและขาย', 'ยกเลิก', 15, '2025-01-17', '2025-01-21'),
(52, '123', 'Smart City', 'ขาย', 'ยกเลิก', 13, '2025-01-20', '2025-01-20'),
(53, '3451L0114', 'Fttx+ICT solution', 'ขาย', 'ยกเลิก', 16, '2025-01-20', '2025-01-20'),
(54, '1235', 'WiFi', 'ขาย', 'ยกเลิก', 16, '2025-01-20', '2025-01-20'),
(55, '1235', 'Fttx+ICT solution', 'เช่าและขาย', 'ใช้งาน', 15, '2025-01-23', '2025-01-23'),
(57, 'AA', 'Fttx', 'เช่า', 'ใช้งาน', 41, '2025-01-24', '2025-01-24');

-- --------------------------------------------------------

--
-- Table structure for table `tambons`
--

CREATE TABLE `tambons` (
  `id_tambons` int(11) NOT NULL,
  `zip_code` int(11) NOT NULL,
  `name_tambons` varchar(150) NOT NULL,
  `id_amphures` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tambons`
--

INSERT INTO `tambons` (`id_tambons`, `zip_code`, `name_tambons`, `id_amphures`) VALUES
(710101, 71000, 'บ้านเหนือ', 7101),
(710102, 71000, 'บ้านใต้', 7101),
(710103, 71000, 'ปากแพรก', 7101),
(710104, 71000, 'ท่ามะขาม', 7101),
(710105, 71000, 'แก่งเสี้ยน', 7101),
(710106, 71190, 'หนองบัว', 7101),
(710107, 71190, 'ลาดหญ้า', 7101),
(710108, 71190, 'วังด้ง', 7101),
(710109, 71190, 'ช่องสะเดา', 7101),
(710110, 71000, 'หนองหญ้า', 7101),
(710111, 71000, 'เกาะสำโรง', 7101),
(710113, 71000, 'บ้านเก่า', 7101),
(710116, 71000, 'วังเย็น', 7101),
(710201, 71150, 'ลุ่มสุ่ม', 7102),
(710202, 71150, 'ท่าเสา', 7102),
(710203, 71150, 'สิงห์', 7102),
(710204, 71150, 'ไทรโยค', 7102),
(710205, 71150, 'วังกระแจะ', 7102),
(710206, 71150, 'ศรีมงคล', 7102),
(710207, 71150, 'บ้องตี้', 7102),
(710301, 71160, 'บ่อพลอย', 7103),
(710302, 71160, 'หนองกุ่ม', 7103),
(710303, 71220, 'หนองรี', 7103),
(710305, 71160, 'หลุมรัง', 7103),
(710308, 71160, 'ช่องด่าน', 7103),
(710309, 71220, 'หนองกร่าง', 7103),
(710401, 71250, 'นาสวน', 7104),
(710402, 71250, 'ด่านแม่แฉลบ', 7104),
(710403, 71250, 'หนองเป็ด', 7104),
(710404, 71250, 'ท่ากระดาน', 7104),
(710405, 71220, 'เขาโจด', 7104),
(710406, 71250, 'แม่กระบุง', 7104),
(710501, 71120, 'พงตึก', 7105),
(710502, 71120, 'ยางม่วง', 7105),
(710503, 71130, 'ดอนชะเอม', 7105),
(710504, 71120, 'ท่าไม้', 7105),
(710505, 71130, 'ตะคร้ำเอน', 7105),
(710506, 71120, 'ท่ามะกา', 7105),
(710507, 71130, 'ท่าเรือ', 7105),
(710508, 71120, 'โคกตะบอง', 7105),
(710509, 71120, 'ดอนขมิ้น', 7105),
(710510, 71130, 'อุโลกสี่หมื่น', 7105),
(710511, 71120, 'เขาสามสิบหาบ', 7105),
(710512, 71130, 'พระแท่น', 7105),
(710513, 71120, 'หวายเหนียว', 7105),
(710514, 71130, 'แสนตอ', 7105),
(710515, 70190, 'สนามแย้', 7105),
(710516, 71120, 'ท่าเสา', 7105),
(710517, 71130, 'หนองลาน', 7105),
(710601, 71110, 'ท่าม่วง', 7106),
(710602, 71110, 'วังขนาย', 7106),
(710603, 71110, 'วังศาลา', 7106),
(710604, 71000, 'ท่าล้อ', 7106),
(710605, 71110, 'หนองขาว', 7106),
(710606, 71110, 'ทุ่งทอง', 7106),
(710607, 71110, 'เขาน้อย', 7106),
(710608, 71110, 'ม่วงชุม', 7106),
(710609, 71110, 'บ้านใหม่', 7106),
(710610, 71110, 'พังตรุ', 7106),
(710611, 71130, 'ท่าตะคร้อ', 7106),
(710612, 71110, 'รางสาลี่', 7106),
(710613, 71110, 'หนองตากยา', 7106),
(710701, 71180, 'ท่าขนุน', 7107),
(710702, 71180, 'ปิล๊อก', 7107),
(710703, 71180, 'หินดาด', 7107),
(710704, 71180, 'ลิ่นถิ่น', 7107),
(710705, 71180, 'ชะแล', 7107),
(710706, 71180, 'ห้วยเขย่ง', 7107),
(710707, 71180, 'สหกรณ์นิคม', 7107),
(710801, 71240, 'หนองลู', 7108),
(710802, 71240, 'ปรังเผล', 7108),
(710803, 71240, 'ไล่โว่', 7108),
(710901, 71140, 'พนมทวน', 7109),
(710902, 71140, 'หนองโรง', 7109),
(710903, 71140, 'ทุ่งสมอ', 7109),
(710904, 71140, 'ดอนเจดีย์', 7109),
(710905, 71140, 'พังตรุ', 7109),
(710906, 71170, 'รางหวาย', 7109),
(710911, 71140, 'หนองสาหร่าย', 7109),
(710912, 71140, 'ดอนตาเพชร', 7109),
(711001, 71210, 'เลาขวัญ', 7110),
(711002, 71210, 'หนองโสน', 7110),
(711003, 71210, 'หนองประดู่', 7110),
(711004, 71210, 'หนองปลิง', 7110),
(711005, 71210, 'หนองนกแก้ว', 7110),
(711006, 71210, 'ทุ่งกระบ่ำ', 7110),
(711007, 71210, 'หนองฝ้าย', 7110),
(711101, 71260, 'ด่านมะขามเตี้ย', 7111),
(711102, 71260, 'กลอนโด', 7111),
(711103, 71260, 'จรเข้เผือก', 7111),
(711104, 71260, 'หนองไผ่', 7111),
(711201, 71220, 'หนองปรือ', 7112),
(711202, 71220, 'หนองปลาไหล', 7112),
(711203, 71220, 'สมเด็จเจริญ', 7112),
(711301, 71170, 'ห้วยกระเจา', 7113),
(711302, 71170, 'วังไผ่', 7113),
(711303, 71170, 'ดอนแสลบ', 7113),
(711304, 71170, 'สระลงเรือ', 7113);

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `id_task` int(11) NOT NULL,
  `name_task` varchar(255) NOT NULL,
  `detail_task` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `reminder_date` int(11) DEFAULT NULL,
  `created_at` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`id_task`, `name_task`, `detail_task`, `start_date`, `end_date`, `user_id`, `reminder_date`, `created_at`) VALUES
(2, 'A', '', '2025-01-30', '2025-01-31', 15, 1, '2025-01-29'),
(3, 'ตรวจงาน', 'ออกนอกสภานที่', '2025-02-21', '2025-02-22', 15, 23, '2025-01-29'),
(4, 'ฺCG', 'สักอย่าง', '2025-01-31', '2025-02-08', 15, 2, '2025-01-29');

-- --------------------------------------------------------

--
-- Table structure for table `task_group`
--

CREATE TABLE `task_group` (
  `id_task_group` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_group`
--

INSERT INTO `task_group` (`id_task_group`, `task_id`, `user_id`) VALUES
(1, 2, 15),
(2, 2, 15),
(3, 3, 15),
(4, 4, 15),
(5, 4, 16);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verify` tinyint(1) DEFAULT 0,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `last_otp_sent` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `password`, `verify`, `otp`, `otp_expiry`, `otp_attempts`, `last_otp_sent`) VALUES
(15, 'rattapoom.p@ku.th', 'Chatchai', '$2y$10$JRRG/Tl3jBYrIyvhSH.KJecyGXNhgW3W8xkxT8dIN2fJ7O1bNxgTe', 1, NULL, NULL, 0, '2025-01-10 14:52:09'),
(16, 'A@example.com', 'John Doe', '$2y$10$JRRG/Tl3jBYrIyvhSH.KJecyGXNhgW3W8xkxT8dIN2fJ7O1bNxgTe', 1, NULL, NULL, 0, '2025-01-10 14:52:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`id_address`),
  ADD KEY `id_amphures` (`id_amphures`),
  ADD KEY `id_tambons` (`id_tambons`);

--
-- Indexes for table `amphures`
--
ALTER TABLE `amphures`
  ADD PRIMARY KEY (`id_amphures`);

--
-- Indexes for table `bill_customer`
--
ALTER TABLE `bill_customer`
  ADD PRIMARY KEY (`id_bill`),
  ADD KEY `id_customer` (`id_customer`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id_customer`),
  ADD KEY `id_address` (`id_address`);

--
-- Indexes for table `gedget`
--
ALTER TABLE `gedget`
  ADD PRIMARY KEY (`id_gedget`),
  ADD KEY `fk_gedget_bill_customer` (`id_bill`);

--
-- Indexes for table `group_service`
--
ALTER TABLE `group_service`
  ADD PRIMARY KEY (`id_group`),
  ADD KEY `fk_group_bill` (`id_bill`);

--
-- Indexes for table `group_servicedetail`
--
ALTER TABLE `group_servicedetail`
  ADD PRIMARY KEY (`id_group_detail`),
  ADD KEY `id_group` (`id_group`),
  ADD KEY `id_service` (`id_service`),
  ADD KEY `id_gedget` (`id_gedget`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notifications`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_bill` (`id_bill`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `overide`
--
ALTER TABLE `overide`
  ADD PRIMARY KEY (`id_overide`),
  ADD KEY `id_product` (`id_product`);

--
-- Indexes for table `package_list`
--
ALTER TABLE `package_list`
  ADD PRIMARY KEY (`id_package`),
  ADD KEY `id_service` (`id_service`);

--
-- Indexes for table `product_list`
--
ALTER TABLE `product_list`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `id_package` (`id_package`);

--
-- Indexes for table `service_customer`
--
ALTER TABLE `service_customer`
  ADD PRIMARY KEY (`id_service`),
  ADD KEY `id_bill` (`id_bill`);

--
-- Indexes for table `tambons`
--
ALTER TABLE `tambons`
  ADD PRIMARY KEY (`id_tambons`),
  ADD KEY `id_amphures` (`id_amphures`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id_task`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_group`
--
ALTER TABLE `task_group`
  ADD PRIMARY KEY (`id_task_group`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `id_address` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `bill_customer`
--
ALTER TABLE `bill_customer`
  MODIFY `id_bill` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id_customer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `gedget`
--
ALTER TABLE `gedget`
  MODIFY `id_gedget` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `group_service`
--
ALTER TABLE `group_service`
  MODIFY `id_group` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `group_servicedetail`
--
ALTER TABLE `group_servicedetail`
  MODIFY `id_group_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notifications` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `overide`
--
ALTER TABLE `overide`
  MODIFY `id_overide` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `package_list`
--
ALTER TABLE `package_list`
  MODIFY `id_package` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `product_list`
--
ALTER TABLE `product_list`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `service_customer`
--
ALTER TABLE `service_customer`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `id_task` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_group`
--
ALTER TABLE `task_group`
  MODIFY `id_task_group` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`id_amphures`) REFERENCES `amphures` (`id_amphures`),
  ADD CONSTRAINT `address_ibfk_2` FOREIGN KEY (`id_tambons`) REFERENCES `tambons` (`id_tambons`);

--
-- Constraints for table `bill_customer`
--
ALTER TABLE `bill_customer`
  ADD CONSTRAINT `bill_customer_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id_customer`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`id_address`) REFERENCES `address` (`id_address`);

--
-- Constraints for table `gedget`
--
ALTER TABLE `gedget`
  ADD CONSTRAINT `fk_gedget_bill_customer` FOREIGN KEY (`id_bill`) REFERENCES `bill_customer` (`id_bill`);

--
-- Constraints for table `group_service`
--
ALTER TABLE `group_service`
  ADD CONSTRAINT `fk_group_bill` FOREIGN KEY (`id_bill`) REFERENCES `bill_customer` (`id_bill`) ON DELETE CASCADE;

--
-- Constraints for table `group_servicedetail`
--
ALTER TABLE `group_servicedetail`
  ADD CONSTRAINT `group_servicedetail_ibfk_1` FOREIGN KEY (`id_group`) REFERENCES `group_service` (`id_group`),
  ADD CONSTRAINT `group_servicedetail_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `service_customer` (`id_service`),
  ADD CONSTRAINT `group_servicedetail_ibfk_3` FOREIGN KEY (`id_gedget`) REFERENCES `gedget` (`id_gedget`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`id_bill`) REFERENCES `bill_customer` (`id_bill`),
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `task` (`id_task`);

--
-- Constraints for table `overide`
--
ALTER TABLE `overide`
  ADD CONSTRAINT `overide_ibfk_1` FOREIGN KEY (`id_product`) REFERENCES `product_list` (`id_product`);

--
-- Constraints for table `package_list`
--
ALTER TABLE `package_list`
  ADD CONSTRAINT `package_list_ibfk_1` FOREIGN KEY (`id_service`) REFERENCES `service_customer` (`id_service`);

--
-- Constraints for table `product_list`
--
ALTER TABLE `product_list`
  ADD CONSTRAINT `product_list_ibfk_1` FOREIGN KEY (`id_package`) REFERENCES `package_list` (`id_package`);

--
-- Constraints for table `service_customer`
--
ALTER TABLE `service_customer`
  ADD CONSTRAINT `service_customer_ibfk_1` FOREIGN KEY (`id_bill`) REFERENCES `bill_customer` (`id_bill`);

--
-- Constraints for table `tambons`
--
ALTER TABLE `tambons`
  ADD CONSTRAINT `tambons_ibfk_1` FOREIGN KEY (`id_amphures`) REFERENCES `amphures` (`id_amphures`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_group`
--
ALTER TABLE `task_group`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_group_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id_task`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
