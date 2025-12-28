-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 01:21 PM
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
-- Database: `pms_yu_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_capture_daily_snapshots` (IN `p_snapshot_date` DATE)   BEGIN
  /* مشاريع */
  INSERT INTO daily_project_health_snapshot
  (snapshot_date, project_id, status_id, tasks_total, tasks_open, tasks_overdue,
   milestones_total, milestones_open, milestones_overdue)
  SELECT p_snapshot_date, project_id, status_id,
         tasks_total, tasks_open, tasks_overdue,
         milestones_total, milestones_open, milestones_overdue
  FROM vw_project_health_summary;

  /* مبادرات */
  INSERT INTO daily_initiative_health_snapshot
  (snapshot_date, initiative_id, status_id, tasks_total, tasks_open, tasks_overdue,
   milestones_total, milestones_open, milestones_overdue)
  SELECT p_snapshot_date, initiative_id, status_id,
         tasks_total, tasks_open, tasks_overdue,
         milestones_total, milestones_open, milestones_overdue
  FROM vw_initiative_health_summary;

  /* KPIs */
  INSERT INTO daily_kpi_summary_snapshot
  (snapshot_date, parent_type, parent_id, kpi_count, avg_current_value,
   max_current_value, min_current_value, latest_kpi_update)
  SELECT p_snapshot_date, parent_type, parent_id, kpi_count, avg_current_value,
         max_current_value, min_current_value, latest_kpi_update
  FROM vw_kpi_summary;

  /* مخاطر */
  INSERT INTO daily_risk_levels_snapshot
  (snapshot_date, parent_type, parent_id, risk_id, title, risk_score, risk_level)
  SELECT p_snapshot_date, parent_type, parent_id, risk_id, title, risk_score, risk_level
  FROM vw_risk_levels;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(150) NOT NULL,
  `entity_type` enum('user','department','pillar','initiative','project','task','milestone','kpi','risk','resource','discussion','message','approval','strategic_objective') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `created_at`) VALUES
