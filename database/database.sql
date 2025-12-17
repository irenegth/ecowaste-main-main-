-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 05:41 PM
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
-- Database: `ecowaste_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `pickup_requests`
--

CREATE TABLE `pickup_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waste_category` varchar(50) NOT NULL,
  `estimated_weight` varchar(20) DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `time_preference` varchar(50) DEFAULT NULL,
  `item_description` text NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `requested_by` varchar(50) NOT NULL,
  `request_date` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickup_requests`
--

INSERT INTO `pickup_requests` (`id`, `user_id`, `waste_category`, `estimated_weight`, `preferred_date`, `time_preference`, `item_description`, `special_instructions`, `status`, `created_at`, `requested_by`, `request_date`) VALUES
(1, 1, 'nabubulok', '12kl', '2025-12-15', '12', 'sample descri[tion', 'sample descri[tion', 'approved', '2025-12-15 13:38:28', 'sample descri[tion', '2025-12-15 15:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `pickup_schedules`
--

CREATE TABLE `pickup_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waste_type` varchar(50) NOT NULL,
  `pickup_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickup_schedules`
--

INSERT INTO `pickup_schedules` (`id`, `user_id`, `waste_type`, `pickup_date`, `time_slot`, `notes`, `status`, `created_at`) VALUES
(2, 1, 'Plastic', '2025-12-20', '08:00 - 10:00', 'Leave at front gate', 'scheduled', '2025-12-15 15:58:43'),
(3, 2, 'Organic', '2025-12-21', '10:00 - 12:00', 'Call before pickup', 'scheduled', '2025-12-15 15:58:43'),
(4, 3, 'Electronic', '2025-12-22', '14:00 - 16:00', 'Handle with care', 'scheduled', '2025-12-15 15:58:43'),
(5, 1, 'Plastic', '2025-12-23', '08:00 - 10:00', 'Bagged separately', 'scheduled', '2025-12-15 15:58:43'),
(6, 2, 'Paper', '2025-12-24', '09:00 - 11:00', 'Include old magazines', 'scheduled', '2025-12-15 15:58:43'),
(7, 3, 'Glass', '2025-12-25', '13:00 - 15:00', 'Wrap fragile items', 'scheduled', '2025-12-15 15:58:43'),
(8, 1, 'Metal', '2025-12-26', '08:00 - 10:00', 'Sort by type', 'scheduled', '2025-12-15 15:58:43'),
(9, 2, 'Organic', '2025-12-27', '10:00 - 12:00', 'Compostable only', 'scheduled', '2025-12-15 15:58:43'),
(10, 3, 'Plastic', '2025-12-28', '14:00 - 16:00', 'No bags', 'scheduled', '2025-12-15 15:58:43'),
(11, 1, 'Electronic', '2025-12-29', '08:00 - 10:00', 'Include cords', 'scheduled', '2025-12-15 15:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `recycling_centers`
--

CREATE TABLE `recycling_centers` (
  `id` int(11) NOT NULL,
  `center_name` varchar(100) NOT NULL,
  `center_type` varchar(50) DEFAULT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `operating_hours` varchar(100) DEFAULT NULL,
  `accepted_items` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `distance_km` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recycling_centers`
--

INSERT INTO `recycling_centers` (`id`, `center_name`, `center_type`, `address`, `phone`, `operating_hours`, `accepted_items`, `latitude`, `longitude`, `distance_km`, `created_at`) VALUES
(1, 'sampleupdate', 'sampleupdate', 'sampleupdate', 'sampleupdate', 'sampleupdate', 'sampleupdate', 14.16900000, 121.24100000, 0.80, '2025-12-15 07:42:28'),
(2, 'Green Valley Recycling', 'Recycling Center', 'Lopez Ave, Batong Malake, Los Baños', '+63 918 234 5678', 'Mon-Sat: 7AM-6PM', '', 14.16900000, 121.24100000, 0.80, '2025-12-15 07:42:28'),
(3, 'LB Junk Buying Station', 'Junk Shop', 'Crossing, Los Baños, Laguna', '+63 919 345 6789', 'Daily: 8AM-7PM', '', 14.16200000, 121.24700000, 1.00, '2025-12-15 07:42:28'),
(4, 'Baybayin Scrap & Junk', 'Junk Shop', 'Baybayin, Los Baños, Laguna', '+63 920 456 7890', 'Tue-Sun: 8AM-5PM', '', 14.15800000, 121.23500000, 1.20, '2025-12-15 07:42:28'),
(5, 'EcoWaste LB', 'Recycling Center', 'National Highway, Baybayin, Los Baños', '+63 921 567 8901', 'Mon-Fri: 9AM-6PM', '', 14.15500000, 121.23800000, 1.50, '2025-12-15 07:42:28'),
(6, 'Bambang Junk Shop', 'Junk Shop', 'Bambang, Los Baños, Laguna', '+63 922 678 9012', 'Mon-Sat: 7AM-6PM', '', 14.17200000, 121.25200000, 1.80, '2025-12-15 07:42:28'),
(7, 'LB Metal Trading', 'Junk Shop', 'Bambang Road, Los Baños', '+63 923 789 0123', 'Daily: 8AM-5PM', '', 14.17500000, 121.25500000, 2.10, '2025-12-15 07:42:28'),
(8, 'UPLB Eco Center', 'Recycling Center', 'College, Los Baños, Laguna', '+63 49 536 2345', 'Mon-Fri: 8AM-5PM', '', 14.16500000, 121.24200000, 0.60, '2025-12-15 07:42:28'),
(9, 'Campus Junkshop', 'Junk Shop', 'Near UPLB Gate, Los Baños', '+63 924 890 1234', 'Mon-Sat: 8AM-6PM', '', 14.16700000, 121.24400000, 0.70, '2025-12-15 07:42:28'),
(10, 'Mayondon Junk Buyer', 'Junk Shop', 'Mayondon, Los Baños, Laguna', '+63 925 901 2345', 'Tue-Sun: 8AM-5PM', '', 14.18000000, 121.26000000, 2.50, '2025-12-15 07:42:28'),
(11, 'Green Earth LB', 'Recycling Center', 'Mayondon Road, Los Baños', '+63 926 012 3456', 'Mon-Sat: 9AM-6PM', '', 14.18200000, 121.26200000, 2.80, '2025-12-15 07:42:28'),
(12, 'Maahas Scrap Shop', 'Junk Shop', 'Maahas, Los Baños, Laguna', '+63 927 123 4567', 'Mon-Sat: 7AM-6PM', '', 14.15000000, 121.23000000, 2.20, '2025-12-15 07:42:28'),
(13, 'LB Waste Solutions', 'Recycling Center', 'Maahas Road, Los Baños', '+63 928 234 5678', 'Mon-Fri: 8AM-5PM', '', 14.14800000, 121.22800000, 2.50, '2025-12-15 07:42:28'),
(14, 'Public Market Junkshop', 'Junk Shop', 'Near Public Market, Los Baños', '+63 929 345 6789', 'Daily: 6AM-7PM', '', 14.16100000, 121.24900000, 1.10, '2025-12-15 07:42:28'),
(15, 'Centro Recycling Hub', 'Recycling Center', 'National Road, Los Baños', '+63 930 456 7890', 'Mon-Sat: 8AM-6PM', '', 14.16000000, 121.25000000, 1.30, '2025-12-15 07:42:28'),
(16, 'Barangay Hall Collection Point', 'Recycling Center', 'Various Barangay Halls, Los Baños', '+63 49 536 0000', 'Mon-Fri: 8AM-5PM', '', 14.16400000, 121.24600000, 0.90, '2025-12-15 07:42:28'),
(17, 'LB E-Waste Facility', 'E-Waste Center', 'Industrial Area, Los Baños', '+63 931 567 8901', 'Mon-Fri: 9AM-5PM', '', 14.17000000, 121.24000000, 1.40, '2025-12-15 07:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','pending','suspended') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890', 'Admin Office, Eco City', 'admin', 'active', '2025-12-15 07:42:28', '2025-12-15 07:42:28'),

-- --------------------------------------------------------

--
-- Table structure for table `user_update_history`
--

CREATE TABLE `user_update_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `updated_by` varchar(50) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_update_history`
--

INSERT INTO `user_update_history` (`id`, `user_id`, `field_name`, `old_value`, `new_value`, `updated_by`, `updated_at`) VALUES
(1, 6, 'full_name', 'Jared Abrera', 'jaja.dev', 'sample updating info', '2025-12-15 14:27:14'),
(2, 6, 'phone', '091970171154', '0919054654065', 'sample updating info', '2025-12-15 14:27:14'),
(3, 6, 'address', 'jan lang sa geldi', 'jan lang sa tabi tabi', 'sample updating info', '2025-12-15 14:27:14'),
(4, 6, 'password', '********', '********', '', '2025-12-15 14:38:36'),
(5, 6, 'password', '********', '********', 'sample updating info', '2025-12-15 14:41:57'),
(6, 6, 'password', '********', '********', 'sample updating info', '2025-12-15 14:46:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pickup_schedules`
--
ALTER TABLE `pickup_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recycling_centers`
--
ALTER TABLE `recycling_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_update_history`
--
ALTER TABLE `user_update_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pickup_schedules`
--
ALTER TABLE `pickup_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `recycling_centers`
--
ALTER TABLE `recycling_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_update_history`
--
ALTER TABLE `user_update_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD CONSTRAINT `pickup_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pickup_schedules`
--
ALTER TABLE `pickup_schedules`
  ADD CONSTRAINT `pickup_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
