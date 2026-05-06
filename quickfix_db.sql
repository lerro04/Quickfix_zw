-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 11:31 AM
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
-- Database: `quickfix_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `bid_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `professional_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) NOT NULL,
  `message` text DEFAULT NULL,
  `estimated_days` int(11) DEFAULT 1,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`bid_id`, `job_id`, `professional_id`, `bid_amount`, `message`, `estimated_days`, `status`, `created_at`) VALUES
(1, 5, 8, 50.00, 'hey i can do', 1, 'pending', '2026-04-12 17:40:50'),
(2, 1, 2, 37.00, 'Uyags', 13, 'rejected', '2026-04-15 11:42:22'),
(3, 7, 8, 350.00, 'I will do with perfection', 7, 'accepted', '2026-04-15 12:19:14'),
(4, 1, 10, 25.00, 'Wil', 1, 'accepted', '2026-04-15 12:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `professional_id` int(11) NOT NULL,
  `bid_id` int(11) DEFAULT NULL,
  `agreed_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `scheduled_date` date DEFAULT NULL,
  `status` enum('confirmed','in_progress','completed','cancelled','disputed') DEFAULT 'confirmed',
  `payment_status` enum('pending','held','released','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `platform_fee_pct` decimal(5,2) DEFAULT 5.00,
  `platform_fee_amount` decimal(10,2) DEFAULT 0.00,
  `professional_payout` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('ecocash','onemoney','zimswitich','bank_transfer','cash') DEFAULT 'cash',
  `payment_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `job_id`, `client_id`, `professional_id`, `bid_id`, `agreed_amount`, `commission_rate`, `commission_amount`, `scheduled_date`, `status`, `payment_status`, `created_at`, `completed_at`, `platform_fee_pct`, `platform_fee_amount`, `professional_payout`, `payment_method`, `payment_reference`) VALUES
(1, NULL, 6, 8, NULL, 35.00, 10.00, 0.00, '2026-04-17', 'completed', 'released', '2026-04-15 11:45:15', '2026-04-15 12:30:25', 5.00, 1.75, 33.25, 'cash', NULL),
(2, NULL, 6, 8, NULL, 35.00, 10.00, 0.00, '2026-04-17', 'completed', 'released', '2026-04-15 11:53:51', '2026-04-15 12:30:20', 5.00, 1.75, 33.25, 'cash', NULL),
(3, 7, 11, 8, 3, 350.00, 10.00, 0.00, NULL, 'in_progress', 'pending', '2026-04-15 12:20:07', NULL, 5.00, 17.50, 332.50, 'cash', NULL),
(4, 7, 11, 8, 3, 350.00, 10.00, 0.00, NULL, 'disputed', 'pending', '2026-04-15 12:21:15', NULL, 5.00, 17.50, 332.50, 'cash', NULL),
(5, 1, 6, 10, 4, 25.00, 10.00, 0.00, NULL, 'completed', 'released', '2026-04-15 12:28:21', '2026-04-15 12:29:39', 5.00, 1.25, 23.75, 'cash', NULL),
(6, NULL, 13, 9, NULL, 6.00, 10.00, 0.00, '2026-04-29', 'completed', 'released', '2026-04-20 09:10:37', '2026-04-20 09:11:05', 5.00, 0.30, 5.70, 'cash', NULL);

--
-- Triggers `bookings`
--
DELIMITER $$
CREATE TRIGGER `trg_booking_fee` BEFORE INSERT ON `bookings` FOR EACH ROW BEGIN
  SET NEW.platform_fee_amount = ROUND(NEW.agreed_amount * NEW.platform_fee_pct / 100, 2);
  SET NEW.professional_payout  = NEW.agreed_amount - NEW.platform_fee_amount;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `contact_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_requests`
--

CREATE TABLE `job_requests` (
  `job_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `trade` varchar(60) NOT NULL,
  `location` varchar(100) NOT NULL,
  `client_budget` decimal(10,2) DEFAULT 0.00,
  `urgency` enum('flexible','within_week','urgent') DEFAULT 'flexible',
  `status` enum('open','in_progress','completed','cancelled') DEFAULT 'open',
  `hired_professional` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_requests`
--

INSERT INTO `job_requests` (`job_id`, `client_id`, `title`, `description`, `trade`, `location`, `client_budget`, `urgency`, `status`, `hired_professional`, `created_at`) VALUES
(1, 6, 'Fix leaking kitchen sink pipe', 'My kitchen sink pipe has been leaking under the cabinet for 3 days. Water is damaging the cabinet floor. Need urgent repair. House in Kuwadzana, Harare.', 'Plumbing', 'Harare', 15.00, 'urgent', 'in_progress', 10, '2026-04-12 17:28:51'),
(2, 6, 'Paint 3 bedroom house exterior', 'Need full exterior of my 3-bedroom house painted. Walls only, not roof. Located in Chinhoyi town. About 180 square metres total.', 'Painting', 'Chinhoyi', 120.00, 'within_week', 'open', NULL, '2026-04-12 17:28:51'),
(3, 7, 'Install 6 ceiling light fittings', 'Need 6 ceiling light fittings installed in my newly built house in Chinhoyi. Wiring is already in place — just need the fittings connected and covers fitted.', 'Electrical', 'Chinhoyi', 40.00, 'flexible', 'open', NULL, '2026-04-12 17:28:51'),
(4, 7, 'Build wooden kitchen cabinets', 'Need a carpenter to build and install 4 wooden kitchen wall cabinets. I have the measurements. Kitchen in Harare Norton Road area.', 'Carpentry', 'Harare', 80.00, 'within_week', 'open', NULL, '2026-04-12 17:28:51'),
(5, 6, 'fix plug fault', 'my plug has a spark and blown', 'Electrical', 'chinhoyi', 100.00, 'urgent', 'open', NULL, '2026-04-12 17:33:54'),
(6, 6, 'roof leak', 'marata angu akabooka akupinza', 'Roofing', 'chinhoyi', 29.00, 'urgent', 'open', NULL, '2026-04-12 18:04:47'),
(7, 11, '6 roomed house to be tubed', 'I want my 6 roomed house to to be tubed and wired all electricals', 'Electrical', 'Chinhoyi', 300.00, 'within_week', 'in_progress', 8, '2026-04-15 12:17:51'),
(8, 6, 'Fix leaking kitchen sink pipe', 'My kitchen sink pipe has been leaking under the cabinet for 3 days. Water is damaging the cabinet floor. Need urgent repair. House in Kuwadzana, Harare.', 'Plumbing', 'Harare', 15.00, 'urgent', 'open', NULL, '2026-04-29 19:24:21'),
(9, 6, 'Paint 3 bedroom house exterior', 'Need full exterior of my 3-bedroom house painted. Walls only, not roof. Located in Chinhoyi town. About 180 square metres total.', 'Painting', 'Chinhoyi', 120.00, 'within_week', 'open', NULL, '2026-04-29 19:24:21'),
(10, 7, 'Install 6 ceiling light fittings', 'Need 6 ceiling light fittings installed in my newly built house in Chinhoyi. Wiring is already in place — just need the fittings connected and covers fitted.', 'Electrical', 'Chinhoyi', 40.00, 'flexible', 'open', NULL, '2026-04-29 19:24:21'),
(11, 7, 'Build wooden kitchen cabinets', 'Need a carpenter to build and install 4 wooden kitchen wall cabinets. I have the measurements. Kitchen in Harare Norton Road area.', 'Carpentry', 'Harare', 80.00, 'within_week', 'open', NULL, '2026-04-29 19:24:21'),
(12, 6, 'Fix leaking kitchen sink pipe', 'My kitchen sink pipe has been leaking under the cabinet for 3 days. Water is damaging the cabinet floor. Need urgent repair. House in Kuwadzana, Harare.', 'Plumbing', 'Harare', 15.00, 'urgent', 'open', NULL, '2026-05-06 09:22:38'),
(13, 6, 'Paint 3 bedroom house exterior', 'Need full exterior of my 3-bedroom house painted. Walls only, not roof. Located in Chinhoyi town. About 180 square metres total.', 'Painting', 'Chinhoyi', 120.00, 'within_week', 'open', NULL, '2026-05-06 09:22:38'),
(14, 7, 'Install 6 ceiling light fittings', 'Need 6 ceiling light fittings installed in my newly built house in Chinhoyi. Wiring is already in place — just need the fittings connected and covers fitted.', 'Electrical', 'Chinhoyi', 40.00, 'flexible', 'open', NULL, '2026-05-06 09:22:38'),
(15, 7, 'Build wooden kitchen cabinets', 'Need a carpenter to build and install 4 wooden kitchen wall cabinets. I have the measurements. Kitchen in Harare Norton Road area.', 'Carpentry', 'Harare', 80.00, 'within_week', 'open', NULL, '2026-05-06 09:22:38');

-- --------------------------------------------------------

--
-- Table structure for table `job_request_images`
--

CREATE TABLE `job_request_images` (
  `image_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `booking_id`, `message`, `is_read`, `created_at`) VALUES
