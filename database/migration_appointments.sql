-- Migration: Appointments System
-- สร้างตารางสำหรับระบบนัดหมาย

-- ตาราง pharmacists (เภสัชกร)
CREATE TABLE IF NOT EXISTS pharmacists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NULL,
    name VARCHAR(255) NOT NULL,
    title VARCHAR(50) DEFAULT '',
    specialty VARCHAR(255) DEFAULT 'เภสัชกร',
    sub_specialty VARCHAR(255) NULL,
    hospital VARCHAR(255) NULL,
    license_no VARCHAR(100) NULL,
    bio TEXT NULL,
    consulting_areas TEXT NULL,
    work_experience TEXT NULL,
    image_url VARCHAR(500) NULL,
    rating DECIMAL(2,1) DEFAULT 5.0,
    review_count INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    consultation_duration INT DEFAULT 15,
    is_available TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง pharmacist_schedules (ตารางเวลาเภสัชกร)
CREATE TABLE IF NOT EXISTS pharmacist_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacist_id) REFERENCES pharmacists(id) ON DELETE CASCADE,
    INDEX idx_pharmacist_day (pharmacist_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง pharmacist_holidays (วันหยุดเภสัชกร)
CREATE TABLE IF NOT EXISTS pharmacist_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacist_id INT NOT NULL,
    holiday_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacist_id) REFERENCES pharmacists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_holiday (pharmacist_id, holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง appointments (นัดหมาย)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NULL,
    appointment_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    pharmacist_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    end_time TIME NULL,
    duration INT DEFAULT 15,
    type ENUM('scheduled', 'walk_in', 'emergency') DEFAULT 'scheduled',
    symptoms TEXT NULL,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    rating TINYINT NULL,
    review TEXT NULL,
    cancelled_by VARCHAR(50) NULL,
    cancelled_reason TEXT NULL,
    reminder_10min_sent TINYINT(1) DEFAULT 0,
    reminder_now_sent TINYINT(1) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pharmacist_id) REFERENCES pharmacists(id) ON DELETE CASCADE,
    INDEX idx_line_account (line_account_id),
    INDEX idx_user (user_id),
    INDEX idx_pharmacist (pharmacist_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_reminder (reminder_10min_sent, reminder_now_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง consultation_logs (บันทึกการปรึกษา)
CREATE TABLE IF NOT EXISTS consultation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    log_type ENUM('start', 'end', 'note', 'prescription') DEFAULT 'note',
    content TEXT NULL,
    created_by VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns to existing tables
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS reminder_10min_sent TINYINT(1) DEFAULT 0;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS reminder_now_sent TINYINT(1) DEFAULT 0;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancelled_reason TEXT NULL;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS line_account_id INT NULL;

-- Sample pharmacist data
INSERT IGNORE INTO pharmacists (id, name, title, specialty, consultation_fee, consultation_duration, is_active) VALUES
(1, 'ภก.สมชาย ใจดี', 'ภก.', 'เภสัชกรทั่วไป', 0, 15, 1),
(2, 'ภญ.สมหญิง รักษ์สุขภาพ', 'ภญ.', 'เภสัชกรคลินิก', 100, 20, 1);

-- Sample schedules (Mon-Fri 9:00-17:00)
INSERT IGNORE INTO pharmacist_schedules (pharmacist_id, day_of_week, start_time, end_time) VALUES
(1, 1, '09:00:00', '17:00:00'),
(1, 2, '09:00:00', '17:00:00'),
(1, 3, '09:00:00', '17:00:00'),
(1, 4, '09:00:00', '17:00:00'),
(1, 5, '09:00:00', '17:00:00'),
(2, 1, '10:00:00', '18:00:00'),
(2, 2, '10:00:00', '18:00:00'),
(2, 3, '10:00:00', '18:00:00'),
(2, 4, '10:00:00', '18:00:00'),
(2, 5, '10:00:00', '18:00:00');
