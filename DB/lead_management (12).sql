-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2025 at 05:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lead_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_details`
--

CREATE TABLE `accommodation_details` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `room_category` varchar(255) NOT NULL,
  `cp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `map_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `eb_adult_cp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `eb_adult_map` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_with_bed_cp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_with_bed_map` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_without_bed_cp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_without_bed_map` decimal(10,2) NOT NULL DEFAULT 0.00,
  `xmas_newyear_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `meal_type` varchar(255) DEFAULT NULL,
  `meal_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `validity_from` date NOT NULL,
  `validity_to` date NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accommodation_details`
--

INSERT INTO `accommodation_details` (`id`, `destination`, `hotel_name`, `room_category`, `cp`, `map_rate`, `eb_adult_cp`, `eb_adult_map`, `child_with_bed_cp`, `child_with_bed_map`, `child_without_bed_cp`, `child_without_bed_map`, `xmas_newyear_charges`, `meal_type`, `meal_charges`, `validity_from`, `validity_to`, `remark`, `created_at`, `updated_at`) VALUES
(1, 'ewr', 'hgf', 'hbvn', 100.00, 100.00, 100.00, 100.00, 1010.00, 10.00, 100.00, 100.00, 1000.00, '100', 10.00, '0000-00-00', '2025-07-30', 'yufgth', '2025-07-28 16:23:52', '2025-07-28 16:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Facebook', NULL, '2025-05-14 03:55:57'),
(2, 'Website', NULL, '2025-05-14 03:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `ad_campaigns`
--

CREATE TABLE `ad_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `department_id` int(11) NOT NULL,
  `planned_days` int(11) NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ad_campaigns`
--

INSERT INTO `ad_campaigns` (`id`, `name`, `platform`, `department_id`, `planned_days`, `budget`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'Facebook', 'Fb', 4, 10, 1000.00, '2025-06-16', '2025-06-23', 'inactive', '2025-06-14 18:58:04'),
(2, 'InstaAd', 'Instagram', 7, 10, 2000.00, '2025-06-16', '2025-06-17', 'inactive', '2025-06-16 10:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `message`, `created_at`) VALUES
(1, 1, 'hi', '2025-05-23 12:42:19'),
(2, 1, 'hi', '2025-05-23 12:46:58'),
(3, 1, 'hi', '2025-05-23 12:50:39'),
(4, 1, 'hi', '2025-05-23 12:50:42'),
(5, 1, 'hi', '2025-05-23 13:11:24'),
(6, 1, 'hi', '2025-05-23 13:13:15'),
(7, 1, 'test 1', '2025-05-23 13:13:24'),
(8, 1, 'test 1', '2025-05-23 13:13:29'),
(9, 1, 'test 1', '2025-05-23 13:14:13');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `enquiry_id`, `user_id`, `comment`, `created_at`) VALUES
(10, 0, 1, 'Hi', '2025-06-15 08:10:45'),
(30, 66, 1, 'sf', '2025-08-01 19:37:39'),
(31, 68, 1, 'ds', '2025-08-04 14:52:24'),
(32, 68, 3, 'dfg', '2025-08-04 14:58:51');

-- --------------------------------------------------------

--
-- Table structure for table `comments_history`
--

CREATE TABLE `comments_history` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_attachments`
--

CREATE TABLE `comment_attachments` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','pdf') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_attachments`
--

INSERT INTO `comment_attachments` (`id`, `enquiry_id`, `user_id`, `original_name`, `file_path`, `file_type`, `created_at`) VALUES
(1, 66, 1, '1751378977.png', 'comment_attachment/688d16ff355be_1754076927.png', 'image', '2025-08-01 19:35:27'),
(2, 66, 1, 'Srikumar_updated_CV.pdf', 'comment_attachment/688d1741bf86d_1754076993.pdf', 'pdf', '2025-08-01 19:36:33'),
(3, 68, 1, 'favicon-32x32.png', 'comment_attachment/6890c9344e99f_1754319156.png', 'image', '2025-08-04 14:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `converted_leads`
--

CREATE TABLE `converted_leads` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `enquiry_number` varchar(20) NOT NULL,
  `lead_type` enum('Hot','Warm','Cold') DEFAULT NULL,
  `customer_location` varchar(255) DEFAULT NULL,
  `secondary_contact` varchar(20) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `other_details` text DEFAULT NULL,
  `travel_month` varchar(20) DEFAULT NULL,
  `travel_start_date` date DEFAULT NULL,
  `travel_end_date` date DEFAULT NULL,
  `adults_count` int(11) DEFAULT NULL,
  `children_count` int(11) DEFAULT NULL,
  `infants_count` int(11) DEFAULT NULL,
  `children_age_details` varchar(255) DEFAULT NULL,
  `customer_available_timing` varchar(100) DEFAULT NULL,
  `file_manager_id` int(11) DEFAULT NULL,
  `booking_confirmed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `night_day` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `converted_leads`
--

INSERT INTO `converted_leads` (`id`, `enquiry_id`, `enquiry_number`, `lead_type`, `customer_location`, `secondary_contact`, `destination_id`, `other_details`, `travel_month`, `travel_start_date`, `travel_end_date`, `adults_count`, `children_count`, `infants_count`, `children_age_details`, `customer_available_timing`, `file_manager_id`, `booking_confirmed`, `created_at`, `night_day`) VALUES
(40, 66, 'LGH-2025/08/02/3928', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, 0, '2025-08-01 19:25:29', NULL),
(42, 68, 'LGH-2025/08/02/3324', NULL, NULL, NULL, 1, NULL, 'October', NULL, NULL, 0, 0, 0, NULL, NULL, 1, 0, '2025-08-01 19:58:35', '7N/8D'),
(43, 67, 'GH 7771', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-08-04 14:53:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cost_sheets`
--

CREATE TABLE `cost_sheets` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `enquiry_number` varchar(50) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `total_expense` decimal(15,2) NOT NULL DEFAULT 0.00,
  `package_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `markup_percentage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_percentage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `services_data` longtext DEFAULT NULL,
  `payment_data` longtext DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cruise_details`
--

CREATE TABLE `cruise_details` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `cruise_details` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `adult_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `kids_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Domestic', '2025-06-14 18:54:59'),
(2, 'GCC Inbound', '2025-06-14 18:54:59'),
(3, 'GCC Medical', '2025-06-14 18:54:59'),
(4, 'GCC Outbound', '2025-06-14 18:54:59'),
(5, 'Inound', '2025-06-14 20:07:44'),
(6, 'Not Provided', '2025-06-14 20:08:02'),
(7, 'Outbound', '2025-06-14 20:08:15');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `created_at`, `status`) VALUES
(1, 'Agra', '2025-07-14 17:54:39', 'Active'),
(2, 'Anadaman', '2025-07-14 17:54:39', 'Active'),
(3, 'Athirapally', '2025-07-14 17:54:39', 'Active'),
(4, 'Azerbaijan', '2025-07-14 17:54:39', 'Active'),
(5, 'Bali', '2025-07-14 17:54:39', 'Active'),
(6, 'Bangalore', '2025-07-14 17:54:39', 'Active'),
(7, 'Calicut', '2025-07-14 17:54:39', 'Active'),
(8, 'Coorg', '2025-07-14 17:54:39', 'Active'),
(9, 'Coorg', '2025-07-14 17:54:39', 'Active'),
(10, 'Darjeeling', '2025-07-14 17:54:39', 'Active'),
(11, 'Delhi', '2025-07-14 17:54:39', 'Active'),
(12, 'Delhi', '2025-07-14 17:54:39', 'Active'),
(13, 'Goa', '2025-07-14 17:54:39', 'Active'),
(14, 'Hyderabad', '2025-07-14 17:54:39', 'Active'),
(15, 'Jaipur', '2025-07-14 17:54:39', 'Active'),
(16, 'Jaipur', '2025-07-14 17:54:39', 'Active'),
(17, 'Kashmir', '2025-07-14 17:54:39', 'Active'),
(18, 'Kerala', '2025-07-14 17:54:39', 'Active'),
(19, 'Kochi', '2025-07-14 17:54:39', 'Active'),
(20, 'Kodai', '2025-07-14 17:54:39', 'Active'),
(21, 'Kollam', '2025-07-14 17:54:39', 'Active'),
(22, 'Kovalam', '2025-07-14 17:54:40', 'Active'),
(23, 'Kulu Manali', '2025-07-14 17:54:40', 'Active'),
(24, 'Kumarakom', '2025-07-14 17:54:40', 'Active'),
(25, 'Laskshadweep', '2025-07-14 17:54:40', 'Active'),
(26, 'Maldives', '2025-07-14 17:54:40', 'Active'),
(27, 'Mauritius', '2025-07-14 17:54:40', 'Active'),
(28, 'Mumbai', '2025-07-14 17:54:40', 'Active'),
(29, 'Munnar', '2025-07-14 17:54:40', 'Active'),
(30, 'Mysore', '2025-07-14 17:54:40', 'Active'),
(31, 'Ooty', '2025-07-14 17:54:40', 'Active'),
(32, 'Pondicherry', '2025-07-14 17:54:40', 'Active'),
(33, 'Seychelles', '2025-07-14 17:54:40', 'Active'),
(34, 'Shimla', '2025-07-14 17:54:40', 'Active'),
(35, 'Sikkim', '2025-07-14 17:54:40', 'Active'),
(36, 'Srilanka', '2025-07-14 17:54:40', 'Active'),
(37, 'Thailand', '2025-07-14 17:54:40', 'Active'),
(38, 'Thekkady', '2025-07-14 17:54:40', 'Active'),
(39, 'Turkey', '2025-07-14 17:54:40', 'Active'),
(40, 'Udaipur', '2025-07-14 17:54:40', 'Active'),
(41, 'Vagamon', '2025-07-14 17:54:40', 'Active'),
(42, 'Varanasi', '2025-07-14 17:54:40', 'Active'),
(43, 'Wayanad', '2025-07-14 17:54:40', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL,
  `lead_number` varchar(50) NOT NULL,
  `received_datetime` datetime NOT NULL,
  `attended_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  `ad_campaign_id` int(11) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `social_media_link` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enquiry_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `lead_number`, `received_datetime`, `attended_by`, `department_id`, `source_id`, `ad_campaign_id`, `referral_code`, `customer_name`, `mobile_number`, `social_media_link`, `email`, `status_id`, `last_updated`, `created_at`, `enquiry_type`) VALUES