(1, 1, 'pillar_approved', 'pillar', 3, '{\"status\":\"Pending Review\"}', '{\"status\":\"Approved\",\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-02 09:49:11'),
(2, 1, 'pillar_rejected', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"status\":\"Pending Review\",\"decision\":\"rejected\",\"comments\":\"\"}', '::1', '2025-12-02 09:52:48'),
(3, 1, 'pillar_returned', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"status\":\"Pending Review\",\"decision\":\"returned\",\"comments\":\"\"}', '::1', '2025-12-02 09:53:04'),
(4, 1, 'pillar_approved', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-02 10:41:41'),
(5, 1, 'pillar_rejected', 'pillar', 1, '{\"status\":\"Approved\"}', '{\"decision\":\"rejected\",\"comments\":\"\"}', '::1', '2025-12-02 10:41:58'),
(6, 3, 'pillar_approved', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-02 11:07:18'),
(7, 1, 'pillar_rejected', 'pillar', 3, '{\"status\":\"Approved\"}', '{\"decision\":\"rejected\",\"comments\":\"\"}', '::1', '2025-12-03 07:20:18'),
(8, 1, 'objective_updated', 'strategic_objective', 1, '{\"old_text\":null,\"old_pillar\":null}', '{\"new_text\":\"Diversifying Income Streams\",\"new_pillar\":\"1\"}', '::1', '2025-12-03 07:37:19'),
(9, 1, 'pillar_sent_for_approval', 'pillar', 1, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-03 09:37:44'),
(10, 1, 'pillar_sent_for_approval', 'pillar', 1, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-03 09:37:50'),
(11, 1, 'pillar_approved', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-03 09:41:07'),
(12, 1, 'pillar_sent_for_approval', 'pillar', 1, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-03 09:41:36'),
(13, 3, 'pillar_approved', 'pillar', 1, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-03 09:42:20'),
(14, 7, 'pillar_sent_for_approval', 'pillar', 8, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-03 12:43:34'),
(15, 3, 'pillar_approved', 'pillar', 8, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"Test2-1\"}', '::1', '2025-12-03 12:49:16'),
(16, 7, 'pillar_sent_for_approval', 'pillar', 9, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-04 05:47:33'),
(17, 3, 'pillar_returned', 'pillar', 9, '{\"status\":\"Pending Review\"}', '{\"decision\":\"returned\",\"comments\":\"\"}', '::1', '2025-12-04 05:48:49'),
(18, 7, 'pillar_sent_for_approval', 'pillar', 9, NULL, '{\"stage\":\"initial_review\"}', '::1', '2025-12-04 05:49:14'),
(19, 3, 'pillar_approved', 'pillar', 9, '{\"status\":\"Pending Review\"}', '{\"decision\":\"approved\",\"comments\":\"\"}', '::1', '2025-12-04 05:49:44'),
(20, 1, 'update', 'department', 3, '{\"id\":3,\"name\":\"Test2\",\"manager_id\":5,\"created_at\":\"2025-12-06 20:55:46\",\"updated_at\":\"2025-12-06 20:57:17\"}', '[\"Test2\",\"6\"]', '::1', '2025-12-06 17:59:45'),
(21, 1, 'delete', 'department', 3, 'false', NULL, '::1', '2025-12-06 18:00:16'),
(22, 1, 'create', 'department', 4, NULL, 'Test2', '::1', '2025-12-06 18:10:35'),
(23, 1, 'soft_delete', 'department', 4, NULL, 'soft deleted', '::1', '2025-12-06 19:08:46'),
(24, 1, 'soft_delete', 'user', 6, NULL, 'soft deleted', '::1', '2025-12-06 19:45:32'),
(25, 1, 'create', 'project', 1, NULL, '{\"project_code\":\"OP-2025-0001\",\"name\":\"Test2\"}', '::1', '2025-12-07 07:58:27'),
(26, 1, 'update', 'project', 1, '{\"id\":1,\"project_code\":\"OP-2025-0001\",\"name\":\"Test2\",\"description\":\"Test2 Test2\",\"department_id\":2,\"manager_id\":5,\"initiative_id\":null,\"budget_min\":\"10000.00\",\"budget_max\":\"20000.00\",\"approved_budget\":null,\"spent_budget\":\"0.00\",\"start_date\":\"2025-12-16\",\"end_date\":\"2026-01-08\",\"status_id\":1,\"progress_percentage\":0,\"priority\":\"high\",\"created_at\":\"2025-12-07 10:58:27\",\"updated_at\":\"2025-12-07 11:56:45\",\"update_frequency\":\"every_2_days\",\"update_time\":\"09:00:00\",\"status_name\":\"Draft\",\"department_name\":\"INTERLINK\",\"manager_name\":\"Department Manager\",\"initiative_name\":null}', '{\"id\":1,\"project_code\":\"OP-2025-0001\",\"name\":\"Test2\",\"description\":\"Test2 Test2\",\"department_id\":1,\"manager_id\":1,\"initiative_id\":null,\"budget_min\":\"10000.00\",\"budget_max\":\"20000.00\",\"approved_budget\":null,\"spent_budget\":\"0.00\",\"start_date\":\"2025-12-16\",\"end_date\":\"2026-01-08\",\"status_id\":1,\"progress_percentage\":0,\"priority\":\"high\",\"created_at\":\"2025-12-07 10:58:27\",\"updated_at\":\"2025-12-07 13:41:30\",\"update_frequency\":\"every_2_days\",\"update_time\":\"09:00:00\",\"status_name\":\"Draft\",\"department_name\":\"IT\",\"manager_name\":\"Amira Bumadyan\",\"initiative_name\":null}', '::1', '2025-12-07 10:41:30'),
(27, 1, 'project_sent_for_approval', 'project', 1, NULL, '{\"approval_instance_id\":1}', '::1', '2025-12-07 11:02:46'),
(28, 1, 'create', 'project', 2, NULL, '{\"project_code\":\"OP-2025-0002\",\"name\":\"Test255\"}', '::1', '2025-12-07 11:20:33'),
(29, 1, 'project_sent_for_approval', 'project', 2, NULL, '{\"approval_instance_id\":2}', '::1', '2025-12-07 11:21:50'),
(30, 10, 'create', 'project', 3, NULL, '{\"project_code\":\"OP-2025-0003\",\"name\":\"Test265\"}', '::1', '2025-12-07 11:47:05'),
(31, 1, 'create', 'department', 5, NULL, 'Finance', '::1', '2025-12-07 11:52:45'),
(32, 1, 'update', 'department', 5, '{\"id\":5,\"name\":\"Finance\",\"manager_id\":null,\"created_at\":\"2025-12-07 14:52:45\",\"updated_at\":\"2025-12-07 14:52:45\",\"is_deleted\":0,\"deleted_at\":null}', '[\"Finance\",\"11\"]', '::1', '2025-12-07 11:53:33'),
(33, 1, 'soft_delete', 'user', 14, NULL, 'soft deleted', '::1', '2025-12-09 06:48:28'),
(34, 1, 'soft_delete', 'user', 13, NULL, 'soft deleted', '::1', '2025-12-09 06:48:56'),
(35, 1, 'create', 'department', 6, NULL, 'SSD', '::1', '2025-12-09 07:00:05'),
(36, 1, 'update', 'department', 2, '{\"id\":2,\"name\":\"INTERLINK\",\"manager_id\":null,\"created_at\":\"2025-11-30 10:51:26\",\"updated_at\":\"2025-11-30 10:52:39\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"INTERLINK\",\"manager\":\"5\"}', '::1', '2025-12-09 07:03:10'),
(37, 1, 'soft_delete', 'department', 6, NULL, 'soft deleted', '::1', '2025-12-09 07:06:11'),
(38, 1, 'create', 'department', 7, NULL, 'SSD', '::1', '2025-12-09 07:06:52'),
(39, 1, 'soft_delete', 'department', 7, NULL, 'soft deleted', '::1', '2025-12-09 07:06:55'),
(40, 1, 'update', 'department', 2, '{\"id\":2,\"name\":\"INTERLINK\",\"manager_id\":5,\"created_at\":\"2025-11-30 10:51:26\",\"updated_at\":\"2025-12-09 10:03:10\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"INTERLINK\",\"manager\":\"5\"}', '::1', '2025-12-09 07:29:13'),
(41, 1, 'create', 'resource', 7, NULL, 'Laptop2', NULL, '2025-12-09 08:19:22'),
(42, 1, 'create', 'resource', 8, NULL, 'Laptop2', NULL, '2025-12-09 08:24:15'),
(43, 1, 'create', 'project', 4, NULL, 'Created Project: Test2552', NULL, '2025-12-09 10:30:16'),
(44, 3, 'create', 'project', 5, NULL, 'Created Project: Test5', NULL, '2025-12-09 18:34:44'),
(45, 1, 'update', 'department', 1, '{\"id\":1,\"name\":\"IT\",\"manager_id\":1,\"created_at\":\"2025-11-29 11:52:35\",\"updated_at\":\"2025-11-29 11:52:35\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"IT\",\"manager\":\"8\"}', '::1', '2025-12-09 18:37:13'),
(46, 1, 'update', 'department', 1, '{\"id\":1,\"name\":\"IT\",\"manager_id\":8,\"created_at\":\"2025-11-29 11:52:35\",\"updated_at\":\"2025-12-09 21:37:13\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"IT\",\"manager\":\"8\"}', '::1', '2025-12-09 18:57:39'),
(47, 1, 'create', 'department', 8, NULL, 'IT', '::1', '2025-12-09 18:59:01'),
(48, 1, 'create', 'department', 9, NULL, 'IT Department', '::1', '2025-12-09 18:59:51'),
(49, 1, 'create', 'department', 10, NULL, 'IT Department', '::1', '2025-12-09 19:00:08'),
(50, 1, 'soft_delete', 'department', 10, NULL, 'soft deleted', '::1', '2025-12-09 19:00:17'),
(51, 1, 'update', 'department', 9, '{\"id\":9,\"name\":\"IT Department\",\"manager_id\":12,\"created_at\":\"2025-12-09 21:59:51\",\"updated_at\":\"2025-12-09 21:59:51\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"IT Department\",\"manager\":\"12\"}', '::1', '2025-12-09 19:00:24'),
(52, 1, 'soft_delete', 'department', 8, NULL, 'soft deleted', '::1', '2025-12-09 19:00:48'),
(53, 1, 'create', 'project', 6, NULL, 'Created Project: Test2555', NULL, '2025-12-11 11:32:45'),
(54, 1, 'soft_delete', 'user', 12, NULL, 'soft deleted', '::1', '2025-12-14 18:50:03'),
(55, 3, 'create', 'project', 7, NULL, 'Created Project: IT-project', NULL, '2025-12-15 09:58:58'),
(56, 1, 'create', 'project', 8, NULL, 'Created Project: IT-project2', NULL, '2025-12-15 11:45:53'),
(57, 5, 'create', 'project', 9, NULL, 'Created Project: Test787', NULL, '2025-12-16 07:57:29'),
(58, 1, 'create', 'project', 10, NULL, 'Created Project: Test987', NULL, '2025-12-16 08:11:46'),
(59, 1, 'create', 'project', 11, NULL, 'Created Project: Test678', NULL, '2025-12-16 09:31:07'),
(60, 1, 'create', 'project', 12, NULL, 'Created Project: Test996', NULL, '2025-12-16 09:47:01'),
(61, 1, 'update', 'department', 5, '{\"id\":5,\"name\":\"Finance\",\"manager_id\":11,\"created_at\":\"2025-12-07 14:52:45\",\"updated_at\":\"2025-12-07 14:53:33\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"Finance\",\"manager\":\"11\"}', '::1', '2025-12-16 09:55:57'),
(62, 8, 'create', 'project', 13, NULL, 'Created Project: Test5654', NULL, '2025-12-16 10:01:20'),
(63, 1, 'create', 'project', 14, NULL, 'Created Project: Test564', NULL, '2025-12-16 10:12:35'),
(64, 1, 'create', 'project', 15, NULL, 'Created Project: Test6587', NULL, '2025-12-16 10:31:57'),
(65, 8, 'create', 'project', 16, NULL, 'Created Project: Test357', NULL, '2025-12-16 10:37:20'),
(66, 11, 'create', 'project', 17, NULL, 'Created Project: Test4562', NULL, '2025-12-16 10:58:27'),
(67, 1, 'create', 'project', 18, NULL, 'Created Project: Test789', NULL, '2025-12-18 04:49:59'),
(68, 1, 'create', 'project', 19, NULL, 'Created Project: Test587', NULL, '2025-12-18 05:12:08'),
(69, 1, 'create', 'project', 20, NULL, 'Created Project: Test287', NULL, '2025-12-21 09:47:27'),
(70, 1, 'create', 'project', 21, NULL, 'Created Project: Test314', NULL, '2025-12-21 10:09:11'),
(71, 1, 'create', 'project', 22, NULL, 'Created Project: Test28888', NULL, '2025-12-21 10:46:00'),
(72, 1, 'create', 'department', 11, NULL, 'Strategy Office', '::1', '2025-12-22 07:10:11'),
(73, 1, 'create', 'department', 12, NULL, 'Strategy Office', '::1', '2025-12-22 07:16:44'),
(74, 1, 'soft_delete', 'department', 12, NULL, 'soft deleted', '::1', '2025-12-22 07:16:51'),
(75, 1, 'create', 'project', 23, NULL, 'Created Project: Test5967', NULL, '2025-12-22 10:44:44'),
(76, 1, 'create', 'project', 24, NULL, 'Created Project: Test7854', NULL, '2025-12-22 12:32:52'),
(77, 1, 'create', 'department', 13, NULL, 'Test2', '::1', '2025-12-23 12:24:02'),
(78, 1, 'soft_delete', 'department', 13, NULL, 'soft deleted', '::1', '2025-12-23 12:24:15'),
(79, 1, 'create', 'department', 14, NULL, 'Test26', '::1', '2025-12-23 12:49:11'),
(80, 1, 'soft_delete', 'department', 14, NULL, 'soft deleted', '::1', '2025-12-23 12:49:17'),
(81, 1, 'create', 'department', 15, NULL, 'Test285', '::1', '2025-12-23 12:55:49'),
(82, 1, 'soft_delete', 'department', 15, NULL, 'soft deleted', '::1', '2025-12-23 12:57:10'),
(83, 1, 'create', 'department', 16, NULL, 'Test299', '::1', '2025-12-23 13:03:48'),
(84, 1, 'soft_delete', 'department', 16, NULL, 'soft deleted', '::1', '2025-12-23 13:04:33'),
(85, 1, 'create', 'department', 17, NULL, 'Test266', '::1', '2025-12-23 13:05:13'),
(86, 1, 'soft_delete', 'department', 17, NULL, 'soft deleted', '::1', '2025-12-23 13:05:18'),
(87, 1, 'update', 'department', 11, '{\"id\":11,\"name\":\"Strategy Office\",\"manager_id\":3,\"created_at\":\"2025-12-22 10:10:11\",\"updated_at\":\"2025-12-22 10:10:11\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"Strategy Office\",\"manager\":\"3\"}', '::1', '2025-12-23 13:09:33'),
(88, 1, 'create', 'user', 16, NULL, 'amiratest', '::1', '2025-12-24 05:34:40'),
(89, 1, 'create', 'department', 18, NULL, 'Test2223', '::1', '2025-12-24 10:03:48'),
(90, 1, 'soft_delete', 'department', 18, NULL, 'soft deleted', '::1', '2025-12-24 10:03:51'),
(91, 1, 'update', 'department', 5, '{\"id\":5,\"name\":\"Finance\",\"manager_id\":11,\"created_at\":\"2025-12-07 14:52:45\",\"updated_at\":\"2025-12-16 12:55:57\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"Finance\",\"manager\":\"11\"}', '::1', '2025-12-24 10:11:49'),
(92, 1, 'update', 'department', 5, '{\"id\":5,\"name\":\"Finance\",\"manager_id\":11,\"created_at\":\"2025-12-07 14:52:45\",\"updated_at\":\"2025-12-24 13:11:49\",\"is_deleted\":0,\"deleted_at\":null}', '{\"name\":\"Finance\",\"manager\":\"11\"}', '::1', '2025-12-24 10:11:57'),
(93, 1, 'soft_delete', 'user', 16, NULL, 'soft deleted', '::1', '2025-12-24 10:26:44'),
(94, 1, 'create', 'user', 17, NULL, 'amirakahtan111', '::1', '2025-12-24 10:32:40'),
(95, 1, 'soft_delete', 'user', 17, NULL, 'soft deleted', '::1', '2025-12-24 10:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','danger','success') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `type`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'ttttttttffgjvghvghgkhghg', 'ttttttttffgjvghvghgkhghgttttttttffgjvghvghgkhghg', 'info', 0, 1, '2025-12-21 17:37:11'),
(2, 'Hello', 'Hello', 'danger', 1, 1, '2025-12-24 11:39:47');

-- --------------------------------------------------------

--
-- Table structure for table `approval_actions`
--

CREATE TABLE `approval_actions` (
  `id` int(11) NOT NULL,
  `approval_instance_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `reviewer_user_id` int(11) NOT NULL,
  `decision` enum('approved','rejected','returned') NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_actions`
--

INSERT INTO `approval_actions` (`id`, `approval_instance_id`, `stage_id`, `reviewer_user_id`, `decision`, `comments`, `created_at`) VALUES
(1, 4, 8, 3, 'approved', 'test', '2025-12-09 11:48:12'),
(2, 4, 9, 5, 'approved', 'test', '2025-12-09 11:49:05'),
(3, 4, 10, 11, 'approved', '', '2025-12-09 11:56:05'),
(4, 4, 11, 4, 'approved', '', '2025-12-09 11:56:33'),
(5, 5, 8, 10, 'returned', '', '2025-12-09 12:21:33'),
(6, 3, 8, 10, 'approved', '', '2025-12-09 13:09:22'),
(7, 6, 9, 8, 'approved', 'Test5', '2025-12-09 18:39:51'),
(8, 6, 10, 11, 'approved', 'Test5 [Budget Approved: 25,000 SAR]', '2025-12-09 18:40:46'),
(9, 6, 11, 4, 'approved', 'Test5', '2025-12-09 18:41:38'),
(10, 7, 9, 8, 'returned', 'ففففف', '2025-12-10 05:09:19'),
(11, 8, 9, 8, 'approved', '', '2025-12-10 05:09:54'),
(12, 8, 10, 11, 'approved', '', '2025-12-10 05:44:55'),
(13, 8, 11, 4, 'returned', 'اااااا', '2025-12-10 05:47:01'),
(14, 3, 5, 3, 'approved', '', '2025-12-10 05:53:59'),
(15, 9, 9, 10, 'approved', '', '2025-12-11 10:52:01'),
(16, 9, 10, 11, 'approved', ' [Budget Approved: 25,000 SAR]', '2025-12-11 10:52:46'),
(17, 9, 11, 4, 'approved', '', '2025-12-11 10:53:12'),
(18, 10, 9, 10, 'approved', '', '2025-12-11 11:36:15'),
(19, 10, 10, 11, 'approved', ' [Budget Approved: 2,000 SAR]', '2025-12-11 11:39:14'),
(20, 3, 6, 11, 'approved', ' [Budget Approved: 2,000 SAR]', '2025-12-11 11:46:19'),
(21, 10, 11, 4, 'approved', '', '2025-12-11 11:48:41'),
(22, 11, 15, 3, 'approved', '', '2025-12-14 05:22:49'),
(23, 11, 18, 4, 'approved', '', '2025-12-14 05:23:21'),
(24, 12, 15, 3, 'approved', '', '2025-12-14 05:54:55'),
(25, 12, 18, 4, 'approved', '', '2025-12-14 05:55:33'),
(26, 14, 9, 11, 'approved', '', '2025-12-15 11:50:46'),
(27, 14, 10, 11, 'approved', ' [Budget Approved: 25,000 SAR]', '2025-12-15 11:56:25'),
(28, 14, 11, 4, 'approved', '', '2025-12-15 11:57:15'),
(29, 17, 9, 8, 'approved', '', '2025-12-16 09:42:14'),
(30, 17, 10, 8, 'approved', '', '2025-12-16 09:42:50'),
(31, 16, 9, 8, 'approved', '', '2025-12-16 09:42:55'),
(32, 16, 10, 8, 'approved', '', '2025-12-16 09:43:02'),
(33, 15, 19, 8, 'approved', '', '2025-12-16 09:44:49'),
(34, 18, 9, 8, 'approved', '', '2025-12-16 09:47:29'),
(35, 19, 9, 8, 'approved', '', '2025-12-16 10:07:21'),
(36, 20, 9, 8, 'approved', '', '2025-12-16 10:13:01'),
(37, 20, 10, 8, 'approved', '', '2025-12-16 10:14:23'),
(38, 20, 11, 8, 'approved', '', '2025-12-16 10:14:50'),
(39, 21, 9, 8, 'approved', '', '2025-12-16 10:32:32'),
(40, 21, 10, 8, 'approved', '', '2025-12-16 10:34:13'),
(41, 22, 9, 8, 'approved', '', '2025-12-16 10:39:28'),
(42, 23, 9, 11, 'approved', ' [Budget Approved: 15,000 SAR]', '2025-12-16 10:59:42'),
(43, 23, 10, 11, 'approved', ' [Budget Approved: 15,000 SAR]', '2025-12-16 10:59:52'),
(44, 22, 10, 11, 'approved', ' [Budget Approved: 10,000 SAR]', '2025-12-16 11:03:15'),
(45, 22, 11, 4, 'approved', '', '2025-12-16 11:03:45'),
(46, 18, 10, 11, 'returned', 'لالالالالااالالال', '2025-12-16 11:06:07'),
(47, 24, 19, 8, 'approved', '', '2025-12-18 04:51:05'),
(48, 24, 20, 4, 'approved', '', '2025-12-18 04:51:42'),
(49, 23, 11, 4, 'rejected', 'Test789', '2025-12-18 04:55:59'),
(50, 25, 19, 11, 'approved', '', '2025-12-18 05:13:06'),
(51, 25, 20, 4, 'approved', '', '2025-12-18 05:13:29'),
(52, 21, 11, 4, 'approved', '', '2025-12-18 05:18:48'),
(53, 13, 9, 8, 'approved', '', '2025-12-20 18:07:08'),
(54, 13, 10, 11, 'approved', '', '2025-12-20 18:14:41'),
(55, 19, 10, 11, 'approved', '', '2025-12-20 18:23:47'),
(56, 19, 11, 4, 'approved', '', '2025-12-20 18:26:19'),
(57, 26, 9, 8, 'approved', '', '2025-12-21 08:16:26'),
(58, 26, 10, 11, 'approved', '', '2025-12-21 08:19:37'),
(59, 26, 11, 4, 'returned', 'comment', '2025-12-21 08:26:51'),
(60, 27, 9, 8, 'approved', '', '2025-12-21 08:28:38'),
(61, 27, 10, 11, 'approved', '', '2025-12-21 08:29:15'),
(62, 27, 11, 4, 'returned', 'comment', '2025-12-21 09:41:38'),
(63, 28, 9, 8, 'approved', '', '2025-12-21 09:43:31'),
(64, 28, 10, 11, 'approved', '', '2025-12-21 09:43:50'),
(65, 28, 11, 4, 'approved', '', '2025-12-21 09:44:30'),
(66, 29, 19, 11, 'approved', '', '2025-12-21 09:54:59'),
(67, 29, 20, 4, 'approved', '', '2025-12-21 10:07:40'),
(68, 30, 19, 8, 'approved', '', '2025-12-21 10:09:57'),
(69, 31, 19, 8, 'approved', '', '2025-12-21 10:46:59'),
(70, 31, 20, 4, 'approved', '', '2025-12-21 10:49:36'),
(71, 32, 15, 3, 'approved', '', '2025-12-22 05:48:53'),
(72, 32, 18, 4, 'approved', '', '2025-12-22 06:29:43'),
(73, 33, 15, 3, 'approved', '', '2025-12-22 10:39:09'),
(74, 33, 18, 4, 'approved', 'comment', '2025-12-22 10:40:46'),
(75, 35, 19, 8, 'approved', '', '2025-12-22 10:46:32'),
(76, 35, 20, 4, 'approved', '', '2025-12-22 10:46:56'),
(77, 17, 11, 4, 'approved', '', '2025-12-22 12:10:08'),
(78, 16, 11, 4, 'approved', 'comment', '2025-12-22 12:19:51'),
(79, 13, 11, 4, 'returned', 'comment', '2025-12-22 12:22:32'),
(80, 36, 19, 8, 'returned', 'comment', '2025-12-22 12:24:30'),
(81, 37, 9, 8, 'approved', '', '2025-12-22 12:25:36'),
(82, 37, 10, 11, 'approved', '', '2025-12-22 12:26:08'),
(83, 37, 11, 4, 'approved', '', '2025-12-22 12:26:33'),
(84, 38, 9, 8, 'approved', '', '2025-12-22 12:33:26'),
(85, 38, 10, 11, 'approved', '', '2025-12-22 12:33:51'),
(86, 38, 11, 4, 'approved', '', '2025-12-22 12:34:26'),
(87, 39, 21, 3, 'approved', '', '2025-12-22 13:01:06'),
(88, 39, 23, 4, 'approved', '', '2025-12-22 13:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `approval_entity_types`
--

CREATE TABLE `approval_entity_types` (
  `id` int(11) NOT NULL,
  `entity_key` varchar(50) NOT NULL,
  `entity_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_entity_types`
--

INSERT INTO `approval_entity_types` (`id`, `entity_key`, `entity_name`) VALUES
(1, 'pillar', 'Strategic Pillar'),
(2, 'initiative', 'Strategic Initiative'),
(3, 'operational_project', 'Operational Project'),
(4, 'collaboration', 'collaboration');

-- --------------------------------------------------------

--
-- Table structure for table `approval_instances`
--

CREATE TABLE `approval_instances` (
  `id` int(11) NOT NULL,
  `entity_type_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `current_stage_id` int(11) DEFAULT NULL,
  `status` enum('in_progress','approved','rejected','returned') DEFAULT 'in_progress',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_instances`
--

INSERT INTO `approval_instances` (`id`, `entity_type_id`, `entity_id`, `current_stage_id`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 1, NULL, 'in_progress', 1, '2025-12-07 11:02:46', '2025-12-07 14:02:46'),
(3, 3, 3, 7, 'in_progress', 5, '2025-12-07 11:48:15', '2025-12-11 14:46:19'),
(4, 3, 4, NULL, 'approved', 1, '2025-12-09 10:32:25', '2025-12-09 14:56:33'),
(5, 3, 3, 8, 'returned', 5, '2025-12-09 10:35:02', '2025-12-09 15:21:33'),
(6, 3, 5, NULL, 'approved', 3, '2025-12-09 18:35:29', '2025-12-09 21:41:38'),
(7, 3, 3, 9, 'returned', 3, '2025-12-10 05:08:29', '2025-12-10 08:09:19'),
(8, 3, 3, 11, 'returned', 3, '2025-12-10 05:09:41', '2025-12-10 08:47:01'),
(9, 3, 3, NULL, 'approved', 1, '2025-12-11 10:50:43', '2025-12-11 13:53:12'),
(10, 3, 6, NULL, 'approved', 1, '2025-12-11 11:33:16', '2025-12-11 14:48:41'),
(11, 1, 12, NULL, 'approved', 3, '2025-12-14 05:22:21', '2025-12-14 08:23:21'),
(12, 1, 13, NULL, 'approved', 3, '2025-12-14 05:50:45', '2025-12-14 08:55:33'),
(13, 3, 7, 11, 'returned', 1, '2025-12-15 11:40:27', '2025-12-22 15:22:32'),
(14, 3, 8, NULL, 'approved', 1, '2025-12-15 11:46:38', '2025-12-15 14:57:15'),
(15, 3, 9, 20, 'in_progress', 1, '2025-12-16 08:10:31', '2025-12-16 12:44:49'),
(16, 3, 10, NULL, 'approved', 1, '2025-12-16 08:12:09', '2025-12-22 15:19:51'),
(17, 3, 11, NULL, 'approved', 1, '2025-12-16 09:31:23', '2025-12-22 15:10:08'),
(18, 3, 12, 10, 'returned', 1, '2025-12-16 09:47:15', '2025-12-16 14:06:07'),
(19, 3, 13, NULL, 'approved', 1, '2025-12-16 10:02:08', '2025-12-20 21:26:19'),
(20, 3, 14, NULL, 'approved', 1, '2025-12-16 10:12:49', '2025-12-16 13:14:50'),
(21, 3, 15, NULL, 'approved', 1, '2025-12-16 10:32:11', '2025-12-18 08:18:48'),
(22, 3, 16, NULL, 'approved', 1, '2025-12-16 10:39:13', '2025-12-16 14:03:45'),
(23, 3, 17, 11, 'rejected', 1, '2025-12-16 10:59:13', '2025-12-18 07:55:59'),
(24, 3, 18, NULL, 'approved', 1, '2025-12-18 04:50:21', '2025-12-18 07:51:42'),
(25, 3, 19, NULL, 'approved', 1, '2025-12-18 05:12:23', '2025-12-18 08:13:29'),
(26, 3, 12, 11, 'returned', 1, '2025-12-21 08:14:15', '2025-12-21 11:26:51'),
(27, 3, 12, 11, 'returned', 1, '2025-12-21 08:28:15', '2025-12-21 12:41:38'),
(28, 3, 12, NULL, 'approved', 1, '2025-12-21 09:42:54', '2025-12-21 12:44:30'),
(29, 3, 20, NULL, 'approved', 1, '2025-12-21 09:54:28', '2025-12-21 13:07:40'),
(30, 3, 21, 20, 'in_progress', 1, '2025-12-21 10:09:28', '2025-12-21 13:09:57'),
(31, 3, 22, NULL, 'approved', 1, '2025-12-21 10:46:32', '2025-12-21 13:49:36'),
(32, 1, 19, NULL, 'approved', 1, '2025-12-22 05:47:37', '2025-12-22 09:29:43'),
(33, 1, 20, NULL, 'approved', 1, '2025-12-22 09:50:25', '2025-12-22 13:40:46'),
(35, 3, 23, NULL, 'approved', 1, '2025-12-22 10:46:03', '2025-12-22 13:46:56'),
(36, 3, 7, 19, 'returned', 1, '2025-12-22 12:23:16', '2025-12-22 15:24:30'),
(37, 3, 7, NULL, 'approved', 1, '2025-12-22 12:25:15', '2025-12-22 15:26:33'),
(38, 3, 24, NULL, 'approved', 1, '2025-12-22 12:33:12', '2025-12-22 15:34:26'),
(39, 2, 11, NULL, 'approved', 1, '2025-12-22 13:00:26', '2025-12-22 16:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflows`
--

CREATE TABLE `approval_workflows` (
  `id` int(11) NOT NULL,
  `entity_type_id` int(11) NOT NULL,
  `workflow_name` varchar(150) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_workflows`
--

INSERT INTO `approval_workflows` (`id`, `entity_type_id`, `workflow_name`, `is_active`) VALUES
(1, 1, 'Pillar Approval Flow', 0),
(2, 2, 'Initiative Approval Flow - without budget\r\n', 0),
(3, 3, 'Operational Project Approval Flow', 1),
(4, 4, 'Collaboration Approval Workflow', 1),
(5, 1, 'Pillar Approval Flow', 1),
(6, 3, 'Operational Project Approval Flow - without budget', 1),
(7, 2, 'Initiative Approval Flow', 1),
(8, 2, 'Initiative Approval Flow - without budget', 1);

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflow_stages`
--

CREATE TABLE `approval_workflow_stages` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `assignee_type` enum('system_role','pillar_lead','initiative_owner','project_manager','department_manager','hierarchy_manager') NOT NULL DEFAULT 'system_role',
  `stage_role_id` int(11) DEFAULT NULL,
  `hierarchy_reporting_type` enum('academic','administrative') DEFAULT NULL,
  `stage_name` varchar(150) NOT NULL,
  `is_final` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_workflow_stages`
--

INSERT INTO `approval_workflow_stages` (`id`, `workflow_id`, `stage_order`, `assignee_type`, `stage_role_id`, `hierarchy_reporting_type`, `stage_name`, `is_final`) VALUES
(4, 2, 1, 'system_role', 13, NULL, 'Strategy Staff Review', 0),
(5, 2, 2, 'system_role', 11, NULL, 'Strategy Office Head Review', 0),
(6, 2, 3, 'system_role', 14, NULL, 'Finance Budget Approval', 0),
(7, 2, 4, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(8, 2, 1, 'project_manager', NULL, NULL, 'Project Manager Approval', 0),
(9, 3, 1, 'system_role', 12, NULL, 'Department Head Approval', 0),
(10, 3, 2, 'system_role', 14, NULL, 'Finance Budget Approval', 0),
(11, 3, 3, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(13, 4, 1, 'system_role', 12, NULL, 'Department Head Approval', 1),
(15, 5, 1, 'system_role', 11, NULL, 'Strategy Office Review', 0),
(18, 5, 2, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(19, 6, 1, 'system_role', 12, NULL, 'Department Head Approval', 0),
(20, 6, 2, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(21, 7, 1, 'system_role', 11, NULL, 'Strategy Office Head Review', 0),
(22, 7, 2, 'system_role', 14, NULL, 'Finance Budget Approval', 0),
(23, 7, 3, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(24, 8, 1, 'system_role', 11, NULL, 'Strategy Office Head Review', 0),
(25, 8, 2, 'system_role', 10, NULL, 'CEO Final Approval', 1);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `branch_name` varchar(150) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `city`, `is_active`, `created_at`) VALUES
(1, 'RYD', 'Riyadh Campus', 'Riyadh', 1, '2025-12-06 12:59:32'),
(2, 'KHB', 'Khobar Campus', 'Khobar', 1, '2025-12-06 12:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `collaborations`
--

CREATE TABLE `collaborations` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `assigned_user_id` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `last_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaborations`
--

INSERT INTO `collaborations` (`id`, `parent_type`, `parent_id`, `department_id`, `reason`, `requested_by`, `status_id`, `assigned_user_id`, `reviewed_by`, `reviewed_at`, `last_comment`, `created_at`, `updated_at`) VALUES
(1, 'project', 5, 2, 'بيليبليل', 1, 1, NULL, NULL, NULL, NULL, '2025-12-10 18:23:19', '2025-12-10 18:23:19'),
(2, 'project', 5, 5, 'بلبالا', 1, 3, NULL, 11, '2025-12-11 08:12:30', '', '2025-12-10 18:34:25', '2025-12-11 05:12:30'),
(3, 'project', 5, 5, 'همنمتنتن', 3, 3, NULL, 11, '2025-12-11 08:12:27', '', '2025-12-10 18:37:32', '2025-12-11 05:12:27'),
(4, 'project', 5, 5, 'hghghfgh', 3, 3, NULL, 11, '2025-12-10 23:02:36', '', '2025-12-10 19:03:13', '2025-12-10 20:02:36'),
(5, 'project', 5, 5, 'تلالتلا', 3, 2, 7, 11, '2025-12-11 10:44:04', 'لبتاتاتا', '2025-12-11 05:15:10', '2025-12-11 07:44:04'),
(6, 'project', 6, 5, 'سسسسس', 10, 2, 7, 11, '2025-12-11 14:53:55', 'بببب', '2025-12-11 11:53:08', '2025-12-11 11:53:55'),
(7, 'project', 6, 5, 'لللااااااااا', 3, 2, 11, 11, '2025-12-15 12:27:37', 'لبتاتاتا', '2025-12-15 09:27:09', '2025-12-15 09:27:37'),
(8, 'project', 6, 1, 'للللل', 3, 2, 14, 8, '2025-12-15 12:39:49', 'للللليقققق', '2025-12-15 09:38:02', '2025-12-15 09:39:49'),
(9, 'project', 5, 2, 'ببلبل', 1, 1, NULL, NULL, NULL, NULL, '2025-12-15 10:58:50', '2025-12-15 10:58:50'),
(10, 'project', 16, 5, 'نتانغععه', 1, 2, 11, 11, '2025-12-17 09:07:53', '', '2025-12-17 06:07:34', '2025-12-17 06:07:53'),
(11, 'project', 19, 1, 'dfdfdsf', 1, 3, NULL, 1, '2025-12-20 19:30:51', '', '2025-12-20 16:28:32', '2025-12-20 16:30:51'),
(12, 'project', 19, 1, 'fdfd', 1, 2, 12, 8, '2025-12-20 19:32:55', 'gfhgfhgf', '2025-12-20 16:31:08', '2025-12-20 16:32:55');

-- --------------------------------------------------------

--
-- Table structure for table `collaboration_approval_history`
--

CREATE TABLE `collaboration_approval_history` (
  `id` int(11) NOT NULL,
  `collaboration_id` int(11) NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_type` enum('approved','rejected','returned') NOT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collaboration_statuses`
--

CREATE TABLE `collaboration_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaboration_statuses`
--

INSERT INTO `collaboration_statuses` (`id`, `status_name`) VALUES
(2, 'approved'),
(1, 'pending'),
(3, 'rejected'),
(4, 'returned');

-- --------------------------------------------------------

--
-- Table structure for table `daily_initiative_health_snapshot`
--

CREATE TABLE `daily_initiative_health_snapshot` (
  `snapshot_date` date NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `tasks_total` int(11) NOT NULL,
  `tasks_open` int(11) NOT NULL,
  `tasks_overdue` int(11) NOT NULL,
  `milestones_total` int(11) NOT NULL,
  `milestones_open` int(11) NOT NULL,
  `milestones_overdue` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_initiative_health_snapshot`
--

INSERT INTO `daily_initiative_health_snapshot` (`snapshot_date`, `initiative_id`, `status_id`, `tasks_total`, `tasks_open`, `tasks_overdue`, `milestones_total`, `milestones_open`, `milestones_overdue`) VALUES
('2025-12-08', 2, 8, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_kpi_summary_snapshot`
--

CREATE TABLE `daily_kpi_summary_snapshot` (
  `snapshot_date` date NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `kpi_count` int(11) NOT NULL,
  `avg_current_value` decimal(15,2) DEFAULT NULL,
  `max_current_value` decimal(15,2) DEFAULT NULL,
  `min_current_value` decimal(15,2) DEFAULT NULL,
  `latest_kpi_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_project_health_snapshot`
--

CREATE TABLE `daily_project_health_snapshot` (
  `snapshot_date` date NOT NULL,
  `project_id` int(11) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `tasks_total` int(11) NOT NULL,
  `tasks_open` int(11) NOT NULL,
  `tasks_overdue` int(11) NOT NULL,
  `milestones_total` int(11) NOT NULL,
  `milestones_open` int(11) NOT NULL,
  `milestones_overdue` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_project_health_snapshot`
--

INSERT INTO `daily_project_health_snapshot` (`snapshot_date`, `project_id`, `status_id`, `tasks_total`, `tasks_open`, `tasks_overdue`, `milestones_total`, `milestones_open`, `milestones_overdue`) VALUES
('2025-12-08', 2, 2, 0, 0, 0, 0, 0, 0),
('2025-12-08', 3, 1, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_risk_levels_snapshot`
--

CREATE TABLE `daily_risk_levels_snapshot` (
  `snapshot_date` date NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `risk_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `risk_score` int(11) NOT NULL,
  `risk_level` enum('Low','Medium','High') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `manager_id`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 'IT', 8, '2025-11-29 08:52:35', '2025-12-09 18:57:39', 0, NULL),
(2, 'INTERLINK', 5, '2025-11-30 07:51:26', '2025-12-09 07:29:13', 0, NULL),
(5, 'Finance', 11, '2025-12-07 11:52:45', '2025-12-24 10:11:57', 0, NULL),
(9, 'IT Department', 12, '2025-12-09 18:59:51', '2025-12-09 19:00:24', 0, NULL),
(11, 'Strategy Office', 3, '2025-12-22 07:10:11', '2025-12-23 13:09:33', 0, NULL),
(13, 'Test2', NULL, '2025-12-23 12:24:02', '2025-12-23 12:24:15', 1, '2025-12-23 15:24:15'),
(14, 'Test26', NULL, '2025-12-23 12:49:11', '2025-12-23 12:49:17', 1, '2025-12-23 15:49:17'),
(15, 'Test285', NULL, '2025-12-23 12:55:49', '2025-12-23 12:57:10', 1, '2025-12-23 15:57:10'),
(16, 'Test299', NULL, '2025-12-23 13:03:48', '2025-12-23 13:04:33', 1, '2025-12-23 16:04:33'),
(17, 'Test266', NULL, '2025-12-23 13:05:13', '2025-12-23 13:05:18', 1, '2025-12-23 16:05:18'),
(18, 'Test2223', NULL, '2025-12-24 10:03:48', '2025-12-24 10:03:51', 1, '2025-12-24 13:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `department_branches`
--

CREATE TABLE `department_branches` (
  `department_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_branches`
--

INSERT INTO `department_branches` (`department_id`, `branch_id`) VALUES
(1, 1),
(2, 1),
(5, 1),
(5, 2),
(9, 2),
(11, 1),
(11, 2),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 2);

-- --------------------------------------------------------

--
-- Table structure for table `discussion_attachments`
--

CREATE TABLE `discussion_attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_messages`
--

CREATE TABLE `discussion_messages` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_threads`
--

CREATE TABLE `discussion_threads` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project','milestone','task') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project','milestone','task','risk','pillar') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `version` int(11) DEFAULT 1,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `parent_type`, `parent_id`, `title`, `description`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `uploaded_at`, `version`, `is_archived`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 'project', 5, 'test1', 'test1', '1765446149_pms_yu_system (26).sql', 'assets/uploads/documents/1765446149_pms_yu_system (26).sql', 140573, 'sql', 3, '2025-12-11 09:42:29', 1, 0, '2025-12-11 09:42:29', '2025-12-15 11:16:02', 1, '2025-12-15 14:16:02'),
(2, 'milestone', 1, 'test2', 'test2', '1765446182_pms_yu_system (26).sql', 'assets/uploads/documents/1765446182_pms_yu_system (26).sql', 140573, 'sql', 3, '2025-12-11 09:43:02', 1, 0, '2025-12-11 09:43:02', '2025-12-11 09:43:02', 0, NULL),
(3, 'project', 6, 'test', '', '1765455825_1765446149_pms_yu_system (26).sql', 'assets/uploads/documents/1765455825_1765446149_pms_yu_system (26).sql', 140573, 'sql', 10, '2025-12-11 12:23:45', 1, 0, '2025-12-11 12:23:45', '2025-12-11 12:23:45', 0, NULL),
(4, 'project', 6, 'plan', '', '1765455851_pms_yu_system (26).sql', 'assets/uploads/documents/1765455851_pms_yu_system (26).sql', 140573, 'sql', 10, '2025-12-11 12:24:11', 1, 0, '2025-12-11 12:24:11', '2025-12-11 12:24:11', 0, NULL),
(5, 'project', 7, 'لللل', 'Supporting Document for Approval', '1765798806_1765446149_pms_yu_system (26) (1).sql', 'assets/uploads/documents/1765798806_1765446149_pms_yu_system (26) (1).sql', 140573, 'sql', 1, '2025-12-15 11:40:06', 1, 0, '2025-12-15 11:40:06', '2025-12-15 11:40:23', 1, '2025-12-15 14:40:23'),
(6, 'project', 8, 'اااا', 'Supporting Document for Approval', '1765799179_1765446149_pms_yu_system (26) (1).sql', 'assets/uploads/documents/1765799179_1765446149_pms_yu_system (26) (1).sql', 140573, 'sql', 1, '2025-12-15 11:46:19', 1, 0, '2025-12-15 11:46:19', '2025-12-15 11:46:19', 0, NULL),
(7, 'project', 9, 'Test787', 'Supporting Document for Approval', '1765872625_pms_yu_system (29).sql', 'assets/uploads/documents/1765872625_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 08:10:25', 1, 0, '2025-12-16 08:10:25', '2025-12-16 08:10:25', 0, NULL),
(8, 'project', 10, 'Test987', 'Supporting Document for Approval', '1765872725_pms_yu_system (29).sql', 'assets/uploads/documents/1765872725_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 08:12:05', 1, 0, '2025-12-16 08:12:05', '2025-12-16 08:12:05', 0, NULL),
(9, 'project', 11, 'Test678', 'Supporting Document for Approval', '1765877479_pms_yu_system (29).sql', 'assets/uploads/documents/1765877479_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 09:31:19', 1, 0, '2025-12-16 09:31:19', '2025-12-16 09:31:19', 0, NULL),
(10, 'project', 12, 'Test996', 'Supporting Document for Approval', '1765878432_pms_yu_system (29).sql', 'assets/uploads/documents/1765878432_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 09:47:12', 1, 0, '2025-12-16 09:47:12', '2025-12-16 09:47:12', 0, NULL),
(11, 'project', 13, 'Test5654', 'Supporting Document for Approval', '1765879324_pms_yu_system (29).sql', 'assets/uploads/documents/1765879324_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 10:02:04', 1, 0, '2025-12-16 10:02:04', '2025-12-16 10:02:04', 0, NULL),
(12, 'project', 14, 'Test564', 'Supporting Document for Approval', '1765879966_pms_yu_system (29).sql', 'assets/uploads/documents/1765879966_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 10:12:46', 1, 0, '2025-12-16 10:12:46', '2025-12-16 10:12:46', 0, NULL),
(13, 'project', 15, 'Test6587', 'Supporting Document for Approval', '1765881128_pms_yu_system (29).sql', 'assets/uploads/documents/1765881128_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 10:32:08', 1, 0, '2025-12-16 10:32:08', '2025-12-16 10:32:08', 0, NULL),
(14, 'project', 16, 'Test357', 'Supporting Document for Approval', '1765881551_pms_yu_system (30).sql', 'assets/uploads/documents/1765881551_pms_yu_system (30).sql', 180345, 'sql', 1, '2025-12-16 10:39:11', 1, 0, '2025-12-16 10:39:11', '2025-12-16 10:39:11', 0, NULL),
(15, 'project', 17, 'Test4562', 'Supporting Document for Approval', '1765882749_pms_yu_system (29).sql', 'assets/uploads/documents/1765882749_pms_yu_system (29).sql', 169106, 'sql', 1, '2025-12-16 10:59:09', 1, 0, '2025-12-16 10:59:09', '2025-12-16 10:59:09', 0, NULL),
(16, 'project', 18, 'Test789', 'Supporting Document for Approval', '1766033414_login.php', 'assets/uploads/documents/1766033414_login.php', 3193, 'php', 1, '2025-12-18 04:50:14', 1, 0, '2025-12-18 04:50:14', '2025-12-18 04:50:14', 0, NULL),
(17, 'project', 19, 'Test587', 'Supporting Document for Approval', '1766034740_index.php', 'assets/uploads/documents/1766034740_index.php', 2929, 'php', 1, '2025-12-18 05:12:20', 1, 0, '2025-12-18 05:12:20', '2025-12-18 05:12:20', 0, NULL),
(18, 'project', 14, 'test121', '', '1766052618_index.php', 'assets/uploads/documents/1766052618_index.php', 2929, 'php', 15, '2025-12-18 10:10:18', 1, 0, '2025-12-18 10:10:18', '2025-12-18 10:10:18', 0, NULL),
(19, 'project', 20, 'Test287', 'Supporting Document for Approval', '1766310861_logout.php', 'assets/uploads/documents/1766310861_logout.php', 542, 'php', 1, '2025-12-21 09:54:21', 1, 0, '2025-12-21 09:54:21', '2025-12-21 09:54:21', 0, NULL),
(20, 'project', 21, 'Test314', 'Supporting Document for Approval', '1766311763_logout.php', 'assets/uploads/documents/1766311763_logout.php', 542, 'php', 1, '2025-12-21 10:09:23', 1, 0, '2025-12-21 10:09:23', '2025-12-21 10:09:23', 0, NULL),
(21, 'project', 22, 'Test787', 'Supporting Document for Approval', '1766313984_login.php', 'assets/uploads/documents/1766313984_login.php', 3193, 'php', 1, '2025-12-21 10:46:24', 1, 0, '2025-12-21 10:46:24', '2025-12-21 10:46:24', 0, NULL),
(22, 'pillar', 20, 'Test6565', 'Test6565', '1766397018_index.php', 'assets/uploads/documents/1766397018_index.php', 26646, 'php', 1, '2025-12-22 09:50:18', 1, 0, '2025-12-22 09:50:18', '2025-12-22 09:50:18', 0, NULL),
(23, 'project', 23, 'Test5967', 'Supporting Document for Approval', '1766400360_index.php', 'assets/uploads/documents/1766400360_index.php', 26646, 'php', 1, '2025-12-22 10:46:00', 1, 0, '2025-12-22 10:46:00', '2025-12-22 10:46:00', 0, NULL),
(24, 'project', 7, 'Test787', 'Supporting Document for Approval', '1766406191_index.php', 'assets/uploads/documents/1766406191_index.php', 26646, 'php', 1, '2025-12-22 12:23:11', 1, 0, '2025-12-22 12:23:11', '2025-12-22 12:23:11', 0, NULL),
(25, 'project', 24, 'Test787', 'Supporting Document for Approval', '1766406788_index.php', 'assets/uploads/documents/1766406788_index.php', 26646, 'php', 1, '2025-12-22 12:33:08', 1, 0, '2025-12-22 12:33:08', '2025-12-22 12:33:08', 0, NULL),
(26, 'milestone', 2, 'test4', '', 'Amira Bumadyan.pdf', '../../assets/uploads/documents/1766469977_Amira_Bumadyan_pdf', 319610, 'pdf', 1, '2025-12-23 06:06:17', 1, 0, '2025-12-23 06:06:17', '2025-12-23 06:06:17', 0, NULL),
(27, 'initiative', 11, 'test4', '', 'Initiative Card.docx', '../../assets/uploads/documents/1766470023_Initiative_Card_docx', 2134631, 'docx', 1, '2025-12-23 06:07:03', 1, 0, '2025-12-23 06:07:03', '2025-12-23 06:07:03', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_tags`
--

CREATE TABLE `document_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_tag_map`
--

CREATE TABLE `document_tag_map` (
  `document_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `initiatives`
--

CREATE TABLE `initiatives` (
  `id` int(11) NOT NULL,
  `initiative_code` varchar(50) NOT NULL,
  `name` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `impact` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `pillar_id` int(11) NOT NULL,
  `strategic_objective_id` int(11) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `budget_min` decimal(15,2) DEFAULT NULL,
  `budget_max` decimal(15,2) DEFAULT NULL,
  `approved_budget` decimal(15,2) DEFAULT NULL,
  `budget_item` varchar(255) DEFAULT NULL,
  `spent_budget` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `progress_percentage` int(11) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_frequency` enum('daily','weekly','monthly','every_2_days') DEFAULT 'weekly',
  `update_time` time DEFAULT '09:00:00',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `initiatives`
--

INSERT INTO `initiatives` (`id`, `initiative_code`, `name`, `description`, `impact`, `notes`, `pillar_id`, `strategic_objective_id`, `owner_user_id`, `budget_min`, `budget_max`, `approved_budget`, `budget_item`, `spent_budget`, `start_date`, `due_date`, `completion_date`, `status_id`, `priority`, `progress_percentage`, `order_index`, `created_at`, `updated_at`, `update_frequency`, `update_time`, `is_deleted`, `deleted_at`) VALUES
(2, 'INIT-2.01', 'Test2', 'Test2 Test2', 'Test2 Test2', 'Test2', 8, 3, 1, 10000.00, 20000.00, NULL, NULL, 0.00, '2025-12-17', '2026-01-09', NULL, 1, 'medium', 0, 0, '2025-12-06 05:44:47', '2025-12-22 11:13:31', 'weekly', '09:00:00', 0, NULL),
(10, 'INIT-2025-4FC4', 'Test65653', 'Test65653', NULL, NULL, 1, 1, 7, 20000.00, 40000.00, 20000.00, 'Test65653', 0.00, '2025-12-27', '2026-01-17', NULL, 1, 'medium', 0, 0, '2025-12-22 12:50:29', '2025-12-22 19:57:02', 'daily', '09:00:00', 0, NULL),
(11, 'INIT-2025-E561', 'Test145', 'submit_approval', NULL, NULL, 1, 1, 7, 0.00, 0.00, 0.00, NULL, 0.00, '2025-12-24', '2026-01-09', NULL, 9, 'medium', 20, 0, '2025-12-22 13:00:13', '2025-12-22 19:55:16', 'weekly', '09:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `initiative_milestones`
--

CREATE TABLE `initiative_milestones` (
  `id` int(11) NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `cost_amount` decimal(15,2) DEFAULT 0.00,
  `cost_spent` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `initiative_milestones`
--

INSERT INTO `initiative_milestones` (`id`, `initiative_id`, `name`, `description`, `start_date`, `due_date`, `completion_date`, `status_id`, `progress`, `order_index`, `cost_amount`, `cost_spent`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 11, 'Test2', 'milestonesmilestones', '2025-12-26', '2025-12-31', NULL, 1, 0, 0, 0.00, 0.00, '2025-12-22 19:23:38', '2025-12-22 19:23:38', 0, NULL),
(2, 11, 'Test3', 'milestones', '2026-01-01', '2026-01-09', NULL, 2, 23, 0, 0.00, 0.00, '2025-12-22 19:31:38', '2025-12-22 19:55:16', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `initiative_objectives`
--

CREATE TABLE `initiative_objectives` (
  `id` int(11) NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `strategic_objective_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiative_objectives`
--

INSERT INTO `initiative_objectives` (`id`, `initiative_id`, `strategic_objective_id`) VALUES
(1, 10, 1),
(2, 11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `initiative_roles`
--

CREATE TABLE `initiative_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiative_roles`
--

INSERT INTO `initiative_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Project Manager', NULL, '2025-11-28 09:34:33'),
(2, 'Team Member', NULL, '2025-11-28 09:35:15'),
(3, 'Coordinator', NULL, '2025-11-28 09:35:15'),
(4, 'Viewer', NULL, '2025-11-28 09:35:15');

-- --------------------------------------------------------

--
-- Table structure for table `initiative_role_permissions`
--

CREATE TABLE `initiative_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiative_role_permissions`
--

INSERT INTO `initiative_role_permissions` (`role_id`, `permission_id`) VALUES
(1, 200),
(1, 201),
(1, 202),
(1, 203),
(1, 210),
(1, 211),
(1, 212),
(1, 213),
(1, 214),
(1, 220),
(1, 221),
(1, 230),
(1, 231),
(1, 232),
(1, 240),
(1, 241),
(1, 242),
(1, 250),
(1, 260),
(1, 261),
(1, 270),
(1, 280),
(2, 200),
(2, 210),
(2, 214),
(2, 230),
(2, 232),
(2, 240),
(2, 241),
(2, 261),
(2, 270),
(3, 200),
(3, 201),
(3, 210),
(3, 211),
(3, 212),
(3, 214),
(3, 220),
(3, 221),
(3, 230),
(3, 232),
(3, 240),
(3, 241),
(3, 242),
(3, 260),
(3, 270),
(4, 100),
(4, 200),
(4, 210),
(4, 220),
(4, 230),
(4, 240),
(4, 261),
(4, 300),
(4, 310),
(4, 330);

-- --------------------------------------------------------

--
-- Table structure for table `initiative_statuses`
--

CREATE TABLE `initiative_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(10) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiative_statuses`
--

INSERT INTO `initiative_statuses` (`id`, `name`, `color`, `sort_order`) VALUES
(1, 'Pending Review', '#3ef4df', 1),
(2, 'Strategy Review', '#3498db', 2),
(3, 'Budget Review', '#f1c40f', 3),
(4, 'Waiting CEO Approval', '#f39c12', 4),
(5, 'Approved', '#2ecc71', 5),
(6, 'Returned for Revision', '#e67e22', 6),
(7, 'Rejected', '#e74c3c', 7),
(8, 'Draft', '#bdc3c7', 0),
(9, 'In Progress', '#9b59b6', 8),
(10, 'Completed', '#16a085', 9);

-- --------------------------------------------------------

--
-- Table structure for table `initiative_tasks`
--

CREATE TABLE `initiative_tasks` (
  `id` int(11) NOT NULL,
  `milestone_id` int(11) DEFAULT NULL,
  `initiative_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `priority_id` int(11) NOT NULL,
  `weight` int(11) DEFAULT 1,
  `cost_estimate` decimal(15,2) DEFAULT 0.00,
  `cost_spent` decimal(15,2) DEFAULT 0.00,
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `initiative_tasks`
--

INSERT INTO `initiative_tasks` (`id`, `milestone_id`, `initiative_id`, `title`, `description`, `assigned_to`, `start_date`, `due_date`, `status_id`, `priority_id`, `weight`, `cost_estimate`, `cost_spent`, `progress`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, NULL, 11, 'test1', 'tasks', 14, '2025-12-25', '2025-12-27', 1, 3, 3, 0.00, 0.00, 18, '2025-12-22 19:53:58', '2025-12-22 19:56:01', 0, NULL),
(2, 2, 11, 'test2', '', 14, '2026-01-01', '2026-01-03', 1, 1, 2, 0.00, 0.00, 23, '2025-12-22 19:55:07', '2025-12-22 19:56:05', 0, NULL),
(3, NULL, 10, 'tasks', '', 7, '2025-12-29', '2025-12-31', 1, 1, 1, 0.00, 0.00, 0, '2025-12-22 19:56:44', '2025-12-22 19:57:02', 0, NULL);

--
-- Triggers `initiative_tasks`
--
DELIMITER $$
CREATE TRIGGER `trg_it_before_insert` BEFORE INSERT ON `initiative_tasks` FOR EACH ROW BEGIN
  IF NEW.milestone_id IS NOT NULL AND
     NOT EXISTS (
       SELECT 1 FROM initiative_milestones
       WHERE id = NEW.milestone_id
         AND initiative_id = NEW.initiative_id
     ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Milestone does not belong to the same initiative.';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_it_before_update` BEFORE UPDATE ON `initiative_tasks` FOR EACH ROW BEGIN
  IF NEW.milestone_id IS NOT NULL AND
     NOT EXISTS (
       SELECT 1 FROM initiative_milestones
       WHERE id = NEW.milestone_id
         AND initiative_id = NEW.initiative_id
     ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Milestone does not belong to the same initiative.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `initiative_team`
--

CREATE TABLE `initiative_team` (
  `id` int(11) NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `initiative_team`
--

INSERT INTO `initiative_team` (`id`, `initiative_id`, `user_id`, `role_id`, `is_active`, `created_at`) VALUES
(2, 10, 7, 1, 1, '2025-12-22 12:50:29'),
(3, 11, 7, 1, 1, '2025-12-22 13:00:13'),
(4, 11, 14, 2, 1, '2025-12-22 18:46:42');

-- --------------------------------------------------------

--
-- Table structure for table `initiative_user_permissions`
--

CREATE TABLE `initiative_user_permissions` (
  `id` int(11) NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `is_granted` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpis`
--

CREATE TABLE `kpis` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_value` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `baseline_value` decimal(15,2) DEFAULT NULL,
  `approved_value` decimal(15,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `kpi_type` varchar(50) DEFAULT 'number',
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `data_source` varchar(255) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `last_updated` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `kpis`
--

INSERT INTO `kpis` (`id`, `name`, `description`, `target_value`, `current_value`, `baseline_value`, `approved_value`, `unit`, `kpi_type`, `frequency`, `data_source`, `status_id`, `owner_id`, `parent_type`, `parent_id`, `last_updated`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 'Test2', 'Test2', 10.00, 7.00, 0.00, NULL, 'software', 'number', 'weekly', NULL, 3, 8, 'project', 5, '2025-12-16 08:20:35', '2025-12-11 08:08:13', '2025-12-16 05:20:35', 0, NULL),
(2, 'Test2', '', 10.00, 0.00, 0.00, NULL, 'software', 'number', 'weekly', '', 1, 7, 'project', 19, NULL, '2025-12-20 16:09:43', '2025-12-20 16:09:43', 0, NULL),
(3, 'Test2', '', 100.00, 0.00, 0.00, NULL, 'test', 'percentage', 'monthly', NULL, 3, 7, 'initiative', 10, NULL, '2025-12-22 20:20:38', '2025-12-22 20:20:52', 1, NULL),
(4, 'test1', '', 10.00, 2.00, 0.00, NULL, 'software', 'number', 'monthly', NULL, 2, 7, 'initiative', 11, '2025-12-23 11:25:13', '2025-12-23 08:25:01', '2025-12-23 08:25:13', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_statuses`
--

CREATE TABLE `kpi_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_statuses`
--

INSERT INTO `kpi_statuses` (`id`, `status_name`, `created_at`) VALUES
(1, 'On Track', '2025-11-27 07:14:21'),
(2, 'At Risk', '2025-11-27 07:14:21'),
(3, 'Needs Work', '2025-11-27 07:14:21'),
(4, 'Achieved', '2025-11-27 07:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `meeting_minutes`
--

CREATE TABLE `meeting_minutes` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project','department','pillar') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `topic` varchar(300) NOT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` varchar(50) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `attendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attendees`)),
  `absentees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`absentees`)),
  `agenda` text DEFAULT NULL,
  `topics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`topics`)),
  `next_meeting_datetime` varchar(100) DEFAULT NULL,
  `adjournment_time` varchar(100) DEFAULT NULL,
  `prepared_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meeting_minutes`
--

INSERT INTO `meeting_minutes` (`id`, `parent_type`, `parent_id`, `topic`, `meeting_date`, `meeting_time`, `venue`, `attendees`, `absentees`, `agenda`, `topics`, `next_meeting_datetime`, `adjournment_time`, `prepared_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'initiative', 11, 'test1', '2025-12-23', '07:45', 'zoom', '[\"7\",\"14\"]', NULL, 'zoom\r\nzoom\r\nzoom', '[{\"point\":\"zoom\",\"type\":\"info\",\"owner\":\"Asmaa\"},{\"point\":\"Strategy\",\"type\":\"decision\",\"owner\":\"Strategy\"}]', NULL, NULL, 1, NULL, '2025-12-23 06:47:13', '2025-12-23 06:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `milestone_statuses`
--

CREATE TABLE `milestone_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `milestone_statuses`
--

INSERT INTO `milestone_statuses` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'upcoming', NULL, '2025-11-27 08:58:20'),
(2, 'in_progress', NULL, '2025-11-27 08:58:20'),
(3, 'completed', NULL, '2025-11-27 08:58:20'),
(4, 'delayed', NULL, '2025-11-27 08:58:20'),
(5, 'on_hold', NULL, '2025-11-27 08:58:20');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `report_month` char(7) NOT NULL,
  `report_type` varchar(100) DEFAULT 'Monthly Achievement Report',
  `owner_id` int(11) NOT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `report_date` date DEFAULT curdate(),
  `executive_summary` text DEFAULT NULL,
  `completed_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_work`)),
  `quantitative_indicators` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quantitative_indicators`)),
  `challenges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`challenges`)),
  `next_month_priorities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`next_month_priorities`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','mention','approval','task','reminder') DEFAULT 'info',
  `related_entity_type` varchar(50) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_entity_type`, `related_entity_id`, `is_read`, `read_at`, `created_at`) VALUES
(1, 3, 'Pillar sent for review', 'Pillar #2 - \"Test2\" requires your review.', 'approval', 'pillar', 8, 0, NULL, '2025-12-03 12:43:34'),
(2, 3, 'Pillar sent for review', 'Pillar #3 - \"Test3\" requires your review.', 'approval', 'pillar', 9, 0, NULL, '2025-12-04 05:47:33'),
(3, 7, 'Pillar returned for revision', 'Pillar #3 - \"Test3\" was returned for revision by Strategy Office.', 'approval', 'pillar', 9, 0, NULL, '2025-12-04 05:48:49'),
(4, 3, 'Pillar sent for review', 'Pillar #3 - \"Test3\" requires your review.', 'approval', 'pillar', 9, 0, NULL, '2025-12-04 05:49:14'),
(5, 5, 'Approval Request', 'Project \'Test5\' requires your approval.', 'approval', 'project', 5, 0, NULL, '2025-12-09 18:35:29'),
(6, 11, 'Approval Request', 'Project \'Test5\' requires your approval.', 'approval', 'project', 5, 0, NULL, '2025-12-09 18:39:51'),
(7, 4, 'Approval Request', 'Project \'Test5\' requires your approval.', 'approval', 'project', 5, 0, NULL, '2025-12-09 18:40:46'),
(8, 5, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:08:29'),
(9, 8, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:08:29'),
(10, 10, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:08:29'),
(11, 12, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:08:29'),
(12, 5, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:09:41'),
(13, 8, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:09:41'),
(14, 10, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:09:41'),
(15, 12, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:09:41'),
(16, 11, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:09:54'),
(17, 4, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:44:55'),
(18, 11, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-10 05:53:59'),
(19, 5, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:50:43'),
(20, 8, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:50:43'),
(21, 10, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:50:43'),
(22, 12, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:50:43'),
(23, 11, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:52:01'),
(24, 4, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 10:52:46'),
(25, 5, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:33:16'),
(26, 8, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:33:16'),
(27, 10, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:33:16'),
(28, 12, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:33:16'),
(29, 11, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:36:15'),
(30, 4, 'Approval Request', 'Project \'Test2555\' requires your approval.', 'approval', 'project', 6, 0, NULL, '2025-12-11 11:39:14'),
(31, 4, 'Approval Request', 'Project \'Test265\' requires your approval.', 'approval', 'project', 3, 0, NULL, '2025-12-11 11:46:19'),
(32, 3, 'Approval Request', 'Project \'Test558\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-14 05:22:21'),
(33, 3, 'Approval Request', 'Project \'Test23332\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-14 05:50:45'),
(34, 5, 'Approval Request', 'Project \'IT-project\' requires your approval.', 'approval', 'project', 7, 0, NULL, '2025-12-15 11:40:27'),
(35, 8, 'Approval Request', 'Project \'IT-project\' requires your approval.', 'approval', 'project', 7, 0, NULL, '2025-12-15 11:40:27'),
(36, 10, 'Approval Request', 'Project \'IT-project\' requires your approval.', 'approval', 'project', 7, 0, NULL, '2025-12-15 11:40:27'),
(37, 12, 'Approval Request', 'Project \'IT-project\' requires your approval.', 'approval', 'project', 7, 0, NULL, '2025-12-15 11:40:27'),
(38, 5, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:46:38'),
(39, 8, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:46:38'),
(40, 10, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:46:38'),
(41, 12, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:46:38'),
(42, 4, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:56:25'),
(43, 5, 'Approval Request', 'Project \'Test678\' requires your approval.', 'approval', 'project', 11, 0, NULL, '2025-12-16 09:42:14'),
(44, 8, 'Approval Request', 'Project \'Test678\' requires your approval.', 'approval', 'project', 11, 0, NULL, '2025-12-16 09:42:14'),
(45, 10, 'Approval Request', 'Project \'Test678\' requires your approval.', 'approval', 'project', 11, 0, NULL, '2025-12-16 09:42:14'),
(46, 12, 'Approval Request', 'Project \'Test678\' requires your approval.', 'approval', 'project', 11, 0, NULL, '2025-12-16 09:42:14'),
(47, 4, 'Approval Request', 'Project \'Test678\' requires your approval.', 'approval', 'project', 11, 0, NULL, '2025-12-16 09:42:50'),
(48, 5, 'Approval Request', 'Project \'Test987\' requires your approval.', 'approval', 'project', 10, 0, NULL, '2025-12-16 09:42:55'),
(49, 8, 'Approval Request', 'Project \'Test987\' requires your approval.', 'approval', 'project', 10, 0, NULL, '2025-12-16 09:42:55'),
(50, 10, 'Approval Request', 'Project \'Test987\' requires your approval.', 'approval', 'project', 10, 0, NULL, '2025-12-16 09:42:55'),
(51, 12, 'Approval Request', 'Project \'Test987\' requires your approval.', 'approval', 'project', 10, 0, NULL, '2025-12-16 09:42:55'),
(52, 4, 'Approval Request', 'Project \'Test987\' requires your approval.', 'approval', 'project', 10, 0, NULL, '2025-12-16 09:43:02'),
(53, 4, 'Approval Request', 'Project \'Test787\' requires your approval.', 'approval', 'project', 9, 0, NULL, '2025-12-16 09:44:49'),
(54, 5, 'Approval Request', 'Project \'Test996\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-16 09:47:29'),
(55, 8, 'Approval Request', 'Project \'Test996\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-16 09:47:29'),
(56, 10, 'Approval Request', 'Project \'Test996\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-16 09:47:29'),
(57, 11, 'Approval Request', 'Project \'Test996\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-16 09:47:29'),
(58, 12, 'Approval Request', 'Project \'Test996\' requires your approval.', 'approval', 'project', 12, 0, NULL, '2025-12-16 09:47:29'),
(59, 5, 'Approval Request', 'Project \'Test5654\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-16 10:07:21'),
(60, 8, 'Approval Request', 'Project \'Test5654\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-16 10:07:21'),
(61, 10, 'Approval Request', 'Project \'Test5654\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-16 10:07:21'),
(62, 11, 'Approval Request', 'Project \'Test5654\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-16 10:07:21'),
(63, 12, 'Approval Request', 'Project \'Test5654\' requires your approval.', 'approval', 'project', 13, 0, NULL, '2025-12-16 10:07:21'),
(64, 8, 'Approval Request', 'Project \'Test564\' requires your approval.', 'approval', 'project', 14, 0, NULL, '2025-12-16 10:13:01'),
(65, 4, 'Approval Request', 'Project \'Test564\' requires your approval.', 'approval', 'project', 14, 0, NULL, '2025-12-16 10:14:23'),
(66, 5, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:32:32'),
(67, 8, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:32:32'),
(68, 10, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:32:32'),
(69, 11, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:32:32'),
(70, 12, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:32:32'),
(71, 4, 'Approval Request', 'Project \'Test6587\' requires your approval.', 'approval', 'project', 15, 0, NULL, '2025-12-16 10:34:13'),
(72, 8, 'Approval Request', 'Project \'Test357\' requires your approval.', 'approval', 'project', 16, 0, NULL, '2025-12-16 10:39:28'),
(73, 11, 'Approval Request', 'Project \'Test4562\' requires your approval.', 'approval', 'project', 17, 0, NULL, '2025-12-16 10:59:42'),
(74, 4, 'Approval Request', 'Project \'Test4562\' requires your approval.', 'approval', 'project', 17, 0, NULL, '2025-12-16 10:59:52'),
(75, 4, 'Approval Request', 'Project \'Test357\' requires your approval.', 'approval', 'project', 16, 0, NULL, '2025-12-16 11:03:15'),
(76, 4, 'Approval Request', 'Project \'Test789\' requires your approval.', 'approval', 'project', 18, 0, NULL, '2025-12-18 04:51:05'),
(77, 4, 'Approval Request', 'Project \'Test587\' requires your approval.', 'approval', 'project', 19, 0, NULL, '2025-12-18 05:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `operational_projects`
--

CREATE TABLE `operational_projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `initiative_id` int(11) DEFAULT NULL,
  `budget_min` decimal(15,2) DEFAULT NULL,
  `budget_max` decimal(15,2) DEFAULT NULL,
  `approved_budget` decimal(15,2) DEFAULT NULL,
  `budget_item` varchar(255) DEFAULT NULL,
  `spent_budget` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `visibility` enum('public','private') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_frequency` enum('daily','weekly','monthly','every_2_days') DEFAULT 'weekly',
  `update_time` time DEFAULT '09:00:00',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `operational_projects`
--

INSERT INTO `operational_projects` (`id`, `project_code`, `name`, `description`, `department_id`, `manager_id`, `initiative_id`, `budget_min`, `budget_max`, `approved_budget`, `budget_item`, `spent_budget`, `start_date`, `end_date`, `status_id`, `progress_percentage`, `priority`, `visibility`, `created_at`, `updated_at`, `update_frequency`, `update_time`, `is_deleted`, `deleted_at`) VALUES
(1, 'OP-2025-0001', 'Test2', 'Test2 Test2', 1, 1, NULL, 10000.00, 20000.00, NULL, NULL, 0.00, '2025-12-16', '2026-01-08', 2, 0, 'high', 'private', '2025-12-07 07:58:27', '2025-12-07 12:37:31', 'every_2_days', '09:00:00', 1, '2025-12-07 15:37:31'),
(2, 'OP-2025-0002', 'Test255', 'Test255Test255', 1, 10, NULL, 15000.00, 25000.00, NULL, NULL, 0.00, '2025-12-10', '2026-01-10', 2, 0, 'high', 'private', '2025-12-07 11:20:33', '2025-12-22 12:29:04', 'every_2_days', '09:00:00', 0, NULL),
(3, 'OP-2025-0003', 'Test265', 'Test265Test265', 1, 10, NULL, 20000.00, 40000.00, 2000.00, NULL, 0.00, '2025-12-11', '2026-01-10', 5, 0, 'critical', 'private', '2025-12-07 11:47:05', '2025-12-20 21:07:18', 'every_2_days', '09:00:00', 0, NULL),
(4, 'OP-2025-0004', 'Test2552', 'Test2552', 2, 15, NULL, 10000.00, 20000.00, 15000.00, NULL, 0.00, '2025-12-11', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-09 10:30:16', '2025-12-10 12:11:01', 'every_2_days', '09:00:00', 0, NULL),
(5, 'OP-2025-0005', 'Test5', 'Test5Test5', 1, 3, NULL, 15000.00, 30000.00, 25000.00, NULL, 31500.00, '2025-12-11', '2026-01-10', 8, 100, 'medium', 'public', '2025-12-09 18:34:44', '2025-12-18 05:29:31', 'every_2_days', '09:00:00', 0, NULL),
(6, 'OP-2025-0006', 'Test2555', 'ققبقبقبق', 2, 10, NULL, 1000.00, 2000.00, 2000.00, NULL, 0.00, '2025-12-12', '2026-01-15', 6, 50, 'medium', 'private', '2025-12-11 11:32:45', '2025-12-20 21:05:13', 'daily', '09:00:00', 0, NULL),
(7, 'OP-2025-0007', 'IT-project', 'IT-project', 1, 1, NULL, 10000.00, 20000.00, 20000.00, 'Test987Test987', 0.00, '2025-12-24', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-15 09:58:58', '2025-12-22 12:26:33', 'weekly', '09:00:00', 0, NULL),
(8, 'OP-2025-0008', 'IT-project2', 'IT-2IT-2', 5, 7, NULL, 10000.00, 30000.00, 25000.00, NULL, 0.00, '2025-12-17', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-15 11:45:53', '2025-12-15 11:57:15', 'daily', '09:00:00', 0, NULL),
(9, 'OP-2025-0009', 'Test787', 'Test787Test787', 1, 14, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-17', '2026-01-08', 2, 0, 'medium', 'private', '2025-12-16 07:57:29', '2025-12-16 08:10:31', 'weekly', '09:00:00', 0, NULL),
(10, 'OP-2025-0010', 'Test987', 'Test987', 1, 8, NULL, 15000.00, 30000.00, 29000.00, 'Test987Test987', 0.00, '2025-12-24', '2026-01-16', 5, 0, 'medium', 'private', '2025-12-16 08:11:46', '2025-12-22 12:19:51', 'weekly', '09:00:00', 0, NULL),
(11, 'OP-2025-0011', 'Test678', 'Test678Test678', 1, 8, NULL, 15000.00, 20000.00, 20000.00, 'Test987Test987', 0.00, '2026-01-02', '2026-01-15', 5, 0, 'medium', 'private', '2025-12-16 09:31:07', '2025-12-22 12:10:08', 'weekly', '09:00:00', 0, NULL),
(12, 'OP-2025-0012', 'Test996', 'Test996', 1, 14, NULL, 15000.00, 30000.00, 25000.00, 'Test996', 0.00, '2025-12-25', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-16 09:47:01', '2025-12-21 09:44:30', 'weekly', '09:00:00', 0, NULL),
(13, 'OP-2025-0013', 'Test5654', 'Test5654', 1, 14, NULL, 10000.00, 30000.00, 20000.00, 'Test5654', 0.00, '2025-12-25', '2026-01-09', 5, 0, 'medium', 'private', '2025-12-16 10:01:20', '2025-12-20 18:26:19', 'weekly', '09:00:00', 0, NULL),
(14, 'OP-2025-0014', 'Test564', 'Test564Test564', 1, 14, NULL, 20000.00, 30000.00, 10000.00, 'Test987Test987', 0.00, '2025-12-17', '2026-01-10', 6, 42, 'medium', 'private', '2025-12-16 10:12:35', '2025-12-21 08:30:46', 'weekly', '09:00:00', 0, NULL),
(15, 'OP-2025-0015', 'Test6587', 'Test6587', 1, 14, NULL, 20000.00, 40000.00, 30000.00, 'Test6587', 0.00, '2025-12-18', '2026-01-08', 6, 50, 'critical', 'private', '2025-12-16 10:31:57', '2025-12-23 10:27:09', 'weekly', '09:00:00', 0, NULL),
(16, 'OP-2025-0016', 'Test357', 'Test357', 1, 14, NULL, 20000.00, 40000.00, 10000.00, 'Test357', 0.00, '2025-12-17', '2026-01-10', 8, 100, 'medium', 'public', '2025-12-16 10:37:20', '2025-12-18 05:53:20', 'weekly', '09:00:00', 0, NULL),
(17, 'OP-2025-0017', 'Test4562', 'Test4562', 5, 7, NULL, 20000.00, 30000.00, 15000.00, 'Test4562', 0.00, '2025-12-18', '2026-01-10', 4, 0, 'medium', 'private', '2025-12-16 10:58:27', '2025-12-18 04:55:59', 'weekly', '09:00:00', 0, NULL),
(18, 'OP-2025-0018', 'Test789', 'Test789', 1, 15, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-18', '2025-12-27', 8, 100, 'medium', 'private', '2025-12-18 04:49:59', '2025-12-18 05:53:48', 'daily', '09:00:00', 0, NULL),
(19, 'OP-2025-0019', 'Test587', 'Test587', 5, 7, NULL, 0.00, 0.00, NULL, NULL, 0.00, '2025-12-18', '2025-12-19', 6, 75, 'medium', 'private', '2025-12-18 05:12:08', '2025-12-20 21:05:25', 'every_2_days', '09:00:00', 0, NULL),
(20, 'OP-2025-0020', 'Test287', 'Test287', 5, 7, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-23', '2026-01-10', 5, 0, 'high', 'private', '2025-12-21 09:47:27', '2025-12-21 10:07:40', 'daily', '09:00:00', 0, NULL),
(21, 'OP-2025-0021', 'Test314', 'Test314', 1, 1, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-23', '2026-01-10', 2, 0, 'high', 'private', '2025-12-21 10:09:11', '2025-12-21 10:09:28', 'daily', '09:00:00', 0, NULL),
(22, 'OP-2025-0022', 'Test28888', '', 1, 14, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-24', '2026-01-10', 6, 50, 'medium', 'private', '2025-12-21 10:46:00', '2025-12-21 10:53:28', 'weekly', '09:00:00', 0, NULL),
(23, 'OP-2025-0023', 'Test5967', 'Test5967', 1, 14, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-23', '2026-01-09', 5, 0, 'medium', 'private', '2025-12-22 10:44:44', '2025-12-22 10:46:56', 'daily', '09:00:00', 0, NULL),
(24, 'OP-2025-0024', 'Test7854', 'Test7854', 1, 14, NULL, 20000.00, 30000.00, 30000.00, 'Test987Test987', 0.00, '2025-12-24', '2026-01-02', 5, 0, 'medium', 'private', '2025-12-22 12:32:52', '2025-12-22 12:34:26', 'weekly', '09:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `operational_project_statuses`
--

CREATE TABLE `operational_project_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(10) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operational_project_statuses`
--

INSERT INTO `operational_project_statuses` (`id`, `name`, `color`, `sort_order`) VALUES
(1, 'Draft', '#bdc3c7', 1),
(2, 'Pending Approval', '#f1c40f', 2),
(3, 'Returned', '#e67e22', 3),
(4, 'Rejected', '#e74c3c', 4),
(5, 'Approved', '#2ecc71', 5),
(6, 'In Progress', '#3498db', 6),
(7, 'On Hold', '#9b59b6', 7),
(8, 'Completed', '#16a085', 8);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_key` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_key`, `description`, `module`) VALUES
(10, 'sys_user_view', 'View users list and profiles', 'users'),
(11, 'sys_user_create', 'Create new system users', 'users'),
(12, 'sys_user_edit', 'Edit user basic details', 'users'),
(13, 'sys_user_password', 'Reset user passwords', 'users'),
(14, 'sys_user_status', 'Activate/Deactivate users', 'users'),
(15, 'sys_user_delete', 'Soft delete users', 'users'),
(16, 'sys_user_assign_role', 'Change user system role', 'users'),
(20, 'sys_dept_view', 'View departments list', 'departments'),
(21, 'sys_dept_create', 'Create new department', 'departments'),
(22, 'sys_dept_edit', 'Edit department info & manager', 'departments'),
(23, 'sys_dept_branches', 'Manage department branches', 'departments'),
(24, 'sys_dept_delete', 'Delete department', 'departments'),
(30, 'sys_role_view', 'View roles and permissions matrix', 'settings'),
(31, 'sys_role_manage', 'Create/Edit/Delete roles', 'settings'),
(32, 'sys_perm_assign', 'Assign permissions to roles', 'settings'),
(40, 'sys_view_logs', 'View system activity logs', 'system'),
(41, 'sys_settings_manage', 'Manage general system settings', 'system'),
(100, 'pillar_view', 'View Pillar Dashboard & Overview', 'pillars'),
(101, 'pillar_edit_basic', 'Edit Pillar Name/Description', 'pillars'),
(102, 'pillar_manage_objectives', 'Add/Edit/Delete Strategic Objectives', 'pillars'),
(103, 'pillar_manage_team', 'Add/Remove Pillar Team Members', 'pillars'),
(104, 'pillar_manage_docs', 'Upload/Delete Documents', 'pillars'),
(105, 'pillar_approve_init', 'Approve linked Initiatives', 'pillars'),
(200, 'init_view_dashboard', 'View Initiative Overview', 'initiatives'),
(201, 'init_edit_basic', 'Edit Initiative Details (Dates/Budget)', 'initiatives'),
(202, 'init_manage_team', 'Manage Initiative Team', 'initiatives'),
(203, 'init_submit_approval', 'Submit for Approval', 'initiatives'),
(210, 'itask_view', 'View Initiative Tasks', 'initiative_tasks'),
(211, 'itask_create', 'Create Tasks', 'initiative_tasks'),
(212, 'itask_edit', 'Edit Task Details (Assignee, Dates)', 'initiative_tasks'),
(213, 'itask_delete', 'Delete Tasks', 'initiative_tasks'),
(214, 'itask_update_progress', 'Update Progress Only', 'initiative_tasks'),
(220, 'imilestone_view', 'View Milestones', 'initiative_milestones'),
(221, 'imilestone_manage', 'Add/Edit/Delete Milestones', 'initiative_milestones'),
(230, 'ikpi_view', 'View KPIs', 'initiative_kpis'),
(231, 'ikpi_create', 'Define New KPIs', 'initiative_kpis'),
(232, 'ikpi_update_reading', 'Update KPI Current Value', 'initiative_kpis'),
(240, 'irisk_view', 'View Risks', 'initiative_risks'),
(241, 'irisk_create', 'Identify New Risks', 'initiative_risks'),
(242, 'irisk_mitigate', 'Update Mitigation Plan', 'initiative_risks'),
(250, 'iresource_manage', 'Manage Budget & Resources', 'initiative_resources'),
(260, 'imeeting_manage', 'Create/Edit Meeting Minutes', 'initiative_meetings'),
(261, 'imeeting_view', 'View Meeting Minutes', 'initiative_meetings'),
(270, 'idoc_manage', 'Upload/Delete Documents', 'initiative_docs'),
(280, 'init_link_projects', 'Link/Unlink Operational Projects', 'initiatives'),
(300, 'proj_view_dashboard', 'View Project Dashboard', 'projects'),
(301, 'proj_edit_basic', 'Edit Project Details', 'projects'),
(302, 'proj_manage_team', 'Manage Project Team', 'projects'),
(303, 'proj_submit_approval', 'Submit Project Approval', 'projects'),
(310, 'ptask_view', 'View Tasks', 'project_tasks'),
(311, 'ptask_create', 'Create Tasks', 'project_tasks'),
(312, 'ptask_edit', 'Edit Task Details', 'project_tasks'),
(313, 'ptask_delete', 'Delete Tasks', 'project_tasks'),
(314, 'ptask_update_progress', 'Update Task Progress', 'project_tasks'),
(320, 'pmilestone_manage', 'Manage Milestones', 'project_milestones'),
(330, 'pkpi_view', 'View KPIs', 'project_kpis'),
(331, 'pkpi_manage', 'Manage KPIs', 'project_kpis'),
(332, 'pkpi_update_reading', 'Update KPI Reading', 'project_kpis'),
(340, 'prisk_manage', 'Manage Risks', 'project_risks'),
(341, 'prisk_create', 'Create Risk', 'project_risks'),
(350, 'presource_manage', 'Manage Resources', 'project_resources'),
(360, 'pdoc_manage', 'Manage Documents', 'project_docs'),
(361, 'sys_manage_announcements', 'Create, Edit, and Delete System Announcements', 'system'),
(362, 'sys_ann_view', 'View announcements archive', 'system'),
(363, 'sys_ann_create', 'Post new announcements', 'system'),
(364, 'sys_ann_edit', 'Edit existing announcements', 'system'),
(365, 'sys_ann_delete', 'Archive/Delete announcements', 'system');

-- --------------------------------------------------------

--
-- Table structure for table `pillars`
--

CREATE TABLE `pillars` (
  `id` int(11) NOT NULL,
  `pillar_number` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `lead_user_id` int(11) DEFAULT NULL,
  `spent_budget` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `color` varchar(7) DEFAULT '#FF8C00',
  `icon` varchar(100) DEFAULT 'fa-building',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `pillars`
--

INSERT INTO `pillars` (`id`, `pillar_number`, `name`, `description`, `lead_user_id`, `spent_budget`, `start_date`, `end_date`, `status_id`, `progress_percentage`, `color`, `icon`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 5, 'Sustainable Investment Growth', 'Focus on diversifying revenue streams and achieving financial sustainability through strategic partnerships and environmental initiatives.', 1, 0.00, '2025-11-08', '2025-12-31', 3, 10, '#ff8c00', 'fa-building', '2025-11-28 08:02:38', '2025-12-23 05:50:41', 0, NULL),
(3, 1, 'Test1', 'Test1', 1, 0.00, '2025-12-02', '2025-12-17', 9, 0, '#12e2d5', 'fa-pencil', '2025-12-01 10:18:10', '2025-12-21 19:24:16', 1, '2025-12-21 22:24:16'),
(8, 2, 'Test2', 'Test2', 3, 0.00, '2025-12-05', '2025-12-12', 9, 0, '#004cff', 'fa-building', '2025-12-03 12:38:48', '2025-12-14 09:32:30', 0, '0000-00-00 00:00:00'),
(9, 3, 'Test3', 'Test3 Test3', 3, 0.00, '2025-12-12', '2026-02-03', 9, 0, '#bb00ff', 'fa-chart-pie', '2025-12-04 05:45:25', '2025-12-04 05:49:14', 0, NULL),
(10, 6, 'Test6', 'Test6', 12, 0.00, '2025-12-17', '2026-01-01', 4, 0, '#ff0059', 'fa-sliders', '2025-12-04 10:05:40', '2025-12-22 06:55:05', 0, NULL),
(11, 7, 'Test7', 'Test7', 1, 0.00, '2025-12-18', '2026-01-07', 4, 0, '#ff0000', 'fa-coins', '2025-12-04 10:13:15', '2025-12-22 13:02:56', 0, '0000-00-00 00:00:00'),
(12, 18, 'Test558', 'عؤبيبيس', 8, 0.00, '2025-12-15', '2026-01-10', 4, 0, '#3498db', 'fa-building', '2025-12-14 05:21:56', '2025-12-22 06:55:05', 0, NULL),
(13, 23, 'Test23332', 'Test23332Test23332Test23332Test23332', 11, 0.00, '2025-12-16', '2026-01-10', 4, 0, '#072d46', 'fa-people-group', '2025-12-14 05:50:04', '2025-12-22 06:55:05', 0, NULL),
(14, 25, 'test12', 'test12', 7, 0.00, '2025-12-23', '2026-01-10', 12, 0, '#ff0000', 'fa-hourglass', '2025-12-22 05:33:03', '2025-12-22 06:10:26', 0, NULL),
(16, 356, 'addPillarMember', 'addPillarMember', 7, 0.00, '2025-12-23', '2026-01-10', 12, 0, '#a633db', 'fa-hourglass', '2025-12-22 05:37:26', '2025-12-22 05:37:26', 0, NULL),
(18, 36589, 'Test255557', 'Test255557', 7, 0.00, '2025-12-25', '2026-01-10', 12, 0, '#ff8c00', 'fa-bookmark', '2025-12-22 05:38:28', '2025-12-22 05:38:28', 0, NULL),
(19, 3569, 'Test3569', 'Test3569', 7, 0.00, '2025-12-24', '2026-01-10', 9, 0, '#ff0088', 'fa-list', '2025-12-22 05:42:39', '2025-12-22 05:47:37', 0, NULL),
(20, 65, 'Test6565', 'Test6565', 8, 0.00, '2025-12-24', '2026-01-10', 9, 0, '#00b3ff', 'fa-lightbulb', '2025-12-22 09:49:25', '2025-12-22 09:50:25', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pillar_roles`
--

CREATE TABLE `pillar_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pillar_roles`
--

INSERT INTO `pillar_roles` (`id`, `name`, `created_at`) VALUES
(1, 'Chair', '2025-12-14 08:29:34'),
(2, 'Deputy Chair', '2025-12-14 08:29:34'),
(3, 'Member', '2025-12-14 08:29:34'),
(4, 'Coordinator', '2025-12-14 08:29:34'),
(5, 'Chair', '2025-12-14 08:29:56'),
(6, 'Deputy Chair', '2025-12-14 08:29:56'),
(7, 'Member', '2025-12-14 08:29:56'),
(8, 'Coordinator', '2025-12-14 08:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `pillar_statuses`
--

CREATE TABLE `pillar_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(10) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pillar_statuses`
--

INSERT INTO `pillar_statuses` (`id`, `name`, `color`, `sort_order`) VALUES
(2, 'Pending', '#F7C948', 4),
(3, 'In Progress', '#4285F4', 5),
(4, 'On Track', '#2ECC71', 6),
(5, 'At Risk', '#E67E22', 7),
(6, 'Off Track', '#E74C3C', 8),
(7, 'Completed', '#9B59B6', 9),
(8, 'Delayed', '#7E7E7E', 10),
(9, 'Pending Review', '#3ef4df', 1),
(10, 'Waiting CEO Approval', '#F39C12', 2),
(11, 'Approved', '#27AE60', 3),
(12, 'Draft', '#BDC3C7', 0),
(13, 'Rejected', '#C0392B', 11);

-- --------------------------------------------------------

--
-- Table structure for table `pillar_team`
--

CREATE TABLE `pillar_team` (
  `id` int(11) NOT NULL,
  `pillar_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pillar_team`
--

INSERT INTO `pillar_team` (`id`, `pillar_id`, `user_id`, `role_id`, `created_at`) VALUES
(1, 13, 11, NULL, '2025-12-14 05:50:04'),
(2, 11, 8, NULL, '2025-12-14 08:00:21'),
(3, 13, 8, 2, '2025-12-14 08:36:04'),
(4, 8, 7, 1, '2025-12-22 05:41:03'),
(5, 19, 7, 1, '2025-12-22 05:42:39'),
(6, 20, 8, 1, '2025-12-22 09:49:25'),
(7, 20, 14, 3, '2025-12-22 09:49:45'),
(8, 20, 1, 4, '2025-12-22 09:49:58'),
(9, 1, 7, 1, '2025-12-22 12:43:22'),
(10, 1, 14, 3, '2025-12-22 12:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `progress_updates`
--

CREATE TABLE `progress_updates` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `update_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_milestones`
--

CREATE TABLE `project_milestones` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `cost_amount` decimal(15,2) DEFAULT 0.00,
  `cost_spent` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `project_milestones`
--

INSERT INTO `project_milestones` (`id`, `project_id`, `name`, `description`, `start_date`, `due_date`, `completion_date`, `status_id`, `progress`, `order_index`, `cost_amount`, `cost_spent`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, 5, 'Test1', 'Test1Test1Test1Test1Test1Test1', '2025-12-11', '2026-01-10', NULL, 3, 100, 0, 9000.00, 31500.00, '2025-12-10 20:33:23', '2025-12-17 05:54:48', 0, NULL),
(2, 5, 'Test2', 'خنمهنمخ', '2025-12-12', '2025-12-18', NULL, 3, 100, 0, 1000.00, 0.00, '2025-12-11 06:44:12', '2025-12-18 05:29:03', 0, NULL),
(3, 6, 'Test2', 'ييييي', '2025-12-12', '2025-12-17', NULL, 4, 50, 0, 1000.00, 0.00, '2025-12-11 11:55:54', '2025-12-20 21:05:13', 0, NULL),
(4, 5, 'Test255543', '', '2025-12-17', '2026-01-09', NULL, 1, 0, 0, 0.00, 0.00, '2025-12-14 21:03:31', '2025-12-14 21:03:31', 0, NULL),
(5, 6, 'Test3', '', '2025-12-16', '2026-01-10', NULL, 1, 0, 0, 0.00, 0.00, '2025-12-15 09:46:15', '2025-12-15 09:46:15', 0, NULL),
(6, 8, 'Test2', '', '2025-12-18', '2025-12-20', NULL, 3, 100, 0, 0.00, 0.00, '2025-12-17 05:41:55', '2025-12-17 05:45:41', 0, NULL),
(7, 15, 'Test587', '', '2025-12-18', '2025-12-26', NULL, 2, 50, 0, 0.00, 0.00, '2025-12-18 05:19:35', '2025-12-18 05:19:57', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project_objectives`
--

CREATE TABLE `project_objectives` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `objective_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_objectives`
--

INSERT INTO `project_objectives` (`id`, `project_id`, `objective_text`, `created_at`) VALUES
(1, 4, 'Test2552', '2025-12-09 10:32:11'),
(2, 3, 'Test255233', '2025-12-09 10:35:00'),
(3, 3, ' Test2552334', '2025-12-09 13:10:34'),
(4, 5, 'Test5-1', '2025-12-09 18:34:54'),
(5, 5, 'Test5-2', '2025-12-09 18:34:59'),
(6, 6, 'Test5-1', '2025-12-11 11:33:04'),
(7, 7, 'Test5-1', '2025-12-15 11:40:17'),
(8, 8, 'Test5-122', '2025-12-15 11:46:34'),
(9, 9, 'Test787', '2025-12-16 08:10:16'),
(10, 10, 'Test987', '2025-12-16 08:11:58'),
(11, 11, 'Test678', '2025-12-16 09:31:11'),
(12, 12, 'Test996', '2025-12-16 09:47:04'),
(13, 13, 'Test5654', '2025-12-16 10:01:56'),
(14, 14, 'Test564', '2025-12-16 10:12:39'),
(15, 15, 'Test6587', '2025-12-16 10:32:01'),
(16, 16, 'Test357', '2025-12-16 10:39:04'),
(17, 17, 'Test4562', '2025-12-16 10:59:02'),
(18, 18, 'Test789', '2025-12-18 04:50:03'),
(19, 19, 'Test587', '2025-12-18 05:12:13'),
(20, 20, 'Test287', '2025-12-21 09:47:32'),
(21, 21, 'Test314', '2025-12-21 10:09:15'),
(22, 22, 'Test5-1', '2025-12-21 10:46:28'),
(23, 23, 'Test5967', '2025-12-22 10:45:51'),
(24, 24, 'Test5-1', '2025-12-22 12:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `project_roles`
--

CREATE TABLE `project_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_roles`
--

INSERT INTO `project_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Team Member', NULL, '2025-12-07 10:43:37'),
(3, 'Coordinator', 'Coordinate and update progress', '2025-12-08 06:42:37'),
(4, 'Viewer', 'Read-only', '2025-12-08 06:42:37'),
(5, 'Project Manager', 'Full control on project scope', '2025-12-08 06:49:02');

-- --------------------------------------------------------

--
-- Table structure for table `project_role_permissions`
--

CREATE TABLE `project_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_role_permissions`
--

INSERT INTO `project_role_permissions` (`role_id`, `permission_id`) VALUES
(5, 360),
(5, 105),
(5, 101),
(5, 104),
(5, 102),
(5, 103),
(5, 100),
(5, 331),
(5, 332),
(5, 330),
(5, 320),
(5, 350),
(5, 341),
(5, 340),
(5, 301),
(5, 302),
(5, 303),
(5, 300),
(5, 311),
(5, 313),
(5, 312),
(5, 314),
(5, 310),
(3, 360),
(3, 320),
(3, 340),
(3, 300),
(3, 311),
(3, 312),
(3, 314),
(3, 310),
(1, 360),
(1, 341),
(1, 300),
(1, 314),
(1, 310);

-- --------------------------------------------------------

--
-- Table structure for table `project_tasks`
--

CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL,
  `milestone_id` int(11) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `priority_id` int(11) NOT NULL,
  `weight` int(11) DEFAULT 1,
  `cost_estimate` decimal(15,2) DEFAULT 0.00,
  `cost_spent` decimal(15,2) DEFAULT 0.00,
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `project_tasks`
--

INSERT INTO `project_tasks` (`id`, `milestone_id`, `project_id`, `title`, `description`, `assigned_to`, `start_date`, `due_date`, `status_id`, `priority_id`, `weight`, `cost_estimate`, `cost_spent`, `progress`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(2, 1, 5, 'Test1', '', 8, '2025-12-10', '2025-12-18', 3, 1, 1, 3000.00, 1500.00, 100, '2025-12-10 20:37:22', '2025-12-11 06:32:05', 0, NULL),
(3, 1, 5, 'test 2', 'jghjh', 8, '2025-12-12', '2025-12-19', 3, 2, 8, 2500.00, 15000.00, 100, '2025-12-11 06:14:09', '2025-12-17 05:54:38', 0, NULL),
(4, 1, 5, 'jkkjkj', 'lkllklkl', 8, '2025-12-12', '2026-01-08', 3, 2, 1, 5000.00, 10000.00, 100, '2025-12-11 06:16:26', '2025-12-17 05:54:48', 0, NULL),
(5, 1, 5, 'test3', 'fgfgfg', 8, '2025-12-11', '2025-12-18', 3, 2, 1, 10000.00, 5000.00, 100, '2025-12-11 06:32:50', '2025-12-11 06:46:44', 0, NULL),
(6, 3, 6, 'test1', 'ddddd', 7, '2025-12-12', '2025-12-14', 2, 2, 4, 122.00, 0.00, 50, '2025-12-11 12:07:46', '2025-12-20 21:05:13', 0, NULL),
(7, 3, 6, 'test2', 'ddd', 14, '2025-12-12', '2025-12-14', 2, 2, 1, 0.00, 0.00, 50, '2025-12-11 12:08:23', '2025-12-15 17:01:06', 0, NULL),
(8, NULL, 8, 'test1', '', 11, '2025-12-18', '2025-12-18', 3, 2, 1, 0.00, 0.00, 100, '2025-12-17 05:41:19', '2025-12-17 05:41:27', 0, NULL),
(9, 6, 8, 'rtree', '', 11, '2025-12-18', '2025-12-19', 3, 2, 1, 0.00, 0.00, 100, '2025-12-17 05:42:18', '2025-12-17 05:45:41', 0, NULL),
(10, 2, 5, 'غففغف', '', 7, '2025-12-17', '2025-12-18', 3, 2, 5, 0.00, 0.00, 100, '2025-12-17 05:49:04', '2025-12-18 05:29:03', 0, NULL),
(11, NULL, 18, 'Test789-1', 'Test789', 14, '2025-12-18', '2025-12-19', 3, 2, 5, 0.00, 0.00, 100, '2025-12-18 04:52:43', '2025-12-18 05:53:48', 0, NULL),
(12, NULL, 19, 'Test587', '', 11, '2025-12-18', '2025-12-19', 2, 2, 1, 0.00, 0.00, 50, '2025-12-18 05:14:17', '2025-12-18 05:14:23', 0, NULL),
(13, 7, 15, 'Test587', '', 13, '2025-12-18', '2025-12-19', 2, 2, 4, 0.00, 0.00, 50, '2025-12-18 05:19:50', '2025-12-20 21:08:48', 0, NULL),
(14, NULL, 16, 'Test587', '', 11, '2025-12-18', '2025-12-18', 3, 2, 1, 0.00, 0.00, 100, '2025-12-18 05:53:04', '2025-12-18 05:53:20', 0, NULL),
(15, NULL, 14, 'test121', '', 15, '2025-12-19', '2025-12-19', 2, 2, 1, 0.00, 0.00, 50, '2025-12-18 06:49:54', '2025-12-18 06:52:09', 0, NULL),
(16, NULL, 14, 'test 234', '', 14, '2025-12-20', '2025-12-21', 1, 2, 1, 0.00, 0.00, 0, '2025-12-18 06:53:06', '2025-12-18 06:53:06', 0, NULL),
(17, NULL, 19, '12-14', '', 7, '2025-12-20', '2025-12-21', 3, 2, 1, 0.00, 0.00, 100, '2025-12-20 15:53:40', '2025-12-20 18:23:00', 0, NULL),
(18, NULL, 14, 'test22', '', 5, '2025-12-22', '2025-12-23', 2, 2, 4, 0.00, 0.00, 50, '2025-12-21 06:34:11', '2025-12-21 08:30:46', 0, NULL),
(19, NULL, 22, 'iiii', '', 8, '2025-12-22', '2025-12-24', 2, 2, 1, 0.00, 0.00, 50, '2025-12-21 10:53:07', '2025-12-21 10:53:28', 0, NULL),
(20, NULL, 11, 'test1121', '', 14, '2025-12-23', '2025-12-24', 1, 2, 1, 0.00, 0.00, 0, '2025-12-22 12:12:21', '2025-12-22 12:12:21', 0, NULL);

--
-- Triggers `project_tasks`
--
DELIMITER $$
CREATE TRIGGER `trg_pt_before_insert` BEFORE INSERT ON `project_tasks` FOR EACH ROW BEGIN
  IF NEW.milestone_id IS NOT NULL AND
     NOT EXISTS (
       SELECT 1 FROM project_milestones
       WHERE id = NEW.milestone_id
         AND project_id = NEW.project_id
     ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Milestone does not belong to the same project.';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pt_before_update` BEFORE UPDATE ON `project_tasks` FOR EACH ROW BEGIN
  IF NEW.milestone_id IS NOT NULL AND
     NOT EXISTS (
       SELECT 1 FROM project_milestones
       WHERE id = NEW.milestone_id
         AND project_id = NEW.project_id
     ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Milestone does not belong to the same project.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `project_team`
--

CREATE TABLE `project_team` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_team`
--

INSERT INTO `project_team` (`id`, `project_id`, `user_id`, `role_id`, `is_active`, `created_at`) VALUES
(1, 1, 8, 1, 1, '2025-12-07 10:44:00'),
(2, 2, 6, 1, 1, '2025-12-07 11:21:30'),
(3, 3, 1, 1, 1, '2025-12-07 11:47:46'),
(4, 5, 8, 1, 1, '2025-12-10 13:01:10'),
(5, 5, 12, 3, 1, '2025-12-11 07:34:14'),
(6, 5, 7, 1, 1, '2025-12-11 07:44:58'),
(9, 6, 11, 4, 1, '2025-12-15 09:28:21'),
(10, 6, 7, 1, 1, '2025-12-15 09:37:01'),
(12, 6, 14, 3, 1, '2025-12-15 17:03:16'),
(13, 8, 11, 1, 1, '2025-12-15 18:13:55'),
(17, 16, 11, 1, 1, '2025-12-17 06:10:01'),
(18, 18, 14, 1, 1, '2025-12-18 04:52:08'),
(19, 19, 11, 3, 1, '2025-12-18 05:13:58'),
(20, 15, 13, 1, 1, '2025-12-18 05:19:16'),
(21, 14, 14, 1, 1, '2025-12-18 06:49:28'),
(22, 14, 15, 1, 1, '2025-12-18 06:50:54'),
(25, 19, 7, 3, 1, '2025-12-20 15:43:36'),
(26, 14, 5, 1, 1, '2025-12-21 06:33:35'),
(27, 22, 8, 1, 1, '2025-12-21 10:52:10'),
(28, 11, 14, 1, 1, '2025-12-22 12:11:09'),
(29, 12, 8, 1, 1, '2025-12-22 12:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `project_updates`
--

CREATE TABLE `project_updates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `progress_percent` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('pending','viewed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_updates`
--

INSERT INTO `project_updates` (`id`, `project_id`, `user_id`, `progress_percent`, `description`, `status`, `created_at`) VALUES
(1, 4, 3, NULL, 'sfdfdgfgfgf', 'viewed', '2025-12-11 10:16:05'),
(2, 5, 3, 50, 'bhfhbfhgdgh', 'viewed', '2025-12-11 10:24:54'),
(3, 5, 3, 50, 'test 2', 'viewed', '2025-12-11 10:30:27'),
(4, 6, 10, 67, 'ddddd', 'viewed', '2025-12-11 12:26:19'),
(5, 6, 10, 67, 'ssssss', 'viewed', '2025-12-11 12:33:43'),
(6, 14, 1, 25, 'test1', 'viewed', '2025-12-18 10:49:55'),
(7, 14, 1, 42, 'test', 'viewed', '2025-12-21 08:30:55'),
(8, 12, 1, 0, 'test', 'viewed', '2025-12-21 09:45:17'),
(9, 22, 1, 50, 'ooooo', 'viewed', '2025-12-21 11:01:32'),
(10, 22, 1, 50, 'oooooop', 'viewed', '2025-12-21 11:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `project_update_reminders`
--

CREATE TABLE `project_update_reminders` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `next_reminder_date` date NOT NULL,
  `frequency` enum('daily','every_2_days','weekly','monthly') DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_update_reminders`
--

INSERT INTO `project_update_reminders` (`id`, `project_id`, `manager_id`, `next_reminder_date`, `frequency`, `is_active`, `created_at`) VALUES
(1, 1, 5, '2025-12-26', 'every_2_days', 1, '2025-12-07 07:58:27'),
(2, 2, 10, '2025-12-26', 'every_2_days', 1, '2025-12-07 11:20:33'),
(3, 3, 10, '2025-12-26', 'every_2_days', 1, '2025-12-07 11:47:05');

-- --------------------------------------------------------

--
-- Table structure for table `project_user_permissions`
--

CREATE TABLE `project_user_permissions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `is_granted` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_types`
--

CREATE TABLE `resource_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `category` enum('material','software','service','human','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resource_types`
--

INSERT INTO `resource_types` (`id`, `type_name`, `category`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Material', 'material', NULL, 1, '2025-11-28 10:45:06', '2025-11-30 10:17:48'),
(2, 'Software', 'software', NULL, 1, '2025-11-28 10:45:06', '2025-11-30 10:17:48'),
(3, 'Venue', 'other', NULL, 1, '2025-11-28 10:45:06', '2025-11-30 10:17:48'),
(4, 'Human Resource', 'human', NULL, 1, '2025-11-28 10:45:06', '2025-11-30 10:17:48'),
(5, 'Services', 'service', NULL, 1, '2025-11-28 10:45:06', '2025-11-30 10:17:48'),
(6, 'Laptop', 'other', 'Laptop', 1, '2025-11-30 09:45:01', '2025-12-09 08:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `risk_assessments`
--

CREATE TABLE `risk_assessments` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `mitigation_plan` text DEFAULT NULL,
  `probability` tinyint(4) NOT NULL DEFAULT 2,
  `impact` tinyint(4) NOT NULL DEFAULT 2,
  `risk_score` int(11) GENERATED ALWAYS AS (`probability` * `impact`) STORED,
  `status_id` int(11) NOT NULL,
  `identified_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `risk_assessments`
--

INSERT INTO `risk_assessments` (`id`, `parent_type`, `parent_id`, `title`, `description`, `mitigation_plan`, `probability`, `impact`, `status_id`, `identified_date`, `created_at`, `updated_at`) VALUES
(1, 'project', 5, 'test1', 'test1test1test1', 'test1test1test1test1test1', 4, 5, 4, '2025-12-11', '2025-12-11 09:17:23', '2025-12-16 06:46:46'),
(2, 'initiative', 11, 'test1', 'test1', 'test1', 3, 4, 4, '2025-12-23', '2025-12-23 05:54:06', '2025-12-23 05:54:31'),
(3, 'initiative', 11, 'test2', 'test2', 'test2', 2, 5, 1, '2025-12-23', '2025-12-23 05:55:05', '2025-12-23 05:55:05'),
(4, 'project', 22, 'test', 'tes', 'test', 3, 3, 1, '2025-12-23', '2025-12-23 08:43:04', '2025-12-23 08:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `risk_statuses`
--

CREATE TABLE `risk_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `risk_statuses`
--

INSERT INTO `risk_statuses` (`id`, `status_name`, `is_active`, `created_at`) VALUES
(1, 'identified', 1, '2025-11-27 08:29:19'),
(2, 'mitigating', 1, '2025-11-27 08:29:19'),
(3, 'resolved', 1, '2025-11-27 08:29:19'),
(4, 'closed', 1, '2025-11-27 08:29:19'),
(5, 'monitoring', 1, '2025-11-27 08:29:19');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_key` varchar(100) NOT NULL,
  `role_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_key`, `role_name`, `description`) VALUES
(1, 'super_admin', 'Super Administrator', ''),
(3, 'university_president', 'University President', NULL),
(4, 'vice_president', 'Vice President', NULL),
(6, 'supervisor', 'Supervisor', ''),
(7, 'secretary', 'Secretary', NULL),
(8, 'employee', 'Employee', ''),
(9, 'auditor', 'Auditor', NULL),
(10, 'ceo', 'Chief Executive Officer', ''),
(11, 'strategy_office', 'Strategy Office', NULL),
(12, 'department_manager', 'Department Manager', ''),
(13, 'strategy_staff', 'Strategy Staff', 'Allowed to create strategic objectives'),
(14, 'finance', 'Finance Manager', '');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 30),
(1, 31),
(1, 32),
(1, 40),
(1, 41),
(1, 100),
(1, 101),
(1, 102),
(1, 103),
(1, 104),
(1, 105),
(1, 200),
(1, 201),
(1, 202),
(1, 203),
(1, 210),
(1, 211),
(1, 212),
(1, 213),
(1, 214),
(1, 220),
(1, 221),
(1, 230),
(1, 231),
(1, 232),
(1, 240),
(1, 241),
(1, 242),
(1, 250),
(1, 260),
(1, 261),
(1, 270),
(1, 280),
(1, 300),
(1, 301),
(1, 302),
(1, 303),
(1, 310),
(1, 311),
(1, 312),
(1, 313),
(1, 314),
(1, 320),
(1, 330),
(1, 331),
(1, 332),
(1, 340),
(1, 341),
(1, 350),
(1, 360),
(1, 361),
(1, 362),
(1, 363),
(1, 364),
(1, 365),
(10, 10),
(10, 20),
(10, 40);

-- --------------------------------------------------------

--
-- Table structure for table `strategic_objectives`
--

CREATE TABLE `strategic_objectives` (
  `id` int(11) NOT NULL,
  `pillar_id` int(11) NOT NULL,
  `objective_code` varchar(50) NOT NULL,
  `objective_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `strategic_objectives`
--

INSERT INTO `strategic_objectives` (`id`, `pillar_id`, `objective_code`, `objective_text`, `created_at`, `is_deleted`, `deleted_at`) VALUES
(1, 1, 'OBJ-5.1', 'Diversifying Income Streams', '2025-11-28 08:04:37', 0, NULL),
(3, 8, 'OBJ-2.1', 'Test2', '2025-12-03 12:40:08', 0, NULL),
(4, 9, 'OBJ-3.1', 'Test3 Test3', '2025-12-04 05:46:43', 0, NULL),
(5, 10, 'OBJ-6.1', 'Test6', '2025-12-04 10:06:14', 0, NULL),
(6, 11, 'OBJ-7.1', 'Test7', '2025-12-04 10:14:19', 0, NULL),
(7, 12, '1111', 'هخخهحخ', '2025-12-14 05:22:17', 0, NULL),
(8, 13, '11113', 'Test23332', '2025-12-14 05:50:40', 0, NULL),
(9, 13, 'OBJ-23.2', 'تعانتنت', '2025-12-14 09:51:39', 0, NULL),
(10, 19, 'OBJ-3569.1', 'Test3569', '2025-12-22 05:47:30', 0, NULL),
(11, 20, 'OBJ-65.1', 'Test6565', '2025-12-22 09:49:33', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_priorities`
--

CREATE TABLE `task_priorities` (
  `id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_priorities`
--

INSERT INTO `task_priorities` (`id`, `label`, `weight`, `created_at`) VALUES
(1, 'Low', 1, '2025-12-10 20:36:32'),
(2, 'Medium', 2, '2025-12-10 20:36:32'),
(3, 'High', 3, '2025-12-10 20:36:32'),
(4, 'Critical', 4, '2025-12-10 20:36:32');

-- --------------------------------------------------------

--
-- Table structure for table `task_statuses`
--

CREATE TABLE `task_statuses` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_statuses`
--

INSERT INTO `task_statuses` (`id`, `name`, `created_at`) VALUES
(1, 'Pending', '2025-12-10 20:36:32'),
(2, 'In Progress', '2025-12-10 20:36:32'),
(3, 'Completed', '2025-12-10 20:36:32'),
(4, 'On Hold', '2025-12-10 20:36:32'),
(5, 'Cancelled', '2025-12-10 20:36:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name_en` varchar(200) NOT NULL,
  `full_name_ar` varchar(200) DEFAULT NULL,
  `primary_role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name_en`, `full_name_ar`, `primary_role_id`, `department_id`, `phone`, `job_title`, `avatar`, `is_active`, `last_login`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`, `is_deleted`, `deleted_at`) VALUES
(1, 'amira.kahtan', 'a_bumadyan1@yu.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amira Bumadyan', 'أميرة بومدين', 1, 1, '0558906017', 'Software Devlpoer', 'user_1_1764450223.jpg', 1, '2025-12-24 14:48:38', '2025-11-27 11:17:35', '2025-12-24 11:48:38', NULL, NULL, 0, NULL),
(3, 'kahtan', 'kahtan@11interlink.edu', '$2y$10$rrlJ92b/e.nK/8roRjD3tOdHylS7kESE9xJmEklbeu5mSE4KC3hmS', 'Mohammed Kahtan', NULL, 12, 11, '0552468325', 'Executive Director', 'user_3_1765648478.jpg', 1, '2025-12-22 21:12:53', '2025-11-30 08:18:15', '2025-12-23 06:02:59', NULL, NULL, 0, NULL),
(4, 'ceo', 'ceo@yu11.edu.sa', '$2y$10$Aa00vZjcUVOpNKHqK7IKruN..9Y2Vku3wWqBPe0s7ko8bfwqrwAPa', 'ceo', NULL, 10, NULL, '0558906018', 'Chief Executive Officer', NULL, 1, '2025-12-22 16:01:42', '2025-12-03 09:45:37', '2025-12-22 13:01:42', NULL, NULL, 0, NULL),
(5, 'DepartmentManager', 'DepartmentManager@11yu.edu.sa', '$2y$10$9ycV5HvoE7eA7TsgCQqjXutii.l2BSbhP76w6x2uXsz/hu170QSYS', 'Department Manager', NULL, 8, 1, '0558906019', 'Department Manager', NULL, 1, '2025-12-22 15:29:24', '2025-12-03 09:46:25', '2025-12-22 12:29:24', NULL, NULL, 0, NULL),
(6, 'Employee', 'Employee@11yu.edu.sa', '$2y$10$yZCtBfCW46FcOA4GHbcnUOSz3cGVywq9pERYYhCKIoi1ANZqEl.AW', 'Employee', NULL, 8, 1, '0558906011', 'Employee', NULL, 0, '2025-12-03 12:57:44', '2025-12-03 09:46:59', '2025-12-20 18:12:20', NULL, NULL, 1, '2025-12-06 22:45:32'),
(7, 'StrategyStaff', 'amirabu1288@gmail.com', '$2y$10$CivrgRB2kgrHy/ab1/eOremC2L0beeQlz.brA7VkWSaaWMIgLQLla', 'Strategy Staff', NULL, 8, 5, '0558906012', 'Strategy Staff', NULL, 1, '2025-12-22 21:13:11', '2025-12-03 09:47:38', '2025-12-22 18:13:11', NULL, NULL, 0, NULL),
(8, 'AhamedMohammed', 'a_bumadyan@yu.edu.sa', '$2y$10$YZU8jVfQ.bWQPQtqicXxbOO5DC.DOkkxrifZKnpkel/YAVtM18p8e', 'Ahamed Mohammed', NULL, 12, 1, '0558906078', 'Employee', NULL, 1, '2025-12-22 15:33:20', '2025-12-07 08:01:11', '2025-12-22 12:33:20', NULL, NULL, 0, NULL),
(10, 'test', 'test@gmail.com', '$2y$10$ZUbS.foqqQKtRyRu3QW2xeymEWcqDIE0iJIN5RlOiSwkTdxIjLmCa', 'Employee Test', NULL, 12, 2, '0558906125', 'Software Devlpoer', NULL, 1, '2025-12-23 13:29:05', '2025-12-07 11:18:59', '2025-12-23 10:29:05', NULL, NULL, 0, NULL),
(11, 'Ali', 'Ali@111yu.edu.sa', '$2y$10$NGIK/G6xhFHvW/vPjYzB8eLeXcQYZ7HKOmUpY11N.IU4SerJYqUDa', 'Ali Ahmed', NULL, 12, 5, '0558906587', 'Finance Head', NULL, 1, '2025-12-24 13:48:58', '2025-12-07 11:52:16', '2025-12-24 10:48:58', NULL, NULL, 0, NULL),
(12, 'Mohammed', 'amiraq128@gmail.com', '$2y$10$rZ7SrJ6hBvTpU/J5FJBDFu/nBoKTsYHpeVeDeBZdfWMTbLcvR4wHO', 'Mohammed Adam', NULL, 12, 1, '0578404042', 'Software Devlpoer', NULL, 1, '2025-12-16 15:27:30', '2025-12-09 06:21:02', '2025-12-20 16:32:43', NULL, NULL, 0, '2025-12-14 21:50:03'),
(13, 'Adam', 'Adam@111yu.edu.sa', '$2y$10$0apeJojo5JEWhMYPVsWLDOFuu/7dAm1NDpSDqqe/tiwRhyJG9vE/W', 'Adam Ali', NULL, 7, 1, '0578404012', 'Software Devlpoer', NULL, 1, NULL, '2025-12-09 06:36:26', '2025-12-20 18:12:40', NULL, NULL, 1, '2025-12-09 09:48:56'),
(14, 'asmaa', 'asmaa@111yu.edu.sa', '$2y$10$Yn/WJ/yTHxI5fy6S/Yi/A.J5XbMg8lORo38o4heBmScXirA6du4hK', 'Asmaa Ali', NULL, 6, 1, '0578404448', 'Employee', NULL, 1, '2025-12-22 15:30:35', '2025-12-09 06:43:33', '2025-12-22 12:30:35', NULL, NULL, 0, '2025-12-09 09:48:28'),
(15, 'khaled', 'khaled@gmail.com', '$2y$10$7NyBapRpECu8aSGMQzHL4uEz4JrMZZ2dW0/2rLaN0mlxzVQETnavy', 'Khaled Mohammed', NULL, 13, 1, '0578404478', 'Employee', NULL, 1, '2025-12-22 14:07:58', '2025-12-10 12:10:38', '2025-12-22 11:07:58', NULL, NULL, 0, NULL),
(16, 'amiratest', 'amiratest@gmail.com', '$2y$10$Tp/A7jnMUXrXOYOqwWB9PeLwOtC2nbIXf9AQSdqr9tqBo82nls1bu', 'amira test', NULL, 8, 5, '0558906452', 'Employee', NULL, 1, NULL, '2025-12-24 05:34:40', '2025-12-24 10:26:44', NULL, NULL, 1, '2025-12-24 13:26:44'),
(17, 'amirakahtan111', 'amirakahtan111@gmail.com', '$2y$10$O1phZQGUUJJFzE.v5HuDHujK1WzctHmyNvIka/ttX3VKHd.2CIh.y', 'Amira Bu-Madyan Kahtanjjj', NULL, 8, 1, '0558906632', 'Employee', NULL, 1, NULL, '2025-12-24 10:32:40', '2025-12-24 10:32:47', NULL, NULL, 1, '2025-12-24 13:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_branches`
--

CREATE TABLE `user_branches` (
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_branches`
--

INSERT INTO `user_branches` (`user_id`, `branch_id`) VALUES
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(12, 2),
(16, 1),
(17, 1),
(17, 2);

-- --------------------------------------------------------

--
-- Table structure for table `user_hierarchy`
--

CREATE TABLE `user_hierarchy` (
  `user_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `reporting_type` enum('academic','administrative') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(3, 4),
(4, 10),
(5, 12),
(6, 8),
(7, 8);

-- --------------------------------------------------------

--
-- Table structure for table `user_todos`
--

CREATE TABLE `user_todos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `is_system_generated` tinyint(1) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0,
  `related_entity_type` varchar(50) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_todos`
--

INSERT INTO `user_todos` (`id`, `user_id`, `title`, `description`, `due_date`, `is_system_generated`, `is_completed`, `related_entity_type`, `related_entity_id`, `created_at`) VALUES
(3, 7, 'Pillar returned for revision', 'Pillar #3 - \"Test3\" was returned for revision by Strategy Office.', NULL, 1, 0, 'pillar', 9, '2025-12-04 05:48:49'),
(5, 1, 'test', '', '2025-12-10 00:00:00', 0, 1, NULL, NULL, '2025-12-09 09:27:07'),
(6, 5, 'Approval Required: Test2552...', 'Please review stage: Department Head Approval', '2025-12-11 00:00:00', 1, 1, 'project', 4, '2025-12-09 11:48:12'),
(7, 11, 'Approval Required: Test2552...', 'Please review stage: Finance Budget Approval', '2025-12-11 00:00:00', 1, 1, 'project', 4, '2025-12-09 11:49:05'),
(8, 4, 'Approval Required: Test2552...', 'Please review stage: CEO Final Approval', '2025-12-11 00:00:00', 1, 1, 'project', 4, '2025-12-09 11:56:05'),
(9, 3, 'Approval Pending: Test265', 'Action required for stage: Strategy Office Head Review', '2025-12-11 00:00:00', 1, 1, 'project', 3, '2025-12-09 13:09:22'),
(10, 5, 'Approval Pending: Test5', 'Action required for stage: Department Head Approval', '2025-12-11 00:00:00', 1, 0, 'project', 5, '2025-12-09 18:35:29'),
(11, 11, 'Approval Pending: Test5', 'Action required for stage: Finance Budget Approval', '2025-12-11 00:00:00', 1, 1, 'project', 5, '2025-12-09 18:39:51'),
(12, 4, 'Approval Pending: Test5', 'Action required for stage: CEO Final Approval', '2025-12-11 00:00:00', 1, 1, 'project', 5, '2025-12-09 18:40:46'),
(13, 5, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 0, 'project', 3, '2025-12-10 05:08:29'),
(14, 8, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:08:29'),
(15, 10, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:08:29'),
(16, 12, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 0, 'project', 3, '2025-12-10 05:08:29'),
(17, 5, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 0, 'project', 3, '2025-12-10 05:09:41'),
(18, 8, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:09:41'),
(19, 10, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:09:41'),
(20, 12, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-12 00:00:00', 1, 0, 'project', 3, '2025-12-10 05:09:41'),
(21, 11, 'Approval Pending: Test265', 'Action required for stage: Finance Budget Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:09:54'),
(22, 4, 'Approval Pending: Test265', 'Action required for stage: CEO Final Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:44:55'),
(23, 11, 'Approval Pending: Test265', 'Action required for stage: Finance Budget Approval', '2025-12-12 00:00:00', 1, 1, 'project', 3, '2025-12-10 05:53:59'),
(24, 8, 'Welcome to Project: Test5', 'You have been added to the team. Check your tasks.', NULL, 1, 0, 'project', 5, '2025-12-10 13:01:10'),
(25, 5, 'Collaboration Request', 'Project \'Test5\' needs resource from your department.', NULL, 1, 0, 'collaboration', 1, '2025-12-10 18:23:19'),
(26, 11, 'Collaboration Request', 'Project \'Test5\' needs resource from your department.', NULL, 1, 1, 'collaboration', 2, '2025-12-10 18:34:25'),
(27, 11, 'Collaboration Request', 'Project \'Test5\' needs resource from your department.', NULL, 1, 1, 'collaboration', 3, '2025-12-10 18:37:32'),
(28, 11, 'Collaboration Request', 'Project \'Test5\' needs resource from your department.', NULL, 1, 1, 'collaboration', 4, '2025-12-10 19:03:13'),
(29, 3, 'Collab Rejected: Test5', 'Your resource request was rejected.', NULL, 1, 1, 'project', 5, '2025-12-10 20:02:36'),
(30, 8, 'New Task Assigned', 'Task: Test1', NULL, 1, 0, 'task', 2, '2025-12-10 20:37:22'),
(31, 3, 'Collab Rejected: Test5', 'Your resource request was rejected.', NULL, 1, 1, 'project', 5, '2025-12-11 05:12:27'),
(32, 1, 'Collab Rejected: Test5', 'Your resource request was rejected.', NULL, 1, 1, 'project', 5, '2025-12-11 05:12:30'),
(33, 11, 'Resource Request: Test5', 'Project \'Test5\' is requesting a resource from your department.', NULL, 1, 1, 'collaboration', 5, '2025-12-11 05:15:10'),
(34, 8, 'New Task: test 2', 'You have a new task.', NULL, 1, 0, 'task', 3, '2025-12-11 06:14:09'),
(35, 8, 'New Task: jkkjkj', 'You have a new task.', NULL, 1, 1, 'task', 4, '2025-12-11 06:16:26'),
(36, 8, 'New Task: test3', 'You have a new task.', NULL, 1, 0, 'task', 5, '2025-12-11 06:32:50'),
(37, 12, 'Welcome to Project: Test5', 'You have been added to the team.', NULL, 1, 0, 'project', 5, '2025-12-11 07:34:14'),
(38, 3, 'Collab Approved: Test5', 'Your resource request has been approved.', NULL, 1, 1, 'project', 5, '2025-12-11 07:44:04'),
(39, 7, 'New Assignment: Test5', 'You have been assigned to collaborate on this project.', NULL, 1, 0, 'project', 5, '2025-12-11 07:44:04'),
(40, 7, 'Welcome to Project: Test5', 'You have been added to the team.', NULL, 1, 0, 'project', 5, '2025-12-11 07:44:58'),
(41, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-18 00:00:00', 1, 1, 'kpi', 1, '2025-12-11 08:08:13'),
(42, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-18 00:00:00', 1, 1, 'kpi', 1, '2025-12-11 08:08:45'),
(43, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-18 00:00:00', 1, 1, 'kpi', 1, '2025-12-11 08:08:56'),
(44, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-18 00:00:00', 1, 1, 'kpi', 1, '2025-12-11 08:09:08'),
(45, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-18 00:00:00', 1, 1, 'kpi', 1, '2025-12-11 08:23:35'),
(46, 5, 'Update Required: Test2', 'It\'s time to submit your periodic progress update.', '2025-12-11 00:00:00', 1, 0, 'project_update', 1, '2025-12-11 10:12:01'),
(47, 10, 'Update Required: Test255', 'It\'s time to submit your periodic progress update.', '2025-12-11 00:00:00', 1, 1, 'project_update', 2, '2025-12-11 10:12:01'),
(48, 10, 'Update Required: Test265', 'It\'s time to submit your periodic progress update.', '2025-12-11 00:00:00', 1, 1, 'project_update', 3, '2025-12-11 10:12:01'),
(49, 4, 'Project Update: Test5', 'New progress update (50%) submitted for review.', NULL, 1, 1, 'ceo_review', 2, '2025-12-11 10:24:54'),
(50, 4, 'Project Update: Test5', 'New progress update (50%) submitted for review.', NULL, 1, 1, 'ceo_review', 3, '2025-12-11 10:30:27'),
(51, 5, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 3, '2025-12-11 10:50:43'),
(52, 8, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 3, '2025-12-11 10:50:43'),
(53, 10, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 1, 'project', 3, '2025-12-11 10:50:43'),
(54, 12, 'Approval Pending: Test265', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 3, '2025-12-11 10:50:43'),
(55, 11, 'Approval Pending: Test265', 'Action required for stage: Finance Budget Approval', '2025-12-13 00:00:00', 1, 1, 'project', 3, '2025-12-11 10:52:01'),
(56, 4, 'Approval Pending: Test265', 'Action required for stage: CEO Final Approval', '2025-12-13 00:00:00', 1, 1, 'project', 3, '2025-12-11 10:52:46'),
(57, 5, 'Approval Pending: Test2555', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 6, '2025-12-11 11:33:16'),
(58, 8, 'Approval Pending: Test2555', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 6, '2025-12-11 11:33:16'),
(59, 10, 'Approval Pending: Test2555', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 1, 'project', 6, '2025-12-11 11:33:16'),
(60, 12, 'Approval Pending: Test2555', 'Action required for stage: Department Head Approval', '2025-12-13 00:00:00', 1, 0, 'project', 6, '2025-12-11 11:33:16'),
(61, 11, 'Approval Pending: Test2555', 'Action required for stage: Finance Budget Approval', '2025-12-13 00:00:00', 1, 1, 'project', 6, '2025-12-11 11:36:15'),
(62, 4, 'Approval Pending: Test2555', 'Action required for stage: CEO Final Approval', '2025-12-13 00:00:00', 1, 1, 'project', 6, '2025-12-11 11:39:14'),
(63, 4, 'Approval Pending: Test265', 'Action required for stage: CEO Final Approval', '2025-12-13 00:00:00', 1, 1, 'project', 3, '2025-12-11 11:46:19'),
(64, 11, 'Resource Request: Test2555', 'Project \'Test2555\' is requesting a resource from your department.', NULL, 1, 1, 'collaboration', 6, '2025-12-11 11:53:08'),
(65, 10, 'Collab Approved: Test2555', 'Your resource request has been approved.', NULL, 1, 0, 'project', 6, '2025-12-11 11:53:55'),
(66, 7, 'New Assignment: Test2555', 'You have been assigned to collaborate on this project.', NULL, 1, 0, 'project', 6, '2025-12-11 11:53:55'),
(67, 7, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-11 11:54:41'),
(68, 7, 'New Task: test1', 'You have a new task.', NULL, 1, 0, 'task', 6, '2025-12-11 12:07:46'),
(69, 7, 'New Task: test2', 'You have a new task.', NULL, 1, 0, 'task', 7, '2025-12-11 12:08:23'),
(70, 4, 'Project Update: Test2555', 'New progress update (67%) submitted for review.', NULL, 1, 1, 'ceo_review', 4, '2025-12-11 12:26:19'),
(71, 4, 'Project Update: Test2555', 'New progress update (67%) submitted for review.', NULL, 1, 1, 'ceo_review', 5, '2025-12-11 12:33:43'),
(72, 10, 'Update Required: Test255', 'It\'s time to submit your periodic progress update.', '2025-12-13 00:00:00', 1, 0, 'project_update', 2, '2025-12-13 17:27:28'),
(73, 10, 'Update Required: Test265', 'It\'s time to submit your periodic progress update.', '2025-12-13 00:00:00', 1, 0, 'project_update', 3, '2025-12-13 17:27:28'),
(74, 3, 'Approval Pending: Test558', 'Action required for stage: Strategy Office Review', '2025-12-16 00:00:00', 1, 0, 'project', 12, '2025-12-14 05:22:21'),
(75, 3, 'Approval Pending: Test23332', 'Action required for stage: Strategy Office Review', '2025-12-16 00:00:00', 1, 0, 'project', 13, '2025-12-14 05:50:45'),
(76, 10, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-15 09:25:09'),
(77, 11, 'Resource Request: Test2555', 'Project \'Test2555\' is requesting a resource from your department.', NULL, 1, 1, 'collaboration', 7, '2025-12-15 09:27:09'),
(78, 3, 'Collab Approved: Test2555', 'Your resource request has been approved.', NULL, 1, 0, 'project', 6, '2025-12-15 09:27:37'),
(79, 11, 'New Assignment: Test2555', 'You have been assigned to collaborate on this project.', NULL, 1, 1, 'project', 6, '2025-12-15 09:27:37'),
(80, 11, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 1, 'project', 6, '2025-12-15 09:28:21'),
(81, 7, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-15 09:37:01'),
(82, 8, 'Resource Request: Test2555', 'Project \'Test2555\' is requesting a resource from your department.', NULL, 1, 1, 'collaboration', 8, '2025-12-15 09:38:02'),
(83, 3, 'Collab Approved: Test2555', 'Your resource request has been approved.', NULL, 1, 0, 'project', 6, '2025-12-15 09:39:49'),
(84, 14, 'New Assignment: Test2555', 'You have been assigned to collaborate on this project.', NULL, 1, 0, 'project', 6, '2025-12-15 09:39:49'),
(85, 5, 'Resource Request: Test5', 'Project \'Test5\' is requesting a resource from your department.', NULL, 1, 0, 'collaboration', 9, '2025-12-15 10:58:50'),
(86, 5, 'Approval Pending: IT-project', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 7, '2025-12-15 11:40:27'),
(87, 8, 'Approval Pending: IT-project', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 7, '2025-12-15 11:40:27'),
(88, 10, 'Approval Pending: IT-project', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 7, '2025-12-15 11:40:27'),
(89, 12, 'Approval Pending: IT-project', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 7, '2025-12-15 11:40:27'),
(90, 5, 'Approval Pending: IT-project2', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 8, '2025-12-15 11:46:38'),
(91, 8, 'Approval Pending: IT-project2', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 8, '2025-12-15 11:46:38'),
(92, 10, 'Approval Pending: IT-project2', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 8, '2025-12-15 11:46:38'),
(93, 12, 'Approval Pending: IT-project2', 'Action required for stage: Department Head Approval', '2025-12-17 00:00:00', 1, 0, 'project', 8, '2025-12-15 11:46:38'),
(94, 4, 'Approval Pending: IT-project2', 'Action required for stage: CEO Final Approval', '2025-12-17 00:00:00', 1, 1, 'project', 8, '2025-12-15 11:56:25'),
(95, 14, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-15 16:56:55'),
(96, 14, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-15 17:03:16'),
(97, 11, 'Welcome to Project: IT-project2', 'You have been added to the team.', NULL, 1, 1, 'project', 8, '2025-12-15 18:13:55'),
(98, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-23 00:00:00', 1, 0, 'kpi', 1, '2025-12-16 05:20:35'),
(99, 5, 'Approval Pending: Test678', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 11, '2025-12-16 09:42:14'),
(100, 8, 'Approval Pending: Test678', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 11, '2025-12-16 09:42:14'),
(101, 10, 'Approval Pending: Test678', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 11, '2025-12-16 09:42:14'),
(102, 12, 'Approval Pending: Test678', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 11, '2025-12-16 09:42:14'),
(103, 4, 'Approval Pending: Test678', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 11, '2025-12-16 09:42:50'),
(104, 5, 'Approval Pending: Test987', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 10, '2025-12-16 09:42:55'),
(105, 8, 'Approval Pending: Test987', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 10, '2025-12-16 09:42:55'),
(106, 10, 'Approval Pending: Test987', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 10, '2025-12-16 09:42:55'),
(107, 12, 'Approval Pending: Test987', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 10, '2025-12-16 09:42:55'),
(108, 4, 'Approval Pending: Test987', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 10, '2025-12-16 09:43:02'),
(109, 4, 'Approval Pending: Test787', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 9, '2025-12-16 09:44:49'),
(110, 5, 'Approval Pending: Test996', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 12, '2025-12-16 09:47:29'),
(111, 8, 'Approval Pending: Test996', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 12, '2025-12-16 09:47:29'),
(112, 10, 'Approval Pending: Test996', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 12, '2025-12-16 09:47:29'),
(113, 11, 'Approval Pending: Test996', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 12, '2025-12-16 09:47:29'),
(114, 12, 'Approval Pending: Test996', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 12, '2025-12-16 09:47:29'),
(115, 5, 'Approval Pending: Test5654', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 13, '2025-12-16 10:07:21'),
(116, 8, 'Approval Pending: Test5654', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 13, '2025-12-16 10:07:21'),
(117, 10, 'Approval Pending: Test5654', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 13, '2025-12-16 10:07:21'),
(118, 11, 'Approval Pending: Test5654', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 13, '2025-12-16 10:07:21'),
(119, 12, 'Approval Pending: Test5654', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 13, '2025-12-16 10:07:21'),
(120, 8, 'Approval Pending: Test564', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 14, '2025-12-16 10:13:01'),
(121, 4, 'Approval Pending: Test564', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 14, '2025-12-16 10:14:23'),
(122, 5, 'Approval Pending: Test6587', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 15, '2025-12-16 10:32:32'),
(123, 8, 'Approval Pending: Test6587', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 15, '2025-12-16 10:32:32'),
(124, 10, 'Approval Pending: Test6587', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 15, '2025-12-16 10:32:32'),
(125, 11, 'Approval Pending: Test6587', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 15, '2025-12-16 10:32:32'),
(126, 12, 'Approval Pending: Test6587', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 0, 'project', 15, '2025-12-16 10:32:32'),
(127, 4, 'Approval Pending: Test6587', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 15, '2025-12-16 10:34:13'),
(128, 8, 'Approval Pending: Test357', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 16, '2025-12-16 10:39:28'),
(129, 11, 'Approval Pending: Test4562', 'Action required for stage: Finance Budget Approval', '2025-12-18 00:00:00', 1, 1, 'project', 17, '2025-12-16 10:59:42'),
(130, 4, 'Approval Pending: Test4562', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 17, '2025-12-16 10:59:52'),
(131, 4, 'Approval Pending: Test357', 'Action required for stage: CEO Final Approval', '2025-12-18 00:00:00', 1, 1, 'project', 16, '2025-12-16 11:03:15'),
(132, 11, 'Resource Request: Test357', 'Project \'Test357\' is requesting a resource from your department.', NULL, 1, 1, 'collaboration', 10, '2025-12-17 06:07:34'),
(133, 1, 'Collab Approved: Test357', 'Your resource request has been approved.', NULL, 1, 1, 'project', 16, '2025-12-17 06:07:53'),
(134, 11, 'New Assignment: Test357', 'You have been assigned to collaborate on this project.', NULL, 1, 1, 'project', 16, '2025-12-17 06:07:53'),
(136, 11, 'Welcome to Project: Test357', 'You have been added to the team.', NULL, 1, 1, 'project', 16, '2025-12-17 06:09:11'),
(137, 11, 'Welcome to Project: Test357', 'You have been added to the team.', NULL, 1, 1, 'project', 16, '2025-12-17 06:09:41'),
(138, 11, 'Welcome to Project: Test357', 'You have been added to the team.', NULL, 1, 0, 'project', 16, '2025-12-17 06:10:01'),
(139, 4, 'Approval Pending: Test789', 'Action required for stage: CEO Final Approval', '2025-12-20 00:00:00', 1, 1, 'project', 18, '2025-12-18 04:51:05'),
(140, 14, 'New Assignment', 'You have been added to project: Test789', NULL, 1, 0, 'project', 18, '2025-12-18 04:52:08'),
(141, 4, 'Approval Pending: Test587', 'Action required for stage: CEO Final Approval', '2025-12-20 00:00:00', 1, 1, 'project', 19, '2025-12-18 05:13:06'),
(142, 11, 'New Assignment', 'You have been added to project: Test587', NULL, 1, 0, 'project', 19, '2025-12-18 05:13:58'),
(143, 13, 'New Assignment', 'You have been added to project: Test6587', NULL, 1, 0, 'project', 15, '2025-12-18 05:19:16'),
(144, 14, 'New Assignment', 'You have been added to project: Test564', NULL, 1, 0, 'project', 14, '2025-12-18 06:49:28'),
(145, 15, 'New Assignment', 'You have been added to project: Test564', NULL, 1, 0, 'project', 14, '2025-12-18 06:50:54'),
(146, 7, 'New Assignment', 'You have been added to project: Test587', NULL, 1, 1, 'project', 19, '2025-12-18 09:44:11'),
(147, 7, 'Added to Project', 'You have been added to the project team: Test587', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 15:17:04'),
(148, 7, 'Removed from Project', 'You have been removed from the project team: Test587', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 15:38:32'),
(149, 7, 'Removed from Project', 'You have been removed from the project team: Test587', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 15:38:38'),
(150, 7, 'Added to Project', 'You have been added to the project team: Test587', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 15:43:36'),
(151, 7, 'New Task Assigned', 'You have been assigned a new task: 12-14', '2025-12-20 00:00:00', 1, 0, 'task_view', 17, '2025-12-20 15:53:40'),
(152, 7, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-27 00:00:00', 1, 0, 'kpi_view_direct', 2, '2025-12-20 16:09:43'),
(153, 7, 'New KPI Assigned: Test2', 'You are now the owner of this KPI. Please ensure regular updates.', '2025-12-20 00:00:00', 1, 0, 'kpi_view', 19, '2025-12-20 16:09:43'),
(154, 8, 'Resource Request: Test587', 'Project \'Test587\' is requesting a resource from your department (IT).\nReason: dfdfdsf...', '2025-12-20 00:00:00', 1, 0, 'collaboration_review', 11, '2025-12-20 16:28:32'),
(155, 1, 'Collab Rejected: Test587', 'Your resource request was rejected.\nComment: ', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 16:30:51'),
(156, 8, 'Resource Request: Test587', 'Project \'Test587\' is requesting a resource from your department (IT).\nReason: fdfd...', '2025-12-20 00:00:00', 1, 1, 'collaboration_review', 12, '2025-12-20 16:31:08'),
(157, 1, 'Collab Approved: Test587', 'Your resource request has been approved. User assigned.', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 16:32:55'),
(158, 12, 'New Assignment: Test587', 'You have been assigned to collaborate on project: Test587.', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 16:32:58'),
(159, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:14'),
(160, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:17'),
(161, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:30'),
(162, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:33'),
(163, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:36'),
(164, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:00:38'),
(165, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:01:06'),
(166, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:01:08'),
(167, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:02:29'),
(168, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 18:02:33'),
(169, 15, 'Overdue Task Alert', 'Task \'test121\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 15, '2025-12-20 18:03:20'),
(170, 14, 'Task Delay Alert', 'Task \'test121\' assigned to user #15 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 15, '2025-12-20 18:03:22'),
(171, 11, 'Approval Required: IT-project', 'Your approval is required for stage: Finance Budget Approval.\nProject: IT-project', '2025-12-20 00:00:00', 1, 1, 'project_approvals', 7, '2025-12-20 18:07:08'),
(172, 4, 'Approval Required: IT-project', 'Your approval is required for stage: CEO Final Approval.\nProject: IT-project', '2025-12-20 00:00:00', 1, 1, 'project_approvals', 7, '2025-12-20 18:14:41'),
(173, 7, 'Project Delay Alert', 'The project \'Test587\' is past its due date (2025-12-19).', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 18:22:34'),
(174, 11, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 18:22:36'),
(175, 7, 'Task Delay Alert', 'Task \'Test587\' assigned to user #11 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 18:22:38'),
(176, 4, 'Approval Required: Test5654', 'Your approval is required for stage: CEO Final Approval.\nProject: Test5654', '2025-12-20 00:00:00', 1, 1, 'project_approvals', 13, '2025-12-20 18:23:47'),
(177, 1, 'Project Approved: Test5654', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-20 00:00:00', 1, 0, 'project_view', 13, '2025-12-20 18:26:19'),
(178, 8, 'test', '', '2025-12-21 00:00:00', 0, 0, NULL, NULL, '2025-12-20 18:43:29'),
(179, 7, 'Project Delay Alert', 'The project \'Test587\' is past its due date (2025-12-19).', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 20:31:39'),
(180, 11, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 20:31:43'),
(181, 7, 'Task Delay Alert', 'Task \'Test587\' assigned to user #11 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 20:31:46'),
(182, 7, 'Project Delay Alert', 'The project \'Test587\' is past its due date (2025-12-19).', '2025-12-20 00:00:00', 1, 0, 'project_view', 19, '2025-12-20 20:42:06'),
(183, 11, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 20:42:10'),
(184, 7, 'Task Delay Alert', 'Task \'Test587\' assigned to user #11 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 12, '2025-12-20 20:42:12'),
(185, 14, 'Overdue Task Alert', 'Task \'test2\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 7, '2025-12-20 21:04:56'),
(186, 10, 'Task Delay Alert', 'Task \'test2\' assigned to user #14 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 7, '2025-12-20 21:04:59'),
(187, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:07:56'),
(188, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:08:00'),
(189, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:08:12'),
(190, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:08:14'),
(191, 13, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:08:27'),
(192, 14, 'Task Delay Alert', 'Task \'Test587\' assigned to user #13 is overdue.', '2025-12-20 00:00:00', 1, 0, 'task_view', 13, '2025-12-20 21:08:31'),
(193, 15, 'Overdue Task Alert', 'Task \'test121\' is overdue. Please update status.', '2025-12-21 00:00:00', 1, 0, 'task_view', 15, '2025-12-21 06:33:10'),
(194, 14, 'Task Delay Alert', 'Task \'test121\' assigned to user #15 is overdue.', '2025-12-21 00:00:00', 1, 1, 'task_view', 15, '2025-12-21 06:33:14'),
(195, 5, 'Added to Project', 'You have been added to the project team: Test564', '2025-12-21 00:00:00', 1, 0, 'project_view', 14, '2025-12-21 06:33:35'),
(196, 5, 'New Task Assigned', 'You have been assigned a new task: test22', '2025-12-21 00:00:00', 1, 0, 'task_view', 18, '2025-12-21 06:34:11'),
(197, 11, 'Approval Required: Test996', 'Your approval is required for stage: Finance Budget Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 08:16:26'),
(198, 4, 'Approval Required: Test996', 'Your approval is required for stage: CEO Final Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 08:19:37'),
(199, 1, 'Project Returned: Test996', 'Your project request has been returned for modifications.\nComments: comment', '2025-12-21 00:00:00', 1, 1, 'project_view', 12, '2025-12-21 08:26:51'),
(200, 11, 'Approval Required: Test996', 'Your approval is required for stage: Finance Budget Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 08:28:38'),
(201, 4, 'Approval Required: Test996', 'Your approval is required for stage: CEO Final Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 08:29:15'),
(202, 15, 'Overdue Task Alert', 'Task \'test121\' is overdue. Please update status.', '2025-12-21 00:00:00', 1, 0, 'task_view', 15, '2025-12-21 08:29:49'),
(203, 14, 'Task Delay Alert', 'Task \'test121\' assigned to user #15 is overdue.', '2025-12-21 00:00:00', 1, 1, 'task_view', 15, '2025-12-21 08:29:52'),
(204, 15, 'Overdue Task Alert', 'Task \'test121\' is overdue. Please update status.', '2025-12-21 00:00:00', 1, 0, 'task_view', 15, '2025-12-21 08:30:15'),
(205, 14, 'Task Delay Alert', 'Task \'test121\' assigned to user #15 is overdue.', '2025-12-21 00:00:00', 1, 1, 'task_view', 15, '2025-12-21 08:30:19'),
(206, 4, 'Project Update: Test564', 'New progress update (42%) submitted for review.\n\nSummary: test...', '2025-12-21 00:00:00', 1, 1, 'ceo_review', 7, '2025-12-21 08:30:55'),
(207, 1, 'Project Returned: Test996', 'Your project request has been returned for modifications.\nComments: comment', '2025-12-21 00:00:00', 1, 1, 'project_view', 12, '2025-12-21 09:41:38'),
(208, 11, 'Approval Required: Test996', 'Your approval is required for stage: Finance Budget Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 09:43:31'),
(209, 4, 'Approval Required: Test996', 'Your approval is required for stage: CEO Final Approval.\nProject: Test996', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 12, '2025-12-21 09:43:50'),
(210, 1, 'Project Approved: Test996', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-21 00:00:00', 1, 0, 'project_view', 12, '2025-12-21 09:44:30'),
(211, 4, 'Project Update: Test996', 'New progress update (0%) submitted for review.\n\nSummary: test...', '2025-12-21 00:00:00', 1, 0, 'ceo_review', 8, '2025-12-21 09:45:17'),
(212, 4, 'Approval Required: Test287', 'Your approval is required for stage: CEO Final Approval.\nProject: Test287', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 20, '2025-12-21 09:54:59'),
(213, 7, 'Project Delay Alert', 'The project \'Test587\' is past its due date (2025-12-19).', '2025-12-21 00:00:00', 1, 0, 'project_view', 19, '2025-12-21 10:02:24'),
(214, 11, 'Overdue Task Alert', 'Task \'Test587\' is overdue. Please update status.', '2025-12-21 00:00:00', 1, 0, 'task_view', 12, '2025-12-21 10:02:27'),
(215, 7, 'Task Delay Alert', 'Task \'Test587\' assigned to user #11 is overdue.', '2025-12-21 00:00:00', 1, 0, 'task_view', 12, '2025-12-21 10:02:30'),
(216, 1, 'Project Approved: Test287', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-21 00:00:00', 1, 0, 'project_view', 20, '2025-12-21 10:07:40'),
(217, 4, 'Approval Required: Test314', 'Your approval is required for stage: CEO Final Approval.\nProject: Test314', '2025-12-21 00:00:00', 1, 0, 'project_approvals', 21, '2025-12-21 10:09:57'),
(218, 4, 'Approval Required: Test28888', 'Your approval is required for stage: CEO Final Approval.\nProject: Test28888', '2025-12-21 00:00:00', 1, 1, 'project_approvals', 22, '2025-12-21 10:46:59'),
(219, 1, 'Project Approved: Test28888', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-21 00:00:00', 1, 0, 'project_view', 22, '2025-12-21 10:49:36'),
(220, 8, 'Added to Project', 'You have been added to the project team: Test28888', '2025-12-21 00:00:00', 1, 0, 'project_view', 22, '2025-12-21 10:52:10'),
(221, 8, 'New Task Assigned', 'You have been assigned a new task: iiii', '2025-12-21 00:00:00', 1, 0, 'task_view', 19, '2025-12-21 10:53:07'),
(222, 4, 'Project Update: Test28888', 'New progress update (50%) submitted for review.\n\nSummary: ooooo...', '2025-12-21 00:00:00', 1, 1, 'ceo_review', 9, '2025-12-21 11:01:32'),
(223, 4, 'Project Update: Test28888', 'New progress update (50%) submitted for review.\n\nSummary: oooooop...', '2025-12-21 00:00:00', 1, 0, 'ceo_review', 10, '2025-12-21 11:42:18'),
(224, 3, 'Approval Required: Test3569', 'Your approval is required for stage: Strategy Office Review.\nProject: Test3569', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 19, '2025-12-22 05:47:37'),
(225, 4, 'Approval Required: Project', 'Your approval is required for stage: CEO Final Approval.\nProject: Project', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 19, '2025-12-22 05:48:53'),
(226, 4, 'Approval Required: Project', 'Your approval is required for stage: CEO Final Approval.\nProject: Project', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 20, '2025-12-22 10:39:09'),
(227, 4, 'Approval Required: Test5967', 'Your approval is required for stage: CEO Final Approval.\nProject: Test5967', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 23, '2025-12-22 10:46:32'),
(228, 1, 'Project Approved: Test5967', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-22 00:00:00', 1, 0, 'project_view', 23, '2025-12-22 10:46:56'),
(229, 1, 'Project Approved: Test678', 'Congratulations! Your project has been fully approved and is now active.', '2025-12-22 00:00:00', 1, 0, 'project_view', 11, '2025-12-22 12:10:08'),
(230, 14, 'Added to Project', 'You have been added to the project team: Test678', '2025-12-22 00:00:00', 1, 0, 'project_view', 11, '2025-12-22 12:11:09'),
(231, 14, 'New Task Assigned', 'You have been assigned a new task: test1121', '2025-12-22 00:00:00', 1, 0, 'task_view', 20, '2025-12-22 12:12:21'),
(232, 8, 'Added to Project', 'You have been added to the project team: Test996', '2025-12-22 00:00:00', 1, 0, 'project_view', 12, '2025-12-22 12:13:48'),
(233, 1, 'Request Approved: Test987', 'Congratulations! Your request has been fully approved.', '2025-12-22 00:00:00', 1, 0, 'project_view', 10, '2025-12-22 12:19:51'),
(234, 1, 'Request Returned: IT-project', 'Your request has been returned for modifications.\nComments: comment', '2025-12-22 00:00:00', 1, 0, 'project_view', 7, '2025-12-22 12:22:32'),
(235, 11, 'Approval Required: IT-project', 'Your approval is required for stage: Finance Budget Approval.\nRequest: IT-project', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 7, '2025-12-22 12:25:36'),
(236, 4, 'Approval Required: IT-project', 'Your approval is required for stage: CEO Final Approval.\nRequest: IT-project', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 7, '2025-12-22 12:26:08'),
(237, 11, 'Approval Required: Test7854', 'Your approval is required for stage: Finance Budget Approval.\nRequest: Test7854', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 24, '2025-12-22 12:33:26'),
(238, 4, 'Approval Required: Test7854', 'Your approval is required for stage: CEO Final Approval.\nRequest: Test7854', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 24, '2025-12-22 12:33:51'),
(239, 1, 'Request Approved: Test7854', 'Congratulations! Your request has been fully approved.', '2025-12-22 00:00:00', 1, 0, 'project_view', 24, '2025-12-22 12:34:26'),
(240, 3, 'Approval Required: Test145', 'Your approval is required for stage: Strategy Office Head Review.\nRequest: Test145', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 11, '2025-12-22 13:00:27'),
(241, 4, 'Approval Required: Test145', 'Your approval is required for stage: CEO Final Approval.\nRequest: Test145', '2025-12-22 00:00:00', 1, 1, 'project_approvals', 11, '2025-12-22 13:01:06'),
(242, 1, 'Request Approved: Test145', 'Congratulations! Your request has been fully approved.', '2025-12-22 00:00:00', 1, 0, 'initiative_view', 11, '2025-12-22 13:01:56'),
(243, 7, 'Overdue Task Alert', 'Task \'test1\' is overdue. Please update status.', '2025-12-23 00:00:00', 1, 0, 'task_view', 6, '2025-12-23 10:31:40'),
(244, 10, 'Task Delay Alert', 'Task \'test1\' assigned to user #7 is overdue.', '2025-12-23 00:00:00', 1, 0, 'task_view', 6, '2025-12-23 10:31:44');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_approval_actions_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_approval_actions_summary` (
`entity_type_id` int(11)
,`entity_id` int(11)
,`stage_id` int(11)
,`decisions_total` bigint(21)
,`approved_count` decimal(23,0)
,`rejected_count` decimal(23,0)
,`returned_count` decimal(23,0)
,`first_action_at` timestamp
,`last_action_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_department_branches_overview`
-- (See below for the actual view)
--
CREATE TABLE `vw_department_branches_overview` (
`department_id` int(11)
,`department_name` varchar(150)
,`branches` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_health_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_health_summary` (
`initiative_id` int(11)
,`initiative_code` varchar(50)
,`initiative_name` varchar(300)
,`status_id` int(11)
,`start_date` date
,`due_date` date
,`tasks_total` decimal(22,0)
,`tasks_open` decimal(22,0)
,`tasks_overdue` decimal(22,0)
,`milestones_total` decimal(22,0)
,`milestones_open` decimal(22,0)
,`milestones_overdue` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_milestones_overdue`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_milestones_overdue` (
`milestone_id` int(11)
,`initiative_id` int(11)
,`name` varchar(200)
,`due_date` date
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_milestones_upcoming`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_milestones_upcoming` (
`milestone_id` int(11)
,`initiative_id` int(11)
,`name` varchar(200)
,`due_date` date
,`days_to_due` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_tasks_overdue`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_tasks_overdue` (
`task_id` int(11)
,`initiative_id` int(11)
,`title` varchar(255)
,`assigned_to` int(11)
,`due_date` date
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_tasks_upcoming`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_tasks_upcoming` (
`task_id` int(11)
,`initiative_id` int(11)
,`title` varchar(255)
,`assigned_to` int(11)
,`due_date` date
,`days_to_due` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_user_permissions`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_user_permissions` (
`initiative_id` int(11)
,`user_id` int(11)
,`permission_key` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_visibility`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_visibility` (
`user_id` int(11)
,`initiative_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_visibility_by_global_role`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_visibility_by_global_role` (
`user_id` int(11)
,`initiative_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_initiative_visibility_by_team`
-- (See below for the actual view)
--
CREATE TABLE `vw_initiative_visibility_by_team` (
`user_id` int(11)
,`initiative_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_kpi_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_kpi_summary` (
`parent_type` enum('initiative','project')
,`parent_id` int(11)
,`kpi_count` bigint(21)
,`avg_current_value` decimal(19,6)
,`max_current_value` decimal(15,2)
,`min_current_value` decimal(15,2)
,`latest_kpi_update` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_latest_approval_status`
-- (See below for the actual view)
--
CREATE TABLE `vw_latest_approval_status` (
`id` int(11)
,`entity_type_id` int(11)
,`entity_id` int(11)
,`current_stage_id` int(11)
,`status` enum('in_progress','approved','rejected','returned')
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_health_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_health_summary` (
`project_id` int(11)
,`project_code` varchar(50)
,`project_name` varchar(255)
,`status_id` int(11)
,`start_date` date
,`end_date` date
,`tasks_total` decimal(22,0)
,`tasks_open` decimal(22,0)
,`tasks_overdue` decimal(22,0)
,`milestones_total` decimal(22,0)
,`milestones_open` decimal(22,0)
,`milestones_overdue` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_milestones_overdue`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_milestones_overdue` (
`milestone_id` int(11)
,`project_id` int(11)
,`name` varchar(200)
,`due_date` date
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_milestones_upcoming`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_milestones_upcoming` (
`milestone_id` int(11)
,`project_id` int(11)
,`name` varchar(200)
,`due_date` date
,`days_to_due` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_tasks_overdue`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_tasks_overdue` (
`task_id` int(11)
,`project_id` int(11)
,`title` varchar(255)
,`assigned_to` int(11)
,`due_date` date
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_tasks_upcoming`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_tasks_upcoming` (
`task_id` int(11)
,`project_id` int(11)
,`title` varchar(255)
,`assigned_to` int(11)
,`due_date` date
,`days_to_due` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_user_permissions`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_user_permissions` (
`project_id` int(11)
,`user_id` int(11)
,`permission_key` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_visibility`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_visibility` (
`user_id` int(11)
,`project_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_visibility_by_branch`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_visibility_by_branch` (
`user_id` int(11)
,`project_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_project_visibility_by_global_role`
-- (See below for the actual view)
--
CREATE TABLE `vw_project_visibility_by_global_role` (
`user_id` int(11)
,`project_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_risk_levels`
-- (See below for the actual view)
--
CREATE TABLE `vw_risk_levels` (
`parent_type` enum('initiative','project')
,`parent_id` int(11)
,`risk_id` int(11)
,`title` varchar(255)
,`risk_score` int(11)
,`risk_level` varchar(6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_initiatives`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_initiatives` (
`user_id` int(11)
,`initiative_id` int(11)
,`initiative_code` varchar(50)
,`initiative_name` varchar(300)
,`pillar_id` int(11)
,`pillar_name` varchar(200)
,`status_id` int(11)
,`start_date` date
,`due_date` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_projects`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_projects` (
`user_id` int(11)
,`project_id` int(11)
,`project_code` varchar(50)
,`project_name` varchar(255)
,`department_id` int(11)
,`department_name` varchar(150)
,`status_id` int(11)
,`start_date` date
,`end_date` date
);

-- --------------------------------------------------------

--
-- Table structure for table `work_resources`
--

CREATE TABLE `work_resources` (
  `id` int(11) NOT NULL,
  `parent_type` enum('initiative','project') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `resource_type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `cost_per_unit` decimal(15,2) DEFAULT 0.00,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS (`qty` * `cost_per_unit`) STORED,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_resources`
--

INSERT INTO `work_resources` (`id`, `parent_type`, `parent_id`, `resource_type_id`, `name`, `qty`, `cost_per_unit`, `assigned_to`, `notes`, `created_at`) VALUES
(1, 'initiative', 11, 2, 'Test2', 1, 0.00, 14, '', '2025-12-23 06:24:28'),
(2, 'initiative', 11, 5, 'Test1', 1, 100.00, 7, 'Test1Test1', '2025-12-23 06:28:28');

-- --------------------------------------------------------

--
-- Structure for view `vw_approval_actions_summary`
--
DROP TABLE IF EXISTS `vw_approval_actions_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_approval_actions_summary`  AS SELECT `ai`.`entity_type_id` AS `entity_type_id`, `ai`.`entity_id` AS `entity_id`, `a`.`stage_id` AS `stage_id`, count(0) AS `decisions_total`, sum(`a`.`decision` = 'approved') AS `approved_count`, sum(`a`.`decision` = 'rejected') AS `rejected_count`, sum(`a`.`decision` = 'returned') AS `returned_count`, min(`a`.`created_at`) AS `first_action_at`, max(`a`.`created_at`) AS `last_action_at` FROM (`approval_instances` `ai` join `approval_actions` `a` on(`a`.`approval_instance_id` = `ai`.`id`)) GROUP BY `ai`.`entity_type_id`, `ai`.`entity_id`, `a`.`stage_id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_department_branches_overview`
--
DROP TABLE IF EXISTS `vw_department_branches_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_department_branches_overview`  AS SELECT `d`.`id` AS `department_id`, `d`.`name` AS `department_name`, group_concat(`b`.`branch_name` order by `b`.`branch_name` ASC separator ', ') AS `branches` FROM ((`department_branches` `db` join `departments` `d` on(`d`.`id` = `db`.`department_id`)) join `branches` `b` on(`b`.`id` = `db`.`branch_id`)) GROUP BY `d`.`id`, `d`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_health_summary`
--
DROP TABLE IF EXISTS `vw_initiative_health_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_health_summary`  AS SELECT `i`.`id` AS `initiative_id`, `i`.`initiative_code` AS `initiative_code`, `i`.`name` AS `initiative_name`, `i`.`status_id` AS `status_id`, `i`.`start_date` AS `start_date`, `i`.`due_date` AS `due_date`, sum(case when `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_total`, sum(case when `s`.`name` <> 'completed' and `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_open`, sum(case when `t`.`due_date` is not null and `t`.`due_date` < curdate() and `s`.`name` <> 'completed' and `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_overdue`, sum(case when `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_total`, sum(case when `ms`.`name` <> 'completed' and `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_open`, sum(case when `m`.`due_date` is not null and `m`.`due_date` < curdate() and `ms`.`name` <> 'completed' and `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_overdue` FROM ((((`initiatives` `i` left join `initiative_tasks` `t` on(`t`.`initiative_id` = `i`.`id`)) left join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) left join `initiative_milestones` `m` on(`m`.`initiative_id` = `i`.`id`)) left join `milestone_statuses` `ms` on(`ms`.`id` = `m`.`status_id`)) WHERE `i`.`is_deleted` = 0 OR `i`.`is_deleted` is null GROUP BY `i`.`id`, `i`.`initiative_code`, `i`.`name`, `i`.`status_id`, `i`.`start_date`, `i`.`due_date` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_milestones_overdue`
--
DROP TABLE IF EXISTS `vw_initiative_milestones_overdue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_milestones_overdue`  AS SELECT `m`.`id` AS `milestone_id`, `m`.`initiative_id` AS `initiative_id`, `m`.`name` AS `name`, `m`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`m`.`due_date`) AS `days_overdue` FROM (`initiative_milestones` `m` join `milestone_statuses` `s` on(`s`.`id` = `m`.`status_id`)) WHERE `m`.`due_date` is not null AND `m`.`due_date` < curdate() AND `m`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_milestones_upcoming`
--
DROP TABLE IF EXISTS `vw_initiative_milestones_upcoming`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_milestones_upcoming`  AS SELECT `m`.`id` AS `milestone_id`, `m`.`initiative_id` AS `initiative_id`, `m`.`name` AS `name`, `m`.`due_date` AS `due_date`, to_days(`m`.`due_date`) - to_days(curdate()) AS `days_to_due` FROM (`initiative_milestones` `m` join `milestone_statuses` `s` on(`s`.`id` = `m`.`status_id`)) WHERE `m`.`due_date` is not null AND `m`.`due_date` >= curdate() AND to_days(`m`.`due_date`) - to_days(curdate()) between 0 and 14 AND `m`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_tasks_overdue`
--
DROP TABLE IF EXISTS `vw_initiative_tasks_overdue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_tasks_overdue`  AS SELECT `t`.`id` AS `task_id`, `t`.`initiative_id` AS `initiative_id`, `t`.`title` AS `title`, `t`.`assigned_to` AS `assigned_to`, `t`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`t`.`due_date`) AS `days_overdue` FROM (`initiative_tasks` `t` join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) WHERE `t`.`due_date` is not null AND `t`.`due_date` < curdate() AND `t`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_tasks_upcoming`
--
DROP TABLE IF EXISTS `vw_initiative_tasks_upcoming`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_tasks_upcoming`  AS SELECT `t`.`id` AS `task_id`, `t`.`initiative_id` AS `initiative_id`, `t`.`title` AS `title`, `t`.`assigned_to` AS `assigned_to`, `t`.`due_date` AS `due_date`, to_days(`t`.`due_date`) - to_days(curdate()) AS `days_to_due` FROM (`initiative_tasks` `t` join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) WHERE `t`.`due_date` is not null AND `t`.`due_date` >= curdate() AND to_days(`t`.`due_date`) - to_days(curdate()) between 0 and 7 AND `t`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_user_permissions`
--
DROP TABLE IF EXISTS `vw_initiative_user_permissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_user_permissions`  AS SELECT `it`.`initiative_id` AS `initiative_id`, `it`.`user_id` AS `user_id`, `p`.`permission_key` AS `permission_key` FROM ((`initiative_team` `it` join `initiative_role_permissions` `irp` on(`irp`.`role_id` = `it`.`role_id`)) join `permissions` `p` on(`p`.`id` = `irp`.`permission_id`)) WHERE `it`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_visibility`
--
DROP TABLE IF EXISTS `vw_initiative_visibility`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_visibility`  AS SELECT `vw_initiative_visibility_by_team`.`user_id` AS `user_id`, `vw_initiative_visibility_by_team`.`initiative_id` AS `initiative_id` FROM `vw_initiative_visibility_by_team`union select `vw_initiative_visibility_by_global_role`.`user_id` AS `user_id`,`vw_initiative_visibility_by_global_role`.`initiative_id` AS `initiative_id` from `vw_initiative_visibility_by_global_role`  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_visibility_by_global_role`
--
DROP TABLE IF EXISTS `vw_initiative_visibility_by_global_role`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_visibility_by_global_role`  AS SELECT DISTINCT `ur`.`user_id` AS `user_id`, `i`.`id` AS `initiative_id` FROM ((`initiatives` `i` join `user_roles` `ur` on(1)) join `roles` `r` on(`r`.`id` = `ur`.`role_id`)) WHERE (`i`.`is_deleted` = 0 OR `i`.`is_deleted` is null) AND `r`.`role_key` in ('super_admin','ceo','strategy_office') ;

-- --------------------------------------------------------

--
-- Structure for view `vw_initiative_visibility_by_team`
--
DROP TABLE IF EXISTS `vw_initiative_visibility_by_team`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_initiative_visibility_by_team`  AS SELECT DISTINCT `it`.`user_id` AS `user_id`, `i`.`id` AS `initiative_id` FROM (((`initiatives` `i` join `initiative_team` `it` on(`it`.`initiative_id` = `i`.`id` and `it`.`is_active` = 1)) join `initiative_role_permissions` `irp` on(`irp`.`role_id` = `it`.`role_id`)) join `permissions` `p` on(`p`.`id` = `irp`.`permission_id`)) WHERE (`i`.`is_deleted` = 0 OR `i`.`is_deleted` is null) AND `p`.`permission_key` = 'view_initiative' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_kpi_summary`
--
DROP TABLE IF EXISTS `vw_kpi_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_kpi_summary`  AS SELECT `kpis`.`parent_type` AS `parent_type`, `kpis`.`parent_id` AS `parent_id`, count(0) AS `kpi_count`, avg(`kpis`.`current_value`) AS `avg_current_value`, max(`kpis`.`current_value`) AS `max_current_value`, min(`kpis`.`current_value`) AS `min_current_value`, max(`kpis`.`last_updated`) AS `latest_kpi_update` FROM `kpis` WHERE `kpis`.`is_deleted` = 0 OR `kpis`.`is_deleted` is null GROUP BY `kpis`.`parent_type`, `kpis`.`parent_id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_latest_approval_status`
--
DROP TABLE IF EXISTS `vw_latest_approval_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_latest_approval_status`  AS SELECT `ai`.`id` AS `id`, `ai`.`entity_type_id` AS `entity_type_id`, `ai`.`entity_id` AS `entity_id`, `ai`.`current_stage_id` AS `current_stage_id`, `ai`.`status` AS `status`, `ai`.`created_by` AS `created_by`, `ai`.`created_at` AS `created_at`, `ai`.`updated_at` AS `updated_at` FROM (`approval_instances` `ai` join (select `approval_instances`.`entity_type_id` AS `entity_type_id`,`approval_instances`.`entity_id` AS `entity_id`,max(`approval_instances`.`updated_at`) AS `max_updated` from `approval_instances` group by `approval_instances`.`entity_type_id`,`approval_instances`.`entity_id`) `latest` on(`latest`.`entity_type_id` = `ai`.`entity_type_id` and `latest`.`entity_id` = `ai`.`entity_id` and `latest`.`max_updated` = `ai`.`updated_at`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_health_summary`
--
DROP TABLE IF EXISTS `vw_project_health_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_health_summary`  AS SELECT `op`.`id` AS `project_id`, `op`.`project_code` AS `project_code`, `op`.`name` AS `project_name`, `op`.`status_id` AS `status_id`, `op`.`start_date` AS `start_date`, `op`.`end_date` AS `end_date`, sum(case when `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_total`, sum(case when `s`.`name` <> 'completed' and `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_open`, sum(case when `t`.`due_date` is not null and `t`.`due_date` < curdate() and `s`.`name` <> 'completed' and `t`.`is_deleted` = 0 then 1 else 0 end) AS `tasks_overdue`, sum(case when `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_total`, sum(case when `ms`.`name` <> 'completed' and `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_open`, sum(case when `m`.`due_date` is not null and `m`.`due_date` < curdate() and `ms`.`name` <> 'completed' and `m`.`is_deleted` = 0 then 1 else 0 end) AS `milestones_overdue` FROM ((((`operational_projects` `op` left join `project_tasks` `t` on(`t`.`project_id` = `op`.`id`)) left join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) left join `project_milestones` `m` on(`m`.`project_id` = `op`.`id`)) left join `milestone_statuses` `ms` on(`ms`.`id` = `m`.`status_id`)) WHERE `op`.`is_deleted` = 0 OR `op`.`is_deleted` is null GROUP BY `op`.`id`, `op`.`project_code`, `op`.`name`, `op`.`status_id`, `op`.`start_date`, `op`.`end_date` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_milestones_overdue`
--
DROP TABLE IF EXISTS `vw_project_milestones_overdue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_milestones_overdue`  AS SELECT `m`.`id` AS `milestone_id`, `m`.`project_id` AS `project_id`, `m`.`name` AS `name`, `m`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`m`.`due_date`) AS `days_overdue` FROM (`project_milestones` `m` join `milestone_statuses` `s` on(`s`.`id` = `m`.`status_id`)) WHERE `m`.`due_date` is not null AND `m`.`due_date` < curdate() AND `m`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_milestones_upcoming`
--
DROP TABLE IF EXISTS `vw_project_milestones_upcoming`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_milestones_upcoming`  AS SELECT `m`.`id` AS `milestone_id`, `m`.`project_id` AS `project_id`, `m`.`name` AS `name`, `m`.`due_date` AS `due_date`, to_days(`m`.`due_date`) - to_days(curdate()) AS `days_to_due` FROM (`project_milestones` `m` join `milestone_statuses` `s` on(`s`.`id` = `m`.`status_id`)) WHERE `m`.`due_date` is not null AND `m`.`due_date` >= curdate() AND to_days(`m`.`due_date`) - to_days(curdate()) between 0 and 14 AND `m`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_tasks_overdue`
--
DROP TABLE IF EXISTS `vw_project_tasks_overdue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_tasks_overdue`  AS SELECT `t`.`id` AS `task_id`, `t`.`project_id` AS `project_id`, `t`.`title` AS `title`, `t`.`assigned_to` AS `assigned_to`, `t`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`t`.`due_date`) AS `days_overdue` FROM (`project_tasks` `t` join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) WHERE `t`.`due_date` is not null AND `t`.`due_date` < curdate() AND `t`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_tasks_upcoming`
--
DROP TABLE IF EXISTS `vw_project_tasks_upcoming`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_tasks_upcoming`  AS SELECT `t`.`id` AS `task_id`, `t`.`project_id` AS `project_id`, `t`.`title` AS `title`, `t`.`assigned_to` AS `assigned_to`, `t`.`due_date` AS `due_date`, to_days(`t`.`due_date`) - to_days(curdate()) AS `days_to_due` FROM (`project_tasks` `t` join `task_statuses` `s` on(`s`.`id` = `t`.`status_id`)) WHERE `t`.`due_date` is not null AND `t`.`due_date` >= curdate() AND to_days(`t`.`due_date`) - to_days(curdate()) between 0 and 7 AND `t`.`is_deleted` = 0 AND `s`.`name` <> 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_user_permissions`
--
DROP TABLE IF EXISTS `vw_project_user_permissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_user_permissions`  AS SELECT `pt`.`project_id` AS `project_id`, `pt`.`user_id` AS `user_id`, `p`.`permission_key` AS `permission_key` FROM ((`project_team` `pt` join `project_role_permissions` `prp` on(`prp`.`role_id` = `pt`.`role_id`)) join `permissions` `p` on(`p`.`id` = `prp`.`permission_id`)) WHERE `pt`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_visibility`
--
DROP TABLE IF EXISTS `vw_project_visibility`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_visibility`  AS SELECT `vw_project_visibility_by_branch`.`user_id` AS `user_id`, `vw_project_visibility_by_branch`.`project_id` AS `project_id` FROM `vw_project_visibility_by_branch`union select `vw_project_visibility_by_global_role`.`user_id` AS `user_id`,`vw_project_visibility_by_global_role`.`project_id` AS `project_id` from `vw_project_visibility_by_global_role`  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_visibility_by_branch`
--
DROP TABLE IF EXISTS `vw_project_visibility_by_branch`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_visibility_by_branch`  AS SELECT DISTINCT `ub`.`user_id` AS `user_id`, `op`.`id` AS `project_id` FROM ((`operational_projects` `op` join `department_branches` `db` on(`db`.`department_id` = `op`.`department_id`)) join `user_branches` `ub` on(`ub`.`branch_id` = `db`.`branch_id`)) WHERE `op`.`is_deleted` = 0 OR `op`.`is_deleted` is null ;

-- --------------------------------------------------------

--
-- Structure for view `vw_project_visibility_by_global_role`
--
DROP TABLE IF EXISTS `vw_project_visibility_by_global_role`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_project_visibility_by_global_role`  AS SELECT DISTINCT `ur`.`user_id` AS `user_id`, `op`.`id` AS `project_id` FROM ((`operational_projects` `op` join `user_roles` `ur` on(1)) join `roles` `r` on(`r`.`id` = `ur`.`role_id`)) WHERE `r`.`role_key` in ('super_admin','ceo','strategy_office') AND (`op`.`is_deleted` = 0 OR `op`.`is_deleted` is null) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_risk_levels`
--
DROP TABLE IF EXISTS `vw_risk_levels`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_risk_levels`  AS SELECT `risk_assessments`.`parent_type` AS `parent_type`, `risk_assessments`.`parent_id` AS `parent_id`, `risk_assessments`.`id` AS `risk_id`, `risk_assessments`.`title` AS `title`, `risk_assessments`.`risk_score` AS `risk_score`, CASE WHEN `risk_assessments`.`risk_score` >= 20 THEN 'High' WHEN `risk_assessments`.`risk_score` >= 10 THEN 'Medium' ELSE 'Low' END AS `risk_level` FROM `risk_assessments` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_initiatives`
--
DROP TABLE IF EXISTS `vw_user_initiatives`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_initiatives`  AS SELECT `v`.`user_id` AS `user_id`, `i`.`id` AS `initiative_id`, `i`.`initiative_code` AS `initiative_code`, `i`.`name` AS `initiative_name`, `i`.`pillar_id` AS `pillar_id`, `p`.`name` AS `pillar_name`, `i`.`status_id` AS `status_id`, `i`.`start_date` AS `start_date`, `i`.`due_date` AS `due_date` FROM ((`vw_initiative_visibility` `v` join `initiatives` `i` on(`i`.`id` = `v`.`initiative_id`)) left join `pillars` `p` on(`p`.`id` = `i`.`pillar_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_projects`
--
DROP TABLE IF EXISTS `vw_user_projects`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_projects`  AS SELECT `v`.`user_id` AS `user_id`, `op`.`id` AS `project_id`, `op`.`project_code` AS `project_code`, `op`.`name` AS `project_name`, `op`.`department_id` AS `department_id`, `d`.`name` AS `department_name`, `op`.`status_id` AS `status_id`, `op`.`start_date` AS `start_date`, `op`.`end_date` AS `end_date` FROM ((`vw_project_visibility` `v` join `operational_projects` `op` on(`op`.`id` = `v`.`project_id`)) left join `departments` `d` on(`d`.`id` = `op`.`department_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approval_actions`
--
ALTER TABLE `approval_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aa_stage` (`stage_id`),
  ADD KEY `idx_aa_instance_stage_decision` (`approval_instance_id`,`stage_id`,`decision`),
  ADD KEY `idx_aa_reviewer` (`reviewer_user_id`,`decision`,`created_at`);

--
-- Indexes for table `approval_entity_types`
--
ALTER TABLE `approval_entity_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entity_key` (`entity_key`);

--
-- Indexes for table `approval_instances`
--
ALTER TABLE `approval_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ai_creator` (`created_by`),
  ADD KEY `fk_ai_stage` (`current_stage_id`),
  ADD KEY `idx_ai_entity_status` (`entity_type_id`,`entity_id`,`status`);

--
-- Indexes for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aw_entity` (`entity_type_id`);

--
-- Indexes for table `approval_workflow_stages`
--
ALTER TABLE `approval_workflow_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aws_workflow` (`workflow_id`),
  ADD KEY `idx_aws_stage_role` (`stage_role_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `collaborations`
--
ALTER TABLE `collaborations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_collab_dept` (`department_id`),
  ADD KEY `fk_collab_user_req` (`requested_by`),
  ADD KEY `fk_collab_status` (`status_id`),
  ADD KEY `fk_collab_assignee` (`assigned_user_id`),
  ADD KEY `fk_collab_reviewer` (`reviewed_by`);

--
-- Indexes for table `collaboration_approval_history`
--
ALTER TABLE `collaboration_approval_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hist_collab` (`collaboration_id`),
  ADD KEY `fk_hist_user` (`action_by`),
  ADD KEY `fk_hist_assignee` (`assigned_user_id`);

--
-- Indexes for table `collaboration_statuses`
--
ALTER TABLE `collaboration_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `daily_initiative_health_snapshot`
--
ALTER TABLE `daily_initiative_health_snapshot`
  ADD PRIMARY KEY (`snapshot_date`,`initiative_id`);

--
-- Indexes for table `daily_kpi_summary_snapshot`
--
ALTER TABLE `daily_kpi_summary_snapshot`
  ADD PRIMARY KEY (`snapshot_date`,`parent_type`,`parent_id`);

--
-- Indexes for table `daily_project_health_snapshot`
--
ALTER TABLE `daily_project_health_snapshot`
  ADD PRIMARY KEY (`snapshot_date`,`project_id`);

--
-- Indexes for table `daily_risk_levels_snapshot`
--
ALTER TABLE `daily_risk_levels_snapshot`
  ADD PRIMARY KEY (`snapshot_date`,`parent_type`,`parent_id`,`risk_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_department_manager` (`manager_id`);

--
-- Indexes for table `department_branches`
--
ALTER TABLE `department_branches`
  ADD PRIMARY KEY (`department_id`,`branch_id`),
  ADD KEY `fk_db_branch` (`branch_id`);

--
-- Indexes for table `discussion_attachments`
--
ALTER TABLE `discussion_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attachment_message` (`message_id`);

--
-- Indexes for table `discussion_messages`
--
ALTER TABLE `discussion_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_discussion_thread` (`thread_id`),
  ADD KEY `fk_discussion_sender` (`sender_id`),
  ADD KEY `fk_discussion_reply` (`parent_message_id`);

--
-- Indexes for table `discussion_threads`
--
ALTER TABLE `discussion_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_discussion_creator` (`created_by`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_docs_parent` (`parent_type`,`parent_id`),
  ADD KEY `idx_docs_uploader` (`uploaded_by`),
  ADD KEY `idx_docs_title` (`title`);

--
-- Indexes for table `document_tags`
--
ALTER TABLE `document_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_tag_map`
--
ALTER TABLE `document_tag_map`
  ADD PRIMARY KEY (`document_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `initiatives`
--
ALTER TABLE `initiatives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `initiative_code` (`initiative_code`),
  ADD KEY `fk_initiative_status` (`status_id`),
  ADD KEY `fk_initiative_objective` (`strategic_objective_id`),
  ADD KEY `idx_init_pillar_status` (`pillar_id`,`status_id`),
  ADD KEY `idx_initiatives_pillar_status` (`pillar_id`,`status_id`),
  ADD KEY `idx_initiatives_owner` (`owner_user_id`),
  ADD KEY `idx_initiatives_dates` (`start_date`,`due_date`);

--
-- Indexes for table `initiative_milestones`
--
ALTER TABLE `initiative_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_im_status` (`status_id`),
  ADD KEY `idx_im_initiative_status` (`initiative_id`,`status_id`),
  ADD KEY `idx_im_dates` (`start_date`,`due_date`);

--
-- Indexes for table `initiative_objectives`
--
ALTER TABLE `initiative_objectives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `initiative_roles`
--
ALTER TABLE `initiative_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_initiative_roles_name` (`name`);

--
-- Indexes for table `initiative_role_permissions`
--
ALTER TABLE `initiative_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `idx_irp_perm` (`permission_id`);

--
-- Indexes for table `initiative_statuses`
--
ALTER TABLE `initiative_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `initiative_tasks`
--
ALTER TABLE `initiative_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_it_milestone` (`milestone_id`),
  ADD KEY `fk_it_status` (`status_id`),
  ADD KEY `fk_it_priority` (`priority_id`),
  ADD KEY `idx_it_initiative_status` (`initiative_id`,`status_id`),
  ADD KEY `idx_it_assignee` (`assigned_to`),
  ADD KEY `idx_it_dates` (`start_date`,`due_date`);

--
-- Indexes for table `initiative_team`
--
ALTER TABLE `initiative_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_init_team_init` (`initiative_id`),
  ADD KEY `fk_init_team_user` (`user_id`),
  ADD KEY `fk_init_team_role` (`role_id`);

--
-- Indexes for table `initiative_user_permissions`
--
ALTER TABLE `initiative_user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_init_perm` (`initiative_id`,`user_id`,`permission_id`);

--
-- Indexes for table `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kpi_owner` (`owner_id`),
  ADD KEY `idx_kpi_parent` (`parent_type`,`parent_id`),
  ADD KEY `idx_kpi_status` (`status_id`);

--
-- Indexes for table `kpi_statuses`
--
ALTER TABLE `kpi_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_minutes`
--
ALTER TABLE `meeting_minutes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mm_prepared` (`prepared_by`),
  ADD KEY `fk_mm_approved` (`approved_by`);

--
-- Indexes for table `milestone_statuses`
--
ALTER TABLE `milestone_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mr_owner` (`owner_id`),
  ADD KEY `fk_mr_publisher` (`publisher_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `operational_projects`
--
ALTER TABLE `operational_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `fk_op_project_status` (`status_id`),
  ADD KEY `fk_op_project_initiative` (`initiative_id`),
  ADD KEY `idx_op_dept_status` (`department_id`,`status_id`),
  ADD KEY `idx_op_manager_status` (`manager_id`,`status_id`),
  ADD KEY `idx_op_dates` (`start_date`,`end_date`);

--
-- Indexes for table `operational_project_statuses`
--
ALTER TABLE `operational_project_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `pillars`
--
ALTER TABLE `pillars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pillar_number` (`pillar_number`),
  ADD KEY `fk_pillar_lead` (`lead_user_id`),
  ADD KEY `idx_pillars_status_progress` (`status_id`,`progress_percentage`);

--
-- Indexes for table `pillar_roles`
--
ALTER TABLE `pillar_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pillar_statuses`
--
ALTER TABLE `pillar_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pillar_team`
--
ALTER TABLE `pillar_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pillar_team_pillar` (`pillar_id`),
  ADD KEY `fk_pillar_team_user` (`user_id`),
  ADD KEY `fk_pt_role` (`role_id`);

--
-- Indexes for table `progress_updates`
--
ALTER TABLE `progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_update_user` (`user_id`);

--
-- Indexes for table `project_milestones`
--
ALTER TABLE `project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pm_status` (`status_id`),
  ADD KEY `idx_pm_project_status` (`project_id`,`status_id`),
  ADD KEY `idx_pm_dates` (`start_date`,`due_date`);

--
-- Indexes for table `project_objectives`
--
ALTER TABLE `project_objectives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_proj_obj_project` (`project_id`);

--
-- Indexes for table `project_roles`
--
ALTER TABLE `project_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_project_roles_name` (`name`);

--
-- Indexes for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pt_milestone` (`milestone_id`),
  ADD KEY `fk_pt_status` (`status_id`),
  ADD KEY `fk_pt_priority` (`priority_id`),
  ADD KEY `idx_pt_project_status` (`project_id`,`status_id`),
  ADD KEY `idx_pt_assignee` (`assigned_to`),
  ADD KEY `idx_pt_dates` (`start_date`,`due_date`);

--
-- Indexes for table `project_team`
--
ALTER TABLE `project_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_proj_team_proj` (`project_id`),
  ADD KEY `fk_proj_team_user` (`user_id`),
  ADD KEY `fk_proj_team_role` (`role_id`);

--
-- Indexes for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_project_update_project` (`project_id`),
  ADD KEY `fk_project_update_user` (`user_id`);

--
-- Indexes for table `project_update_reminders`
--
ALTER TABLE `project_update_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_user_permissions`
--
ALTER TABLE `project_user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_perm` (`project_id`,`user_id`,`permission_id`),
  ADD KEY `fk_pup_user` (`user_id`),
  ADD KEY `fk_pup_perm` (`permission_id`);

--
-- Indexes for table `resource_types`
--
ALTER TABLE `resource_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_risk_status` (`status_id`);

--
-- Indexes for table `risk_statuses`
--
ALTER TABLE `risk_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_rp_perm` (`permission_id`);

--
-- Indexes for table `strategic_objectives`
--
ALTER TABLE `strategic_objectives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_strategic_objectives_code` (`objective_code`),
  ADD KEY `idx_obj_pillar` (`pillar_id`);

--
-- Indexes for table `task_priorities`
--
ALTER TABLE `task_priorities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_statuses`
--
ALTER TABLE `task_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_department` (`department_id`),
  ADD KEY `fk_user_primary_role` (`primary_role_id`);

--
-- Indexes for table `user_branches`
--
ALTER TABLE `user_branches`
  ADD PRIMARY KEY (`user_id`,`branch_id`),
  ADD KEY `fk_ub_branch` (`branch_id`);

--
-- Indexes for table `user_hierarchy`
--
ALTER TABLE `user_hierarchy`
  ADD PRIMARY KEY (`user_id`,`reporting_type`),
  ADD KEY `fk_uh_manager` (`manager_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_ur_role` (`role_id`);

--
-- Indexes for table `user_todos`
--
ALTER TABLE `user_todos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_todo_user` (`user_id`);

--
-- Indexes for table `work_resources`
--
ALTER TABLE `work_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resource_type` (`resource_type_id`),
  ADD KEY `fk_resource_user` (`assigned_to`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `approval_actions`
--
ALTER TABLE `approval_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `approval_entity_types`
--
ALTER TABLE `approval_entity_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `approval_instances`
--
ALTER TABLE `approval_instances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `approval_workflow_stages`
--
ALTER TABLE `approval_workflow_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `collaborations`
--
ALTER TABLE `collaborations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `collaboration_approval_history`
--
ALTER TABLE `collaboration_approval_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collaboration_statuses`
--
ALTER TABLE `collaboration_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `discussion_attachments`
--
ALTER TABLE `discussion_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_messages`
--
ALTER TABLE `discussion_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_threads`
--
ALTER TABLE `discussion_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_tags`
--
ALTER TABLE `document_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `initiatives`
--
ALTER TABLE `initiatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `initiative_milestones`
--
ALTER TABLE `initiative_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `initiative_objectives`
--
ALTER TABLE `initiative_objectives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `initiative_roles`
--
ALTER TABLE `initiative_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `initiative_statuses`
--
ALTER TABLE `initiative_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `initiative_tasks`
--
ALTER TABLE `initiative_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `initiative_team`
--
ALTER TABLE `initiative_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `initiative_user_permissions`
--
ALTER TABLE `initiative_user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpis`
--
ALTER TABLE `kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_statuses`
--
ALTER TABLE `kpi_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meeting_minutes`
--
ALTER TABLE `meeting_minutes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `milestone_statuses`
--
ALTER TABLE `milestone_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `operational_projects`
--
ALTER TABLE `operational_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `operational_project_statuses`
--
ALTER TABLE `operational_project_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=366;

--
-- AUTO_INCREMENT for table `pillars`
--
ALTER TABLE `pillars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pillar_roles`
--
ALTER TABLE `pillar_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pillar_statuses`
--
ALTER TABLE `pillar_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pillar_team`
--
ALTER TABLE `pillar_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `progress_updates`
--
ALTER TABLE `progress_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_milestones`
--
ALTER TABLE `project_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_objectives`
--
ALTER TABLE `project_objectives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `project_roles`
--
ALTER TABLE `project_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_tasks`
--
ALTER TABLE `project_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_team`
--
ALTER TABLE `project_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `project_update_reminders`
--
ALTER TABLE `project_update_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_user_permissions`
--
ALTER TABLE `project_user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_types`
--
ALTER TABLE `resource_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_statuses`
--
ALTER TABLE `risk_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `strategic_objectives`
--
ALTER TABLE `strategic_objectives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `task_priorities`
--
ALTER TABLE `task_priorities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_statuses`
--
ALTER TABLE `task_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_todos`
--
ALTER TABLE `user_todos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT for table `work_resources`
--
ALTER TABLE `work_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_actions`
--
ALTER TABLE `approval_actions`
  ADD CONSTRAINT `fk_aa_instance` FOREIGN KEY (`approval_instance_id`) REFERENCES `approval_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aa_stage` FOREIGN KEY (`stage_id`) REFERENCES `approval_workflow_stages` (`id`),
  ADD CONSTRAINT `fk_aa_user` FOREIGN KEY (`reviewer_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approval_instances`
--
ALTER TABLE `approval_instances`
  ADD CONSTRAINT `fk_ai_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ai_entity` FOREIGN KEY (`entity_type_id`) REFERENCES `approval_entity_types` (`id`),
  ADD CONSTRAINT `fk_ai_stage` FOREIGN KEY (`current_stage_id`) REFERENCES `approval_workflow_stages` (`id`);

--
-- Constraints for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD CONSTRAINT `fk_aw_entity` FOREIGN KEY (`entity_type_id`) REFERENCES `approval_entity_types` (`id`);

--
-- Constraints for table `approval_workflow_stages`
--
ALTER TABLE `approval_workflow_stages`
  ADD CONSTRAINT `fk_aws_stage_role` FOREIGN KEY (`stage_role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_aws_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `approval_workflows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `collaborations`
--
ALTER TABLE `collaborations`
  ADD CONSTRAINT `fk_collab_assignee` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_collab_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_collab_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_collab_status` FOREIGN KEY (`status_id`) REFERENCES `collaboration_statuses` (`id`),
  ADD CONSTRAINT `fk_collab_user_req` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `collaboration_approval_history`
--
ALTER TABLE `collaboration_approval_history`
  ADD CONSTRAINT `fk_hist_assignee` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_hist_collab` FOREIGN KEY (`collaboration_id`) REFERENCES `collaborations` (`id`),
  ADD CONSTRAINT `fk_hist_user` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_department_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `department_branches`
--
ALTER TABLE `department_branches`
  ADD CONSTRAINT `fk_db_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_db_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussion_attachments`
--
ALTER TABLE `discussion_attachments`
  ADD CONSTRAINT `fk_attachment_message` FOREIGN KEY (`message_id`) REFERENCES `discussion_messages` (`id`);

--
-- Constraints for table `discussion_messages`
--
ALTER TABLE `discussion_messages`
  ADD CONSTRAINT `fk_discussion_reply` FOREIGN KEY (`parent_message_id`) REFERENCES `discussion_messages` (`id`),
  ADD CONSTRAINT `fk_discussion_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_discussion_thread` FOREIGN KEY (`thread_id`) REFERENCES `discussion_threads` (`id`);

--
-- Constraints for table `discussion_threads`
--
ALTER TABLE `discussion_threads`
  ADD CONSTRAINT `fk_discussion_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_tag_map`
--
ALTER TABLE `document_tag_map`
  ADD CONSTRAINT `document_tag_map_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `document_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `document_tags` (`id`);

--
-- Constraints for table `initiatives`
--
ALTER TABLE `initiatives`
  ADD CONSTRAINT `fk_initiative_objective` FOREIGN KEY (`strategic_objective_id`) REFERENCES `strategic_objectives` (`id`),
  ADD CONSTRAINT `fk_initiative_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_initiative_pillar` FOREIGN KEY (`pillar_id`) REFERENCES `pillars` (`id`),
  ADD CONSTRAINT `fk_initiative_status` FOREIGN KEY (`status_id`) REFERENCES `initiative_statuses` (`id`);

--
-- Constraints for table `initiative_milestones`
--
ALTER TABLE `initiative_milestones`
  ADD CONSTRAINT `fk_im_initiative` FOREIGN KEY (`initiative_id`) REFERENCES `initiatives` (`id`),
  ADD CONSTRAINT `fk_im_status` FOREIGN KEY (`status_id`) REFERENCES `milestone_statuses` (`id`);

--
-- Constraints for table `initiative_role_permissions`
--
ALTER TABLE `initiative_role_permissions`
  ADD CONSTRAINT `fk_irp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_irp_role` FOREIGN KEY (`role_id`) REFERENCES `initiative_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `initiative_tasks`
--
ALTER TABLE `initiative_tasks`
  ADD CONSTRAINT `fk_it_initiative` FOREIGN KEY (`initiative_id`) REFERENCES `initiatives` (`id`),
  ADD CONSTRAINT `fk_it_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `initiative_milestones` (`id`),
  ADD CONSTRAINT `fk_it_priority` FOREIGN KEY (`priority_id`) REFERENCES `task_priorities` (`id`),
  ADD CONSTRAINT `fk_it_status` FOREIGN KEY (`status_id`) REFERENCES `task_statuses` (`id`),
  ADD CONSTRAINT `fk_it_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `initiative_team`
--
ALTER TABLE `initiative_team`
  ADD CONSTRAINT `fk_init_team_init` FOREIGN KEY (`initiative_id`) REFERENCES `initiatives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_init_team_role` FOREIGN KEY (`role_id`) REFERENCES `initiative_roles` (`id`),
  ADD CONSTRAINT `fk_init_team_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `kpis`
--
ALTER TABLE `kpis`
  ADD CONSTRAINT `fk_kpi_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kpi_status` FOREIGN KEY (`status_id`) REFERENCES `kpi_statuses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `meeting_minutes`
--
ALTER TABLE `meeting_minutes`
  ADD CONSTRAINT `fk_mm_approved` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_mm_prepared` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD CONSTRAINT `fk_mr_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_mr_publisher` FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `operational_projects`
--
ALTER TABLE `operational_projects`
  ADD CONSTRAINT `fk_op_project_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_op_project_initiative` FOREIGN KEY (`initiative_id`) REFERENCES `initiatives` (`id`),
  ADD CONSTRAINT `fk_op_project_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_op_project_status` FOREIGN KEY (`status_id`) REFERENCES `operational_project_statuses` (`id`);

--
-- Constraints for table `pillars`
--
ALTER TABLE `pillars`
  ADD CONSTRAINT `fk_pillar_lead` FOREIGN KEY (`lead_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_pillar_status` FOREIGN KEY (`status_id`) REFERENCES `pillar_statuses` (`id`);

--
-- Constraints for table `pillar_team`
--
ALTER TABLE `pillar_team`
  ADD CONSTRAINT `fk_pillar_team_pillar` FOREIGN KEY (`pillar_id`) REFERENCES `pillars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pillar_team_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pt_role` FOREIGN KEY (`role_id`) REFERENCES `pillar_roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `progress_updates`
--
ALTER TABLE `progress_updates`
  ADD CONSTRAINT `fk_update_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `project_milestones`
--
ALTER TABLE `project_milestones`
  ADD CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`),
  ADD CONSTRAINT `fk_pm_status` FOREIGN KEY (`status_id`) REFERENCES `milestone_statuses` (`id`);

--
-- Constraints for table `project_objectives`
--
ALTER TABLE `project_objectives`
  ADD CONSTRAINT `fk_proj_obj_project` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD CONSTRAINT `fk_pt_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones` (`id`),
  ADD CONSTRAINT `fk_pt_priority` FOREIGN KEY (`priority_id`) REFERENCES `task_priorities` (`id`),
  ADD CONSTRAINT `fk_pt_project` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`),
  ADD CONSTRAINT `fk_pt_status` FOREIGN KEY (`status_id`) REFERENCES `task_statuses` (`id`),
  ADD CONSTRAINT `fk_pt_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `project_team`
--
ALTER TABLE `project_team`
  ADD CONSTRAINT `fk_proj_team_proj` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`),
  ADD CONSTRAINT `fk_proj_team_role` FOREIGN KEY (`role_id`) REFERENCES `project_roles` (`id`),
  ADD CONSTRAINT `fk_proj_team_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD CONSTRAINT `fk_project_update_project` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_update_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_user_permissions`
--
ALTER TABLE `project_user_permissions`
  ADD CONSTRAINT `fk_pup_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pup_proj` FOREIGN KEY (`project_id`) REFERENCES `operational_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pup_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_assessments`
--
ALTER TABLE `risk_assessments`
  ADD CONSTRAINT `fk_risk_status` FOREIGN KEY (`status_id`) REFERENCES `risk_statuses` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `strategic_objectives`
--
ALTER TABLE `strategic_objectives`
  ADD CONSTRAINT `fk_obj_pillar` FOREIGN KEY (`pillar_id`) REFERENCES `pillars` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_primary_role` FOREIGN KEY (`primary_role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_branches`
--
ALTER TABLE `user_branches`
  ADD CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_hierarchy`
--
ALTER TABLE `user_hierarchy`
  ADD CONSTRAINT `fk_uh_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_todos`
--
ALTER TABLE `user_todos`
  ADD CONSTRAINT `fk_todo_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `work_resources`
--
ALTER TABLE `work_resources`
  ADD CONSTRAINT `fk_resource_type` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`),
  ADD CONSTRAINT `fk_resource_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_capture_daily_snapshots` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-08 02:30:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_capture_daily_snapshots(CURDATE())$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
