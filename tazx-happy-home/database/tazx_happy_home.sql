-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 07:05 PM
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
-- Database: `tazx_happy_home`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `booking_date` date DEFAULT NULL,
  `payment_method` enum('cod') DEFAULT 'cod',
  `payment_status` enum('pending','paid','done','cancelled') DEFAULT 'pending',
  `booking_time` time DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `problem_description` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','accepted','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `service_charge` decimal(10,2) DEFAULT NULL,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `material_charge` decimal(10,2) DEFAULT NULL,
  `contact_phone` varchar(15) NOT NULL,
  `provider_note` varchar(30) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `consumer_id`, `provider_id`, `service_id`, `booking_date`, `payment_method`, `payment_status`, `booking_time`, `address`, `pincode`, `problem_description`, `total_amount`, `status`, `created_at`, `service_charge`, `gst_amount`, `material_charge`, `contact_phone`, `provider_note`, `closed_at`) VALUES
(14, 21, 5, 1, '2026-01-22', 'cod', 'paid', NULL, 'sdfghjk', '731236', 'sdfghj,', 590.00, 'completed', '2026-01-15 08:17:12', 200.00, 90.00, 300.00, '', 'completed properly', '2026-01-16 15:30:54'),
(15, 21, 5, 1, '2026-01-16', 'cod', 'cancelled', NULL, 'asdfghjk', '731236', 'asdfghjkl', 354.00, 'cancelled', '2026-01-15 08:56:56', 100.00, 54.00, 200.00, '', 'cancelled by customer', '2026-01-16 15:30:47'),
(17, 21, 5, 1, '2026-01-17', 'cod', 'paid', NULL, 'asdfghjk', '384012', 'qwertyuiop', 590.00, 'completed', '2026-01-17 08:29:28', 200.00, 90.00, 300.00, '+911234567890', 'completed properly', '2026-01-17 14:08:31'),
(18, 21, 5, 1, '2026-01-18', 'cod', 'paid', NULL, 'qwertyuio', '731236', 'qwertyuiop', 354.00, 'completed', '2026-01-17 08:45:32', 100.00, 54.00, 200.00, '+911234567890', 'completed properly', '2026-01-17 14:29:22');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` enum('online','cod') DEFAULT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `method`, `status`, `created_at`) VALUES
(1, 14, 590.00, 'cod', 'paid', '2026-01-16 10:00:54'),
(3, 17, 590.00, 'cod', 'paid', '2026-01-17 08:38:31'),
(5, 18, 354.00, 'cod', 'paid', '2026-01-17 08:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `profession_key` varchar(50) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `min_price` decimal(10,2) DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `pricing_type` enum('fixed','hourly','inspection') DEFAULT 'fixed',
  `is_featured` tinyint(4) DEFAULT 0,
  `total_bookings` int(11) DEFAULT 0,
  `estimated_hours` int(11) DEFAULT 1,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `slug`, `profession_key`, `icon`, `description`, `base_price`, `min_price`, `max_price`, `pricing_type`, `is_featured`, `total_bookings`, `estimated_hours`, `status`) VALUES
(1, 'Electrician', 'Electrician', '', '🔌', 'Electrician for repairing house hold appliances', NULL, NULL, NULL, 'fixed', 0, 0, 1, 1),
(3, 'Carpenter', 'carpenter', 'carpenter', '🪚', 'Wood & furniture work', NULL, NULL, NULL, 'fixed', 0, 0, 1, 1),
(4, 'Painter', 'painter', 'painter', '🎨', 'House & wall painting', NULL, NULL, NULL, 'fixed', 0, 0, 1, 1),
(5, 'AC Technician', 'ac-technician', 'ac', '❄️', 'AC repair & servicing in your area', NULL, NULL, NULL, 'fixed', 0, 0, 1, 1),
(6, 'cleaner', 'cleaner', '', '🧹', 'Need cleaner for house hold', NULL, NULL, NULL, 'fixed', 0, 0, 1, 1),
(7, 'Mechanic', 'mechanic', '', '🛠️', 'Vehicle Mechanic', NULL, NULL, NULL, 'fixed', 0, 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

CREATE TABLE `service_providers` (
  `provider_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `id_proof` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(4) DEFAULT 0,
  `profession` varchar(100) DEFAULT NULL,
  `about_work` text DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_providers`
--

INSERT INTO `service_providers` (`provider_id`, `user_id`, `age`, `specialization`, `experience`, `area`, `id_proof`, `is_approved`, `profession`, `about_work`, `pincode`, `phone`) VALUES
(1, 14, 22, NULL, 2, NULL, NULL, 1, 'Electrician', 'qwertyuiop', '731236', '1234567890'),
(2, 17, 22, NULL, 1, NULL, NULL, -1, 'Carpenter', 'asdfghjk', '384012', '1234567890'),
(3, 18, 22, NULL, 2, NULL, NULL, 1, 'Carpenter', 'asdfghjkl', '384012', '1234567890'),
(4, 19, 24, NULL, 8, NULL, NULL, 1, 'Painter', 'sdfghjkl', '784575', '1234567980'),
(5, 20, 20, NULL, 2, 'ganpat university', NULL, 1, 'Electrician', 'hello', '384012', '1234567890');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('consumer','provider','admin') NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `is_active`) VALUES
(3, 'SK TAJUDDIN', 'tajuddin.green@gmail.com', NULL, '$2y$10$nSffPBIoXk1AEDkHkQtxfeznshq.ujkMkovbJWd8Tq/hf1wlexRLm', 'consumer', 1, '2026-01-10 10:25:44', 1),
(14, 'SK TAJUDDIN', 'employee@test.com', NULL, '$2y$10$NSo2B3NMIrD4o4J9zbMb4.0.1IcAAn5ESuBKmpbYreH4LL3LwuQT.', 'provider', 0, '2026-01-10 18:04:45', 1),
(15, 'Admin', 'admin@tazx.com', NULL, '$2y$10$yZxk4Dq5BVTv8bCZMjINL.O24KBLPcW1BMkqGxLP9v5O73rgCGAiC', 'admin', 1, '2026-01-11 15:50:46', 1),
(17, 'SK TAJUDDIN', 'tajuddin@employee.com', NULL, '$2y$10$07Cxum8Ku.klyfbAzAYLr.67nIZ.1cWjwhmpa.rWdzYTESVoqY6KG', 'provider', 0, '2026-01-11 18:16:35', 1),
(18, 'SK TAJUDDIN', 'employee@tazx.com', NULL, '$2y$10$Jr3sDnpNPFT29qwWNgqsT.pRJ3tIhDeXSw1SuHX6kMD76rSHDsAga', 'provider', 0, '2026-01-11 18:21:54', 1),
(19, 'SK TAJUDDIN', 'tajuddin.g@gmail.com', NULL, '$2y$10$fBvT7BbRwq3iGqyLAPUM1OUVY5U1umV2m3U7gHCss8Ht/9m2LttEO', 'provider', 1, '2026-01-11 18:24:30', 1),
(20, 'SK TAJUDDIN', 'tajuddin.gre@gmail.com', NULL, '$2y$10$HP4WHoURtK1itMghAt0lEOo3XWxbPkPOBN4Ze.LfqyJTh2kt6A6D2', 'provider', 1, '2026-01-11 18:31:22', 1),
(21, 'SK TAJUDDIN', 'test@tazx.com', '123456789', '$2y$10$A7KHzL4ZvKOKCA6TFn1bI.H6J.Y2iQRLBgc1uAeto8svxUSEgbtou', 'consumer', 1, '2026-01-11 19:07:28', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_consumer` (`consumer_id`),
  ADD KEY `fk_booking_service` (`service_id`),
  ADD KEY `fk_booking_provider` (`provider_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_booking_review` (`booking_id`),
  ADD KEY `fk_reviews_consumer` (`consumer_id`),
  ADD KEY `fk_reviews_provider` (`provider_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_profession` (`profession_key`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_consumer` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_booking_provider` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`provider_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_consumer` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_reviews_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD CONSTRAINT `service_providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
