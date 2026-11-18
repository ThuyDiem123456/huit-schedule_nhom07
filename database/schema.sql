-- HUIT Schedule Hub · Database + demo records
-- Import using HeidiSQL or mysql -u root -p < database/huit_db.sql
-- Data below is for demonstration; all sample names/addresses are illustrative.
CREATE DATABASE IF NOT EXISTS huit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE huit_db;

CREATE TABLE IF NOT EXISTS branches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(160) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    expertise VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
    teacher_id INT UNSIGNED DEFAULT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedules (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    subject_name VARCHAR(190) NOT NULL,
    room VARCHAR(60) NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Monday, 7=Sunday (ISO)',
    shift_or_periods VARCHAR(30) NOT NULL COMMENT 'Example: 1-3',
    period_start TINYINT UNSIGNED NOT NULL,
    period_end TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_schedule_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    INDEX idx_teacher_day (teacher_id, day_of_week, period_start, period_end),
    INDEX idx_room_day (branch_id, room, day_of_week, period_start, period_end)
) ENGINE=InnoDB;

