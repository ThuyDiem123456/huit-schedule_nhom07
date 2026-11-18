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

-- Seed sample campuses (not asserted to be an official current campus list).
INSERT INTO branches (id, branch_name, address) VALUES
(1, 'Cơ sở chính – Tân Phú', '140 Lê Trọng Tấn, TP. Hồ Chí Minh'),
(2, 'Cơ sở liên kết – Gò Vấp', '12 Nguyễn Văn Bảo, TP. Hồ Chí Minh (minh họa)'),
(3, 'Cơ sở thực hành – Tân Phú', '93 Tân Kỳ Tân Quý, TP. Hồ Chí Minh (minh họa)');

INSERT INTO teachers (id, full_name, email, phone, expertise) VALUES
(1, 'Nguyễn Minh Anh', 'minhanh@huit.edu.vn', '0901000001', 'Lập trình PHP, Phát triển Web'),
(2, 'Trần Hoàng Nam', 'hoangnam@huit.edu.vn', '0901000002', 'Cơ sở dữ liệu, MySQL'),
(3, 'Lê Thị Thu Hà', 'thuha@huit.edu.vn', '0901000003', 'Phát triển Web, Lập trình PHP'),
(4, 'Phạm Quốc Bảo', 'quocbao@huit.edu.vn', '0901000004', 'Mạng máy tính, An toàn thông tin'),
(5, 'Võ Thanh Tùng', 'thanhtung@huit.edu.vn', '0901000005', 'Phân tích hệ thống, Cơ sở dữ liệu'),
(6, 'Đặng Ngọc Lan', 'ngoclan@huit.edu.vn', '0901000006', 'Trí tuệ nhân tạo, Python'),
(7, 'Đỗ Tuấn Kiệt', 'tuankiet@huit.edu.vn', '0901000007', 'Toán rời rạc, Cấu trúc dữ liệu'),
(8, 'Mai Thị Kim Chi', 'kimchi@huit.edu.vn', '0901000008', 'Thiết kế đồ họa, UI/UX');

INSERT INTO schedules (id, teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods, period_start, period_end) VALUES
(1, 1, 1, 'Lập trình PHP', 'A201', 1, '1-3', 1, 3),
(2, 2, 1, 'Cơ sở dữ liệu', 'A202', 1, '4-6', 4, 6),
(3, 3, 2, 'Phát triển Web', 'LAB101', 2, '1-3', 1, 3),
(4, 4, 3, 'Mạng máy tính', 'LAB202', 3, '7-9', 7, 9),
(5, 5, 1, 'Phân tích hệ thống', 'A301', 4, '4-6', 4, 6),
(6, 6, 2, 'Trí tuệ nhân tạo', 'LAB102', 5, '1-3', 1, 3),
(7, 1, 1, 'Lập trình PHP', 'A201', 3, '4-6', 4, 6),
(8, 2, 2, 'MySQL', 'LAB101', 2, '7-9', 7, 9),
(9, 3, 2, 'Phát triển Web', 'LAB201', 4, '1-3', 1, 3),
(10, 7, 1, 'Toán rời rạc', 'A301', 5, '7-9', 7, 9),
(11, 8, 3, 'Thiết kế đồ họa', 'LAB301', 1, '10-12', 10, 12),
(12, 4, 3, 'An toàn thông tin', 'LAB202', 6, '1-3', 1, 3);

-- Login accounts are generated using password_hash() in database/seed.php.
-- Never insert plaintext passwords in the database.
