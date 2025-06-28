-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2025 at 07:51 PM
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
(2, 'InstaAd', 'Instagram', 7, 10, 1000.00, '2025-06-16', '2025-06-17', 'inactive', '2025-06-16 10:17:47');

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
(10, 0, 1, 'Hi', '2025-06-15 08:10:45');

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
  `travel_month` date DEFAULT NULL,
  `travel_start_date` date DEFAULT NULL,
  `travel_end_date` date DEFAULT NULL,
  `adults_count` int(11) DEFAULT NULL,
  `children_count` int(11) DEFAULT NULL,
  `infants_count` int(11) DEFAULT NULL,
  `customer_available_timing` varchar(100) DEFAULT NULL,
  `file_manager_id` int(11) DEFAULT NULL,
  `booking_confirmed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `converted_leads`
--

INSERT INTO `converted_leads` (`id`, `enquiry_id`, `enquiry_number`, `lead_type`, `customer_location`, `secondary_contact`, `destination_id`, `other_details`, `travel_month`, `travel_start_date`, `travel_end_date`, `adults_count`, `children_count`, `infants_count`, `customer_available_timing`, `file_manager_id`, `booking_confirmed`, `created_at`) VALUES
(12, 29, 'GH 2691', 'Hot', 'surulipatti', '798465354684', 11, NULL, '0000-00-00', '0000-00-00', '2025-06-30', 1, 0, 0, NULL, 1, 1, '2025-06-16 16:25:48'),
(13, 31, 'GH 9086', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-06-16 17:22:04'),
(14, 32, 'GH 4437', 'Hot', 'cumbum', '9876543210', 13, NULL, '0000-00-00', '0000-00-00', '2025-06-24', 1, 1, 0, NULL, 3, 0, '2025-06-17 15:24:58'),
(15, 33, 'GH 3274', 'Warm', 'wf', '988751426', 2, NULL, '0000-00-00', '0000-00-00', '2025-06-19', 1, 1, 0, NULL, NULL, 0, '2025-06-17 18:07:25');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `created_at`) VALUES
(1, 'AbuDhabi', '2025-06-14 18:54:59'),
(2, 'Agartala', '2025-06-14 20:19:49'),
(3, 'Agra', '2025-06-14 20:21:13'),
(4, 'Ahmedabad', '2025-06-14 20:21:13'),
(5, 'Aizawl', '2025-06-14 20:21:13'),
(6, 'Ajmer', '2025-06-14 20:21:13'),
(7, 'Alappuzha', '2025-06-14 20:21:13'),
(8, 'Allahabad', '2025-06-14 20:21:13'),
(9, 'Amritsar', '2025-06-14 20:21:13'),
(10, 'Andhra Pradesh', '2025-06-14 20:21:13'),
(11, 'Araku Valley', '2025-06-14 20:21:13'),
(12, 'Arunachal Pradesh', '2025-06-14 20:21:13'),
(13, 'Assam', '2025-06-14 20:21:13'),
(14, 'Athirapally', '2025-06-14 20:21:13'),
(15, 'Aurangabad', '2025-06-14 20:21:13'),
(16, 'Azherbaijan', '2025-06-14 20:21:13'),
(17, 'Baga', '2025-06-14 20:21:13'),
(18, 'Bali', '2025-06-14 20:21:13'),
(19, 'Bangkok', '2025-06-14 20:21:13'),
(20, 'Bastar', '2025-06-14 20:21:13'),
(21, 'Belonia', '2025-06-14 20:21:13'),
(22, 'Bengaluru', '2025-06-14 20:21:13'),
(23, 'Bhopal', '2025-06-14 20:21:13'),
(24, 'Bhubaneswar', '2025-06-14 20:21:13'),
(25, 'Bihar', '2025-06-14 20:21:13'),
(26, 'Bilaspur', '2025-06-14 20:21:13'),
(27, 'Bodh Gaya', '2025-06-14 20:21:13'),
(28, 'Bokaro', '2025-06-14 20:21:13'),
(29, 'Bomdila', '2025-06-14 20:21:13'),
(30, 'Calangute', '2025-06-14 20:21:13'),
(31, 'Champhai', '2025-06-14 20:21:13'),
(32, 'Chandigarh', '2025-06-14 20:21:13'),
(33, 'Chennai', '2025-06-14 20:21:13'),
(34, 'Cherrapunji', '2025-06-14 20:21:13'),
(35, 'Chhattisgarh', '2025-06-14 20:21:13'),
(36, 'Churachandpur', '2025-06-14 20:21:13'),
(37, 'Coimbatore', '2025-06-14 20:21:13'),
(38, 'Cooch Behar', '2025-06-14 20:21:13'),
(39, 'Coorg', '2025-06-14 20:21:13'),
(40, 'Cuttack', '2025-06-14 20:21:13'),
(41, 'Darjeeling', '2025-06-14 20:21:13'),
(42, 'Dehradun', '2025-06-14 20:21:13'),
(43, 'Deoghar', '2025-06-14 20:21:13'),
(44, 'Dhanbad', '2025-06-14 20:21:13'),
(45, 'Dharamshala', '2025-06-14 20:21:13'),
(46, 'Dharmanagar', '2025-06-14 20:21:13'),
(47, 'Dibrugarh', '2025-06-14 20:21:13'),
(48, 'Dimapur', '2025-06-14 20:21:13'),
(49, 'Doha', '2025-06-14 20:21:13'),
(50, 'Dubai', '2025-06-14 20:21:13'),
(51, 'Dwarka', '2025-06-14 20:21:13'),
(52, 'Faridabad', '2025-06-14 20:21:13'),
(53, 'Gandhinagar', '2025-06-14 20:21:13'),
(54, 'Gangtok', '2025-06-14 20:21:13'),
(55, 'Gaya', '2025-06-14 20:21:13'),
(56, 'Georgia', '2025-06-14 20:21:13'),
(57, 'Goa', '2025-06-14 20:21:13'),
(58, 'Gujarat', '2025-06-14 20:21:13'),
(59, 'Gulmarg', '2025-06-14 20:21:13'),
(60, 'Gurgaon', '2025-06-14 20:21:13'),
(61, 'Guwahati', '2025-06-14 20:21:13'),
(62, 'Hampi', '2025-06-14 20:21:13'),
(63, 'Haridwar', '2025-06-14 20:21:13'),
(64, 'Haryana', '2025-06-14 20:21:13'),
(65, 'Himachal Pradesh', '2025-06-14 20:21:13'),
(66, 'Hyderabad', '2025-06-14 20:21:13'),
(67, 'Imphal', '2025-06-14 20:21:13'),
(68, 'Indore', '2025-06-14 20:21:13'),
(69, 'Itanagar', '2025-06-14 20:21:13'),
(70, 'Jabalpur', '2025-06-14 20:21:13'),
(71, 'Jagdalpur', '2025-06-14 20:21:13'),
(72, 'Jaipur', '2025-06-14 20:21:13'),
(73, 'Jaisalmer', '2025-06-14 20:21:13'),
(74, 'Jalandhar', '2025-06-14 20:21:13'),
(75, 'Jammu', '2025-06-14 20:21:13'),
(76, 'Jammu and Kashmir', '2025-06-14 20:21:13'),
(77, 'Jamshedpur', '2025-06-14 20:21:13'),
(78, 'Jharkhand', '2025-06-14 20:21:13'),
(79, 'Jodhpur', '2025-06-14 20:21:13'),
(80, 'Jorhat', '2025-06-14 20:21:13'),
(81, 'Jowai', '2025-06-14 20:21:13'),
(82, 'Kailashahar', '2025-06-14 20:21:13'),
(83, 'Kanker', '2025-06-14 20:21:13'),
(84, 'Kanpur', '2025-06-14 20:21:13'),
(85, 'Kanyakumari', '2025-06-14 20:21:13'),
(86, 'Kargil', '2025-06-14 20:21:13'),
(87, 'Karimnagar', '2025-06-14 20:21:13'),
(88, 'Karnal', '2025-06-14 20:21:13'),
(89, 'Karnataka', '2025-06-14 20:21:13'),
(90, 'Kaula Lampur', '2025-06-14 20:21:13'),
(91, 'Kazahkistan', '2025-06-14 20:21:13'),
(92, 'Kaziranga National Park', '2025-06-14 20:21:13'),
(93, 'Kerala', '2025-06-14 20:21:13'),
(94, 'Khajuraho', '2025-06-14 20:21:13'),
(95, 'Khammam', '2025-06-14 20:21:13'),
(96, 'Kochi', '2025-06-14 20:21:13'),
(97, 'Kodaikanal', '2025-06-14 20:21:13'),
(98, 'Kohima', '2025-06-14 20:21:13'),
(99, 'Kolasib', '2025-06-14 20:21:13'),
(100, 'Kolhapur', '2025-06-14 20:21:13'),
(101, 'Kolkata', '2025-06-14 20:21:13'),
(102, 'Konark', '2025-06-14 20:21:13'),
(103, 'Kozhikode', '2025-06-14 20:21:13'),
(104, 'Krabi', '2025-06-14 20:21:13'),
(105, 'Kullu', '2025-06-14 20:21:13'),
(106, 'Kumarakom', '2025-06-14 20:21:13'),
(107, 'Langkawi', '2025-06-14 20:21:13'),
(108, 'Leh', '2025-06-14 20:21:13'),
(109, 'Lonavala', '2025-06-14 20:21:13'),
(110, 'Lucknow', '2025-06-14 20:21:13'),
(111, 'Ludhiana', '2025-06-14 20:21:13'),
(112, 'Lunglei', '2025-06-14 20:21:13'),
(113, 'Madhya Pradesh', '2025-06-14 20:21:13'),
(114, 'Madurai', '2025-06-14 20:21:13'),
(115, 'Maharashtra', '2025-06-14 20:21:13'),
(116, 'Maldives', '2025-06-14 20:21:13'),
(117, 'Manali', '2025-06-14 20:21:13'),
(118, 'Manas National Park', '2025-06-14 20:21:13'),
(119, 'Mangaluru', '2025-06-14 20:21:13'),
(120, 'Mangan', '2025-06-14 20:21:13'),
(121, 'Manipur', '2025-06-14 20:21:13'),
(122, 'Margao', '2025-06-14 20:21:13'),
(123, 'Mauritius', '2025-06-14 20:21:13'),
(124, 'Meghalaya', '2025-06-14 20:21:13'),
(125, 'Mizoram', '2025-06-14 20:21:13'),
(126, 'Mokokchung', '2025-06-14 20:21:13'),
(127, 'Mon', '2025-06-14 20:21:13'),
(128, 'Mumbai', '2025-06-14 20:21:13'),
(129, 'Munnar', '2025-06-14 20:21:13'),
(130, 'Murshidabad', '2025-06-14 20:21:13'),
(131, 'Muscat', '2025-06-14 20:21:13'),
(132, 'Mussoorie', '2025-06-14 20:21:13'),
(133, 'Muzaffarpur', '2025-06-14 20:21:13'),
(134, 'Mysuru', '2025-06-14 20:21:13'),
(135, 'Nagaland', '2025-06-14 20:21:13'),
(136, 'Nagpur', '2025-06-14 20:21:13'),
(137, 'Nainital', '2025-06-14 20:21:13'),
(138, 'Nalanda', '2025-06-14 20:21:13'),
(139, 'Namchi', '2025-06-14 20:21:13'),
(140, 'Namdapha National Park', '2025-06-14 20:21:13'),
(141, 'Nizamabad', '2025-06-14 20:21:13'),
(142, 'Nongpoh', '2025-06-14 20:21:13'),
(143, 'Odisha', '2025-06-14 20:21:13'),
(144, 'Panaji', '2025-06-14 20:21:13'),
(145, 'Panipat', '2025-06-14 20:21:13'),
(146, 'Patiala', '2025-06-14 20:21:13'),
(147, 'Patna', '2025-06-14 20:21:13'),
(148, 'Pattaya', '2025-06-14 20:21:13'),
(149, 'Pelling', '2025-06-14 20:21:13'),
(150, 'Phuket', '2025-06-14 20:21:13'),
(151, 'Pune', '2025-06-14 20:21:13'),
(152, 'Punjab', '2025-06-14 20:21:13'),
(153, 'Puri', '2025-06-14 20:21:13'),
(154, 'Raipur', '2025-06-14 20:21:13'),
(155, 'Rajasthan', '2025-06-14 20:21:13'),
(156, 'Rajkot', '2025-06-14 20:21:13'),
(157, 'Rameshwaram', '2025-06-14 20:21:13'),
(158, 'Ranchi', '2025-06-14 20:21:13'),
(159, 'Ravangla', '2025-06-14 20:21:13'),
(160, 'Rishikesh', '2025-06-14 20:21:13'),
(161, 'Saiha', '2025-06-14 20:21:13'),
(162, 'Salem', '2025-06-14 20:21:13'),
(163, 'Sambalpur', '2025-06-14 20:21:13'),
(164, 'Senapati', '2025-06-14 20:21:13'),
(165, 'Seychelles', '2025-06-14 20:21:13'),
(166, 'Shillong', '2025-06-14 20:21:13'),
(167, 'Shimla', '2025-06-14 20:21:13'),
(168, 'Sikkim', '2025-06-14 20:21:13'),
(169, 'Silabari', '2025-06-14 20:21:13'),
(170, 'Singapore', '2025-06-14 20:21:13'),
(171, 'Solan', '2025-06-14 20:21:13'),
(172, 'Srikakulam', '2025-06-14 20:21:13'),
(173, 'Srilanka', '2025-06-14 20:21:13'),
(174, 'Srinagar', '2025-06-14 20:21:13'),
(175, 'Surat', '2025-06-14 20:21:13'),
(176, 'Tamenglong', '2025-06-14 20:21:13'),
(177, 'Tamil Nadu', '2025-06-14 20:21:13'),
(178, 'Tawang', '2025-06-14 20:21:13'),
(179, 'Telangana', '2025-06-14 20:21:13'),
(180, 'Thekkady', '2025-06-14 20:21:13'),
(181, 'Thiruvananthapuram', '2025-06-14 20:21:13'),
(182, 'Thrissur', '2025-06-14 20:21:13'),
(183, 'Tiruchirappalli', '2025-06-14 20:21:13'),
(184, 'Tirupati', '2025-06-14 20:21:13'),
(185, 'Tripura', '2025-06-14 20:21:13'),
(186, 'Tuensang', '2025-06-14 20:21:13'),
(187, 'Tura', '2025-06-14 20:21:13'),
(188, 'Turkey', '2025-06-14 20:21:13'),
(189, 'Udaipur', '2025-06-14 20:21:13'),
(190, 'Ujjain', '2025-06-14 20:21:13'),
(191, 'Ukhrul', '2025-06-14 20:21:13'),
(192, 'Uttar Pradesh', '2025-06-14 20:21:13'),
(193, 'Uttarakhand', '2025-06-14 20:21:13'),
(194, 'Vagamon', '2025-06-14 20:21:13'),
(195, 'Varanasi', '2025-06-14 20:21:13'),
(196, 'Varkala', '2025-06-14 20:21:13'),
(197, 'Vasco da Gama', '2025-06-14 20:21:13'),
(198, 'Vietman', '2025-06-14 20:21:13'),
(199, 'Vijayawada', '2025-06-14 20:21:13'),
(200, 'Visakhapatnam', '2025-06-14 20:21:13'),
(201, 'Warangal', '2025-06-14 20:21:13'),
(202, 'Wayanad', '2025-06-14 20:21:13'),
(203, 'West Bengal', '2025-06-14 20:21:13'),
(204, 'Ziro', '2025-06-14 20:21:13');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `lead_number`, `received_datetime`, `attended_by`, `department_id`, `source_id`, `ad_campaign_id`, `referral_code`, `customer_name`, `mobile_number`, `social_media_link`, `email`, `status_id`, `last_updated`, `created_at`) VALUES
(29, 'LGH-2025/06/16/6587', '2025-06-16 18:25:24', 4, 4, 2, 2, 'SRI1', 'madhu', '08610056926', 'https://web.whatsapp.com/', 'srikumarbe97@gmail.com', 3, '2025-06-16 16:25:48', '2025-06-16 16:25:24'),
(30, 'LGH-2025/06/16/0688', '2025-06-16 18:34:03', 1, 1, 2, 1, 'REF123', 'John Doe', '1234567890', 'https://facebook.com/johndoe', 'john@example.com', 1, '2025-06-16 16:34:03', '2025-06-16 16:34:03'),
(31, 'LGH-2025/06/16/5716', '2025-06-16 18:34:03', 1, 2, 1, NULL, '', 'Jane Smith', '9876543210', '', 'jane@example.com', 3, '2025-06-16 17:22:04', '2025-06-16 16:34:03'),
(32, 'LGH-2025/06/17/4788', '2025-06-17 17:24:58', 4, 1, 8, 2, 'SRI1', 'sibi', '08610056926', 'https://web.whatsapp.com/', 'srikumarbe97@gmail.com', 3, '2025-06-17 15:24:58', '2025-06-17 15:24:58'),
(33, 'LGH-2025/06/17/8782', '2025-06-17 20:07:25', 4, 2, 10, 1, 'SRI1', 'sibi sri', '08610056926', 'https://web.whatsapp.com/', 'srikumarbe97@gmail.com', 3, '2025-06-17 18:07:25', '2025-06-17 18:07:25');

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

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `lead_number`, `received_datetime`, `attended_datetime`, `time_difference`, `attended_by`, `market_category_id`, `source_id`, `ad_id`, `referral_code`, `customer_name`, `mobile_number`, `social_media_link`, `status`, `comments`, `last_updated`, `updated_by`, `enquiry_number`, `ad_campaign_id`, `file_manager_id`) VALUES
(7, 'LN-0001', '2025-04-28 21:51:51', '2025-04-28 21:51:51', '00:00:00', 3, 5, 10, NULL, '23', 'Srikumar', '8610056296', 'https://web.whatsapp.com/', '', NULL, '2025-06-05 10:31:24', 1, NULL, NULL, NULL),
(8, 'LN-0002', '2025-04-28 23:17:12', '2025-04-28 23:17:12', '00:00:00', 3, 2, 10, NULL, '23', 'Srikumar', '8610056296', 'https://web.whatsapp.com/', 'Not interested', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'LN-0003', '2025-04-28 23:26:28', '2025-04-28 23:26:28', '00:00:00', 3, 6, 4, NULL, '23', 'Srikumar', '8610056296', 'https://web.whatsapp.com/', 'Not picking call', NULL, '2025-04-28 21:53:13', NULL, NULL, NULL, NULL),
(10, 'LN-0004', '2025-04-30 18:28:59', '2025-04-30 18:28:59', '00:00:00', 3, 3, 12, NULL, '23', 'Srikumar', '8610056296', 'https://web.whatsapp.com/', '', NULL, '2025-05-22 12:05:19', 1, 'ENQ-20250504-00010', NULL, NULL),
(40, 'LN-0007', '2025-05-14 07:18:09', '2025-05-14 07:18:09', '00:00:00', 2, 3, 15, NULL, NULL, 'Jane Smith', '8765432109', NULL, 'Not interested', NULL, '2025-05-14 01:48:09', 1, NULL, NULL, NULL),
(53, 'LN-0008', '2025-05-25 07:48:50', '2025-05-25 07:48:50', '00:00:00', 3, 17, 15, NULL, NULL, 'Madhu', '975 465 6468', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
(54, 'LN-0009', '2025-05-25 08:00:15', '2025-05-25 08:00:15', '00:00:00', 3, 17, 2, NULL, NULL, 'Madhukumar', '975 465 6468', 'https://web.whatsapp.com/', 'No response', 'Status: cruize enquiry', NULL, NULL, NULL, NULL, NULL),
(55, 'LN-0010', '2025-05-25 08:05:01', '2025-05-25 08:05:01', '00:00:00', 3, 16, 6, NULL, NULL, 'Madhukumar1', '975 465 6468', 'https://web.whatsapp.com/', '', 'Status: cruize enquiry', NULL, NULL, NULL, NULL, NULL),
(56, 'LN-0011', '2025-05-25 08:12:22', '2025-05-25 08:12:22', '00:00:00', 3, NULL, NULL, NULL, NULL, 'Madhukumar2', '975 465 6468', 'https://web.whatsapp.com/', 'Converted', 'Status: cruize enquiry', '2025-06-05 07:19:26', 1, 'ENQ-20250528-00056', NULL, NULL);

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

--
-- Dumping data for table `lead_comments`
--

INSERT INTO `lead_comments` (`id`, `lead_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 7, 1, 'Test hi', '2025-04-28 20:43:54'),
(2, 7, 1, 'test', '2025-04-28 21:00:53'),
(3, 7, 1, 'Converted', '2025-04-28 21:14:25'),
(4, 7, 1, 'Converted', '2025-04-28 21:15:23'),
(7, 7, 1, 'test1', '2025-04-28 21:16:40'),
(9, 9, 1, 'Hi test', '2025-04-28 21:53:13'),
(10, 10, 1, 'test', '2025-05-01 13:47:12'),
(11, 10, 1, 'test1', '2025-05-01 13:47:24'),
(12, 10, 1, 'sri', '2025-05-03 20:23:35'),
(13, 10, 1, 'test', '2025-05-03 21:13:33'),
(14, 10, 1, 'Converted', '2025-05-03 21:14:06'),
(15, 10, 1, 'test', '2025-05-04 16:10:17'),
(16, 10, 1, 'df', '2025-05-04 16:46:53'),
(17, 10, 1, 'df', '2025-05-04 17:01:51'),
(18, 10, 1, 'Converted', '2025-05-14 05:16:16'),
(19, 56, 1, 'hi', '2025-05-27 17:51:46'),
(20, 56, 1, 'test', '2025-05-27 18:36:28'),
(21, 56, 1, 'hello', '2025-06-05 07:14:57'),
(22, 56, 1, 'Test1', '2025-06-05 07:19:26');

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