(66, 'GHE/2025/07/0040', '2025-07-29 10:50:03', 1, 3, 11, NULL, 'tick', 'tick', '8610056926', NULL, 'dfjsk@hbfd.com', 3, '2025-08-01 19:25:29', '2025-07-29 05:20:03', 'Medical Tourism Enquiry'),
(67, 'GHE/2025/08/0001', '2025-08-02 01:07:55', 1, 1, 2, 1, 'REF123', 'John Doe', '1234567890', 'https://facebook.com/johndoe', 'john@example.com', 3, '2025-08-01 19:37:55', '2025-08-01 19:37:55', 'Family Tour Package'),
(68, 'GHE/2025/08/0002', '2025-08-02 01:08:15', 1, 3, 15, NULL, 'sd', 'sdf', '08610056926', 'sad', 'srikumariphone1@gmail.com', 3, '2025-08-01 19:58:35', '2025-08-01 19:38:15', 'Just Hotel Booking Enquiry');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_status`
--

CREATE TABLE `enquiry_status` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiry_status`
--

INSERT INTO `enquiry_status` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Hot Prospect - Pipeline', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(2, 'Prospect - Quote given', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(3, 'Prospect - Attended', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(4, 'Prospect - Awaiting Rate from Agent', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(5, 'Neutral Prospect - In Discussion', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(6, 'Future Hot Prospect - Quote Given (with delay)', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(7, 'Future Prospect - Postponed', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(8, 'Call Back - Call Back Scheduled', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(9, 'Re-Opened - Re-Engaged Lead', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(10, 'Re-Assigned - Transferred Lead', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(11, 'Not Connected - No Response', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(12, 'Not Interested - Cancelled', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(13, 'Junk - Junk', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(14, 'Duplicate - Duplicate', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(15, 'Closed – Booked', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(16, 'Change Request – Active Amendment', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15'),
(17, 'Booking Value - Sale Amount', 'Active', '2025-07-28 18:01:15', '2025-07-28 18:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_types`
--

CREATE TABLE `enquiry_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiry_types`
--

INSERT INTO `enquiry_types` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Advertisement Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(2, 'Budget Travel Request', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(3, 'Collaboration', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(4, 'Corporate Tour Request', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(5, 'Cruise Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(6, 'Cruise Plan (Lakshadweep)', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(7, 'DMCs', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(8, 'Early Bird Offer Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(9, 'Family Tour Package', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(10, 'Flight + Hotel Combo Request', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(11, 'Group Tour Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(12, 'Honeymoon Package Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(13, 'Job Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(14, 'Just Hotel Booking Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(15, 'Luxury Travel Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(16, 'Medical Tourism Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(17, 'Need Train + Bus Tickets', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(18, 'Only Tickets', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(19, 'Religious Tour Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(20, 'School / College Tour Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(21, 'Sightseeing Only Request', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(22, 'Solo Travel Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(23, 'Sponsorship', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(24, 'Ticket Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(25, 'Travel Insurance Required', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(26, 'Visa Assistance Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(27, 'Vloggers / Influencers', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11'),
(28, 'Weekend Getaway Enquiry', 'Active', '2025-07-28 17:50:11', '2025-07-28 17:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `extras_details`
--

CREATE TABLE `extras_details` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `extras_details` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `adult_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `kids_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `file_number` varchar(50) NOT NULL,
  `booking_date` datetime NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `trip_start` date NOT NULL,
  `trip_end` date NOT NULL,
  `destinations` text DEFAULT NULL,
  `adults` int(11) DEFAULT NULL,
  `children` int(11) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `lead_id`, `file_number`, `booking_date`, `customer_name`, `mobile_number`, `email`, `trip_start`, `trip_end`, `destinations`, `adults`, `children`, `budget`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 10, 'FILE-682F12FF7F38B', '2025-05-22 17:35:19', 'Srikumar', '8610056296', 'srikuar@gmail.com', '2025-05-22', '2025-05-29', 'Singapore', 1, 2, 150000.00, 'Active', NULL, 1, '2025-05-22 17:35:19'),
(2, 7, 'FILE-684171FC01ADF', '2025-06-05 16:01:24', 'Srikumar', '8610056296', NULL, '2025-06-05', '2025-06-12', NULL, NULL, NULL, NULL, 'Active', NULL, 1, '2025-06-05 16:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_details`
--

CREATE TABLE `hospital_details` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `hospital_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `lead_number` varchar(20) NOT NULL,
  `received_datetime` datetime NOT NULL,
  `attended_datetime` datetime DEFAULT NULL,
  `time_difference` varchar(50) DEFAULT NULL,
  `attended_by` int(11) DEFAULT NULL,
  `market_category_id` int(11) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `ad_id` int(11) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `social_media_link` varchar(255) DEFAULT NULL,
  `status` enum('Not interested','Not picking call','No response','Converted','Sales') DEFAULT 'No response',
  `comments` text DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `enquiry_number` varchar(20) DEFAULT NULL,
  `ad_campaign_id` int(11) DEFAULT NULL,
  `file_manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_comments`
--

CREATE TABLE `lead_comments` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_details`
--

CREATE TABLE `lead_details` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `customer_location` text DEFAULT NULL,
  `secondary_contact` varchar(20) DEFAULT NULL,
  `destination` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `travel_month` varchar(20) DEFAULT NULL,
  `travel_period` varchar(50) DEFAULT NULL,
  `day_night` varchar(20) DEFAULT NULL,
  `adults_count` int(11) DEFAULT NULL,
  `children_count` int(11) DEFAULT NULL,
  `infants_count` int(11) DEFAULT NULL,
  `available_timing` time DEFAULT NULL,
  `file_manager` text DEFAULT NULL,
  `enquiry_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_status`
--

CREATE TABLE `lead_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_status`
--

INSERT INTO `lead_status` (`id`, `name`, `created_at`, `status`) VALUES
(1, 'Data Mismatch', '2025-07-16 05:11:13', 'Active'),
(2, 'Disappearing Messages on', '2025-07-16 05:11:13', 'Active'),
(3, 'Converted', '2025-07-16 05:11:13', 'Active'),
(4, 'Discussion Ongoing', '2025-07-16 05:11:13', 'Active'),
(5, 'Invalid', '2025-07-16 05:11:13', 'Active'),
(6, 'Last-Minute Booking', '2025-07-16 05:11:13', 'Active'),
(7, 'Lost to Competitors', '2025-07-16 05:11:13', 'Active'),
(8, 'No Incoming Calls', '2025-07-16 05:11:13', 'Active'),
(9, 'No Response', '2025-07-16 05:11:13', 'Active'),
(10, 'Not Interested', '2025-07-16 05:11:13', 'Active'),
(11, 'Package Shared', '2025-07-16 05:11:13', 'Active'),
(12, 'Plan Dropped', '2025-07-16 05:11:13', 'Active'),
(13, 'Postponed', '2025-07-16 05:11:13', 'Active'),
(14, 'Reschedule Request', '2025-07-16 05:11:13', 'Active'),
(15, 'Switched Off', '2025-07-16 05:11:13', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `lead_status_change_log`
--

CREATE TABLE `lead_status_change_log` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `old_status` varchar(100) DEFAULT NULL,
  `new_status` varchar(100) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_status_map`
--

CREATE TABLE `lead_status_map` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_reason` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_status_map`
--

INSERT INTO `lead_status_map` (`id`, `enquiry_id`, `status_name`, `created_at`, `last_reason`) VALUES
(3, 29, 'Prospect - Attended', '2025-06-18 10:44:14', NULL),
(11, 33, 'Prospect - Awaiting Rate from Agent', '2025-06-21 05:10:34', NULL),
(18, 32, 'Closed – Booked', '2025-06-24 16:54:35', NULL),
(23, 30, 'Closed – Booked', '2025-06-30 16:55:34', NULL),
(41, 34, 'Closed – Booked', '2025-07-09 19:23:22', NULL),
(42, 52, 'Closed – Booked', '2025-07-16 15:46:18', NULL),
(43, 53, 'Closed – Booked', '2025-07-16 15:48:06', NULL),
(44, 57, 'Closed – Booked', '2025-07-17 17:35:37', NULL),
(49, 56, 'Closed – Booked', '2025-07-19 18:17:50', NULL),
(54, 55, 'Not Connected - No Response', '2025-07-19 19:53:55', 'Not Interested'),
(55, 54, 'Closed – Booked', '2025-07-19 20:16:20', NULL),
(64, 61, 'Closed – Booked', '2025-07-23 03:21:09', '');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_data`
--

CREATE TABLE `marketing_data` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `campaign_date` date DEFAULT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `amount_spent` decimal(10,2) NOT NULL,
  `impressions` int(11) NOT NULL,
  `cpm` decimal(10,2) NOT NULL,
  `reach` int(11) NOT NULL,
  `link_clicks` int(11) NOT NULL,
  `cpc` decimal(10,2) NOT NULL,
  `results` int(11) NOT NULL,
  `cost_per_result` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_data`
--

INSERT INTO `marketing_data` (`id`, `file_id`, `campaign_date`, `campaign_name`, `amount_spent`, `impressions`, `cpm`, `reach`, `link_clicks`, `cpc`, `results`, `cost_per_result`) VALUES
(1, 1, '2025-04-25', 'test1', 15000.00, 10000, 10000.00, 5000, 50, 15.00, 13, 13.00),
(2, 1, '2025-04-26', 'test2', 20000.00, 15000, 12000.00, 7500, 75, 18.00, 20, 15.00),
(3, 1, '2025-04-27', 'test3', 10000.00, 8000, 8000.00, 4000, 40, 12.00, 10, 10.00),
(4, 2, '2025-04-25', 'test1', 15000.00, 10000, 10000.00, 5000, 50, 15.00, 13, 13.00),
(5, 2, '2025-04-26', 'test2', 20000.00, 15000, 12000.00, 7500, 75, 18.00, 20, 15.00),
(6, 2, '2025-04-27', 'test3', 10000.00, 8000, 8000.00, 4000, 40, 12.00, 10, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `marketing_files`
--

CREATE TABLE `marketing_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_files`
--

INSERT INTO `marketing_files` (`id`, `file_name`, `uploaded_by`, `upload_date`) VALUES
(1, 'marketing_data_sample (3).csv', 1, '2025-06-16 18:22:16'),
(2, 'marketing_data_sample (4).csv', 1, '2025-07-15 22:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `market_categories`
--

CREATE TABLE `market_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_categories`
--

INSERT INTO `market_categories` (`id`, `name`) VALUES
(18, 'Adventure'),
(17, 'Cruise'),
(3, 'Domestic'),
(1, 'inbound'),
(6, 'Inbound domatic'),
(4, 'Inbound GCC'),
(5, 'Inbound-medical'),
(16, 'International'),
(8, 'Not provided'),
(2, 'Outbound'),
(7, 'Outbound arab');

-- --------------------------------------------------------

--
-- Table structure for table `new_ads`
--

CREATE TABLE `new_ads` (
  `id` int(11) NOT NULL,
  `ad_platform` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `ad_name` varchar(100) NOT NULL,
  `planned_days` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `lifetime_budget` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `new_ads`
--

INSERT INTO `new_ads` (`id`, `ad_platform`, `department`, `ad_name`, `planned_days`, `start_date`, `end_date`, `lifetime_budget`, `created_at`, `created_by`, `is_active`) VALUES
(9, 'INS001', 'Domestic', 'Facebook', 10, '2025-05-28', '2025-06-12', 10000.00, '2025-05-28 17:08:54', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `night_day`
--

CREATE TABLE `night_day` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `night_day`
--

INSERT INTO `night_day` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, '1N/2D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(2, '2N/3D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(3, '3N/4D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(4, '4N/5D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(5, '5N/6D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(6, '6N/7D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(7, '7N/8D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(8, '8N/9D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(9, '9N/10D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(10, '10N/11D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(11, '11N/12D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(12, '12N/13D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(13, '13N/14D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25'),
(14, '14N/15D', 'Active', '2025-07-28 17:53:25', '2025-07-28 17:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `number_sequences`
--

CREATE TABLE `number_sequences` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `number_sequences`
--

INSERT INTO `number_sequences` (`id`, `type`, `year`, `month`, `last_number`) VALUES
(1, 'cost_sheet', 2025, 6, 41),
(2, 'enquiry', 2025, 6, 25),
(3, 'lead', 2025, 6, 0),
(4, 'enquiry', 2025, 7, 40),
(5, 'lead', 2025, 7, 10),
(6, 'cost_sheet', 2025, 7, 143),
(7, 'enquiry', 2025, 8, 2),
(8, 'lead', 2025, 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `cost_file_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_bank` varchar(100) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `cost_file_id`, `payment_date`, `payment_bank`, `payment_amount`, `payment_receipt`, `created_at`, `updated_at`) VALUES
(4, 13, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-21 18:55:01', '2025-07-21 18:55:01'),
(5, 14, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-21 19:09:30', '2025-07-21 19:09:30'),
(6, 15, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:14:17', '2025-07-23 16:14:17'),
(7, 16, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:14:24', '2025-07-23 16:14:24'),
(8, 17, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:25:36', '2025-07-23 16:25:36'),
(9, 19, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:46:42', '2025-07-23 16:46:42'),
(10, 20, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:55:42', '2025-07-23 16:55:42'),
(11, 21, '2025-07-22', 'HDFC BANK', 5000.00, 'uploads/receipts/1753124101_1751378977.png', '2025-07-23 16:56:27', '2025-07-23 16:56:27');

-- --------------------------------------------------------

--
-- Table structure for table `referral_codes`
--

CREATE TABLE `referral_codes` (
  `id` int(11) NOT NULL,
  `referral_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2025-06-14 18:54:58'),
(2, 'Master Admin', '2025-06-14 18:54:58'),
(3, 'General Manager', '2025-06-14 18:54:58'),
(4, 'Product Manager', '2025-06-17 19:50:38'),
(5, 'Product Team', '2025-06-17 19:50:38'),
(6, 'Marketing Manager', '2025-06-17 19:50:38'),
(7, 'Marketing Team', '2025-06-17 19:50:38'),
(8, 'Lead Manager', '2025-06-17 19:50:38'),
(9, 'Lead Team', '2025-06-17 19:50:38'),
(10, 'Tele Sales', '2025-06-17 19:50:38'),
(11, 'Sales Manager', '2025-06-17 19:50:38'),
(12, 'Sales Team', '2025-06-17 19:50:38'),
(13, 'Operation Manager', '2025-06-17 19:50:38'),
(14, 'Reservation Team', '2025-06-17 19:50:38'),
(15, 'Operation Support Team', '2025-06-17 19:50:38'),
(16, 'Accounts Manager', '2025-06-17 19:50:38'),
(17, 'Accounts Team', '2025-06-17 19:50:38'),
(18, 'HR Manager', '2025-07-17 15:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `role_menu`
--

CREATE TABLE `role_menu` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `menu_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_menu`
--

INSERT INTO `role_menu` (`id`, `role_name`, `menu_id`) VALUES
(81, 'admin', 'ads'),
(78, 'admin', 'all_leads'),
(76, 'admin', 'dashboard'),
(86, 'admin', 'department_report'),
(79, 'admin', 'enquiries'),
(80, 'admin', 'files'),
(77, 'admin', 'leads'),
(85, 'admin', 'main_report'),
(82, 'admin', 'marketing_upload'),
(83, 'admin', 'users'),
(84, 'admin', 'user_privileges'),
(92, 'sales', 'all_leads'),
(91, 'sales', 'dashboard'),
(93, 'sales', 'enquiries'),
(94, 'sales', 'sales');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `enquiry_number` varchar(20) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `destination` text DEFAULT NULL,
  `travel_period` varchar(50) DEFAULT NULL,
  `adults_count` int(11) DEFAULT 0,
  `children_count` int(11) DEFAULT 0,
  `infants_count` int(11) DEFAULT 0,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sequential_numbers`
--

CREATE TABLE `sequential_numbers` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE `sources` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sources`
--

INSERT INTO `sources` (`id`, `name`, `created_at`, `status`) VALUES
(1, 'Agent', '2025-06-14 18:54:59', 'Active'),
(2, 'Direct Call', '2025-06-14 18:54:59', 'Active'),
(3, 'Facebook', '2025-06-14 18:54:59', 'Active'),
(4, 'Instagram', '2025-06-14 18:54:59', 'Active'),
(5, 'Email', '2025-06-14 18:54:59', 'Active'),
(6, 'Meta Ad- Instagram', '2025-06-14 20:12:22', 'Active'),
(7, 'Meta Ad- WhatsApp', '2025-06-14 20:12:22', 'Active'),
(8, 'Meta Ad-Lead form', '2025-06-14 20:12:22', 'Active'),
(9, 'Old Data', '2025-06-14 20:12:22', 'Active'),
(10, 'Pinterest', '2025-06-14 20:12:22', 'Active'),
(11, 'Referral', '2025-06-14 20:12:22', 'Active'),
(12, 'Snapchat', '2025-06-14 20:12:22', 'Active'),
(13, 'Website Lead', '2025-06-14 20:12:22', 'Active'),
(14, 'Whatsapp', '2025-06-14 20:12:22', 'Active'),
(15, 'Youtube', '2025-06-14 20:12:22', 'Active'),
(16, 'LinkedIn', '2025-06-17 15:49:41', 'Active'),
(17, 'Existing Customer', '2025-06-18 11:00:15', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `status_change_log`
--

CREATE TABLE `status_change_log` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `old_status_id` int(11) DEFAULT NULL,
  `new_status_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_change_log`
--

INSERT INTO `status_change_log` (`id`, `enquiry_id`, `old_status_id`, `new_status_id`, `changed_by`, `changed_at`) VALUES
(1, 65, 14, 14, 1, '2025-07-27 18:18:49'),
(2, 65, 14, 14, 1, '2025-07-27 18:19:01'),
(3, 65, 14, 14, 1, '2025-07-27 18:19:48'),
(4, 65, 14, 14, 1, '2025-07-27 18:22:04'),
(5, 65, 15, 15, 1, '2025-07-27 18:30:15'),
(6, 65, 15, 15, 1, '2025-07-27 18:32:54'),
(7, 65, NULL, 15, 1, '2025-07-27 18:34:54'),
(8, 65, NULL, 15, 1, '2025-07-27 18:39:09'),
(9, 65, NULL, 15, 1, '2025-07-27 18:41:08'),
(10, 65, NULL, 15, 1, '2025-07-27 18:42:55'),
(11, 65, NULL, 15, 1, '2025-07-27 18:44:25'),
(12, 65, NULL, 15, 1, '2025-07-27 18:47:49'),
(13, 65, NULL, 15, 1, '2025-07-27 18:49:11'),
(14, 65, NULL, 15, 1, '2025-07-27 18:51:04'),
(15, 65, NULL, 15, 1, '2025-07-27 18:51:16'),
(16, 65, NULL, 15, 1, '2025-07-27 18:52:24'),
(17, 65, NULL, 15, 1, '2025-07-27 18:53:58'),
(18, 65, NULL, 15, 1, '2025-07-27 18:55:36'),
(19, 65, NULL, 1, 1, '2025-07-27 18:57:17'),
(20, 65, NULL, 9, 1, '2025-07-27 18:58:36'),
(21, 65, NULL, 9, 1, '2025-07-27 19:03:25'),
(22, 65, NULL, 9, 1, '2025-07-27 19:03:40'),
(23, 65, NULL, 9, 1, '2025-07-27 19:07:26'),
(24, 65, NULL, 9, 1, '2025-07-27 19:08:44'),
(25, 65, NULL, 9, 1, '2025-07-27 19:09:01'),
(26, 65, NULL, 9, 1, '2025-07-27 19:11:09'),
(27, 65, NULL, 9, 1, '2025-07-27 19:12:42'),
(28, 65, NULL, 5, 1, '2025-07-27 19:14:29'),
(29, 65, NULL, 5, 1, '2025-07-27 19:18:42'),
(30, 65, NULL, 5, 1, '2025-07-27 19:20:24'),
(31, 65, NULL, 4, 1, '2025-07-27 19:21:56'),
(32, 65, NULL, 3, 1, '2025-07-27 19:25:39'),
(33, 56, NULL, 3, 1, '2025-07-29 05:14:59'),
(34, 65, NULL, 3, 1, '2025-07-29 05:15:22'),
(35, 65, NULL, 3, 1, '2025-07-29 05:17:10'),
(36, 66, NULL, 12, 1, '2025-07-29 05:20:11'),
(37, 66, NULL, 12, 1, '2025-07-29 05:21:05'),
(38, 66, NULL, 12, 1, '2025-07-29 05:21:55'),
(39, 66, NULL, 12, 1, '2025-07-29 05:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `tour_costings`
--

CREATE TABLE `tour_costings` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `cost_sheet_number` varchar(50) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_address` text DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `tour_package` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `nationality` varchar(10) DEFAULT NULL,
  `selected_services` text DEFAULT NULL,
  `visa_data` text DEFAULT NULL,
  `accommodation_data` text DEFAULT NULL,
  `transportation_data` text DEFAULT NULL,
  `cruise_data` text DEFAULT NULL,
  `extras_data` text DEFAULT NULL,
  `agent_package_data` text DEFAULT NULL,
  `payment_data` text DEFAULT NULL,
  `total_expense` decimal(10,2) DEFAULT 0.00,
  `markup_percentage` decimal(5,2) DEFAULT 0.00,
  `markup_amount` decimal(10,2) DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 18.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `package_cost` decimal(10,2) DEFAULT 0.00,
  `currency_rate` decimal(10,4) DEFAULT 1.0000,
  `converted_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adults_count` int(11) DEFAULT 0,
  `children_count` int(11) DEFAULT 0,
  `infants_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tour_costings`
--

INSERT INTO `tour_costings` (`id`, `enquiry_id`, `cost_sheet_number`, `guest_name`, `guest_address`, `whatsapp_number`, `tour_package`, `currency`, `nationality`, `selected_services`, `visa_data`, `accommodation_data`, `transportation_data`, `cruise_data`, `extras_data`, `agent_package_data`, `payment_data`, `total_expense`, `markup_percentage`, `markup_amount`, `tax_percentage`, `tax_amount`, `package_cost`, `currency_rate`, `converted_amount`, `created_at`, `updated_at`, `adults_count`, `children_count`, `infants_count`) VALUES
(1, 53, 'GHL/2025/07/0103-S1', 'srikumar', 'bangalore', '8610056926', '', 'USD', 'BH', '[\"accommodation\",\"transportation\",\"cruise_hire\"]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"Anadaman\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-22\",\"check_out\":\"2025-07-16\",\"room_type\":\"Triple Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"1\",\"extra_adult_rate\":\"100\",\"extra_child_no\":\"10\",\"extra_child_rate\":\"1100\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"12100.00\"}]', '[{\"supplier\":\"jdhf;\",\"car_type\":\"Mini Bus\",\"daily_rent\":\"1\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"1\",\"price_per_km\":\"100\",\"toll\":\"1\",\"total\":\"102.00\"}]', '[{\"supplier\":\"1000\",\"boat_type\":\"Cutter\",\"cruise_type\":\"Themed Cruise\",\"check_in\":\"2025-07-20T00:58\",\"check_out\":\"2025-07-20T00:58\",\"rate\":\"1\",\"extra\":\"100\",\"total\":\"101.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"0.00\",\"balance_amount\":\"12303.00\"}', 12303.00, 0.00, 0.00, 0.00, 0.00, 12303.00, 0.0000, 0.00, '2025-07-19 19:29:32', '2025-07-19 19:29:32', 0, 0, 0),
(2, 56, 'GHL/2025/07/0105-S1', 'sri kumar', '', '', '', 'USD', '', '[]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"\",\"hotel\":\"\",\"check_in\":\"2025-07-12\",\"check_out\":\"2025-09-11\",\"room_type\":\"\",\"rooms_no\":\"0\",\"rooms_rate\":\"0\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"0\",\"meal_plan\":\"\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"car_type\":\"\",\"daily_rent\":\"0\",\"days\":\"\",\"km\":\"0\",\"extra_km\":\"0\",\"price_per_km\":\"0\",\"toll\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"\",\"check_out\":\"\",\"rate\":\"0\",\"extra\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"\",\"balance_amount\":\"\"}', 0.00, 0.00, 0.00, 18.00, 0.00, 0.00, 0.0000, 0.00, '2025-07-19 19:34:42', '2025-07-19 19:51:51', 0, 0, 0),
(3, 53, 'GHL/2025/07/0103-S2', 'sri', 'bangalore', '8610056926', '', 'USD', '', '[\"accommodation\",\"transportation\",\"cruise_hire\"]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"Anadaman\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-22\",\"check_out\":\"2025-07-16\",\"room_type\":\"\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"1\",\"extra_adult_rate\":\"100\",\"extra_child_no\":\"10\",\"extra_child_rate\":\"1100\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"12100.00\"}]', '[{\"supplier\":\"jdhf;\",\"car_type\":\"Mini Bus\",\"daily_rent\":\"1\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"1\",\"price_per_km\":\"100\",\"toll\":\"1\",\"total\":\"102.00\"}]', '[{\"supplier\":\"1000\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-20T00:58\",\"check_out\":\"2025-07-20T00:58\",\"rate\":\"1\",\"extra\":\"100\",\"total\":\"101.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"0.00\",\"balance_amount\":\"12303.00\"}', 12303.00, 0.00, 0.00, 0.00, 0.00, 12303.00, 0.0000, 12303.00, '2025-07-19 19:35:03', '2025-07-19 19:35:03', 0, 0, 0),
(4, 56, 'GHL/2025/07/0105-S2', 'sri kumar', '', '', '', 'USD', '', '[]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"\",\"hotel\":\"\",\"check_in\":\"2025-07-12\",\"check_out\":\"2025-09-11\",\"room_type\":\"\",\"rooms_no\":\"0\",\"rooms_rate\":\"0\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"0\",\"meal_plan\":\"\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"car_type\":\"\",\"daily_rent\":\"0\",\"days\":\"\",\"km\":\"0\",\"extra_km\":\"0\",\"price_per_km\":\"0\",\"toll\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"\",\"check_out\":\"\",\"rate\":\"0\",\"extra\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"\",\"balance_amount\":\"\"}', 0.00, 0.00, 0.00, 18.00, 0.00, 0.00, 0.0000, 0.00, '2025-07-19 19:35:16', '2025-07-19 19:51:51', 0, 0, 0),
(8, 53, 'GHL/2025/07/0103-S3', 'sri', 'bangalore', '8610056926', '', 'USD', '', '[\"accommodation\",\"transportation\",\"cruise_hire\"]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"Anadaman\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-22\",\"check_out\":\"2025-07-16\",\"room_type\":\"\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"1\",\"extra_adult_rate\":\"100\",\"extra_child_no\":\"10\",\"extra_child_rate\":\"1100\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"12100.00\"}]', '[{\"supplier\":\"jdhf;\",\"car_type\":\"Mini Bus\",\"daily_rent\":\"1\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"1\",\"price_per_km\":\"100\",\"toll\":\"1\",\"total\":\"102.00\"}]', '[{\"supplier\":\"1000\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-20T00:58\",\"check_out\":\"2025-07-20T00:58\",\"rate\":\"1\",\"extra\":\"100\",\"total\":\"101.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-21\",\"bank\":\"ICICI BANK\",\"amount\":\"1000\",\"total_received\":\"1000.00\",\"balance_amount\":\"11303.00\"}', 12303.00, 0.00, 0.00, 0.00, 0.00, 12303.00, 0.0000, 12303.00, '2025-07-19 20:21:22', '2025-07-19 20:21:22', 0, 0, 0),
(12, 54, 'GHL/2025/07/0121-S1', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"Speedboat \\/ Powerboat\",\"cruise_type\":\"Ocean Cruise\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"0.00\",\"balance_amount\":\"5000.00\",\"receipt\":null}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-21 18:03:24', '2025-07-21 18:35:34', 0, 0, 0),
(13, 54, 'GHL/2025/07/0121-S2', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-22\",\"bank\":\"HDFC BANK\",\"amount\":\"5000\",\"total_received\":\"5000.00\",\"balance_amount\":\"0.00\",\"receipt\":\"uploads\\/receipts\\/1753124101_1751378977.png\"}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-21 18:55:01', '2025-07-21 18:55:01', 0, 0, 0),
(14, 54, 'GHL/2025/07/0121-S3', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-22\",\"bank\":\"HDFC BANK\",\"amount\":\"5000\",\"total_received\":\"5000.00\",\"balance_amount\":\"0.00\",\"receipt\":\"uploads\\/receipts\\/1753124101_1751378977.png\"}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-21 19:09:30', '2025-07-21 19:09:30', 0, 0, 0),
(15, 54, 'GHL/2025/07/0121-S4', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-22\",\"bank\":\"HDFC BANK\",\"amount\":\"5000\",\"total_received\":\"5000.00\",\"balance_amount\":\"0.00\",\"receipt\":\"uploads\\/receipts\\/1753124101_1751378977.png\"}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:14:17', '2025-07-23 16:14:17', 0, 0, 0),
(16, 54, 'GHL/2025/07/0121-S5', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-22\",\"bank\":\"HDFC BANK\",\"amount\":\"5000\",\"total_received\":\"5000.00\",\"balance_amount\":\"0.00\",\"receipt\":\"uploads\\/receipts\\/1753124101_1751378977.png\"}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:14:24', '2025-07-23 16:14:24', 0, 0, 0),
(17, 54, 'GHL/2025/07/0121-S6', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', NULL, '{\"date\":\"2025-07-22\",\"bank\":\"HDFC BANK\",\"amount\":\"5000\",\"total_received\":\"5000.00\",\"balance_amount\":\"0.00\",\"receipt\":\"uploads\\/receipts\\/1753124101_1751378977.png\"}', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:25:36', '2025-07-23 16:25:36', 0, 0, 0),
(18, 61, 'GHL/2025/07/0140-S1', 'srikumar', 'bangalore', '8610056926', '', 'RM', 'DZ', '[\"agent_package\"]', '[{\"sector\":\"\",\"supplier\":\"\",\"travel_date\":\"\",\"passengers\":\"0\",\"rate_per_person\":\"0\",\"roe\":\"\",\"total\":\"\"}]', '[{\"destination\":\"\",\"hotel\":\"\",\"check_in\":\"\",\"check_out\":\"\",\"room_type\":\"\",\"rooms_no\":\"0\",\"rooms_rate\":\"0\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"0\",\"meal_plan\":\"\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"car_type\":\"\",\"daily_rent\":\"0\",\"days\":\"\",\"km\":\"0\",\"extra_km\":\"0\",\"price_per_km\":\"0\",\"toll\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"\",\"check_out\":\"\",\"rate\":\"0\",\"extra\":\"0\",\"total\":\"\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', '[{\"destination\":\"\",\"agent_supplier\":\"\",\"start_date\":\"\",\"end_date\":\"\",\"adult_count\":\"\",\"adult_price\":\"0\",\"child_count\":\"\",\"child_price\":\"0\",\"infant_count\":\"\",\"infant_price\":\"0\",\"total\":\"\"}]', '{\"date\":\"\",\"bank\":\"\",\"amount\":\"\",\"total_received\":\"0.00\",\"balance_amount\":\"400.00\",\"receipt\":null}', 0.00, 0.00, 400.00, 18.00, 0.00, 400.00, 0.0000, 0.00, '2025-07-23 16:38:12', '2025-07-23 16:57:35', 0, 0, 0),
(19, 54, 'GHL/2025/07/0121-S7', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', '[]', '0', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:46:42', '2025-07-23 16:46:42', 0, 0, 0),
(20, 54, 'GHL/2025/07/0121-S8', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"extras\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', '[]', '0', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:55:42', '2025-07-23 16:55:42', 0, 0, 0),
(21, 54, 'GHL/2025/07/0121-S9', 'payment-pdf', 'bangalore', '8610056926', '', 'INR', 'IN', '[\"visa_flight\",\"accommodation\",\"transportation\",\"cruise_hire\",\"agent_package\"]', '[{\"sector\":\"SECTOR\",\"supplier\":\"SUPPLIER\",\"travel_date\":\"2025-07-21\",\"passengers\":\"1\",\"rate_per_person\":\"1000\",\"roe\":\"10\",\"total\":\"1000.00\"}]', '[{\"destination\":\"Agra\",\"hotel\":\"ABAAM CHELSEA\",\"check_in\":\"2025-07-20\",\"check_out\":\"2025-07-22\",\"room_type\":\"Single Room\",\"rooms_no\":\"1\",\"rooms_rate\":\"1000\",\"extra_adult_no\":\"0\",\"extra_adult_rate\":\"0\",\"extra_child_no\":\"0\",\"extra_child_rate\":\"0\",\"child_no_bed_no\":\"0\",\"child_no_bed_rate\":\"0\",\"nights\":\"1\",\"meal_plan\":\"Room Only\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"car_type\":\"Sedan\",\"daily_rent\":\"1000\",\"days\":\"1\",\"km\":\"1000\",\"extra_km\":\"100\",\"price_per_km\":\"10\",\"toll\":\"0\",\"total\":\"2000.00\"}]', '[{\"supplier\":\"SUPPLIER\",\"boat_type\":\"\",\"cruise_type\":\"\",\"check_in\":\"2025-07-21T23:32\",\"check_out\":\"2025-07-22T23:32\",\"rate\":\"1000\",\"extra\":\"\",\"total\":\"1000.00\"}]', '[{\"supplier\":\"\",\"service_type\":\"\",\"amount\":\"0\",\"extras\":\"0\",\"total\":\"\"}]', '[{\"destination\":\"Kashmir\",\"agent_supplier\":\"test\",\"start_date\":\"2025-07-23\",\"end_date\":\"2025-07-29\",\"adult_count\":\"1\",\"adult_price\":\"150\",\"child_count\":\"1\",\"child_price\":\"150\",\"infant_count\":\"1\",\"infant_price\":\"100\",\"total\":\"400.00\"}]', '0', 5000.00, 0.00, 0.00, 18.00, 0.00, 5000.00, 0.0000, 0.00, '2025-07-23 16:56:27', '2025-07-23 16:56:27', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tour_costing_extras`
--

CREATE TABLE `tour_costing_extras` (
  `id` int(11) NOT NULL,
  `sheet_id` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `extras` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_costing_hotels`
--

CREATE TABLE `tour_costing_hotels` (
  `id` int(11) NOT NULL,
  `sheet_id` int(11) DEFAULT NULL,
  `hotel_name` varchar(100) DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `room_count` int(11) DEFAULT 1,
  `room_rate` decimal(10,2) DEFAULT 0.00,
  `nights` int(11) DEFAULT 1,
  `extra_bed_count` int(11) DEFAULT 0,
  `extra_bed_rate` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_costing_houseboat`
--

CREATE TABLE `tour_costing_houseboat` (
  `id` int(11) NOT NULL,
  `sheet_id` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `boat_type` varchar(50) DEFAULT NULL,
  `cruise_type` varchar(50) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT 0.00,
  `extra` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_costing_sheets`
--

CREATE TABLE `tour_costing_sheets` (
  `id` int(11) NOT NULL,
  `sheet_number` varchar(20) DEFAULT NULL,
  `file_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `travel_agent` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `total_pax` int(11) DEFAULT 0,
  `adults` int(11) DEFAULT 0,
  `children` int(11) DEFAULT 0,
  `infants` int(11) DEFAULT 0,
  `meal_plan` varchar(50) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `vehicle_details` text DEFAULT NULL,
  `handling_officer` varchar(100) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT 0.00,
  `markup` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `package_cost` decimal(10,2) DEFAULT 0.00,
  `usd_rate` decimal(10,2) DEFAULT 55.00,
  `usd_amount` decimal(10,2) DEFAULT 0.00,
  `local_currency` varchar(10) DEFAULT 'SAR',
  `local_rate` decimal(10,2) DEFAULT 14.50,
  `local_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_costing_transport`
--

CREATE TABLE `tour_costing_transport` (
  `id` int(11) NOT NULL,
  `sheet_id` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `car_type` varchar(50) DEFAULT NULL,
  `daily_rent` decimal(10,2) DEFAULT 0.00,
  `days` int(11) DEFAULT 0,
  `total` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `driver_batta_rate` decimal(10,2) DEFAULT 0.00,
  `driver_batta_nights` int(11) DEFAULT 0,
  `driver_batta_total` decimal(10,2) DEFAULT 0.00,
  `extra_km_rate` decimal(10,2) DEFAULT 0.00,
  `extra_km` int(11) DEFAULT 0,
  `extra_km_total` decimal(10,2) DEFAULT 0.00,
  `toll_parking` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_details`
--

CREATE TABLE `transport_details` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `vehicle` varchar(255) NOT NULL,
  `daily_rent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_details`
--

INSERT INTO `transport_details` (`id`, `destination`, `company_name`, `contact_person`, `mobile`, `email`, `vehicle`, `daily_rent`, `rate_per_km`, `status`, `created_at`, `updated_at`) VALUES
(1, 'test', 'test', 'test', '8610056292', 'srikumarbe97@gmail.com', 'SUV', 100.00, 10.00, 'Active', '2025-07-28 16:13:58', '2025-07-28 16:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `travel_agents`
--

CREATE TABLE `travel_agents` (
  `id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `agent_type` enum('Domestic','Outbound') NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role_id`, `created_at`, `profile_image`) VALUES
(1, 'admin', '$2y$10$DJ8eBTFz8n6ThT7r6/pBG.wAU0dt4rMRXx9IwhbUjm4pG9xMvhQnm', 'Administrator', 'admin@example.com', 1, '2025-06-14 18:54:59', 'assets/images/profiles/1.jpg'),
(2, 'sales', '$2y$10$C1tPRmtVpzr.F6S7i5y.9.mUDi7QuE59kFhVjkKSPbaE/BQDQW6im', 'sales', 'sales@gleesire.in', 1, '2025-06-14 18:59:08', NULL),
(3, 'sri', '$2y$10$x6uVNkpZtJ1tjTqQPNLV8unCdHjb4pHCu7gQuHn.x1Dw3Y/ghJIq6', 'sri', 'srikumarbe97@gmail.com', 11, '2025-06-14 18:59:52', 'assets/images/profiles/3.jpg'),
(4, 'madhu', '$2y$10$NUNc86jiL22alzw8pwb2WuIHKQDNit1CuD2ywYoRQpAm5cSABRh1.', 'madhukumar', 'madhu@gmail.com', 13, '2025-06-16 10:19:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_login_logs`
--

CREATE TABLE `user_login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime DEFAULT NULL,
  `session_duration` int(11) DEFAULT 0,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_login_logs`
--

INSERT INTO `user_login_logs` (`id`, `user_id`, `login_time`, `logout_time`, `session_duration`, `date`, `created_at`) VALUES
(1, 1, '2025-08-02 02:46:52', '2025-08-04 20:23:43', 236211, '2025-08-02', '2025-08-01 21:16:52'),
(2, 3, '2025-08-02 02:47:49', '2025-08-02 02:48:42', 53, '2025-08-02', '2025-08-01 21:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_menu_permissions`
--

CREATE TABLE `user_menu_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_menu_permissions`
--

INSERT INTO `user_menu_permissions` (`id`, `user_id`, `menu_id`, `created_at`) VALUES
(1, 19, 1, '2025-06-03 19:22:16'),
(2, 19, 2, '2025-06-03 19:22:16'),
(3, 19, 3, '2025-06-03 19:22:16'),
(4, 20, 1, '2025-06-05 03:48:38'),
(5, 20, 2, '2025-06-05 03:48:38'),
(6, 20, 3, '2025-06-05 03:48:38'),
(7, 20, 4, '2025-06-05 03:48:38'),
(8, 20, 5, '2025-06-05 03:48:38'),
(9, 20, 6, '2025-06-05 03:48:38'),
(10, 20, 8, '2025-06-05 03:48:38'),
(11, 20, 9, '2025-06-05 03:48:38'),
(12, 20, 11, '2025-06-05 03:48:38'),
(13, 20, 12, '2025-06-05 03:48:38'),
(14, 20, 13, '2025-06-05 03:48:38'),
(15, 20, 14, '2025-06-05 03:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_privileges`
--

CREATE TABLE `user_privileges` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_name` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_privileges`
--

INSERT INTO `user_privileges` (`id`, `role_id`, `menu_name`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES
(109, 2, 'dashboard', 1, 1, 1, 0, '2025-06-14 21:10:51'),
(110, 2, 'upload_enquiries', 1, 1, 1, 0, '2025-06-14 21:10:51'),
(111, 2, 'view_enquiries', 1, 1, 1, 0, '2025-06-14 21:10:51'),
(112, 2, 'view_leads', 1, 1, 1, 0, '2025-06-14 21:10:51'),
(113, 2, 'booking_confirmed', 0, 0, 0, 0, '2025-06-14 21:10:51'),
(114, 2, 'ad_campaign', 0, 0, 0, 0, '2025-06-14 21:10:51'),
(115, 3, 'dashboard', 1, 1, 1, 1, '2025-06-16 10:20:18'),
(116, 3, 'upload_enquiries', 1, 1, 1, 1, '2025-06-16 10:20:18'),
(117, 3, 'view_enquiries', 1, 1, 1, 1, '2025-06-16 10:20:18'),
(118, 3, 'view_leads', 1, 1, 1, 1, '2025-06-16 10:20:18'),
(119, 3, 'booking_confirmed', 0, 0, 0, 0, '2025-06-16 10:20:18'),
(120, 3, 'ad_campaign', 0, 0, 0, 0, '2025-06-16 10:20:18'),
(147, 17, 'dashboard', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(148, 17, 'upload_enquiries', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(149, 17, 'view_enquiries', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(150, 17, 'edit_enquiry', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(151, 17, 'delete_enquiry', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(152, 17, 'view_leads', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(153, 17, 'pipeline', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(154, 17, 'booking_confirmed', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(155, 17, 'move_to_confirmed', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(156, 17, 'move_to_leads', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(157, 17, 'cost_sheet', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(158, 17, 'new_cost_file', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(159, 17, 'view_cost_sheets', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(160, 17, 'edit_cost_file', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(161, 17, 'export_cost_sheet', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(162, 17, 'upload_marketing_data', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(163, 17, 'view_marketing_data', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(164, 17, 'ad_campaign', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(165, 17, 'department_report', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(166, 17, 'source_report', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(167, 17, 'add_user', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(168, 17, 'edit_user', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(169, 17, 'user_privileges', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(170, 17, 'profile', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(171, 17, 'comments', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(172, 17, 'search_results', 0, 0, 0, 0, '2025-07-09 18:48:43'),
(173, 13, 'dashboard', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(174, 13, 'upload_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(175, 13, 'view_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(176, 13, 'job_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(177, 13, 'ticket_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(178, 13, 'influencer_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(179, 13, 'dmc_agent_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(180, 13, 'cruise_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(181, 13, 'no_response_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(182, 13, 'follow_up_enquiries', 1, 1, 1, 1, '2025-07-17 17:03:24'),
(183, 13, 'view_leads', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(184, 13, 'fixed_package_lead', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(185, 13, 'custom_package_leads', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(186, 13, 'medical_tourism_leads', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(187, 13, 'lost_to_competitors', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(188, 13, 'no_response_leads', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(189, 13, 'follow_up_leads', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(190, 13, 'pipeline', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(191, 13, 'booking_confirmed', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(192, 13, 'travel_completed', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(193, 13, 'view_cost_sheets', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(194, 13, 'feedbacks', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(195, 13, 'hotel_resorts', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(196, 13, 'cruise_reservation', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(197, 13, 'visa_air_ticket', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(198, 13, 'transportation_reservation', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(199, 13, 'upload_marketing_data', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(200, 13, 'ad_campaign', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(201, 13, 'summary_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(202, 13, 'daily_movement_register', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(203, 13, 'user_activity_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(204, 13, 'department_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(205, 13, 'source_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(206, 13, 'user_performance_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(207, 13, 'package_performance_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(208, 13, 'marketing_performance_report', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(209, 13, 'transportation_details', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(210, 13, 'accommodation_details', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(211, 13, 'cruise_details', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(212, 13, 'extras_miscellaneous_details', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(213, 13, 'add_user', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(214, 13, 'user_privileges', 0, 0, 0, 0, '2025-07-17 17:03:24'),
(215, 1, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(216, 2, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(217, 3, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(218, 4, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(219, 5, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(220, 6, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(221, 7, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(222, 8, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(223, 9, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(224, 10, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(225, 11, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(227, 14, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(228, 15, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(229, 16, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(230, 17, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(231, 18, 'transportation_details', 1, 1, 1, 1, '2025-07-28 16:14:26'),
(232, 1, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(233, 2, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(234, 3, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(235, 4, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(236, 5, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(237, 6, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(238, 7, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(239, 8, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(240, 9, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(241, 10, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(242, 11, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(244, 14, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(245, 15, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(246, 16, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(247, 17, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(248, 18, 'accommodation_details', 1, 1, 1, 1, '2025-07-28 16:23:11'),
(249, 1, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(250, 2, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(251, 3, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(252, 4, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(253, 5, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(254, 6, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(255, 7, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(256, 8, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(257, 9, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(258, 10, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(259, 11, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(261, 14, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(262, 15, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(263, 16, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(264, 17, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(265, 18, 'cruise_details', 1, 1, 1, 1, '2025-07-28 16:47:59'),
(266, 1, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:33'),
(267, 2, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:33'),
(268, 3, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:33'),
(269, 4, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:33'),
(270, 5, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(271, 6, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(272, 7, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(273, 8, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(274, 9, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(275, 10, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(276, 11, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(278, 13, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(279, 14, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(280, 15, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(281, 16, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(282, 17, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(283, 18, 'referral_code', 1, 1, 1, 1, '2025-07-28 17:32:34'),
(284, 1, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(285, 2, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(286, 3, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(287, 4, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(288, 5, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(289, 6, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(290, 7, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(291, 8, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(292, 9, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(293, 10, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(294, 11, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(296, 13, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(297, 14, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(298, 15, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(299, 16, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(300, 17, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(301, 18, 'source_channel', 1, 1, 1, 1, '2025-07-28 17:32:43'),
(302, 1, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(303, 2, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(304, 3, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(305, 4, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(306, 5, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(307, 6, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(308, 7, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(309, 8, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(310, 9, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(311, 10, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(312, 11, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(314, 13, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(315, 14, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(316, 15, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(317, 16, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(318, 17, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(319, 18, 'hospital_details', 1, 1, 1, 1, '2025-07-28 17:32:54'),
(320, 1, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(321, 2, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(322, 3, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(323, 4, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(324, 5, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(325, 6, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(326, 7, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(327, 8, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(328, 9, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(329, 10, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(330, 11, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(332, 13, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(333, 14, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(334, 15, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(335, 16, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(336, 17, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(337, 18, 'travel_agents', 1, 1, 1, 1, '2025-07-28 17:33:03'),
(338, 1, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(339, 2, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(340, 3, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(341, 4, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(342, 5, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(343, 6, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(344, 7, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(345, 8, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(346, 9, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(347, 10, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(348, 11, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(350, 14, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(351, 15, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(352, 16, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(353, 17, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(354, 18, 'extras_miscellaneous_details', 1, 1, 1, 1, '2025-07-28 17:33:14'),
(355, 1, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(356, 2, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(357, 3, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(358, 4, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(359, 5, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(360, 6, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(361, 7, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(362, 8, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(363, 9, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(364, 10, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(365, 11, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(367, 13, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(368, 14, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(369, 15, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(370, 16, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(371, 17, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(372, 18, 'destinations', 1, 1, 1, 1, '2025-07-28 17:40:25'),
(373, 1, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(374, 2, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(375, 3, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(376, 4, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(377, 5, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(378, 6, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(379, 7, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(380, 8, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(381, 9, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(382, 10, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(383, 11, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(385, 13, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(386, 14, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(387, 15, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(388, 16, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(389, 17, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(390, 18, 'lead_status', 1, 1, 1, 1, '2025-07-28 17:43:50'),
(391, 1, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(392, 2, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(393, 3, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(394, 4, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(395, 5, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(396, 6, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(397, 7, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(398, 8, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(399, 9, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(400, 10, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(401, 11, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(403, 13, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(404, 14, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(405, 15, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(406, 16, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(407, 17, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(408, 18, 'enquiry_type', 1, 1, 1, 1, '2025-07-28 17:50:11'),
(409, 1, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(410, 2, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(411, 3, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(412, 4, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(413, 5, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(414, 6, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(415, 7, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(416, 8, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(417, 9, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(418, 10, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(419, 11, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(421, 13, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(422, 14, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(423, 15, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(424, 16, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(425, 17, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(426, 18, 'night_day', 1, 1, 1, 1, '2025-07-28 17:53:25'),
(427, 1, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(428, 2, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(429, 3, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(430, 4, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(431, 5, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(432, 6, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(433, 7, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(434, 8, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(435, 9, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(436, 10, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(437, 11, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(439, 13, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(440, 14, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(441, 15, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(442, 16, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(443, 17, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(444, 18, 'enquiry_status', 1, 1, 1, 1, '2025-07-28 18:01:15'),
(445, 12, 'dashboard', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(446, 12, 'upload_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(447, 12, 'view_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(448, 12, 'job_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(449, 12, 'ticket_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(450, 12, 'influencer_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(451, 12, 'dmc_agent_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(452, 12, 'cruise_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(453, 12, 'no_response_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(454, 12, 'follow_up_enquiries', 1, 1, 1, 1, '2025-08-01 19:23:19'),
(455, 12, 'view_leads', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(456, 12, 'fixed_package_lead', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(457, 12, 'custom_package_leads', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(458, 12, 'medical_tourism_leads', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(459, 12, 'lost_to_competitors', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(460, 12, 'no_response_leads', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(461, 12, 'follow_up_leads', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(462, 12, 'pipeline', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(463, 12, 'booking_confirmed', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(464, 12, 'travel_completed', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(465, 12, 'view_cost_sheets', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(466, 12, 'view_payment_receipts', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(467, 12, 'feedbacks', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(468, 12, 'hotel_resorts', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(469, 12, 'cruise_reservation', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(470, 12, 'visa_air_ticket', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(471, 12, 'transportation_reservation', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(472, 12, 'upload_marketing_data', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(473, 12, 'ad_campaign', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(474, 12, 'summary_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(475, 12, 'daily_movement_register', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(476, 12, 'user_activity_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(477, 12, 'department_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(478, 12, 'source_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(479, 12, 'user_performance_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(480, 12, 'package_performance_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(481, 12, 'marketing_performance_report', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(482, 12, 'transportation_details', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(483, 12, 'accommodation_details', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(484, 12, 'cruise_details', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(485, 12, 'extras_miscellaneous_details', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(486, 12, 'add_user', 0, 0, 0, 0, '2025-08-01 19:23:19'),
(487, 12, 'user_privileges', 0, 0, 0, 0, '2025-08-01 19:23:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodation_details`
--
ALTER TABLE `accommodation_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments_history`
--
ALTER TABLE `comments_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comment_attachments`
--
ALTER TABLE `comment_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `converted_leads`
--
ALTER TABLE `converted_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `file_manager_id` (`file_manager_id`);

--
-- Indexes for table `cost_sheets`
--
ALTER TABLE `cost_sheets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cruise_details`
--
ALTER TABLE `cruise_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lead_number` (`lead_number`),
  ADD KEY `attended_by` (`attended_by`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `source_id` (`source_id`),
  ADD KEY `ad_campaign_id` (`ad_campaign_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `enquiry_status`
--
ALTER TABLE `enquiry_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enquiry_types`
--
ALTER TABLE `enquiry_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `extras_details`
--
ALTER TABLE `extras_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_number` (`file_number`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `hospital_details`
--
ALTER TABLE `hospital_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lead_number` (`lead_number`),
  ADD KEY `attended_by` (`attended_by`),
  ADD KEY `fk_market_category` (`market_category_id`),
  ADD KEY `fk_source` (`source_id`),
  ADD KEY `fk_ad` (`ad_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `fk_ad_campaign` (`ad_campaign_id`),
  ADD KEY `fk_leads_file_manager` (`file_manager_id`);

--
-- Indexes for table `lead_comments`
--
ALTER TABLE `lead_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lead_details`
--
ALTER TABLE `lead_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `lead_status`
--
ALTER TABLE `lead_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_status_change_log`
--
ALTER TABLE `lead_status_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enquiry_id` (`enquiry_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `lead_status_map`
--
ALTER TABLE `lead_status_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enquiry_id` (`enquiry_id`);

--
-- Indexes for table `marketing_data`
--
ALTER TABLE `marketing_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marketing_files`
--
ALTER TABLE `marketing_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `market_categories`
--
ALTER TABLE `market_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `new_ads`
--
ALTER TABLE `new_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `night_day`
--
ALTER TABLE `night_day`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `number_sequences`
--
ALTER TABLE `number_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sequence` (`type`,`year`,`month`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cost_file_id` (`cost_file_id`);

--
-- Indexes for table `referral_codes`
--
ALTER TABLE `referral_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_menu`
--
ALTER TABLE `role_menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_menu_unique` (`role_name`,`menu_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `sequential_numbers`
--
ALTER TABLE `sequential_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_year_month` (`type`,`year`,`month`);

--
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_change_log`
--
ALTER TABLE `status_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enquiry_id` (`enquiry_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `tour_costings`
--
ALTER TABLE `tour_costings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enquiry_id` (`enquiry_id`);

--
-- Indexes for table `tour_costing_extras`
--
ALTER TABLE `tour_costing_extras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sheet_id` (`sheet_id`);

--
-- Indexes for table `tour_costing_hotels`
--
ALTER TABLE `tour_costing_hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sheet_id` (`sheet_id`);

--
-- Indexes for table `tour_costing_houseboat`
--
ALTER TABLE `tour_costing_houseboat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sheet_id` (`sheet_id`);

--
-- Indexes for table `tour_costing_sheets`
--
ALTER TABLE `tour_costing_sheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sheet_number` (`sheet_number`);

--
-- Indexes for table `tour_costing_transport`
--
ALTER TABLE `tour_costing_transport`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sheet_id` (`sheet_id`);

--
-- Indexes for table `transport_details`
--
ALTER TABLE `transport_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `travel_agents`
--
ALTER TABLE `travel_agents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_menu_permissions`
--
ALTER TABLE `user_menu_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`menu_id`);

--
-- Indexes for table `user_privileges`
--
ALTER TABLE `user_privileges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodation_details`
--
ALTER TABLE `accommodation_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `comments_history`
--
ALTER TABLE `comments_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_attachments`
--
ALTER TABLE `comment_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `converted_leads`
--
ALTER TABLE `converted_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `cost_sheets`
--
ALTER TABLE `cost_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cruise_details`
--
ALTER TABLE `cruise_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `enquiry_status`
--
ALTER TABLE `enquiry_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `enquiry_types`
--
ALTER TABLE `enquiry_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `extras_details`
--
ALTER TABLE `extras_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hospital_details`
--
ALTER TABLE `hospital_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `lead_comments`
--
ALTER TABLE `lead_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `lead_details`
--
ALTER TABLE `lead_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lead_status`
--
ALTER TABLE `lead_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=302;

--
-- AUTO_INCREMENT for table `lead_status_change_log`
--
ALTER TABLE `lead_status_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_status_map`
--
ALTER TABLE `lead_status_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `marketing_data`
--
ALTER TABLE `marketing_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `marketing_files`
--
ALTER TABLE `marketing_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `market_categories`
--
ALTER TABLE `market_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `new_ads`
--
ALTER TABLE `new_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `night_day`
--
ALTER TABLE `night_day`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `number_sequences`
--
ALTER TABLE `number_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `referral_codes`
--
ALTER TABLE `referral_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `role_menu`
--
ALTER TABLE `role_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sequential_numbers`
--
ALTER TABLE `sequential_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sources`
--
ALTER TABLE `sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `status_change_log`
--
ALTER TABLE `status_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tour_costings`
--
ALTER TABLE `tour_costings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tour_costing_extras`
--
ALTER TABLE `tour_costing_extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_costing_hotels`
--
ALTER TABLE `tour_costing_hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_costing_houseboat`
--
ALTER TABLE `tour_costing_houseboat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_costing_sheets`
--
ALTER TABLE `tour_costing_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_costing_transport`
--
ALTER TABLE `tour_costing_transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_details`
--
ALTER TABLE `transport_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `travel_agents`
--
ALTER TABLE `travel_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_menu_permissions`
--
ALTER TABLE `user_menu_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_privileges`
--
ALTER TABLE `user_privileges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=488;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  ADD CONSTRAINT `ad_campaigns_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments_history`
--
ALTER TABLE `comments_history`
  ADD CONSTRAINT `comments_history_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `converted_leads`
--
ALTER TABLE `converted_leads`
  ADD CONSTRAINT `converted_leads_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`),
  ADD CONSTRAINT `converted_leads_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  ADD CONSTRAINT `converted_leads_ibfk_3` FOREIGN KEY (`file_manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD CONSTRAINT `enquiries_ibfk_1` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enquiries_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `enquiries_ibfk_3` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`),
  ADD CONSTRAINT `enquiries_ibfk_4` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`),
  ADD CONSTRAINT `enquiries_ibfk_5` FOREIGN KEY (`status_id`) REFERENCES `lead_status` (`id`);

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `fk_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`),
  ADD CONSTRAINT `fk_ad_campaign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ads` (`id`),
  ADD CONSTRAINT `fk_leads_file_manager` FOREIGN KEY (`file_manager_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_market_category` FOREIGN KEY (`market_category_id`) REFERENCES `market_categories` (`id`),
  ADD CONSTRAINT `fk_source` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`),
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`market_category_id`) REFERENCES `market_categories` (`id`),
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`),
  ADD CONSTRAINT `leads_ibfk_4` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`),
  ADD CONSTRAINT `leads_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_comments`
--
ALTER TABLE `lead_comments`
  ADD CONSTRAINT `lead_comments_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`),
  ADD CONSTRAINT `lead_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_details`
--
ALTER TABLE `lead_details`
  ADD CONSTRAINT `lead_details_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `new_ads`
--
ALTER TABLE `new_ads`
  ADD CONSTRAINT `new_ads_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`cost_file_id`) REFERENCES `tour_costings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`);

--
-- Constraints for table `tour_costing_extras`
--
ALTER TABLE `tour_costing_extras`
  ADD CONSTRAINT `tour_costing_extras_ibfk_1` FOREIGN KEY (`sheet_id`) REFERENCES `tour_costing_sheets` (`id`);

--
-- Constraints for table `tour_costing_hotels`
--
ALTER TABLE `tour_costing_hotels`
  ADD CONSTRAINT `tour_costing_hotels_ibfk_1` FOREIGN KEY (`sheet_id`) REFERENCES `tour_costing_sheets` (`id`);

--
-- Constraints for table `tour_costing_houseboat`
--
ALTER TABLE `tour_costing_houseboat`
  ADD CONSTRAINT `tour_costing_houseboat_ibfk_1` FOREIGN KEY (`sheet_id`) REFERENCES `tour_costing_sheets` (`id`);

--
-- Constraints for table `tour_costing_transport`
--
ALTER TABLE `tour_costing_transport`
  ADD CONSTRAINT `tour_costing_transport_ibfk_1` FOREIGN KEY (`sheet_id`) REFERENCES `tour_costing_sheets` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  ADD CONSTRAINT `user_login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_menu_permissions`
--
ALTER TABLE `user_menu_permissions`
  ADD CONSTRAINT `user_menu_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_privileges`
--
ALTER TABLE `user_privileges`
  ADD CONSTRAINT `user_privileges_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
