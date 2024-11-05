-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2024 at 01:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(255) NOT NULL,
  `menu_path` varchar(255) NOT NULL,
  `menu_icon` text DEFAULT NULL,
  `menu_order` int(11) DEFAULT 0,
  `menu_section` enum('Menu','Others') DEFAULT 'Menu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`menu_id`, `menu_name`, `menu_path`, `menu_icon`, `menu_order`, `menu_section`, `created_at`) VALUES
(1, 'หน้าหลัก', 'dashboard.php', '<path d=\"M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z\" /><polyline points=\"9 22 9 12 15 12 15 22\" />', 1, 'Menu', '2024-11-04 16:42:16'),
(2, 'ชำระค่าส่วนกลาง', 'payment.php', '<rect x=\"2\" y=\"5\" width=\"20\" height=\"14\" rx=\"2\" /><line x1=\"2\" y1=\"10\" x2=\"22\" y2=\"10\" /><path d=\"M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4z\" />', 2, 'Menu', '2024-11-04 16:42:16'),
(3, 'การแจ้งซ่อม', 'request.php', '<path d=\"M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z\" /><path d=\"M15 7l-8 8\" />', 3, 'Menu', '2024-11-04 16:42:16'),
(4, 'รายละเอียดการแจ้งซ่อม', 'view_request.php', '<path d=\"M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z\" /><polyline points=\"14 2 14 8 20 8\" /><line x1=\"16\" y1=\"13\" x2=\"8\" y2=\"13\" /><line x1=\"16\" y1=\"17\" x2=\"8\" y2=\"17\" /><polyline points=\"10 9 9 9 8 9\" />', 4, 'Menu', '2024-11-04 16:42:16'),
(5, 'จัดการค่าส่วนกลาง', 'manage_payment.php', '<circle cx=\"9\" cy=\"7\" r=\"4\" /><path d=\"M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2\" /><line x1=\"19\" y1=\"8\" x2=\"19\" y2=\"14\" /><line x1=\"22\" y1=\"11\" x2=\"16\" y2=\"11\" />', 5, 'Menu', '2024-11-04 16:42:16'),
(6, 'จัดการแจ้งซ่อม', 'manage_request.php', '<circle cx=\"12\" cy=\"12\" r=\"3\" /><path d=\"M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z\" />', 6, 'Menu', '2024-11-04 16:42:16'),
(7, 'จัดการข้อมูลผู้ใช้', 'manage_users.php', '<path d=\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\" /><circle cx=\"9\" cy=\"7\" r=\"4\" /><path d=\"M23 21v-2a4 4 0 0 0-3-3.87\" /><path d=\"M16 3.13a4 4 0 0 1 0 7.75\" />', 7, 'Menu', '2024-11-04 16:42:16'),
(8, 'จัดการสิทธิ์การใช้งาน', 'permission.php', '<rect x=\"3\" y=\"11\" width=\"18\" height=\"11\" rx=\"2\" ry=\"2\" /><path d=\"M7 11V7a5 5 0 0 1 10 0v4\" /><circle cx=\"12\" cy=\"16\" r=\"1\" />', 8, 'Menu', '2024-11-04 16:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `created_at`) VALUES
(1, 'ผู้ดูแลระบบ', '2024-11-04 16:42:17'),
(2, 'ผู้ใช้งานทั่วไป', '2024-11-04 16:42:17'),
(7, 'เจ้าหน้าที่', '2024-11-04 17:52:03');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `can_access` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_access`, `created_at`) VALUES
(1, 1, 1, '2024-11-04 16:42:17'),
(1, 2, 1, '2024-11-04 16:42:17'),
(1, 3, 1, '2024-11-04 16:42:17'),
(1, 4, 1, '2024-11-04 16:42:17'),
(1, 5, 1, '2024-11-04 16:42:17'),
(1, 6, 1, '2024-11-04 16:42:17'),
(1, 7, 1, '2024-11-04 16:42:17'),
(1, 8, 1, '2024-11-04 16:42:17'),
(2, 1, 1, '2024-11-04 17:51:24'),
(2, 2, 1, '2024-11-04 17:51:24'),
(2, 3, 1, '2024-11-04 17:51:24'),
(2, 4, 1, '2024-11-04 17:51:24'),
(7, 1, 1, '2024-11-04 17:52:03'),
(7, 5, 1, '2024-11-04 17:52:03'),
(7, 6, 1, '2024-11-04 17:52:03'),
(7, 7, 1, '2024-11-04 17:52:03'),
(7, 8, 1, '2024-11-04 17:52:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fullname` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `house_no` varchar(20) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `road` varchar(100) DEFAULT NULL,
  `subdistrict` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(5) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role_id`, `created_at`, `fullname`, `phone`, `house_no`, `village`, `road`, `subdistrict`, `district`, `province`, `postal_code`, `profile_image`) VALUES
(1, 'admin', 'admin', 1, '2024-11-04 16:48:22', 'จิรภัทร ป่าไพร', NULL, '152/11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'users', 'users', 2, '0000-00-00 00:00:00', 'ทวีศักดิ์ นำมา', NULL, '25/3', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'off', 'off', 7, '0000-00-00 00:00:00', 'สราวุฒิ ชัยวงค์', '111', '88/4', '', '', '', '', 'เชียงใหม่', '', NULL),
(4, 'users2', 'users2', 2, '0000-00-00 00:00:00', 'ชลสิทธิ์ แสงปินตา', '', '85/16', '', '', '', '', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`),
  ADD CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`menu_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