(1, 11, 8, NULL, 'Hey makupi', 1, '2026-04-15 12:22:18'),
(2, 10, 6, NULL, 'Im done', 1, '2026-04-15 12:29:01'),
(3, 1, 11, NULL, 'whats wrong', 1, '2026-04-15 12:32:28'),
(4, 11, 1, NULL, 'He stole amy tubes', 1, '2026-04-15 12:32:52'),
(5, 13, 5, NULL, 'hello', 0, '2026-04-20 09:12:19'),
(6, 13, 8, NULL, 'kuthrMRj', 1, '2026-04-20 09:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `browser_url` varchar(500) DEFAULT NULL,
  `poll_url` varchar(500) DEFAULT NULL,
  `status` enum('created','sent','pending','completed','failed','refunded') DEFAULT 'created',
  `raw_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `professional_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) DEFAULT 0.00,
  `professional_receives` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'card',
  `payment_gateway` varchar(50) DEFAULT 'stripe',
  `gateway_tx_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_analytics`
--

CREATE TABLE `platform_analytics` (
  `analytics_id` int(11) NOT NULL,
  `month` date DEFAULT NULL,
  `total_bookings` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `platform_earnings` decimal(12,2) DEFAULT 0.00,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `new_professionals` int(11) DEFAULT 0,
  `new_clients` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_images`
--

CREATE TABLE `portfolio_images` (
  `image_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `caption` varchar(200) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professional_portfolio_images`
--

CREATE TABLE `professional_portfolio_images` (
  `image_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professional_profiles`
--

CREATE TABLE `professional_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trade` varchar(60) NOT NULL,
  `bio` text DEFAULT NULL,
  `years_experience` int(11) DEFAULT 0,
  `hourly_rate` decimal(10,2) DEFAULT 5.00,
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `jobs_completed` int(11) DEFAULT 0,
  `is_available` tinyint(4) DEFAULT 1,
  `service_area` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professional_profiles`
--

INSERT INTO `professional_profiles` (`profile_id`, `user_id`, `trade`, `bio`, `years_experience`, `hourly_rate`, `rating_avg`, `total_reviews`, `jobs_completed`, `is_available`, `service_area`) VALUES
(1, 2, 'Plumbing', 'Experienced plumber specialising in burst pipes, geyser installation, bathroom fitting and drainage. Available across Mashonaland West.', 5, 8.00, 4.80, 12, 15, 1, 'Chinhoyi, Karoi, Murombedzi, Harare'),
(2, 3, 'Electrical', 'Certified electrician. DB board installation, fault finding, solar system wiring, and new house connections. Based in Harare.', 7, 10.00, 4.60, 8, 20, 1, 'Harare, Chitungwiza, Epworth'),
(3, 4, 'Painting', 'Professional interior and exterior painter. We supply materials or work with yours. Clean, neat finish guaranteed.', 3, 6.00, 4.90, 5, 10, 1, 'Chinhoyi, Kadoma, Chegutu'),
(4, 5, 'Carpentry', 'Furniture making, door and window fitting, kitchen units, decking, and general carpentry. 9 years experience in Harare.', 9, 9.00, 4.70, 3, 8, 1, 'Harare, Ruwa, Marondera'),
(5, 8, 'Electrical', NULL, 0, 5.00, 5.00, 2, 2, 1, NULL),
(6, 9, 'Roofing', NULL, 0, 5.00, 5.00, 1, 1, 1, NULL),
(7, 10, 'Plumbing', NULL, 0, 5.00, 3.00, 1, 1, 1, NULL),
(9, 17, 'Roofing', '', 0, 5.00, 0.00, 0, 0, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `created_at`) VALUES
(1, 5, 6, 10, 3, '', '2026-04-15 12:29:55'),
(2, 2, 6, 8, 5, '', '2026-04-15 12:30:22'),
(3, 1, 6, 8, 5, '', '2026-04-15 12:30:28'),
(4, 6, 13, 9, 5, 'hey', '2026-04-20 09:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(80) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
('2026-05-01-contact-messages', '2026-05-06 09:29:57'),
('2026-05-01-payments', '2026-05-06 09:29:57'),
('2026-05-01-trades-seed', '2026-05-06 09:29:57'),
('2026-05-01-trades-table', '2026-05-06 09:29:57'),
('2026-05-01-trades-varchar-jobs', '2026-05-06 09:29:57'),
('2026-05-01-trades-varchar-profiles', '2026-05-06 09:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trades`
--

CREATE TABLE `trades` (
  `trade_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trades`
--

INSERT INTO `trades` (`trade_id`, `name`, `is_active`, `created_at`) VALUES
(1, 'Plumbing', 1, '2026-05-06 09:29:57'),
(2, 'Electrical', 1, '2026-05-06 09:29:57'),
(3, 'Painting', 1, '2026-05-06 09:29:57'),
(4, 'Carpentry', 1, '2026-05-06 09:29:57'),
(5, 'Tiling', 1, '2026-05-06 09:29:57'),
(6, 'Roofing', 1, '2026-05-06 09:29:57'),
(7, 'Welding', 1, '2026-05-06 09:29:57'),
(8, 'Landscaping', 1, '2026-05-06 09:29:57'),
(9, 'General Handyman', 1, '2026-05-06 09:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('client','professional','admin') NOT NULL,
  `national_id` varchar(30) DEFAULT NULL,
  `national_id_file` varchar(255) DEFAULT NULL,
  `verified` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deletion_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `national_id`, `national_id_file`, `verified`, `is_active`, `location`, `created_at`, `deleted_at`, `deletion_reason`) VALUES
(1, 'System Admin', 'admin@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263771000000', 'admin', NULL, NULL, 1, 1, 'Harare', '2026-04-12 17:28:51', NULL, NULL),
(2, 'Tafadzwa Moyo', 'plumber@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772111111', 'professional', '63-111111A21', NULL, 1, 1, 'Chinhoyi', '2026-04-12 17:28:51', NULL, NULL),
(3, 'Blessing Chikwanda', 'electrician@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772222222', 'professional', '63-222222B21', NULL, 1, 1, 'Harare', '2026-04-12 17:28:51', NULL, NULL),
(4, 'Rudo Masara', 'painter@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772333333', 'professional', '63-333333C21', NULL, 1, 1, 'Chinhoyi', '2026-04-12 17:28:51', NULL, NULL),
(5, 'Farai Zimba', 'carpenter@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772444444', 'professional', '63-444444D21', NULL, 1, 1, 'Harare', '2026-04-12 17:28:51', NULL, NULL),
(6, 'Tendai Mwari', 'client1@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263773000001', 'client', NULL, NULL, 1, 1, 'Harare', '2026-04-12 17:28:51', NULL, NULL),
(7, 'Chipo Nyamukapa', 'client2@quickfixzw.co.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263773000002', 'client', NULL, NULL, 1, 1, 'Chinhoyi', '2026-04-12 17:28:51', NULL, NULL),
(8, 'William Bvuure', 'lerro@gmail.com', '$2y$10$Md/hOKgvktcctA/C1T38Xe87VPsKrISiWQ99/nXGPMcieJs0XY6je', '0774726816', 'professional', '70-325226M38', NULL, 1, 1, 'chinhoyi', '2026-04-12 17:37:22', NULL, NULL),
(9, 'tendai bvuure', 'qwerty@gmail.com', '$2y$10$uzzLvgaomYsVHL/J08QEBOKO9GYI3JwIyxbgLKyODIaXBzrSsyoh6', '0772465626', 'professional', '70-4657845648m45', NULL, 1, 1, 'chinhoyi', '2026-04-12 18:06:34', NULL, NULL),
(10, 'tadiwa makore', 'qwertyuiop@gmail.com', '$2y$10$yItrUf3EMOESj0ushZyaEet4066.la379v/i7m6V6ucselVrVFI0i', '076653776276', 'professional', '70-6763328m768', NULL, 1, 1, 'chinhoyi', '2026-04-13 09:04:53', NULL, NULL),
(11, 'Mikayla Bvuure', 'mikayla@gmail.com', '$2y$10$OoEgBYQiLgvdRtLW51WC.ednsFtHnhBREY8D.afRRT6uPaF.Y.yGO', '+263717076805', 'client', '70-584846M38', NULL, 1, 1, 'Kwekwe', '2026-04-15 11:58:41', NULL, NULL),
(12, 'teo', 'teo@gmail', '$2y$10$lDOXGKrax4kmT3NYNwLCW.mK.IXQS8U2R8YE1DsJrCCkxR36R7Hta', '0777628689', 'client', '702040323G45', NULL, 1, 1, 'Ruvimbo', '2026-04-20 09:04:23', NULL, NULL),
(13, 'theophelus gore', 'theophelusgore319@gmail.com', '$2y$10$gN35xxGUKaRjxzjYqF8qaOkMBIHdgKfyO3DTYur/sM4ykQQNq/X.i', '0777628689', 'client', '70-2040323 G 45', NULL, 1, 1, 'chinhoyi', '2026-04-20 09:06:19', NULL, NULL),
(14, 'Theophelus', 'theophelus@gmail.com', '$2y$10$LQP57qkd6r46bWQQAazxB.NzvHC049FgXzOkYEsdBqBdNLkZbsuAm', '0777628689', 'client', '70-2040323 G 45', NULL, 1, 1, 'chinhoyi', '2026-04-20 09:09:18', NULL, NULL),
(15, 'System Admin1', 'lerro@quickfix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263771234344', 'admin', NULL, NULL, 1, 1, 'Chinhoyi', '2026-04-29 19:24:21', NULL, NULL),
(17, 'william bvuure', 'lerr@gmail.com', '$2y$10$EmfZfIRhEywfQSJCByVb4OUndh57pPJh3lDYVFdsq7fwSnFZ94d9y', '075446836565', 'professional', '70-765386A45', NULL, 0, 1, 'karoi', '2026-04-30 14:11:26', NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_platform_revenue`
-- (See below for the actual view)
--
CREATE TABLE `v_platform_revenue` (
`total_bookings` bigint(21)
,`gross_transaction_value` decimal(32,2)
,`platform_revenue` decimal(32,2)
,`professional_earnings` decimal(32,2)
,`collected_revenue` decimal(32,2)
,`pending_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_platform_revenue`
--
DROP TABLE IF EXISTS `v_platform_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_platform_revenue`  AS SELECT count(`bookings`.`booking_id`) AS `total_bookings`, sum(`bookings`.`agreed_amount`) AS `gross_transaction_value`, sum(`bookings`.`platform_fee_amount`) AS `platform_revenue`, sum(`bookings`.`professional_payout`) AS `professional_earnings`, sum(case when `bookings`.`payment_status` = 'released' then `bookings`.`platform_fee_amount` else 0 end) AS `collected_revenue`, sum(case when `bookings`.`payment_status` = 'pending' then `bookings`.`platform_fee_amount` else 0 end) AS `pending_revenue` FROM `bookings` WHERE `bookings`.`status` <> 'cancelled' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `professional_id` (`professional_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `bid_id` (`bid_id`),
  ADD KEY `idx_bookings_client` (`client_id`),
  ADD KEY `idx_bookings_pro` (`professional_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `job_requests`
--
ALTER TABLE `job_requests`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `hired_professional` (`hired_professional`),
  ADD KEY `idx_jobs_status_trade` (`status`,`trade`),
  ADD KEY `idx_jobs_created` (`created_at`);

--
-- Indexes for table `job_request_images`
--
ALTER TABLE `job_request_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `professional_id` (`professional_id`);

--
-- Indexes for table `platform_analytics`
--
ALTER TABLE `platform_analytics`
  ADD PRIMARY KEY (`analytics_id`);

--
-- Indexes for table `portfolio_images`
--
ALTER TABLE `portfolio_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `professional_portfolio_images`
--
ALTER TABLE `professional_portfolio_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `professional_profiles`
--
ALTER TABLE `professional_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_pro_trade_rating` (`trade`,`rating_avg`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trades`
--
ALTER TABLE `trades`
  ADD PRIMARY KEY (`trade_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_deleted` (`role`,`deleted_at`),
  ADD KEY `idx_users_verified` (`verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `bid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_requests`
--
ALTER TABLE `job_requests`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `job_request_images`
--
ALTER TABLE `job_request_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_analytics`
--
ALTER TABLE `platform_analytics`
  MODIFY `analytics_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio_images`
--
ALTER TABLE `portfolio_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professional_portfolio_images`
--
ALTER TABLE `professional_portfolio_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professional_profiles`
--
ALTER TABLE `professional_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trades`
--
ALTER TABLE `trades`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_requests` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`professional_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_requests` (`job_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`professional_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`bid_id`);

--
-- Constraints for table `job_requests`
--
ALTER TABLE `job_requests`
  ADD CONSTRAINT `job_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `job_requests_ibfk_2` FOREIGN KEY (`hired_professional`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `job_request_images`
--
ALTER TABLE `job_request_images`
  ADD CONSTRAINT `job_request_images_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_requests` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payment_transactions_ibfk_3` FOREIGN KEY (`professional_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `portfolio_images`
--
ALTER TABLE `portfolio_images`
  ADD CONSTRAINT `portfolio_images_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `professional_portfolio_images`
--
ALTER TABLE `professional_portfolio_images`
  ADD CONSTRAINT `professional_portfolio_images_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `professional_profiles`
--
ALTER TABLE `professional_profiles`
  ADD CONSTRAINT `professional_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
