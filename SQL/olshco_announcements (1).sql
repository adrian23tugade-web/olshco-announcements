-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 12:43 PM
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
-- Database: `olshco_announcements`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetActiveAnnouncements` ()   BEGIN
    SELECT a.*, u.username, u.first_name, u.last_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    WHERE a.status = 'active' 
    AND (a.expiration_date IS NULL OR a.expiration_date >= CURDATE())
    ORDER BY 
        CASE a.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'normal' THEN 3 
            WHEN 'low' THEN 4 
        END, 
        a.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDashboardStats` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM announcements) AS total_announcements,
        (SELECT COUNT(*) FROM announcements WHERE status = 'active' AND (expiration_date IS NULL OR expiration_date >= CURDATE())) AS active_announcements,
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM users WHERE status = 'active') AS active_users;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `IncrementAnnouncementViews` (IN `announcement_id` INT)   BEGIN
    UPDATE announcements SET views = views + 1 WHERE id = announcement_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'login', 'Admin logged in', '127.0.0.1', NULL, '2026-04-08 15:35:49'),
(2, NULL, 'create', 'Created welcome announcement', '127.0.0.1', NULL, '2026-04-08 15:35:49'),
(3, 2, 'login', 'Staff logged in', '127.0.0.1', NULL, '2026-04-08 15:35:49'),
(4, 3, 'register', 'New user registered', '127.0.0.1', NULL, '2026-04-08 15:35:49'),
(5, NULL, 'update', 'Updated announcement settings', '127.0.0.1', NULL, '2026-04-08 15:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT 'Announcement',
  `priority` enum('urgent','high','normal','low') DEFAULT 'normal',
  `expiration_date` date DEFAULT NULL,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `category`, `priority`, `expiration_date`, `status`, `views`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to OLSHCO Announcement System', 'Welcome to the new OLSHCO Announcement System! This platform will keep you updated with the latest news, events, and announcements from Our Lady of the Sacred Heart College of Guimba. Stay tuned for more updates!', 'Announcement', 'high', '2026-05-08', 'active', 150, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(2, 'College Foundation Day Celebration', 'We are pleased to announce the upcoming College Foundation Day celebration on March 15, 2024. The event will feature various activities including:\n Parade (8:00 AM)\n Cultural Presentations (10:00 AM)\n Sports Festival (1:00 PM)\n Fireworks Display (7:00 PM)\nAll students and staff are encouraged to participate and wear their school uniforms. Let us celebrate this special day together!', 'Event', 'urgent', '2024-03-16', 'active', 89, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(3, 'Registration for Second Semester', 'The registration for the second semester will start on November 15, 2024. Please prepare the following requirements:\n1. Completed enrollment form\n2. Previous semester grades\n3. Clearance from previous semester\n4. Payment for tuition fees\nFor inquiries, please contact the Registrar\'s Office at (044) 123-4567.', 'Academic', 'high', '2024-12-15', 'active', 234, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(4, 'Library Extended Hours During Exam Week', 'Good news to all students! The library will extend its operating hours during the exam week from 7:00 AM to 8:00 PM. This will give students more time to review and prepare for their exams. Please maintain silence and proper decorum while inside the library.', 'Announcement', 'normal', '2024-04-15', 'active', 67, 2, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(5, 'Scholarship Opportunities Available', 'The school is now accepting applications for the following scholarships:\n Academic Excellence Scholarship\n Athletic Scholarship\n Financial Assistance Program\nDeadline for application is March 30, 2024. Please submit your requirements to the Scholarship Office.', 'News', 'high', '2024-03-30', 'active', 112, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(6, 'Intramurals 2024', 'Get ready for the most anticipated sports event of the year! The Intramurals 2024 will be held on February 20-25, 2024. Sports included:\n Basketball\n Volleyball\n Badminton\n Chess\n Table Tennis\nRegister your teams at the Sports Office before February 10, 2024.', 'Sports', 'high', '2024-02-25', 'active', 78, 2, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(7, 'New Course Offerings for Next School Year', 'OLSHCO is excited to announce new course offerings for the next school year:\n BS Information Technology\n BS Hospitality Management\n BS Criminology\n AB Communication\nEnrollment for these courses will start in May 2024. For more details, please visit the Admissions Office.', 'Academic', 'normal', '2024-06-30', 'active', 145, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(8, 'Campus Safety Protocols', 'To ensure the safety of everyone on campus, please follow these protocols:\n Always wear your ID\n Observe social distancing\n Use hand sanitizers at entry points\n Report any suspicious activity to security\nYour cooperation is greatly appreciated.', 'Bulletin', 'urgent', '2024-12-31', 'active', 56, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(9, 'Graduation Ceremony 2024', 'The Graduation Ceremony for the Class of 2024 will be held on May 20, 2024 at 9:00 AM at the school gymnasium. Graduates are required to attend the rehearsal on May 18, 2024. Caps and gowns can be claimed at the Registrar\'s Office starting May 10, 2024.', 'Event', 'high', '2024-05-20', 'active', 203, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(10, 'Midterm Examination Schedule', 'The midterm examinations will be held from March 10-15, 2024. Please check the posted schedule at your department bulletin board. Bring your school ID and examination permit. No ID, no exam policy will be strictly implemented.', 'Academic', 'high', '2024-03-15', 'active', 178, NULL, NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49');

--
-- Triggers `announcements`
--
DELIMITER $$
CREATE TRIGGER `log_announcement_insert` AFTER INSERT ON `announcements` FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, action, description) 
    VALUES (NEW.created_by, 'create', CONCAT('Created announcement: ', NEW.title));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_announcements_timestamp` BEFORE UPDATE ON `announcements` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `announcement_summary`
-- (See below for the actual view)
--
CREATE TABLE `announcement_summary` (
`id` int(11)
,`title` varchar(255)
,`category` varchar(50)
,`priority` enum('urgent','high','normal','low')
,`status` enum('active','inactive','draft')
,`views` int(11)
,`created_at` timestamp
,`author` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Announcement', 'General announcements from the administration', 'fas fa-bullhorn', 1, 'active', '2026-04-08 15:35:49'),
(2, 'Event', 'Upcoming events and activities', 'fas fa-calendar-alt', 2, 'active', '2026-04-08 15:35:49'),
(3, 'Bulletin', 'Important bulletins and notices', 'fas fa-chalkboard', 3, 'active', '2026-04-08 15:35:49'),
(4, 'News', 'Latest news and updates', 'fas fa-newspaper', 4, 'active', '2026-04-08 15:35:49'),
(5, 'Academic', 'Academic related announcements', 'fas fa-graduation-cap', 5, 'active', '2026-04-08 15:35:49'),
(6, 'Sports', 'Sports events and activities', 'fas fa-futbol', 6, 'active', '2026-04-08 15:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('active','hidden') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `announcement_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'announcement',
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `user_type` enum('admin','staff','user') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `user_type`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 'staff', 'staff@olshco.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School', 'Staff', 'staff', 'active', NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(3, 'john.doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'user', 'active', NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(4, 'jane.smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'user', 'active', NULL, '2026-04-08 15:35:49', '2026-04-08 15:35:49'),
(5, 'lester', 'lestermarcacinense@gmail.com', '$2y$10$tN6/bexCx2iId7n85c4UWe1Fc45cdhx1irxPExkW6/SGOCPayrAm.', 'Lester', 'Cinense', 'user', 'active', '2026-04-09 18:18:26', '2026-04-09 03:15:47', '2026-04-09 10:18:26'),
(10, 'admin', 'admin@olshco.edu.ph', '$2y$10$51o9gttQvDulX3Bt2qloCe5EJYHeIKtT5.I2xNrQZHB/sg6fSTD5C', 'System', 'Administrator', 'admin', 'active', '2026-04-09 16:55:28', '2026-04-09 08:26:10', '2026-04-09 08:55:28');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_activity_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_activity_summary` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`user_type` enum('admin','staff','user')
,`status` enum('active','inactive')
,`last_login` datetime
,`created_at` timestamp
,`total_announcements` bigint(21)
,`total_activities` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `announcement_summary`
--
DROP TABLE IF EXISTS `announcement_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `announcement_summary`  AS SELECT `a`.`id` AS `id`, `a`.`title` AS `title`, `a`.`category` AS `category`, `a`.`priority` AS `priority`, `a`.`status` AS `status`, `a`.`views` AS `views`, `a`.`created_at` AS `created_at`, `u`.`username` AS `author`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name` FROM (`announcements` `a` left join `users` `u` on(`a`.`created_by` = `u`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `user_activity_summary`
--
DROP TABLE IF EXISTS `user_activity_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_activity_summary`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`user_type` AS `user_type`, `u`.`status` AS `status`, `u`.`last_login` AS `last_login`, `u`.`created_at` AS `created_at`, count(`a`.`id`) AS `total_announcements`, count(`al`.`id`) AS `total_activities` FROM ((`users` `u` left join `announcements` `a` on(`u`.`id` = `a`.`created_by`)) left join `activity_logs` `al` on(`u`.`id` = `al`.`user_id`)) GROUP BY `u`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_expiration` (`expiration_date`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_announcements_date_status` (`status`,`expiration_date`,`created_at`);
ALTER TABLE `announcements` ADD FULLTEXT KEY `idx_search` (`title`,`content`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_users_login` (`last_login`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
