-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 01:48 PM
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
-- Database: `course_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`user_id`, `permission_id`) VALUES
(66, 1),
(66, 2),
(66, 3),
(66, 4),
(66, 5),
(66, 6),
(66, 7),
(66, 8),
(66, 9),
(66, 10);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `target_role` varchar(50) NOT NULL DEFAULT 'all',
  `created_by` int(11) DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `date_enrolled` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','dropped','completed') DEFAULT 'enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `appointment_date` date DEFAULT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_subjects`
--

CREATE TABLE `enrollment_subjects` (
  `enrollment_subject_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `prelim` decimal(5,2) DEFAULT NULL,
  `midterm` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) GENERATED ALWAYS AS (round((coalesce(`prelim`,0) + coalesce(`midterm`,0) + coalesce(`finals`,0)) / 3,2)) STORED,
  `remarks` enum('Passed','Failed','Incomplete') DEFAULT 'Incomplete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`instructor_id`, `user_id`, `employee_no`, `first_name`, `last_name`, `profile_picture_path`, `middle_name`, `department_id`, `specialization`, `contact_no`, `email`) VALUES
(9, 33, NULL, 'John', 'Smith', NULL, NULL, 14, NULL, NULL, NULL),
(10, 34, NULL, 'Ada', 'Lovelace', NULL, NULL, 14, NULL, NULL, NULL),
(11, 35, NULL, 'Grace', 'Hopper', NULL, NULL, 14, NULL, NULL, NULL),
(12, 36, NULL, 'Alan', 'Turing', NULL, NULL, 14, NULL, NULL, NULL),
(13, 37, NULL, 'Charles', 'Babbage', NULL, NULL, 14, NULL, NULL, NULL),
(14, 38, NULL, 'Tim', 'Berners-Lee', NULL, NULL, 15, NULL, NULL, NULL),
(15, 39, NULL, 'Vint', 'Cerf', NULL, NULL, 15, NULL, NULL, NULL),
(16, 40, NULL, 'Radia', 'Perlman', NULL, NULL, 15, NULL, NULL, NULL),
(17, 41, NULL, 'Linus', 'Torvalds', NULL, NULL, 15, NULL, NULL, NULL),
(18, 42, NULL, 'Margaret', 'Hamilton', NULL, NULL, 15, NULL, NULL, NULL),
(19, 43, NULL, 'John', 'Smith', NULL, NULL, 13, NULL, NULL, NULL),
(20, 44, NULL, 'Ada', 'Lovelace', NULL, NULL, 13, NULL, NULL, NULL),
(21, 45, NULL, 'Grace', 'Hopper', NULL, NULL, 13, NULL, NULL, NULL),
(22, 46, NULL, 'Alan', 'Turing', NULL, NULL, 13, NULL, NULL, NULL),
(23, 47, NULL, 'Charles', 'Babbage', 'assets/images/profile_pictures/instructor_47_1764489861.png', NULL, 13, NULL, NULL, NULL),
(24, 48, NULL, 'Tim', 'Berners-Lee', NULL, NULL, 14, NULL, NULL, NULL),
(25, 49, NULL, 'Vint', 'Cerf', NULL, NULL, 14, NULL, NULL, NULL),
(26, 50, NULL, 'Radia', 'Perlman', NULL, NULL, 14, NULL, NULL, NULL),
(27, 51, NULL, 'Linus', 'Torvalds', NULL, NULL, 14, NULL, NULL, NULL),
(28, 52, NULL, 'Margaret', 'Hamilton', NULL, NULL, 14, NULL, NULL, NULL),
(29, 62, NULL, 'uzumaki', 'naruto', NULL, NULL, 14, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_sent` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('read','unread') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `description`, `category`) VALUES
(1, 'view_dashboard', 'View Admin Dashboard & Statistics', 'Dashboard & Core'),
(2, 'manage_announcements', 'Create, Edit, and Delete Announcements', 'Dashboard & Core'),
(3, 'manage_students_instructors', 'Manage Instructor & Student Accounts (Add, Edit, Deactivate)', 'User Management'),
(4, 'manage_courses', 'Manage Courses', 'Academic Management'),
(5, 'manage_subjects', 'Manage Subjects', 'Academic Management'),
(6, 'manage_rooms_sections', 'Manage Rooms & Sections', 'Academic Management'),
(7, 'manage_schedules', 'Create and Assign Class Schedules', 'Academic Management'),
(8, 'manage_enrollments', 'Approve or Reject Student Enrollments', 'Academic Management'),
(9, 'manage_instructors', 'View and Manage Instructor list', 'User Management'),
(10, 'manage_departments', 'Manage Academic Departments', 'Academic Management');

