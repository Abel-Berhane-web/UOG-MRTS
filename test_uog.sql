-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 31, 2025 at 06:52 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_uog`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `type`, `is_read`, `created_at`) VALUES
(1, 7, 'Payment proof uploaded for request \'kxjwcnlucrwe\' by hana sisay. Verify it.', 'finance_payment_verification.php', 'info', 1, '2025-08-21 10:03:52'),
(2, 7, 'Request \'cftvsvsv\' submitted by hana sisay requires price setting.', 'dashboard/finance_pending_price.php', 'info', 1, '2025-08-21 10:16:08'),
(3, 7, 'Request \'cwevbtrynumt\' submitted by hana sisay requires price setting.', 'finance_pending_price.php', 'info', 1, '2025-08-21 10:21:16'),
(4, 1, 'New user \'Ethun admas\' (ethu) registered as staff. Approve or reject.', 'admin_approve_users.php', 'warning', 1, '2025-08-21 10:57:10'),
(5, 15, 'New user \'Semret Berhane\' (samri) registered as technician. Approve or reject.', 'admin_approve_users.php', 'warning', 1, '2025-08-21 11:14:53'),
(6, 7, 'Payment proof uploaded for request \'kxjwcnlucrwe\' by . Verify it.', 'finance_payment_verification.php?id=57', 'info', 1, '2025-08-21 11:56:17'),
(7, 2, 'You have been assigned to request \'\' in Maraki, Room 12.', 'tech_view_detail.php?id=60', 'info', 1, '2025-08-21 11:59:09'),
(8, 23, 'Your maintenance request \'fcghbjnkml,;xewc\' has been completed.', 'view_requests.php?id=60', 'success', 1, '2025-08-21 12:14:52'),
(9, 6, 'Your maintenance request \'abebe\' has been completed.', 'view_requests.php', 'success', 1, '2025-08-23 15:57:52'),
(10, 7, 'Request \'vidoe\' submitted by hana sisay requires price setting.', 'finance_pending_price.php?id=61', 'info', 1, '2025-08-25 10:14:27'),
(11, 7, 'Request \'css\' submitted by Nardos Teshager requires price setting.', 'finance_pending_price.php?id=62', 'info', 1, '2025-08-27 20:29:13'),
(12, 7, 'Request \'css22\' submitted by Nardos Teshager requires price setting.', 'finance_pending_price.php?id=63', 'info', 1, '2025-08-27 20:53:36'),
(13, 26, 'Your maintenance request \'css\' has been completed.', 'view_requests.php', 'success', 1, '2025-08-27 21:07:46'),
(14, 7, 'Request \'today\' submitted by Nardos Teshager requires price setting.', 'finance_pending_price.php?id=64', 'info', 1, '2025-08-28 08:19:02'),
(15, 7, 'Payment proof uploaded for request \'today\' by . Verify it.', 'finance_payment_verification.php?id=64', 'info', 1, '2025-08-28 08:21:37'),
(16, 18, 'You have been assigned to request \'\' in Maraki, Room 12.', 'tech_view_detail.php?id=65', 'info', 0, '2025-08-28 09:19:43'),
(17, 7, 'Request \'Mobile try\' submitted by Nardos Teshager requires price setting.', 'finance_pending_price.php?id=66', 'info', 1, '2025-08-28 10:03:06'),
(18, 7, 'Request \'\' submitted by Nardos Teshager requires price setting.', 'finance_pending_price.php?id=67', 'info', 1, '2025-08-28 10:04:31'),
(19, 7, 'Payment proof uploaded for request \'kxjwcnlucrwe\' by . Verify it.', 'finance_payment_verification.php?id=57', 'info', 1, '2025-08-28 10:59:16'),
(20, 6, 'Payment verified for your request \'kxjwcnlucrwe\'. Technician assignment will follow.', 'view_requests.php?id=57', 'success', 1, '2025-08-28 10:59:44'),
(21, 29, 'You have been assigned to request \'\' in Fasil, Room 2.', 'tech_view_detail.php?id=68', 'info', 0, '2025-08-29 09:03:14'),
(22, 6, 'Payment verified for your request \'kxjwcnlucrwe\'. Technician assignment will follow.', 'view_requests.php?id=57', 'success', 1, '2025-08-29 13:03:29'),
(23, 7, 'Request \'upload video\' submitted by hana sisay requires price setting.', 'finance_pending_price.php?id=69', 'info', 0, '2025-08-31 12:21:56');

-- --------------------------------------------------------

--
-- Table structure for table `paymentproof`
--

DROP TABLE IF EXISTS `paymentproof`;
CREATE TABLE IF NOT EXISTS `paymentproof` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `payment_instructions` varchar(255) DEFAULT NULL,
  `payment_code` varchar(100) DEFAULT NULL,
  `payment_screenshot_path` varchar(255) DEFAULT NULL,
  `verified_status` enum('Pending','Waiting Payment','Pending Payment Verification','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tx_ref` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `paymentproof`
--

