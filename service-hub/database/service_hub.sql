-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2026 at 08:33 AM
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
-- Database: `service_hub`
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
(19, 24, NULL, 1, '2026-03-10', 'cod', 'cancelled', NULL, 'ganpat uni', '384012', 'qwtuga', NULL, 'cancelled', '2026-03-10 05:41:33', NULL, NULL, NULL, '+911234567890', NULL, '2026-03-10 11:12:56');

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_otps`
--

INSERT INTO `email_otps` (`id`, `email`, `otp`, `expires_at`, `created_at`) VALUES
(6, 'tajuddin.green@gmail.com', '083781', '2026-03-15 17:53:11', '2026-03-15 16:43:11');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(6, 23, 22, NULL, 5, NULL, NULL, 1, 'Electrician', 'Good Work profile', '384012', '1234567890');

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
(1, 'Admin', 'team110.servicehub@gmail.com', NULL, '$2y$10$yZxk4Dq5BVTv8bCZMjINL.O24KBLPcW1BMkqGxLP9v5O73rgCGAiC', 'admin', 1, '2026-01-11 15:50:46', 1),
(2, 'SK TAJUDDIN', 'tajuddin@servicehub.com', '1234567890', '$2y$10$yZxk4Dq5BVTv8bCZMjINL.O24KBLPcW1BMkqGxLP9v5O73rgCGAiC', 'consumer', 1, '2026-01-10 10:25:44', 1),
(22, 'SK TAJUDDIN', 's19236094@gmail.com', NULL, '$2y$10$DhK60LT5p4F2eGZGejjEiuncCRm02Trnb./yRCZ5S872H3YbDDYFq', 'consumer', 1, '2026-03-07 09:37:40', 1),
(23, 'John Doe', 'tajuddin.green@gmail.com', NULL, '$2y$10$mbH34ROPL/2KG.Z9/ls6X.pqbAQ4O2Hhp6pNp.dRiaeZIvIwkgOe.', 'provider', 1, '2026-03-07 10:06:23', 1),
(24, 'SK TAJUDDIN', '23012011132@gnu.ac.in', NULL, '$2y$10$dIFDF3hvdWG9LrfqzgE.K.YGofLQqj7QfAkPmMJBM9n7KpXO3szGy', 'consumer', 1, '2026-03-10 05:40:10', 1);

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
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_email` (`email`);

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
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