-- --------------------------------------------------------

--
-- Table structure for table `print_requests`
--

CREATE TABLE `print_requests` (
  `request_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `preferred_claim_date` date NOT NULL,
  `status` enum('pending','ready','claimed') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by_admin_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_key`, `is_allowed`) VALUES
(1, 'admin', 'view_admin_dashboard', 0),
(2, 'admin', 'manage_announcements', 1),
(3, 'admin', 'manage_instructors', 1),
(4, 'admin', 'manage_students', 1),
(5, 'admin', 'manage_academics', 1),
(6, 'admin', 'manage_schedules', 1),
(7, 'admin', 'manage_enrollments', 1),
(8, 'admin', 'view_instructor_dashboard', 0),
(9, 'admin', 'view_instructor_schedule', 0),
(10, 'admin', 'view_class_lists', 0),
(11, 'admin', 'manage_grades', 0),
(12, 'admin', 'view_announcements', 0),
(13, 'admin', 'edit_instructor_profile', 0),
(14, 'admin', 'view_student_dashboard', 0),
(15, 'admin', 'perform_enrollment', 0),
(16, 'admin', 'view_student_schedule', 0),
(17, 'admin', 'view_grades', 0),
(18, 'admin', 'edit_student_profile', 0),
(19, 'instructor', 'view_admin_dashboard', 0),
(20, 'instructor', 'manage_announcements', 0),
(21, 'instructor', 'manage_instructors', 0),
(22, 'instructor', 'manage_students', 0),
(23, 'instructor', 'manage_academics', 0),
(24, 'instructor', 'manage_schedules', 0),
(25, 'instructor', 'manage_enrollments', 0),
(26, 'instructor', 'view_instructor_dashboard', 1),
(27, 'instructor', 'view_instructor_schedule', 1),
(28, 'instructor', 'view_class_lists', 1),
(29, 'instructor', 'manage_grades', 1),
(30, 'instructor', 'view_announcements', 1),
(31, 'instructor', 'edit_instructor_profile', 1),
(32, 'instructor', 'view_student_dashboard', 0),
(33, 'instructor', 'perform_enrollment', 0),
(34, 'instructor', 'view_student_schedule', 0),
(35, 'instructor', 'view_grades', 0),
(36, 'instructor', 'edit_student_profile', 0),
(37, 'student', 'view_admin_dashboard', 0),
(38, 'student', 'manage_announcements', 0),
(39, 'student', 'manage_instructors', 0),
(40, 'student', 'manage_students', 0),
(41, 'student', 'manage_academics', 0),
(42, 'student', 'manage_schedules', 0),
(43, 'student', 'manage_enrollments', 0),
(44, 'student', 'view_instructor_dashboard', 0),
(45, 'student', 'view_instructor_schedule', 0),
(46, 'student', 'view_class_lists', 0),
(47, 'student', 'manage_grades', 0),
(48, 'student', 'view_announcements', 0),
(49, 'student', 'edit_instructor_profile', 0),
(50, 'student', 'view_student_dashboard', 1),
(51, 'student', 'perform_enrollment', 0),
(52, 'student', 'view_student_schedule', 1),
(53, 'student', 'view_grades', 1),
(54, 'student', 'edit_student_profile', 1),
(163, 'admin', 'manage_users', 0),
(165, 'admin', 'manage_enrollment', 0),
(167, 'admin', 'edit_grades', 0),
(168, 'admin', 'post_announcements', 0),
(169, 'admin', 'view_analytics', 0),
(170, 'instructor', 'manage_users', 0),
(172, 'instructor', 'manage_enrollment', 0),
(174, 'instructor', 'edit_grades', 0),
(175, 'instructor', 'post_announcements', 0),
(176, 'instructor', 'view_analytics', 0),
(177, 'student', 'manage_users', 0),
(179, 'student', 'manage_enrollment', 0),
(181, 'student', 'edit_grades', 0),
(182, 'student', 'post_announcements', 0),
(183, 'student', 'view_analytics', 0),
(396, 'admin', 'manage_role_permissions', 0),
(397, 'admin', 'manage_system_settings', 0),
(398, 'admin', 'manage_system_modules', 0),
(399, 'admin', 'manage_database', 0),
(795, 'student', 'submit_enrollment', 1),
(913, 'admin', 'manage_courses', 0),
(914, 'admin', 'manage_subjects', 0),
(915, 'admin', 'manage_rooms_sections', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL DEFAULT '1st'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT '1st Year',
  `course_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `prelim` decimal(5,2) DEFAULT NULL,
  `midterm` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `date_encoded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `father_full_name` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father_contact_number` varchar(50) DEFAULT NULL,
  `mother_full_name` varchar(255) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `mother_contact_number` varchar(50) DEFAULT NULL,
  `elementary_school` varchar(255) DEFAULT NULL,
  `elementary_graduated` varchar(4) DEFAULT NULL,
  `high_school` varchar(255) DEFAULT NULL,
  `high_school_graduated` varchar(4) DEFAULT NULL,
  `senior_high_school` varchar(255) DEFAULT NULL,
  `senior_high_graduated` varchar(4) DEFAULT NULL,
  `college_school` varchar(255) DEFAULT NULL,
  `college_graduated` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`profile_id`, `user_id`, `profile_picture_path`, `full_name`, `date_of_birth`, `gender`, `nationality`, `civil_status`, `religion`, `address`, `contact_number`, `father_full_name`, `father_occupation`, `father_contact_number`, `mother_full_name`, `mother_occupation`, `mother_contact_number`, `elementary_school`, `elementary_graduated`, `high_school`, `high_school_graduated`, `senior_high_school`, `senior_high_graduated`, `college_school`, `college_graduated`) VALUES
(14, 53, 'assets/images/profile_pictures/user_53_1764343796.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 59, 'assets/images/profile_pictures/user_59_1764487106.png', 'monkey luffy', '0000-00-00', 'male', 'filipino', 'single', 'Catholic', 'hidden leaf village', '09811999321', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(16, 69, 'assets/images/profile_pictures/user_69_1764831171.png', 'monkey luffy', '0000-00-00', 'male', 'filipino', 'single', 'Catholic', 'hidden leaf village', '09811999321', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `units` int(11) DEFAULT 3,
  `semester` enum('1st','2nd','Summer') DEFAULT '1st',
  `year_level` int(11) DEFAULT 1,
  `course_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`) VALUES
(1, 'site_name', 'SchedMaster'),
(2, 'school_year', '2024-2025'),
(3, 'semester', '1st Semester'),
(4, 'site_logo', 'logo.jpg'),
(5, 'module_enrollment', '1'),
(6, 'module_grades', '1'),
(7, 'module_registration', '1'),
(65, 'active_sy', '2024-2025'),
(66, 'active_semester', '2nd Semester');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','instructor','student','superadmin') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `status`, `date_created`) VALUES
(1, 'superadmin', '$2y$10$OJNbFXyipVlJw9Weu3Tq/.z2zekd5NpOSjS9DUJyximgt4HL6oy6u', 'superadmin@ascot.edu.ph', 'superadmin', 'active', '2025-11-07 13:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`user_id`, `permission_key`, `is_allowed`) VALUES
(32, 'manage_announcements', 1),
(32, 'manage_courses', 1),
(32, 'manage_enrollments', 1),
(32, 'manage_instructors', 1),
(32, 'manage_rooms_sections', 1),
(32, 'manage_schedules', 1),
(32, 'manage_students', 1),
(32, 'manage_subjects', 1),
(32, 'view_admin_dashboard', 1),
(63, 'manage_announcements', 1),
(63, 'manage_courses', 1),
(63, 'manage_enrollments', 1),
(63, 'manage_instructors', 1),
(63, 'manage_rooms_sections', 1),
(63, 'manage_schedules', 1),
(63, 'manage_students', 1),
(63, 'manage_subjects', 1),
(63, 'view_admin_dashboard', 1),
(66, 'manage_announcements', 1),
(66, 'manage_courses', 1),
(66, 'manage_enrollments', 1),
(66, 'manage_instructors', 1),
(66, 'manage_rooms_sections', 1),
(66, 'manage_schedules', 1),
(66, 'manage_students', 1),
(66, 'manage_subjects', 1),
(66, 'view_admin_dashboard', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `enrollment_subjects`
--
ALTER TABLE `enrollment_subjects`
  ADD PRIMARY KEY (`enrollment_subject_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `print_requests`
--
ALTER TABLE `print_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission` (`role`,`permission_key`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_no` (`student_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollment_subjects`
--
ALTER TABLE `enrollment_subjects`
  MODIFY `enrollment_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `print_requests`
--
ALTER TABLE `print_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1287;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=514;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `admin_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_user_permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
