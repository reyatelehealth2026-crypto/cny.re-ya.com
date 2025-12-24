-- =============================================
-- USER DETAILS - ข้อมูลลูกค้าเพิ่มเติม
-- =============================================

-- เพิ่มคอลัมน์ในตาราง users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS real_name VARCHAR(255) NULL COMMENT 'ชื่อจริง',
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL COMMENT 'เบอร์โทร',
ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL COMMENT 'อีเมล',
ADD COLUMN IF NOT EXISTS birthday DATE NULL COMMENT 'วันเกิด',
ADD COLUMN IF NOT EXISTS address TEXT NULL COMMENT 'ที่อยู่',
ADD COLUMN IF NOT EXISTS province VARCHAR(100) NULL COMMENT 'จังหวัด',
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(10) NULL COMMENT 'รหัสไปรษณีย์',
ADD COLUMN IF NOT EXISTS note TEXT NULL COMMENT 'หมายเหตุ',
ADD COLUMN IF NOT EXISTS total_orders INT DEFAULT 0 COMMENT 'จำนวนออเดอร์ทั้งหมด',
ADD COLUMN IF NOT EXISTS total_spent DECIMAL(12,2) DEFAULT 0 COMMENT 'ยอดซื้อรวม';

-- Index สำหรับค้นหา
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_phone (phone);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_birthday (birthday);


-- =============================================
-- USER NOTES - บันทึกเกี่ยวกับลูกค้า
-- =============================================
CREATE TABLE IF NOT EXISTS user_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'FK to users table',
    line_account_id INT DEFAULT NULL,
    note_type VARCHAR(50) DEFAULT 'general' COMMENT 'general, order, complaint, feedback',
    content TEXT NOT NULL COMMENT 'เนื้อหาบันทึก',
    created_by VARCHAR(100) COMMENT 'ผู้สร้างบันทึก',
    is_pinned TINYINT(1) DEFAULT 0 COMMENT 'ปักหมุด',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_notes_user (user_id),
    INDEX idx_user_notes_account (line_account_id),
    INDEX idx_user_notes_type (note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER CUSTOM FIELDS - ฟิลด์กำหนดเองสำหรับลูกค้า
-- =============================================
CREATE TABLE IF NOT EXISTS user_custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    field_name VARCHAR(100) NOT NULL COMMENT 'ชื่อฟิลด์',
    field_key VARCHAR(50) NOT NULL COMMENT 'key สำหรับใช้ในโค้ด',
    field_type ENUM('text', 'number', 'date', 'select', 'checkbox', 'textarea') DEFAULT 'text',
    field_options JSON COMMENT 'ตัวเลือก (สำหรับ select)',
    is_required TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_custom_fields_account (line_account_id),
    UNIQUE KEY unique_field_key (line_account_id, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER CUSTOM FIELD VALUES - ค่าของฟิลด์กำหนดเอง
-- =============================================
CREATE TABLE IF NOT EXISTS user_custom_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_field (user_id, field_id),
    INDEX idx_field_values_user (user_id),
    INDEX idx_field_values_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