INSERT INTO `paymentproof` (`id`, `request_id`, `price`, `payment_instructions`, `payment_code`, `payment_screenshot_path`, `verified_status`, `created_at`, `tx_ref`) VALUES
(10, 57, 2000.00, 'cbe 1234567890', 'Fhvxhjnxxh', 'uploads/payments/68b03680e2917.jpg', 'Verified', '2025-08-28 10:59:12', NULL),
(9, 56, 2000.00, 'cbe 1234567890', NULL, NULL, 'Waiting Payment', '2025-08-21 11:44:37', NULL),
(7, 51, 500.00, 'cbe 10000241490061', 'zsxdfcgbhnj', 'uploads/payments/68a379112019a.png', 'Verified', '2025-08-18 19:03:45', NULL),
(6, 47, 2000.00, 'swdferbg', '', '', 'Verified', '2025-08-14 18:46:54', NULL),
(8, 55, 2000.00, 'cbe 1000241490061', 'zsxdfcgbhnj', 'uploads/payments/68a5930f4e4f2.jpg', 'Verified', '2025-08-20 09:19:11', NULL),
(11, 58, 2000.00, 'cbe 1234567890', NULL, NULL, 'Verified', '2025-08-21 11:54:02', NULL),
(12, 59, 100.00, 'cgfbny', NULL, NULL, 'Verified', '2025-08-21 11:56:44', 'tx_68b4176b5dd5f'),
(13, 61, 2000.00, 'cbe dlkmwnfhrbe', NULL, NULL, 'Verified', '2025-08-25 10:15:05', 'tx_68b427fcc200a'),
(14, 62, 3000.00, 'cbe: 1000241490061', NULL, NULL, 'Verified', '2025-08-27 20:52:05', NULL),
(15, 63, 2000.00, 'cbe: 1234567890', NULL, NULL, 'Verified', '2025-08-27 20:54:04', NULL),
(16, 64, 3000.00, 'cbe 1234567890', 'sadfghgj', 'uploads/payments/68b0118d8af78.png', 'Verified', '2025-08-28 08:21:33', NULL),
(17, 66, 2059.00, 'cbe 1234567890', NULL, NULL, 'Verified', '2025-08-28 10:09:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

DROP TABLE IF EXISTS `requests`;
CREATE TABLE IF NOT EXISTS `requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `requested_by` int DEFAULT NULL,
  `issue_title` varchar(255) DEFAULT NULL,
  `issue_description` text,
  `category` varchar(100) DEFAULT NULL,
  `campus` varchar(100) DEFAULT NULL,
  `building_number` varchar(50) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `assigned_technician_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_type` enum('staff','external') DEFAULT 'staff',
  `status` enum('Pending Assignment','Assigned','In Progress','Completed') NOT NULL DEFAULT 'Pending Assignment',
  `price_status` enum('Not Set','Pending Payment','Paid','Verified') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `requested_by`, `issue_title`, `issue_description`, `category`, `campus`, `building_number`, `room_number`, `image_path`, `audio_path`, `video_path`, `assigned_technician_id`, `created_at`, `user_type`, `status`, `price_status`) VALUES
(68, 16, 'Video trial', 'Dhsksbcsjjshs dudjhsvsb. Dmsigscsbaksv. Skgcscscs', 'Electronics', 'Fasil', 'F5', '2', NULL, NULL, NULL, 29, '2025-08-29 09:03:10', 'staff', 'Assigned', NULL),
(66, 26, 'Mobile try', 'Gajsbjxkdgsccsjdbcsxsb\r\nGsudvhdsjbsctsh', 'Electrical', 'Maraki', 'M14', '22', 'uploads/images/68b029574646f.jpg', NULL, NULL, 8, '2025-08-28 10:03:03', 'external', 'Assigned', 'Pending Payment'),
(4, 4, 'dfgv5bhnh', 'sdfgbhtnbtrnyujtymk5l,ikmyjhnbgvcvebrhnjtkml,mknbhfcdxdgfbhnmkmkjnhbgvf5c', 'Networking', 'Hospital', 'h25', '33', NULL, NULL, NULL, 4, '2025-08-08 07:45:37', 'staff', 'Completed', NULL),
(33, 10, 'phone screen', 'broken screen ', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 18:58:20', 'staff', 'Assigned', NULL),
(34, 10, 'phone screen', 'broken screen ', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 19:04:02', 'staff', 'Assigned', NULL),
(32, 10, 'phone screen', 'broken screen', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 18:40:51', 'staff', 'Assigned', NULL),
(31, 10, 'phone screen', 'my phone screen got broken and it not turnnig on', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 18:38:13', 'staff', 'Assigned', NULL),
(30, 3, 'seven', 'sdvbnt', 'Electrical', 'Teda', 't35', '19', NULL, NULL, NULL, 8, '2025-08-08 18:36:00', 'staff', 'Assigned', NULL),
(64, 26, 'today', 'exrdtcfybuhnjkml', 'Electronics', 'Maraki', 'M22', '34', NULL, NULL, NULL, 17, '2025-08-28 08:18:57', 'external', 'In Progress', 'Verified'),
(28, 8, 'five', 'sdvdbnm', 'Electrical', 'Tedy', 't23', '6', NULL, NULL, NULL, 8, '2025-08-08 18:33:55', 'staff', 'Assigned', NULL),
(27, 8, 'four', 'xdfcghbjkml', 'Electrical', 'Teda', 't22', '61', NULL, NULL, NULL, 8, '2025-08-08 18:33:31', 'staff', 'Assigned', NULL),
(26, 8, 'three', 'sadsvdgfbn', 'Electrical', 'Hospital', 'h22', '61', NULL, NULL, NULL, 8, '2025-08-08 18:32:53', 'staff', 'Assigned', NULL),
(23, 6, 'abebe', 'no abebe', 'Networking', 'Teda', 'T08', '13', NULL, NULL, NULL, 4, '2025-08-08 18:21:20', 'external', 'Completed', NULL),
(65, 16, 'staff', 'asfdgchbjnk', 'Networking', 'Maraki', 'm45', '12', NULL, NULL, NULL, 18, '2025-08-28 09:19:40', 'staff', 'Assigned', NULL),
(35, 10, 'phone screen', 'broken screen ', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 19:10:42', 'staff', 'Assigned', NULL),
(36, 10, 'phone screen', 'broken screen ', 'Electronics', 'Maraki', 'm11', '24', NULL, NULL, NULL, 10, '2025-08-09 19:11:10', 'staff', 'Assigned', NULL),
(37, 9, 'phone screen', 'broken screen', 'Electronics', 'Teda', 't11', '24', NULL, NULL, NULL, 10, '2025-08-09 20:27:48', 'staff', 'Assigned', NULL),
(61, 6, 'vidoe', 'geleta', 'Networking', 'Maraki', 'm4', '23', 'uploads/images/68ac377f1bf00.png', NULL, NULL, 2, '2025-08-25 10:14:23', 'external', 'In Progress', 'Pending Payment'),
(62, 26, 'css', 'cwevw', 'Networking', 'Maraki', 'm10', '22', NULL, NULL, NULL, 2, '2025-08-27 20:29:08', 'external', 'Completed', 'Pending Payment'),
(47, 6, 'hana', 'xdcfhbjkl', 'Electrical', 'Maraki', 'm15', '32', NULL, NULL, NULL, NULL, '2025-08-14 19:21:11', 'external', 'Assigned', 'Pending Payment'),
(69, 6, 'upload video', 'upload video from the user', 'Electrical', 'Tedy', 't6', '33', NULL, NULL, NULL, NULL, '2025-08-31 12:21:52', 'external', 'Pending Assignment', 'Not Set'),
(57, 6, 'kxjwcnlucrwe', 'vtebrynut', 'Electronics', 'Maraki', 'm22', '13', NULL, NULL, NULL, NULL, '2025-08-21 10:03:48', 'external', 'Pending Assignment', 'Verified'),
(63, 26, 'css22', 'acsvbgd', 'Electronics', 'Maraki', 'm12', '23', NULL, NULL, NULL, NULL, '2025-08-27 20:53:31', 'external', 'Pending Assignment', 'Pending Payment'),
(58, 6, 'cftvsvsv', 'vdfgdvcscd d eafce rvaer', 'Networking', 'Teda', 't12', '23', NULL, NULL, NULL, NULL, '2025-08-21 10:16:04', 'external', 'Pending Assignment', 'Pending Payment'),
(59, 6, 'cwevbtrynumt', 'vsgdbhfngm vsbtnru', 'Networking', 'Fasil', 'f14', '33', NULL, NULL, NULL, NULL, '2025-08-21 10:21:13', 'external', 'Pending Assignment', 'Pending Payment'),
(60, 23, 'fcghbjnkml,;xewc', 'xdfcgtbhynjukm', 'Networking', 'Maraki', 't11', '12', NULL, NULL, NULL, 2, '2025-08-21 11:59:07', 'staff', 'Completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `systemlogs`
--

DROP TABLE IF EXISTS `systemlogs`;
CREATE TABLE IF NOT EXISTS `systemlogs` (
  `logId` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`logId`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `systemlogs`
--

INSERT INTO `systemlogs` (`logId`, `userId`, `action`, `ip_address`, `timestamp`) VALUES
(1, 15, 'User logged in', '::1', '2025-08-15 17:30:19'),
(3, 5, 'User logged in', '::1', '2025-08-15 17:37:29'),
(4, 2, 'User logged in', '::1', '2025-08-15 17:55:16'),
(5, 3, 'User logged in', '::1', '2025-08-15 17:57:04'),
(6, 3, 'User logged in', '::1', '2025-08-15 18:19:24'),
(7, 4, 'User logged in', '::1', '2025-08-15 18:19:45'),
(8, 6, 'User logged in', '::1', '2025-08-16 14:10:49'),
(9, 4, 'User logged in', '::1', '2025-08-16 14:27:44'),
(10, 6, 'User logged in', '::1', '2025-08-18 07:17:25'),
(11, 7, 'User logged in', '::1', '2025-08-18 07:25:02'),
(12, 7, 'User logged in', '::1', '2025-08-18 07:53:30'),
(13, 6, 'User logged in', '::1', '2025-08-18 08:03:49'),
(14, 6, 'User logged in', '::1', '2025-08-18 08:19:58'),
(15, 5, 'User logged in', '::1', '2025-08-18 10:16:53'),
(16, 6, 'User logged in', '::1', '2025-08-18 10:26:00'),
(17, 6, 'User logged in', '::1', '2025-08-18 14:22:00'),
(18, 6, 'User logged in', '::1', '2025-08-18 14:25:48'),
(19, 7, 'User logged in', '::1', '2025-08-18 15:09:27'),
(20, 6, 'User logged in', '::1', '2025-08-18 15:27:06'),
(21, 6, 'User logged in', '::1', '2025-08-18 15:54:14'),
(22, 7, 'User logged in', '::1', '2025-08-18 16:13:06'),
(23, 5, 'User logged in', '::1', '2025-08-19 07:40:52'),
(24, 6, 'User logged in', '::1', '2025-08-19 09:34:02'),
(25, 15, 'User logged in', '::1', '2025-08-19 10:00:01'),
(26, 6, 'User logged in', '::1', '2025-08-19 10:04:11'),
(27, 6, 'User logged in', '::1', '2025-08-20 05:44:43'),
(28, 5, 'User logged in', '::1', '2025-08-20 05:48:12'),
(29, 4, 'User logged in', '::1', '2025-08-20 05:48:46'),
(30, 15, 'User logged in', '::1', '2025-08-20 05:53:30'),
(31, 26, 'User logged in', '::1', '2025-08-20 05:58:22'),
(32, 7, 'User logged in', '::1', '2025-08-20 06:01:07'),
(33, 26, 'User logged in', '::1', '2025-08-20 06:02:11'),
(34, 5, 'User logged in', '::1', '2025-08-20 06:03:25'),
(35, 2, 'User logged in', '::1', '2025-08-20 06:05:31'),
(36, 15, 'User logged in', '::1', '2025-08-20 06:09:37'),
(37, 16, 'User logged in', '::1', '2025-08-20 06:15:57'),
(38, 26, 'User logged in', '::1', '2025-08-20 06:16:35'),
(39, 7, 'User logged in', '::1', '2025-08-20 06:19:25'),
(40, 26, 'User logged in', '::1', '2025-08-20 06:22:53'),
(41, 26, 'User logged in', '::1', '2025-08-20 06:34:08'),
(42, 15, 'User logged in', '::1', '2025-08-20 06:42:09'),
(43, 5, 'User logged in', '::1', '2025-08-21 05:08:30'),
(44, 9, 'User logged in', '::1', '2025-08-21 05:12:22'),
(45, 9, 'User logged in', '::1', '2025-08-21 06:21:05'),
(46, 6, 'User logged in', '::1', '2025-08-21 06:54:22'),
(47, 7, 'User logged in', '::1', '2025-08-21 07:04:18'),
(48, 7, 'User logged in', '::1', '2025-08-21 07:15:09'),
(49, 6, 'User logged in', '::1', '2025-08-21 07:15:27'),
(50, 7, 'User logged in', '::1', '2025-08-21 07:16:22'),
(51, 7, 'User logged in', '::1', '2025-08-21 07:18:35'),
(52, 6, 'User logged in', '::1', '2025-08-21 07:20:49'),
(53, 7, 'User logged in', '::1', '2025-08-21 07:21:31'),
(54, 15, 'User logged in', '::1', '2025-08-21 07:53:17'),
(55, 7, 'User logged in', '::1', '2025-08-21 07:54:00'),
(56, 15, 'User logged in', '::1', '2025-08-21 07:54:16'),
(57, 2, 'User logged in', '::1', '2025-08-21 07:54:41'),
(58, 15, 'User logged in', '::1', '2025-08-21 08:01:31'),
(59, 15, 'User logged in', '::1', '2025-08-21 08:09:20'),
(60, 7, 'User logged in', '::1', '2025-08-21 08:09:37'),
(61, 6, 'User logged in', '::1', '2025-08-21 08:10:28'),
(62, 15, 'User logged in', '::1', '2025-08-21 08:11:34'),
(63, 15, 'User logged in', '::1', '2025-08-21 08:13:48'),
(64, 15, 'User logged in', '::1', '2025-08-21 08:15:01'),
(65, 6, 'User logged in', '::1', '2025-08-21 08:16:12'),
(66, 7, 'User logged in', '::1', '2025-08-21 08:44:19'),
(67, 6, 'User logged in', '::1', '2025-08-21 08:54:28'),
(68, 7, 'User logged in', '::1', '2025-08-21 08:56:27'),
(69, 6, 'User logged in', '::1', '2025-08-21 08:56:57'),
(70, 7, 'User logged in', '::1', '2025-08-21 08:57:14'),
(71, 6, 'User logged in', '::1', '2025-08-21 08:57:50'),
(72, 23, 'User logged in', '::1', '2025-08-21 08:58:40'),
(73, 2, 'User logged in', '::1', '2025-08-21 09:00:28'),
(74, 23, 'User logged in', '::1', '2025-08-21 09:15:21'),
(75, 5, 'User logged in', '::1', '2025-08-21 09:25:51'),
(76, 6, 'User logged in', '::1', '2025-08-22 13:34:54'),
(77, 4, 'User logged in', '::1', '2025-08-23 12:40:40'),
(78, 6, 'User logged in', '::1', '2025-08-23 12:41:27'),
(79, 16, 'User logged in', '::1', '2025-08-23 12:41:56'),
(80, 16, 'User logged in', '::1', '2025-08-23 12:42:16'),
(81, 4, 'User logged in', '::1', '2025-08-23 12:57:41'),
(82, 6, 'User logged in', '::1', '2025-08-23 12:58:01'),
(83, 4, 'User logged in', '::1', '2025-08-23 13:59:47'),
(84, 6, 'User logged in', '::1', '2025-08-23 14:01:13'),
(85, 4, 'User logged in', '::1', '2025-08-23 14:01:53'),
(86, 4, 'User logged in', '::1', '2025-08-23 14:12:04'),
(87, 6, 'User logged in', '::1', '2025-08-23 14:23:48'),
(88, 6, 'User logged in', '::1', '2025-08-23 14:38:00'),
(89, 4, 'User logged in', '::1', '2025-08-23 14:41:30'),
(90, 6, 'User logged in', '::1', '2025-08-23 15:25:52'),
(91, 6, 'User logged in', '::1', '2025-08-23 15:59:05'),
(92, 4, 'User logged in', '::1', '2025-08-23 15:59:20'),
(93, 4, 'User logged in', '::1', '2025-08-23 16:23:07'),
(94, 6, 'User logged in', '::1', '2025-08-23 16:23:34'),
(95, 4, 'User logged in', '::1', '2025-08-23 16:30:09'),
(96, 4, 'User logged in', '::1', '2025-08-23 16:44:32'),
(97, 4, 'User logged in', '::1', '2025-08-23 17:10:56'),
(98, 4, 'User logged in', '::1', '2025-08-24 05:57:46'),
(99, 6, 'User logged in', '::1', '2025-08-24 05:57:49'),
(100, 6, 'User logged in', '::1', '2025-08-24 06:01:18'),
(101, 4, 'User logged in', '::1', '2025-08-24 06:10:59'),
(102, 4, 'User logged in', '::1', '2025-08-24 08:16:03'),
(103, 6, 'User logged in', '::1', '2025-08-24 08:16:50'),
(104, 4, 'User logged in', '::1', '2025-08-24 08:27:02'),
(105, 4, 'User logged in', '::1', '2025-08-25 06:52:29'),
(106, 6, 'User logged in', '::1', '2025-08-25 06:52:49'),
(107, 6, 'User logged in', '::1', '2025-08-25 07:01:39'),
(108, 7, 'User logged in', '::1', '2025-08-25 07:14:45'),
(109, 6, 'User logged in', '::1', '2025-08-25 07:15:34'),
(110, 5, 'User logged in', '::1', '2025-08-25 07:16:08'),
(111, 2, 'User logged in', '::1', '2025-08-25 07:17:05'),
(112, 6, 'User logged in', '::1', '2025-08-25 07:17:41'),
(113, 4, 'User logged in', '::1', '2025-08-25 07:43:47'),
(114, 6, 'User logged in', '::1', '2025-08-25 08:49:09'),
(115, 6, 'User logged in', '::1', '2025-08-25 09:11:41'),
(116, 6, 'User logged in', '::1', '2025-08-25 10:52:30'),
(117, 15, 'User logged in', '::1', '2025-08-25 11:01:40'),
(118, 6, 'User logged in', '::1', '2025-08-25 12:48:17'),
(119, 15, 'User logged in', '::1', '2025-08-25 13:21:26'),
(120, 15, 'User logged in', '::1', '2025-08-25 13:41:57'),
(121, 15, 'User logged in', '::1', '2025-08-25 13:43:17'),
(122, 6, 'User logged in', '::1', '2025-08-25 13:51:29'),
(123, 4, 'User logged in', '::1', '2025-08-25 14:08:01'),
(124, 4, 'User logged in', '::1', '2025-08-25 14:14:53'),
(125, 6, 'User logged in', '::1', '2025-08-25 14:16:12'),
(126, 6, 'User logged in', '::1', '2025-08-25 14:32:27'),
(127, 7, 'User logged in', '::1', '2025-08-25 16:34:41'),
(128, 6, 'User logged in', '::1', '2025-08-26 04:59:22'),
(129, 6, 'User logged in', '::1', '2025-08-26 04:59:34'),
(130, 4, 'User logged in', '::1', '2025-08-26 05:00:49'),
(131, 6, 'User logged in', '::1', '2025-08-26 05:03:27'),
(132, 4, 'User logged in', '::1', '2025-08-26 05:10:08'),
(133, 6, 'User logged in', '::1', '2025-08-26 05:15:51'),
(134, 4, 'User logged in', '::1', '2025-08-26 05:16:49'),
(135, 4, 'User logged in', '::1', '2025-08-26 06:27:52'),
(136, 6, 'User logged in', '::1', '2025-08-26 08:11:28'),
(137, 6, 'User logged in', '::1', '2025-08-26 08:17:51'),
(138, 6, 'User logged in', '::1', '2025-08-26 09:02:47'),
(139, 2, 'User logged in', '::1', '2025-08-26 09:02:59'),
(140, 2, 'User logged in', '::1', '2025-08-26 09:03:31'),
(141, 2, 'User logged in', '::1', '2025-08-26 09:03:47'),
(142, 7, 'User logged in', '::1', '2025-08-26 09:06:38'),
(143, 7, 'User logged in', '::1', '2025-08-26 09:18:24'),
(144, 16, 'User logged in', '::1', '2025-08-26 09:26:36'),
(145, 2, 'User logged in', '::1', '2025-08-26 09:43:27'),
(146, 6, 'User logged in', '::1', '2025-08-26 10:25:58'),
(147, 6, 'User logged in', '::1', '2025-08-26 15:56:12'),
(148, 6, 'User logged in', '::1', '2025-08-27 15:35:01'),
(149, 6, 'User logged in', '::1', '2025-08-27 15:52:33'),
(150, 6, 'User logged in', '::1', '2025-08-27 16:00:19'),
(151, 26, 'User logged in', '::1', '2025-08-27 16:04:04'),
(152, 15, 'User logged in', '::1', '2025-08-27 16:35:22'),
(153, 15, 'User logged in', '::1', '2025-08-27 16:41:32'),
(154, 7, 'User logged in', '::1', '2025-08-27 16:47:28'),
(155, 4, 'User logged in', '::1', '2025-08-27 16:59:32'),
(156, 5, 'User logged in', '::1', '2025-08-27 17:02:49'),
(157, 5, 'User logged in', '::1', '2025-08-27 17:04:09'),
(158, 26, 'User logged in', '::1', '2025-08-27 17:11:23'),
(159, 7, 'User logged in', '::1', '2025-08-27 17:51:32'),
(160, 5, 'User logged in', '::1', '2025-08-27 18:01:28'),
(161, 4, 'User logged in', '::1', '2025-08-27 18:02:57'),
(162, 2, 'User logged in', '::1', '2025-08-27 18:03:09'),
(163, 16, 'User logged in', '::1', '2025-08-27 18:08:20'),
(164, 2, 'User logged in', '::1', '2025-08-27 18:23:00'),
(165, 26, 'User logged in', '::1', '2025-08-27 19:04:17'),
(166, 26, 'User logged in', '::1', '2025-08-27 19:21:08'),
(167, 15, 'User logged in', '::1', '2025-08-27 19:40:32'),
(168, 5, 'User logged in', '::1', '2025-08-27 20:08:13'),
(169, 15, 'User logged in', '::1', '2025-08-27 20:11:50'),
(170, 26, 'User logged in', '::1', '2025-08-28 03:46:33'),
(171, 5, 'User logged in', '::1', '2025-08-28 04:22:48'),
(172, 15, 'User logged in', '::1', '2025-08-28 04:23:22'),
(173, 15, 'User logged in', '::1', '2025-08-28 04:34:22'),
(174, 5, 'User logged in', '::1', '2025-08-28 05:03:21'),
(175, 26, 'User logged in', '::1', '2025-08-28 05:17:43'),
(176, 7, 'User logged in', '::1', '2025-08-28 05:19:30'),
(177, 26, 'User logged in', '::1', '2025-08-28 05:21:03'),
(178, 7, 'User logged in', '::1', '2025-08-28 05:21:47'),
(179, 26, 'User logged in', '::1', '2025-08-28 05:22:37'),
(180, 5, 'User logged in', '::1', '2025-08-28 05:23:34'),
(181, 26, 'User logged in', '::1', '2025-08-28 05:24:48'),
(182, 23, 'User logged in', '::1', '2025-08-28 05:25:03'),
(183, 17, 'User logged in', '::1', '2025-08-28 05:25:15'),
(184, 26, 'User logged in', '::1', '2025-08-28 05:28:02'),
(185, 26, 'User logged in', '::1', '2025-08-28 05:45:52'),
(186, 26, 'User logged in', '::1', '2025-08-28 05:46:19'),
(187, 26, 'User logged in', '::1', '2025-08-28 05:47:10'),
(188, 17, 'User logged in', '::1', '2025-08-28 05:47:40'),
(189, 26, 'User logged in', '::1', '2025-08-28 05:52:02'),
(190, 26, 'User logged in', '::1', '2025-08-28 05:55:17'),
(191, 16, 'User logged in', '::1', '2025-08-28 05:57:54'),
(192, 16, 'User logged in', '::1', '2025-08-28 06:16:02'),
(193, 18, 'User logged in', '::1', '2025-08-28 06:21:06'),
(194, 16, 'User logged in', '::1', '2025-08-28 06:24:28'),
(195, 26, 'User logged in', '::1', '2025-08-28 06:57:32'),
(196, 26, 'User logged in', '::1', '2025-08-28 07:06:49'),
(197, 7, 'User logged in', '::1', '2025-08-28 07:08:59'),
(198, 26, 'User logged in', '::1', '2025-08-28 07:11:23'),
(199, 7, 'User logged in', '::1', '2025-08-28 07:46:52'),
(200, 6, 'User logged in', '::1', '2025-08-28 07:58:48'),
(201, 7, 'User logged in', '::1', '2025-08-28 07:59:22'),
(202, 26, 'User logged in', '::1', '2025-08-28 08:44:27'),
(203, 7, 'User changed temporary password', '::1', '2025-08-28 08:46:21'),
(204, 5, 'User logged in', '::1', '2025-08-28 08:47:38'),
(205, 26, 'User logged in', '::1', '2025-08-28 16:29:11'),
(206, 5, 'User logged in', '::1', '2025-08-29 04:45:11'),
(207, 6, 'User logged in', '::1', '2025-08-29 05:28:51'),
(208, 26, 'User logged in', '::1', '2025-08-29 05:30:48'),
(209, 6, 'User logged in', '::1', '2025-08-29 05:39:55'),
(210, 6, 'User logged in', '::1', '2025-08-29 05:47:48'),
(211, 6, 'User logged in', '::1', '2025-08-29 05:48:11'),
(212, 5, 'User logged in', '::1', '2025-08-29 05:50:35'),
(213, 4, 'User logged in', '::1', '2025-08-29 05:53:33'),
(214, 2, 'User logged in', '::1', '2025-08-29 05:54:04'),
(215, 6, 'User logged in', '::1', '2025-08-29 05:56:37'),
(216, 16, 'User logged in', '::1', '2025-08-29 06:02:04'),
(217, 5, 'User logged in', '::1', '2025-08-29 06:03:39'),
(218, 29, 'User logged in', '::1', '2025-08-29 06:05:17'),
(219, 5, 'User logged in', '::1', '2025-08-29 06:19:59'),
(220, 5, 'User logged in', '::1', '2025-08-29 06:58:46'),
(221, 6, 'User logged in', '::1', '2025-08-29 07:01:53'),
(222, 5, 'User logged in', '::1', '2025-08-29 07:18:15'),
(223, 6, 'User logged in', '::1', '2025-08-29 08:28:24'),
(224, 26, 'User logged in', '::1', '2025-08-29 08:28:59'),
(225, 15, 'User logged in', '::1', '2025-08-29 08:59:30'),
(226, 26, 'User logged in', '::1', '2025-08-29 09:01:20'),
(227, 7, 'User changed temporary password', '::1', '2025-08-29 09:13:05'),
(228, 7, 'User logged in', '::1', '2025-08-29 09:40:42'),
(229, 6, 'User logged in', '::1', '2025-08-29 10:28:59'),
(230, 15, 'User logged in', '::1', '2025-08-31 04:34:15'),
(231, 7, 'User changed temporary password', '::1', '2025-08-31 04:36:52'),
(232, 6, 'User logged in', '::1', '2025-08-31 05:40:50'),
(233, 6, 'User logged in', '::1', '2025-08-31 09:20:48'),
(234, 5, 'User logged in', '::1', '2025-08-31 09:23:02'),
(235, 5, 'User logged in', '::1', '2025-08-31 09:52:44'),
(236, 15, 'User logged in', '::1', '2025-08-31 09:57:29'),
(237, 5, 'User logged in', '::1', '2025-08-31 09:58:02'),
(238, 5, 'User logged in', '::1', '2025-08-31 10:04:54'),
(239, 6, 'User logged in', '::1', '2025-08-31 11:11:32'),
(240, 4, 'User logged in', '::1', '2025-08-31 11:11:38'),
(241, 6, 'User logged in', '::1', '2025-08-31 11:12:22'),
(242, 4, 'User logged in', '::1', '2025-08-31 11:16:06'),
(243, 2, 'User logged in', '::1', '2025-08-31 11:24:22'),
(244, 2, 'User logged in', '::1', '2025-08-31 14:31:17'),
(245, 5, 'User logged in', '::1', '2025-08-31 14:35:27'),
(246, 5, 'User logged in', '::1', '2025-08-31 15:27:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `telegram` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `temp_password_flag` tinyint(1) DEFAULT '0',
  `account_status` enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `failed_attempts` int NOT NULL DEFAULT '0',
  `last_failed_login` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `phone`, `telegram`, `role`, `specialization`, `password`, `is_approved`, `temp_password_flag`, `account_status`, `failed_attempts`, `last_failed_login`, `profile_image`) VALUES
(2, 'selam alemu', 'selina', 'selam@gmail.com', '0918704090', 'seli', 'technician', 'Networking', '$2y$10$Kc/W2TazksnuZlkE/Z33ku/wXvIUYvh2WOxgPEC4kkfqXU6TvocO2', 1, 1, 'enabled', 0, NULL, '1756212585_Screenshot 2025-08-26 134749.png'),
(3, 'kebede belay', 'kebie', 'kebede@gmail.com', '0987432134', 'kebebig', 'technician', 'Electrical', '$2y$10$h75PuaFw17P64933z3EypuhCrP//n3VUE35AVpeh9GHqVHuayt1n6', 1, 1, 'enabled', 0, NULL, NULL),
(4, 'gashaw mule', 'gashiti', 'abdc@gmail.com', '0987654321', 'gashitiman', 'technician', 'Networking', '$2y$10$NwVOmMVoFH0mkFpPGqtR/uSEtAk/yJS20dFVKFqPzGr4N5/8.4ac6', 1, 1, 'enabled', 0, NULL, NULL),
(5, 'nathnaeal banteamlack', 'natibt', 'nati@gmail.com', '0918704090', 'natiBt', 'chief_technician', '', '$2y$10$YjSHcv9XWC85Dt8KPYCTPOf0YFFuAIUNFO4nMonKwHRw1PrcgYPR.', 1, 1, 'enabled', 0, NULL, NULL),
(6, 'hana sisay', 'sina', 'hana@gmail.com', '0989069804', 'sinasina', 'external_user', '', '$2y$10$O4a1tz8OCzWF53jbwqyI8uRjVR3VSEBPh458eFqJVurpwMKMV2sNG', 1, 1, 'enabled', 0, NULL, '1756638827_rope.png'),
(7, 'senayt dagnaw', 'seni', 'alemubela6@gmail.com', '0123450897', 'senisha', 'finance', '', '$2y$10$kHFc9gtBrbrAq3EppiNqRO1DRqHdR/.V53oN16Ca8zRJefJ3xsvKa', 1, 1, 'enabled', 0, NULL, NULL),
(8, 'tamrat laynie', 'tame', 'tame@gmail.com', '896564452', 'tabesha', 'technician', 'Electrical', '$2y$10$SOqJW5Ra//CCUbcWJm4Y5OvZ3HtAzN9nvfKwo204aQD7MAdUZ2yLi', 1, 1, 'enabled', 0, NULL, NULL),
(9, 'dave weldu', 'weldu', 'dave@gmail.com', '0965342290', 'aton_b', 'staff', '', '$2y$10$ZAVR7opNSyrWJKpIptQPR.btXJOq9PiD2HbNM9e0haqXxIhmM9n52', 1, 1, 'enabled', 0, NULL, NULL),
(10, 'yab gzachew', 'yab', 'abelabelberhane1993@gmail.com', '0988522633', 'zeab_studio', 'technician', 'Electronics', '$2y$10$t00LjUinNFp5zkZgzPxAwuMRtDB3LPyLKmZxI.h0QGF7CtvDN693O', 1, 1, 'enabled', 0, NULL, NULL),
(15, 'Abel Berhane', 'AbelBer', 'bellaberhan@gmail.com', '0965342290', 'Aton_B', 'admin', '', '$2y$10$pXMcTUWC6geQ8YOOT3zI/eG2Y2Ln.k0P4/9sh/7zp7AdsLRbwdytu', 1, 1, 'enabled', 0, NULL, NULL),
(16, 'getachew gebre', 'getman', 'gechegebre12@gmail.com', '0937372473', 'trumpforeve1', 'staff', '', '$2y$10$WhPgbxTj.RWA34lpCKxQNe1yMMhHsM3Lo9IIZ..JYuWPUuTzdBexW', 1, 1, 'enabled', 0, NULL, NULL),
(17, 'chala chebudie', 'chala', 'chala@gmail.com', '0923456232', 'challa', 'technician', 'Electronics', '$2y$10$rXYtuZ.HYzr4h0vSKg/SQuJ5mBk/p77w9FCHCmZWdq5qi.soz/7Mu', 1, 1, 'enabled', 0, NULL, NULL),
(18, 'asfdgfg dsfdghj', 'asdfg', 'aaaa@gmail.com', '0987654321', 'hgfcxe', 'technician', 'Networking', '$2y$10$DaVmySKtmypTeXKfZxxBsuqC64P9p.bYkq9MsXnw8/77pzPAQvmYm', 1, 0, 'enabled', 0, NULL, NULL),
(23, 'kirubiel abebe', 'kiru', 'chalachubie3@gmail.com', '0987654321', 'kirusha', 'staff', '', '$2y$10$p0QcgbMF73.P8NGaeJDyjOS.0T4rZ79UgRGQkh0niERtfbGqFGg/a', 1, 1, 'enabled', 0, NULL, NULL),
(26, 'Nardos Teshager', 'maya', 'nardosteshager@gmail.com', '+251900417915', 'nardi1221', 'external_user', '', '$2y$10$pegrx6cbkL1Rs6ZBdhSX7.tvDdL0PKKkp.nhDyqljDkipNGuUO/UG', 1, 1, 'enabled', 0, NULL, NULL),
(27, 'wubet getahun', 'wubie', 'wubetgetahun7@gmail.com', '+251977732120', 'wubie27', 'staff', '', '$2y$10$D.yQxWWtRfTFBuICSQLTQO7T60oD5F4aYmw1a/iJYaof5l2tVBEBG', 1, 1, 'enabled', 0, NULL, NULL),
(29, 'Semret Berhane', 'samri', 'samri@gmail.com', '+251987432134', 'sisu', 'technician', 'Electronics', '$2y$10$a4Svdx7AXfuFLfZyjTNdreHEJaA6d.ETrZXjG1JtLAuPy/SkkHL16', 1, 1, 'enabled', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `zoom_meetings`
--

DROP TABLE IF EXISTS `zoom_meetings`;
CREATE TABLE IF NOT EXISTS `zoom_meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `created_by` int NOT NULL,
  `meeting_id` varchar(50) NOT NULL,
  `join_url` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
