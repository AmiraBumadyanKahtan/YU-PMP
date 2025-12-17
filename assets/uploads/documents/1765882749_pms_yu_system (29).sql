-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 09:02 AM
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
(57, 5, 'create', 'project', 9, NULL, 'Created Project: Test787', NULL, '2025-12-16 07:57:29');

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
(28, 14, 11, 4, 'approved', '', '2025-12-15 11:57:15');

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
(13, 3, 7, 9, 'in_progress', 1, '2025-12-15 11:40:27', '2025-12-15 14:40:27'),
(14, 3, 8, NULL, 'approved', 1, '2025-12-15 11:46:38', '2025-12-15 14:57:15');

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
(2, 2, 'Initiative Approval Flow', 1),
(3, 3, 'Operational Project Approval Flow', 1),
(4, 4, 'Collaboration Approval Workflow', 1),
(5, 1, 'Pillar Approval Flow', 1),
(6, 3, 'Operational Project Approval Flow - without budget', 1);

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
(12, 2, 2, 'hierarchy_manager', NULL, 'academic', 'Academic Dean Approval', 0),
(13, 4, 1, 'system_role', 12, NULL, 'Department Head Approval', 1),
(15, 5, 1, 'system_role', 11, NULL, 'Strategy Office Review', 0),
(18, 5, 2, 'system_role', 10, NULL, 'CEO Final Approval', 1),
(19, 6, 1, 'system_role', 12, NULL, 'Department Head Approval', 0),
(20, 6, 2, 'system_role', 10, NULL, 'CEO Final Approval', 1);

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
(9, 'project', 5, 2, 'ببلبل', 1, 1, NULL, NULL, NULL, NULL, '2025-12-15 10:58:50', '2025-12-15 10:58:50');

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
(4, 'Test2', 5, '2025-12-06 18:10:35', '2025-12-06 19:08:46', 1, '2025-12-06 22:08:46'),
(5, 'Finance', 11, '2025-12-07 11:52:45', '2025-12-07 11:53:33', 0, NULL),
(6, 'SSD', 5, '2025-12-09 07:00:05', '2025-12-09 07:06:11', 1, '2025-12-09 10:06:11'),
(7, 'SSD', 5, '2025-12-09 07:06:52', '2025-12-09 07:06:55', 1, '2025-12-09 10:06:55'),
(8, 'IT', 12, '2025-12-09 18:59:01', '2025-12-09 19:00:48', 1, '2025-12-09 22:00:48'),
(9, 'IT Department', 12, '2025-12-09 18:59:51', '2025-12-09 19:00:24', 0, NULL),
(10, 'IT Department', 12, '2025-12-09 19:00:08', '2025-12-09 19:00:17', 1, '2025-12-09 22:00:17');

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
(9, 2);

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
(6, 'project', 8, 'اااا', 'Supporting Document for Approval', '1765799179_1765446149_pms_yu_system (26) (1).sql', 'assets/uploads/documents/1765799179_1765446149_pms_yu_system (26) (1).sql', 140573, 'sql', 1, '2025-12-15 11:46:19', 1, 0, '2025-12-15 11:46:19', '2025-12-15 11:46:19', 0, NULL);

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

INSERT INTO `initiatives` (`id`, `initiative_code`, `name`, `description`, `impact`, `notes`, `pillar_id`, `strategic_objective_id`, `owner_user_id`, `budget_min`, `budget_max`, `approved_budget`, `spent_budget`, `start_date`, `due_date`, `completion_date`, `status_id`, `priority`, `progress_percentage`, `order_index`, `created_at`, `updated_at`, `update_frequency`, `update_time`, `is_deleted`, `deleted_at`) VALUES
(2, 'INIT-2.01', 'Test2', 'Test2 Test2', 'Test2 Test2', 'Test2', 8, 3, 1, 10000.00, 20000.00, NULL, 0.00, '2025-12-17', '2026-01-09', NULL, 8, 'medium', 0, 0, '2025-12-06 05:44:47', '2025-12-06 05:44:47', 'weekly', '09:00:00', 0, NULL);

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

