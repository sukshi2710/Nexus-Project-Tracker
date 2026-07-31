CREATE DATABASE IF NOT EXISTS `nexus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nexus`;

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS `milestone_ledger`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `project_groups`;

-- 1. project_groups table
CREATE TABLE `project_groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_name` VARCHAR(100) NOT NULL,
  `faculty_id` INT NOT NULL,
  `project_title` VARCHAR(255) DEFAULT NULL,
  `project_abstract` TEXT DEFAULT NULL,
  `title_status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `current_phase` INT DEFAULT 0,
  `github_link` VARCHAR(255) DEFAULT NULL,
  `doc_link` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. users table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `register_number` VARCHAR(50) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `role` ENUM('Admin', 'Faculty', 'Student') NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `group_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `project_groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Foreign Key for Faculty Advisor on project_groups
ALTER TABLE `project_groups`
  ADD CONSTRAINT `fk_faculty_advisor`
  FOREIGN KEY (`faculty_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- 3. milestone_ledger table
CREATE TABLE `milestone_ledger` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `phase_number` INT NOT NULL,
  `justification_text` TEXT NOT NULL,
  `submission_status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `rejection_feedback` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `project_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Accounts (Default password: password123)
-- Admin Account
INSERT INTO `users` (`register_number`, `name`, `email`, `role`, `password`, `group_id`) 
VALUES ('ADM001', 'System Administrator', 'admin@nexus.edu', 'Admin', '$2y$10$4.4yR/4gM5/3jUeX3R9M2.871W5a42u9E9fE/K3b4gU3.jWv21m.6', NULL);

-- Faculty Accounts
INSERT INTO `users` (`register_number`, `name`, `email`, `role`, `password`, `group_id`) 
VALUES 
('FAC001', 'Dr. Alan Turing', 'alan@nexus.edu', 'Faculty', '$2y$10$4.4yR/4gM5/3jUeX3R9M2.871W5a42u9E9fE/K3b4gU3.jWv21m.6', NULL),
('FAC002', 'Prof. Grace Hopper', 'grace@nexus.edu', 'Faculty', '$2y$10$4.4yR/4gM5/3jUeX3R9M2.871W5a42u9E9fE/K3b4gU3.jWv21m.6', NULL);