--
-- Dumping data for table `lead_details`
--

INSERT INTO `lead_details` (`id`, `lead_id`, `customer_location`, `secondary_contact`, `destination`, `others`, `travel_month`, `travel_period`, `day_night`, `adults_count`, `children_count`, `infants_count`, `available_timing`, `file_manager`, `enquiry_number`) VALUES
(1, 7, 'chennai', '798465354684', 'singapore', 'yesy', '2025-02', '05/12-10/12', '5D/4N', 1, 1, 1, '04:35:00', NULL, 'ENQ-0007'),
(3, 10, '', '', '', NULL, NULL, '', NULL, 0, 0, 0, NULL, NULL, NULL),
(4, 10, 'test', '98798465165', 'test', 'test', '2025-02', '05/12-10/12', '5D/4N\n', 1, 1, 0, '04:35:00', NULL, NULL),
(5, 10, '', '', '', NULL, NULL, '', NULL, 0, 0, 0, NULL, NULL, NULL),
(6, 10, '', '', '', NULL, NULL, '', NULL, 0, 0, 0, NULL, NULL, NULL),
(7, 10, 'wf', '9798795465', 'ewr', NULL, NULL, '05/12-10/12', NULL, 1, 1, 0, NULL, NULL, NULL),
(8, 10, 'wf', '9798795465', 'ewr', NULL, NULL, '05/12-10/12', NULL, 1, 0, 1, NULL, NULL, NULL),
(9, 10, 'wf', '9798795465', 'ewr', '', '2025-11', '05/12-10/12', NULL, 1, 1, 0, '22:37:00', '21', NULL),
(10, 10, '', '', '', '', '', '', NULL, 0, 0, 0, '00:00:00', '', NULL),
(11, 10, '', '', '', '', '', '', NULL, 0, 0, 0, '00:00:00', '', NULL),
(12, 10, 'wf', '9798795465', 'ewr', 'ewr', '', '05/12-10/12', NULL, 0, 0, 0, '00:00:00', '21', NULL),
(13, 10, 'test1', '9798795465', 'ewr', '', '', '05/12-10/12', NULL, 0, 0, 0, '00:00:00', '21', NULL),
(14, 56, '', '', '', '', '', '', NULL, 0, 0, 0, '00:00:00', '', NULL),
(15, 56, '', '', '', '', '', '', NULL, 0, 0, 0, '00:00:00', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lead_status`
--

CREATE TABLE `lead_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_status`
--

INSERT INTO `lead_status` (`id`, `name`, `created_at`) VALUES
(1, 'Advertisements', '2025-06-14 18:54:59'),
(2, 'Already Assigned', '2025-06-14 20:16:12'),
(3, 'Converted', '2025-06-14 20:16:25'),
(4, 'Collaboration', '2025-06-14 20:16:25'),
(5, 'Booked', '2025-06-14 20:16:12'),
(6, 'Cruise plan lakshadweep', '2025-06-14 20:16:25'),
(7, 'Cruize enquiry', '2025-06-14 20:16:25'),
(8, 'Disappearing messages on', '2025-06-14 20:16:25'),
(9, 'Discussion ongoing', '2025-06-14 20:16:25'),
(10, 'DMC\'s', '2025-06-14 20:16:25'),
(11, 'Fixed departures', '2025-06-14 20:16:25'),
(12, 'Invalid', '2025-06-14 20:16:25'),
(13, 'Job Enquiry', '2025-06-14 20:16:25'),
(14, 'Lost to competitors', '2025-06-14 20:16:25'),
(15, 'Mismatch', '2025-06-14 20:16:25'),
(16, 'Need train+bus tickets', '2025-06-14 20:16:25'),
(17, 'No incoming calls to this number', '2025-06-14 20:16:25'),
(18, 'No Response', '2025-06-14 20:16:25'),
(19, 'Not interested', '2025-06-14 20:16:25'),
(20, 'Only Tickets', '2025-06-14 20:16:25'),
(21, 'Package shared', '2025-06-14 20:16:25'),
(22, 'Plan dropped', '2025-06-14 20:16:25'),
(23, 'Postponed', '2025-06-14 20:16:25'),
(24, 'Sponsorship', '2025-06-14 20:16:25'),
(25, 'Switched off', '2025-06-14 20:16:25'),
(26, 'Ticket Enquiry', '2025-06-14 20:16:25'),
(27, 'Vloggers/Influencers', '2025-06-14 20:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `lead_status_map`
--

CREATE TABLE `lead_status_map` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_status_map`
--

INSERT INTO `lead_status_map` (`id`, `enquiry_id`, `status_name`, `created_at`) VALUES
(3, 29, 'Prospect - Attended', '2025-06-18 10:44:14'),
(11, 33, 'Prospect - Awaiting Rate from Agent', '2025-06-21 05:10:34'),
(18, 32, 'Closed – Booked', '2025-06-24 16:54:35');

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
(3, 1, '2025-04-27', 'test3', 10000.00, 8000, 8000.00, 4000, 40, 12.00, 10, 10.00);

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
(1, 'marketing_data_sample (3).csv', 1, '2025-06-16 18:22:16');

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
(1, 'cost_sheet', 2025, 6, 36),
(2, 'enquiry', 2025, 6, 6),
(3, 'lead', 2025, 6, 6);

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
(17, 'Accounts Team', '2025-06-17 19:50:38');

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

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `lead_id`, `enquiry_number`, `customer_name`, `mobile_number`, `destination`, `travel_period`, `adults_count`, `children_count`, `infants_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 'ENQ-0007', 'Srikumar', '8610056296', 'singapore', '05/12-10/12', 1, 1, 1, 'Pending', '2025-04-29 02:44:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE `sources` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sources`
--

INSERT INTO `sources` (`id`, `name`, `created_at`) VALUES
(1, 'Agent', '2025-06-14 18:54:59'),
(2, 'Direct Call', '2025-06-14 18:54:59'),
(3, 'Facebook', '2025-06-14 18:54:59'),
(4, 'Instagram', '2025-06-14 18:54:59'),
(5, 'Email', '2025-06-14 18:54:59'),
(6, 'Meta Ad- Instagram', '2025-06-14 20:12:22'),
(7, 'Meta Ad- WhatsApp', '2025-06-14 20:12:22'),
(8, 'Meta Ad-Lead form', '2025-06-14 20:12:22'),
(9, 'Old Data', '2025-06-14 20:12:22'),
(10, 'Pinterest', '2025-06-14 20:12:22'),
(11, 'Referral', '2025-06-14 20:12:22'),
(12, 'Snapchat', '2025-06-14 20:12:22'),
(13, 'Website Lead', '2025-06-14 20:12:22'),
(14, 'Whatsapp', '2025-06-14 20:12:22'),
(15, 'Youtube', '2025-06-14 20:12:22'),
(16, 'LinkedIn', '2025-06-17 15:49:41'),
(17, 'Existing Customer', '2025-06-18 11:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `tour_costings`
--

CREATE TABLE `tour_costings` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `sheet_number` varchar(50) NOT NULL,
  `costing_date` date NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `total_pax` int(11) NOT NULL DEFAULT 0,
  `adults` int(11) NOT NULL DEFAULT 0,
  `children` int(11) NOT NULL DEFAULT 0,
  `infants` int(11) NOT NULL DEFAULT 0,
  `arrival_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `hotel_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transport_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `extras_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sub_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mark_up` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `package_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costing_data` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tour_costings`
--

INSERT INTO `tour_costings` (`id`, `file_id`, `sheet_number`, `costing_date`, `guest_name`, `total_pax`, `adults`, `children`, `infants`, `arrival_date`, `departure_date`, `hotel_total`, `transport_total`, `extras_total`, `sub_total`, `mark_up`, `tax_percent`, `package_cost`, `costing_data`, `created_at`, `updated_at`) VALUES
(1, 1, '4404/COK/05', '2025-05-24', 'Srikumar', 2, 1, 1, 0, '2025-05-22', '2025-05-29', 0.00, 0.00, 0.00, 7000.00, 0.00, 0.00, 7000.00, '{\"file_id\":\"1\",\"sheet_number\":\"4404\\/COK\\/05\",\"date\":\"2025-05-24\",\"file_no\":\"FILE-682F12FF7F38B\",\"guest_name\":\"Srikumar\",\"total_pax\":\"2\",\"travel_agent\":\"TEst\",\"adults\":\"1\",\"nationality\":\"IN\",\"children\":\"1\",\"telephone\":\"987846561\",\"infants\":\"0\",\"arrival_date\":\"2025-05-22\",\"arrival_from\":\"k\",\"arrival_to\":\"jhgj\",\"arrival_flight\":\"ih\",\"arrival_time\":\"02:02\",\"total_days\":\"7\",\"departure_date\":\"2025-05-29\",\"departure_from\":\"2sdf\",\"departure_to\":\"khk\",\"departure_flight\":\"kjb\",\"departure_time\":\"10:10\",\"vehicle_type\":\"kjnk\",\"vehicle_number\":\"kj\",\"driver_name\":\"bkkjb\",\"driver_contact\":\"kjbnkj\",\"starting_km\":\"1500\",\"ending_km\":\"2000\",\"hotel\":[\"kjhk;\"],\"checkin\":[\"2025-05-26\"],\"checkout\":[\"2025-05-30\"],\"room_type\":[\"Double\"],\"room_category\":[\"Deluxe\"],\"room_count\":[\"1\"],\"room_rate\":[\"1500\"],\"nights\":[\"4\"],\"xbed_count\":[\"1\"],\"xbed_rate\":[\"1000\"],\"meal_plan\":[\"CP\"],\"row_total\":[\"7000\"],\"transport_supplier\":\"\",\"car_type\":\"\",\"daily_rent\":\"0\",\"days_count\":\"0\",\"driver_batta\":\"0\",\"extra_km_rate\":\"0\",\"toll_parking\":\"0\",\"transport_total\":\"\",\"boat_supplier\":\"\",\"boat_type\":\"\",\"cruise_type\":\"\",\"boat_rate\":\"0\",\"boat_extra\":\"0\",\"houseboat_total\":\"\",\"handling_officer\":\"dysg\",\"sub_total\":\"7000\",\"mark_up\":\"0\",\"tax_percent\":\"0\",\"package_cost\":\"7000\"}', '2025-05-25 01:55:43', '2025-05-25 01:55:43');

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
(3, 'sri', '$2y$10$Im97NO.30ueRFog.qgP5fuoUVAdpHa.FgmVa4HrbzsmAq7lsn58Di', 'sri', 'srikumarbe97@gmail.com', 2, '2025-06-14 18:59:52', 'assets/images/profiles/3.jpg'),
(4, 'madhu', '$2y$10$12e3C9fG9aHC5bVwn3BN9eojUJnd0cYLmnzFUYe6DtYDtiCd0g6KS', 'madhukumar', 'madhu@gmail.com', 3, '2025-06-16 10:19:45', NULL);

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
(120, 3, 'ad_campaign', 0, 0, 0, 0, '2025-06-16 10:20:18');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_number` (`file_number`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `created_by` (`created_by`);

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
-- Indexes for table `number_sequences`
--
ALTER TABLE `number_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sequence` (`type`,`year`,`month`);

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
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tour_costings`
--
ALTER TABLE `tour_costings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `comments_history`
--
ALTER TABLE `comments_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `converted_leads`
--
ALTER TABLE `converted_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `cost_sheets`
--
ALTER TABLE `cost_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `lead_status_map`
--
ALTER TABLE `lead_status_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `marketing_data`
--
ALTER TABLE `marketing_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `marketing_files`
--
ALTER TABLE `marketing_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `number_sequences`
--
ALTER TABLE `number_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
-- AUTO_INCREMENT for table `sources`
--
ALTER TABLE `sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tour_costings`
--
ALTER TABLE `tour_costings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_menu_permissions`
--
ALTER TABLE `user_menu_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_privileges`
--
ALTER TABLE `user_privileges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

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