-- --------------------------------------------------------

--
-- Table structure for table `initiative_objectives`
--

CREATE TABLE `initiative_objectives` (
  `id` int(11) NOT NULL,
  `initiative_id` int(11) NOT NULL,
  `strategic_objective_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 58),
(1, 59),
(1, 60),
(1, 62),
(1, 63),
(1, 64),
(1, 65),
(1, 66),
(1, 72),
(1, 73),
(2, 58),
(2, 63),
(2, 72),
(3, 58),
(3, 65),
(3, 72),
(4, 58);

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
(1, 'Test2', 'Test2', 10.00, 7.00, 0.00, NULL, 'software', 'number', 'weekly', NULL, 3, 8, 'project', 5, '2025-12-16 08:20:35', '2025-12-11 08:08:13', '2025-12-16 05:20:35', 0, NULL);

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
(42, 4, 'Approval Request', 'Project \'IT-project2\' requires your approval.', 'approval', 'project', 8, 0, NULL, '2025-12-15 11:56:25');

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
(2, 'OP-2025-0002', 'Test255', 'Test255Test255', 1, 10, NULL, 15000.00, 25000.00, NULL, NULL, 0.00, '2025-12-10', '2026-01-10', 2, 0, 'high', 'private', '2025-12-07 11:20:33', '2025-12-07 11:23:07', 'every_2_days', '09:00:00', 0, NULL),
(3, 'OP-2025-0003', 'Test265', 'Test265Test265', 1, 10, NULL, 20000.00, 40000.00, 2000.00, NULL, 0.00, '2025-12-11', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-07 11:47:05', '2025-12-11 11:46:19', 'every_2_days', '09:00:00', 0, NULL),
(4, 'OP-2025-0004', 'Test2552', 'Test2552', 2, 15, NULL, 10000.00, 20000.00, 15000.00, NULL, 0.00, '2025-12-11', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-09 10:30:16', '2025-12-10 12:11:01', 'every_2_days', '09:00:00', 0, NULL),
(5, 'OP-2025-0005', 'Test5', 'Test5Test5', 1, 3, NULL, 15000.00, 30000.00, 25000.00, NULL, 31500.00, '2025-12-11', '2026-01-10', 5, 33, 'medium', 'public', '2025-12-09 18:34:44', '2025-12-15 05:08:58', 'every_2_days', '09:00:00', 0, NULL),
(6, 'OP-2025-0006', 'Test2555', 'ققبقبقبق', 2, 10, NULL, 1000.00, 2000.00, 2000.00, NULL, 0.00, '2025-12-12', '2026-01-15', 5, 45, 'medium', 'private', '2025-12-11 11:32:45', '2025-12-15 17:01:06', 'daily', '09:00:00', 0, NULL),
(7, 'OP-2025-0007', 'IT-project', 'IT-project', 1, 1, NULL, 10000.00, 20000.00, NULL, NULL, 0.00, '2025-12-24', '2026-01-10', 2, 0, 'medium', 'private', '2025-12-15 09:58:58', '2025-12-15 11:40:27', 'weekly', '09:00:00', 0, NULL),
(8, 'OP-2025-0008', 'IT-project2', 'IT-2IT-2', 5, 7, NULL, 10000.00, 30000.00, 25000.00, NULL, 0.00, '2025-12-17', '2026-01-10', 5, 0, 'medium', 'private', '2025-12-15 11:45:53', '2025-12-15 11:57:15', 'daily', '09:00:00', 0, NULL),
(9, 'OP-2025-0009', 'Test787', 'Test787Test787', 1, 14, NULL, 0.00, 0.00, NULL, '', 0.00, '2025-12-17', '2026-01-08', 1, 0, 'medium', 'private', '2025-12-16 07:57:29', '2025-12-16 07:57:29', 'weekly', '09:00:00', 0, NULL);

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
(1, 'manage_users', NULL, 'users'),
(2, 'manage_roles', NULL, 'rbac'),
(3, 'view_ceo_dashboard', NULL, 'dashboard'),
(4, 'approve_pillar', NULL, 'pillars'),
(5, 'approve_initiative', NULL, 'initiatives'),
(6, 'create_initiative', NULL, 'initiatives'),
(8, 'view_reports', NULL, 'reports'),
(9, 'manage_departments', 'Manage Departments', 'departments'),
(11, 'view_approvals', 'Access approval dashboard', 'approvals'),
(12, 'approve_requests', 'Approve or reject requests', NULL),
(30, 'create_project', 'Create operational projects', 'projects'),
(31, 'edit_project', 'Edit operational projects', 'projects'),
(32, 'delete_project', 'Delete operational projects', 'projects'),
(33, 'send_project_for_approval', 'Send operational project for approval workflow', 'projects'),
(34, 'manage_project_team', 'Manage project team members', 'projects'),
(35, 'manage_project_tasks', 'Manage project tasks', 'projects'),
(36, 'manage_project_resources', 'Manage project resources', 'projects'),
(37, 'manage_project_kpis', 'Manage project KPIs', 'projects'),
(38, 'view_project', 'View operational projects', 'projects'),
(39, 'send_progress_update', 'Send project progress update', 'projects'),
(40, 'approve_progress_update', 'Approve project progress update', 'projects'),
(41, 'manage_rbac', 'Manage roles & permissions', 'security'),
(42, 'view_project_updates_ceo', 'View project updates for CEO', 'reports'),
(58, 'view_initiative', 'View initiatives', 'initiatives'),
(59, 'edit_initiative', 'Edit initiatives', 'initiatives'),
(60, 'delete_initiative', 'Delete initiatives', 'initiatives'),
(61, 'send_initiative_for_approval', 'Send initiative for approval', 'initiatives'),
(62, 'manage_initiative_team', 'Manage initiative team', 'initiatives'),
(63, 'manage_initiative_tasks', 'Manage initiative tasks', 'initiatives'),
(64, 'manage_initiative_kpis', 'Manage initiative KPIs', 'initiatives'),
(65, 'manage_initiative_documents', 'Manage initiative documents', 'initiatives'),
(66, 'manage_initiative_risks', 'Manage initiative risks', 'initiatives'),
(67, 'approve_initiative_update', 'Approve initiative progress update', 'initiatives'),
(68, 'view_pillars', 'View strategic pillars', 'pillars'),
(69, 'create_pillar', 'Create strategic pillar', 'pillars'),
(70, 'edit_pillar', 'Edit strategic pillar', 'pillars'),
(71, 'delete_pillar', 'Delete strategic pillar', 'pillars'),
(72, 'send_initiative_progress_update', 'Send initiative progress update', 'initiatives'),
(73, 'approve_initiative_progress_update', 'Approve initiative progress update', 'initiatives'),
(75, 'view_strategic_objectives', 'View strategic objectives', 'pillars'),
(76, 'create_strategic_objective', 'Create strategic objective', 'pillars'),
(77, 'edit_strategic_objective', 'Edit strategic objective', 'pillars'),
(78, 'delete_strategic_objective', 'Delete strategic objective', 'pillars'),
(80, 'manage_project_documents', 'manage_project_documents', 'projects'),
(81, 'manage_project_risks', 'manage_project_risks', 'projects'),
(91, 'view_project_collaborations', 'View project collaborations tab', 'projects'),
(92, 'view_project_updates', 'View project updates tab', 'projects'),
(93, 'view_project_budget', 'View sensitive budget info', 'projects'),
(94, 'edit_assigned_tasks', 'Edit tasks assigned to the user', 'projects'),
(95, 'manage_project_permissions', 'Manage project team permissions', 'projects');

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
(1, 5, 'Sustainable Investment Growth', 'Focus on diversifying revenue streams and achieving financial sustainability through strategic partnerships and environmental initiatives.', 1, 0.00, '2025-11-08', '2025-12-31', 3, 0, '#ff8c00', 'fa-building', '2025-11-28 08:02:38', '2025-12-14 06:09:45', 0, NULL),
(3, 1, 'Test1', 'Test1', 1, 0.00, '2025-12-02', '2025-12-17', 9, 0, '#12e2d5', 'fa-pencil', '2025-12-01 10:18:10', '2025-12-03 07:20:18', 0, NULL),
(8, 2, 'Test2', 'Test2', 3, 0.00, '2025-12-05', '2025-12-12', 9, 0, '#004cff', 'fa-building', '2025-12-03 12:38:48', '2025-12-14 09:32:30', 0, '0000-00-00 00:00:00'),
(9, 3, 'Test3', 'Test3 Test3', 3, 0.00, '2025-12-12', '2026-02-03', 9, 0, '#bb00ff', 'fa-chart-pie', '2025-12-04 05:45:25', '2025-12-04 05:49:14', 0, NULL),
(10, 6, 'Test6', 'Test6', 12, 0.00, '2025-12-17', '2026-01-01', 2, 0, '#ff0059', 'fa-sliders', '2025-12-04 10:05:40', '2025-12-14 05:21:04', 0, NULL),
(11, 7, 'Test7', 'Test7', 1, 0.00, '2025-12-18', '2026-01-07', 2, 0, '#ff0000', 'fa-coins', '2025-12-04 10:13:15', '2025-12-14 09:31:16', 0, '0000-00-00 00:00:00'),
(12, 18, 'Test558', 'عؤبيبيس', 8, 0.00, '2025-12-15', '2026-01-10', 11, 0, '#3498db', 'fa-building', '2025-12-14 05:21:56', '2025-12-14 06:07:21', 0, NULL),
(13, 23, 'Test23332', 'Test23332Test23332Test23332Test23332', 11, 0.00, '2025-12-16', '2026-01-10', 11, 0, '#072d46', 'fa-people-group', '2025-12-14 05:50:04', '2025-12-14 09:37:53', 0, NULL);

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
(3, 13, 8, 2, '2025-12-14 08:36:04');

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
(1, 5, 'Test1', 'Test1Test1Test1Test1Test1Test1', '2025-12-11', '2026-01-10', NULL, 3, 100, 0, 9000.00, 31500.00, '2025-12-10 20:33:23', '2025-12-15 05:08:58', 0, NULL),
(2, 5, 'Test2', 'خنمهنمخ', '2025-12-12', '2025-12-18', NULL, 1, 0, 0, 1000.00, 0.00, '2025-12-11 06:44:12', '2025-12-11 06:44:12', 0, NULL),
(3, 6, 'Test2', 'ييييي', '2025-12-12', '2025-12-17', NULL, 2, 90, 0, 1000.00, 0.00, '2025-12-11 11:55:54', '2025-12-15 17:01:06', 0, NULL),
(4, 5, 'Test255543', '', '2025-12-17', '2026-01-09', NULL, 1, 0, 0, 0.00, 0.00, '2025-12-14 21:03:31', '2025-12-14 21:03:31', 0, NULL),
(5, 6, 'Test3', '', '2025-12-16', '2026-01-10', NULL, 1, 0, 0, 0.00, 0.00, '2025-12-15 09:46:15', '2025-12-15 09:46:15', 0, NULL);

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
(8, 8, 'Test5-122', '2025-12-15 11:46:34');

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
(1, 35),
(1, 38),
(1, 39),
(3, 38),
(3, 39),
(4, 38),
(5, 31),
(5, 32),
(5, 34),
(5, 35),
(5, 36),
(5, 37),
(5, 38),
(5, 39),
(5, 40);

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
(3, 1, 5, 'test 2', 'jghjh', 8, '2025-12-12', '2025-12-19', 3, 2, 8, 2500.00, 15000.00, 100, '2025-12-11 06:14:09', '2025-12-11 06:49:30', 0, NULL),
(4, 1, 5, 'jkkjkj', 'lkllklkl', 8, '2025-12-12', '2026-01-08', 3, 2, 1, 5000.00, 10000.00, 100, '2025-12-11 06:16:26', '2025-12-15 05:08:58', 0, NULL),
(5, 1, 5, 'test3', 'fgfgfg', 8, '2025-12-11', '2025-12-18', 3, 2, 1, 10000.00, 5000.00, 100, '2025-12-11 06:32:50', '2025-12-11 06:46:44', 0, NULL),
(6, 3, 6, 'test1', 'ddddd', 7, '2025-12-12', '2025-12-14', 3, 2, 4, 122.00, 0.00, 100, '2025-12-11 12:07:46', '2025-12-11 12:08:26', 0, NULL),
(7, 3, 6, 'test2', 'ddd', 14, '2025-12-12', '2025-12-14', 2, 2, 1, 0.00, 0.00, 50, '2025-12-11 12:08:23', '2025-12-15 17:01:06', 0, NULL);

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
(13, 8, 11, 1, 1, '2025-12-15 18:13:55');

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
(5, 6, 10, 67, 'ssssss', 'viewed', '2025-12-11 12:33:43');

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
(1, 1, 5, '2025-12-17', 'every_2_days', 1, '2025-12-07 07:58:27'),
(2, 2, 10, '2025-12-17', 'every_2_days', 1, '2025-12-07 11:20:33'),
(3, 3, 10, '2025-12-17', 'every_2_days', 1, '2025-12-07 11:47:05');

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

--
-- Dumping data for table `project_user_permissions`
--

INSERT INTO `project_user_permissions` (`id`, `project_id`, `user_id`, `permission_id`, `is_granted`, `created_at`) VALUES
(3, 6, 14, 94, 1, '2025-12-15 18:49:50'),
(4, 5, 7, 35, 0, '2025-12-15 19:34:37'),
(5, 5, 8, 35, 0, '2025-12-15 19:56:24'),
(6, 5, 8, 94, 1, '2025-12-15 19:56:29'),
(7, 5, 7, 94, 1, '2025-12-15 19:56:31'),
(8, 5, 12, 94, 1, '2025-12-15 19:56:32'),
(9, 5, 8, 39, 0, '2025-12-15 20:00:10'),
(10, 5, 7, 39, 0, '2025-12-15 20:00:20'),
(11, 5, 12, 39, 0, '2025-12-15 20:00:30');

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
(1, 'project', 5, 'test1', 'test1test1test1', 'test1test1test1test1test1', 4, 5, 4, '2025-12-11', '2025-12-11 09:17:23', '2025-12-16 06:46:46');

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
(6, 'supervisor', 'Supervisor', NULL),
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
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 8),
(1, 9),
(1, 11),
(1, 12),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(1, 58),
(1, 59),
(1, 60),
(1, 61),
(1, 62),
(1, 63),
(1, 64),
(1, 65),
(1, 66),
(1, 67),
(1, 68),
(1, 69),
(1, 70),
(1, 71),
(1, 72),
(1, 73),
(1, 75),
(1, 76),
(1, 77),
(1, 78),
(1, 80),
(1, 81),
(3, 4),
(3, 5),
(3, 6),
(3, 11),
(3, 12),
(4, 11),
(4, 12),
(4, 30),
(4, 31),
(4, 33),
(4, 34),
(4, 35),
(4, 38),
(4, 39),
(6, 6),
(7, 11),
(7, 12),
(8, 1),
(8, 11),
(8, 30),
(8, 33),
(8, 38),
(8, 58),
(9, 8),
(9, 58),
(9, 68),
(9, 75),
(10, 3),
(10, 4),
(10, 5),
(10, 8),
(10, 11),
(10, 12),
(10, 30),
(10, 38),
(10, 40),
(10, 42),
(10, 58),
(10, 68),
(10, 75),
(11, 4),
(11, 5),
(11, 6),
(11, 58),
(11, 59),
(11, 60),
(11, 61),
(11, 62),
(11, 63),
(11, 64),
(11, 65),
(11, 66),
(11, 68),
(11, 69),
(11, 70),
(11, 71),
(11, 72),
(11, 73),
(11, 75),
(11, 76),
(11, 77),
(11, 78),
(12, 11),
(12, 30),
(12, 34),
(12, 38),
(12, 40),
(12, 58),
(12, 68),
(12, 75),
(14, 4),
(14, 8),
(14, 11),
(14, 33),
(14, 34),
(14, 38),
(14, 58),
(14, 68),
(14, 75);

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
(9, 13, 'OBJ-23.2', 'تعانتنت', '2025-12-14 09:51:39', 0, NULL);

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
(1, 'amira.kahtan', 'a_bumadyan@yu.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amira Bumadyan', 'أميرة بومدين', 1, 1, '0558906017', 'Software Devlpoer', 'user_1_1764450223.jpg', 1, '2025-12-16 10:57:52', '2025-11-27 11:17:35', '2025-12-16 07:57:52', NULL, NULL, 0, NULL),
(3, 'kahtan', 'kahtan@interlink.edu', '$2y$10$rrlJ92b/e.nK/8roRjD3tOdHylS7kESE9xJmEklbeu5mSE4KC3hmS', 'Bu-Madyan Kahtan', NULL, 11, NULL, '0552468325', 'Executive Director', 'user_3_1765648478.jpg', 1, '2025-12-15 21:47:25', '2025-11-30 08:18:15', '2025-12-15 18:47:25', NULL, NULL, 0, NULL),
(4, 'ceo', 'ceo@yu.edu.sa', '$2y$10$Aa00vZjcUVOpNKHqK7IKruN..9Y2Vku3wWqBPe0s7ko8bfwqrwAPa', 'ceo', NULL, 10, NULL, '0558906018', 'Chief Executive Officer', NULL, 1, '2025-12-15 14:56:38', '2025-12-03 09:45:37', '2025-12-15 11:56:38', NULL, NULL, 0, NULL),
(5, 'DepartmentManager', 'DepartmentManager@yu.edu.sa', '$2y$10$9ycV5HvoE7eA7TsgCQqjXutii.l2BSbhP76w6x2uXsz/hu170QSYS', 'Department Manager', NULL, 12, 1, '0558906019', 'Department Manager', NULL, 1, '2025-12-16 10:56:32', '2025-12-03 09:46:25', '2025-12-16 07:56:32', NULL, NULL, 0, NULL),
(6, 'Employee', 'Employee@yu.edu.sa', '$2y$10$yZCtBfCW46FcOA4GHbcnUOSz3cGVywq9pERYYhCKIoi1ANZqEl.AW', 'Employee', NULL, 8, 1, '0558906011', 'Employee', NULL, 0, '2025-12-03 12:57:44', '2025-12-03 09:46:59', '2025-12-09 19:27:59', NULL, NULL, 1, '2025-12-06 22:45:32'),
(7, 'StrategyStaff', 'StrategyStaff@yu.edu.sa', '$2y$10$CivrgRB2kgrHy/ab1/eOremC2L0beeQlz.brA7VkWSaaWMIgLQLla', 'Strategy Staff', NULL, 8, 5, '0558906012', 'Strategy Staff', NULL, 1, '2025-12-16 10:19:24', '2025-12-03 09:47:38', '2025-12-16 07:19:24', NULL, NULL, 0, NULL),
(8, 'AhamedMohammed', 'AhamedMohammed@gmail.com', '$2y$10$YZU8jVfQ.bWQPQtqicXxbOO5DC.DOkkxrifZKnpkel/YAVtM18p8e', 'Ahamed Mohammed', NULL, 12, 1, '0558906078', 'Employee', NULL, 1, '2025-12-16 10:29:34', '2025-12-07 08:01:11', '2025-12-16 07:29:34', NULL, NULL, 0, NULL),
(10, 'test', 'test@gmail.com', '$2y$10$ZUbS.foqqQKtRyRu3QW2xeymEWcqDIE0iJIN5RlOiSwkTdxIjLmCa', 'Employee Test', NULL, 12, 2, '0558906125', 'Software Devlpoer', NULL, 1, '2025-12-16 10:46:39', '2025-12-07 11:18:59', '2025-12-16 07:46:39', NULL, NULL, 0, NULL),
(11, 'Ali', 'Ali@yu.edu.sa', '$2y$10$NGIK/G6xhFHvW/vPjYzB8eLeXcQYZ7HKOmUpY11N.IU4SerJYqUDa', 'Ali Ahmed', NULL, 14, 5, '0558906587', 'Finance Head', NULL, 1, '2025-12-15 14:56:11', '2025-12-07 11:52:16', '2025-12-15 11:56:11', NULL, NULL, 0, NULL),
(12, 'Mohammed', 'Mohammed@yu.edu.sa', '$2y$10$rZ7SrJ6hBvTpU/J5FJBDFu/nBoKTsYHpeVeDeBZdfWMTbLcvR4wHO', 'Mohammed Adam', NULL, 12, 1, '0578404042', 'Software Devlpoer', NULL, 1, '2025-12-15 12:39:05', '2025-12-09 06:21:02', '2025-12-15 09:39:05', NULL, NULL, 0, '2025-12-14 21:50:03'),
(13, 'Adam', 'Adam@yu.edu.sa', '$2y$10$0apeJojo5JEWhMYPVsWLDOFuu/7dAm1NDpSDqqe/tiwRhyJG9vE/W', 'Adam Ali', NULL, 7, 1, '0578404012', 'Software Devlpoer', NULL, 1, NULL, '2025-12-09 06:36:26', '2025-12-09 06:48:56', NULL, NULL, 1, '2025-12-09 09:48:56'),
(14, 'asmaa', 'asmaa@yu.edu.sa', '$2y$10$Yn/WJ/yTHxI5fy6S/Yi/A.J5XbMg8lORo38o4heBmScXirA6du4hK', 'Asmaa Ali', NULL, 6, 1, '0578404448', 'Employee', NULL, 1, '2025-12-16 10:19:44', '2025-12-09 06:43:33', '2025-12-16 07:19:44', NULL, NULL, 0, '2025-12-09 09:48:28'),
(15, 'khaled', 'khaled@gmail.com', '$2y$10$7NyBapRpECu8aSGMQzHL4uEz4JrMZZ2dW0/2rLaN0mlxzVQETnavy', 'Khaled Mohammed', NULL, 8, 1, '0578404478', 'Employee', NULL, 1, '2025-12-16 10:19:58', '2025-12-10 12:10:38', '2025-12-16 07:19:58', NULL, NULL, 0, NULL);

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
(4, 1),
(4, 2),
(12, 2);

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
(35, 8, 'New Task: jkkjkj', 'You have a new task.', NULL, 1, 0, 'task', 4, '2025-12-11 06:16:26'),
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
(63, 4, 'Approval Pending: Test265', 'Action required for stage: CEO Final Approval', '2025-12-13 00:00:00', 1, 0, 'project', 3, '2025-12-11 11:46:19'),
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
(79, 11, 'New Assignment: Test2555', 'You have been assigned to collaborate on this project.', NULL, 1, 0, 'project', 6, '2025-12-15 09:27:37'),
(80, 11, 'Welcome to Project: Test2555', 'You have been added to the team.', NULL, 1, 0, 'project', 6, '2025-12-15 09:28:21'),
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
(97, 11, 'Welcome to Project: IT-project2', 'You have been added to the team.', NULL, 1, 0, 'project', 8, '2025-12-15 18:13:55'),
(98, 8, 'Update KPI: Test2', 'It\'s time to update the reading for this KPI (weekly).', '2025-12-23 00:00:00', 1, 0, 'kpi', 1, '2025-12-16 05:20:35');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `approval_actions`
--
ALTER TABLE `approval_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `approval_entity_types`
--
ALTER TABLE `approval_entity_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `approval_instances`
--
ALTER TABLE `approval_instances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `approval_workflow_stages`
--
ALTER TABLE `approval_workflow_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `collaborations`
--
ALTER TABLE `collaborations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_update_reminders`
--
ALTER TABLE `project_update_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_user_permissions`
--
ALTER TABLE `project_user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `strategic_objectives`
--
ALTER TABLE `strategic_objectives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_todos`
--
ALTER TABLE `user_todos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `work_resources`
--
ALTER TABLE `work_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
