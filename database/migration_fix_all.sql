-- =============================================
-- MIGRATION FIX ALL - แก้ไขปัญหาทั้งหมด
-- รันไฟล์นี้เพื่อสร้างตารางที่ขาดหายไป
-- =============================================

-- 1. เพิ่ม columns ในตาราง users (ถ้ายังไม่มี)
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
ADD COLUMN IF NOT EXISTS total_spent DECIMAL(12,2) DEFAULT 0 COMMENT 'ยอดซื้อรวม',
ADD COLUMN IF NOT EXISTS line_account_id INT DEFAULT NULL;

-- Index สำหรับค้นหา
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_phone (phone);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_birthday (birthday);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_line_account (line_account_id);

-- 2. USER TAGS - ระบบ Tag
CREATE TABLE IF NOT EXISTS user_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    description TEXT,
    tag_type ENUM('manual', 'auto', 'system') DEFAULT 'manual',
    auto_assign_rules JSON COMMENT 'เงื่อนไขการติด Tag อัตโนมัติ',
    auto_remove_rules JSON COMMENT 'เงื่อนไขการถอด Tag อัตโนมัติ',
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_tag (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. USER TAG ASSIGNMENTS - การติด Tag ให้ลูกค้า
CREATE TABLE IF NOT EXISTS user_tag_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tag_id INT NOT NULL,
    assigned_by VARCHAR(50) DEFAULT 'manual' COMMENT 'manual, auto, system, campaign',
    assigned_reason TEXT,
    score INT DEFAULT 0 COMMENT 'คะแนนความสนใจ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'Tag หมดอายุเมื่อไหร่',
    UNIQUE KEY unique_user_tag (user_id, tag_id),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ACCOUNT EVENTS - เก็บ event ทุกประเภทแยกตามบอท
CREATE TABLE IF NOT EXISTS account_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL COMMENT 'บอทที่รับ event',
    event_type VARCHAR(50) NOT NULL COMMENT 'ประเภท event: follow, unfollow, message, postback, etc.',
    line_user_id VARCHAR(50) NOT NULL COMMENT 'LINE User ID',
    user_id INT DEFAULT NULL COMMENT 'FK to users table',
    event_data JSON COMMENT 'ข้อมูล event ดิบจาก LINE',
    webhook_event_id VARCHAR(100) COMMENT 'Webhook Event ID จาก LINE',
    source_type VARCHAR(20) DEFAULT 'user' COMMENT 'user, group, room',
    source_id VARCHAR(50) COMMENT 'Group ID หรือ Room ID (ถ้ามี)',
    reply_token VARCHAR(255) COMMENT 'Reply Token',
    timestamp BIGINT COMMENT 'Timestamp จาก LINE (milliseconds)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_events_account (line_account_id),
    INDEX idx_account_events_user (line_user_id),
    INDEX idx_account_events_type (event_type),
    INDEX idx_account_events_created (created_at),
    INDEX idx_account_events_webhook (webhook_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ACCOUNT FOLLOWERS - ประวัติการ follow/unfollow แยกตามบอท
CREATE TABLE IF NOT EXISTS account_followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL COMMENT 'บอทที่ถูก follow',
    line_user_id VARCHAR(50) NOT NULL COMMENT 'LINE User ID',
    user_id INT DEFAULT NULL COMMENT 'FK to users table',
    display_name VARCHAR(255) COMMENT 'ชื่อผู้ใช้ตอน follow',
    picture_url TEXT COMMENT 'รูปโปรไฟล์ตอน follow',
    status_message TEXT COMMENT 'Status message ตอน follow',
    is_following TINYINT(1) DEFAULT 1 COMMENT '1=กำลัง follow, 0=unfollow แล้ว',
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'เวลาที่ follow',
    unfollowed_at TIMESTAMP NULL COMMENT 'เวลาที่ unfollow',
    follow_count INT DEFAULT 1 COMMENT 'จำนวนครั้งที่ follow (กรณี follow ซ้ำ)',
    last_interaction_at TIMESTAMP NULL COMMENT 'ครั้งสุดท้ายที่มี interaction',
    total_messages INT DEFAULT 0 COMMENT 'จำนวนข้อความทั้งหมด',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_account_follower (line_account_id, line_user_id),
    INDEX idx_followers_account (line_account_id),
    INDEX idx_followers_user (line_user_id),
    INDEX idx_followers_following (is_following),
    INDEX idx_followers_date (followed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ACCOUNT DAILY STATS - สถิติรายวันแยกตามบอท
CREATE TABLE IF NOT EXISTS account_daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    stat_date DATE NOT NULL,
    new_followers INT DEFAULT 0 COMMENT 'ผู้ติดตามใหม่',
    unfollowers INT DEFAULT 0 COMMENT 'ยกเลิกติดตาม',
    total_messages INT DEFAULT 0 COMMENT 'ข้อความทั้งหมด',
    incoming_messages INT DEFAULT 0 COMMENT 'ข้อความขาเข้า',
    outgoing_messages INT DEFAULT 0 COMMENT 'ข้อความขาออก',
    unique_users INT DEFAULT 0 COMMENT 'ผู้ใช้ที่ active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_account_date (line_account_id, stat_date),
    INDEX idx_stats_account (line_account_id),
    INDEX idx_stats_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. เพิ่ม is_read column ใน messages (ถ้ายังไม่มี)
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS line_account_id INT DEFAULT NULL;
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_msg_line_account (line_account_id);

-- 8. เพิ่ม line_account_id ใน orders (ถ้ายังไม่มี)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS line_account_id INT DEFAULT NULL;
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_line_account (line_account_id);

-- Default Tags
INSERT IGNORE INTO user_tags (name, color, description, tag_type) VALUES
('New Customer', '#10B981', 'ลูกค้าใหม่', 'system'),
('VIP', '#F59E0B', 'ลูกค้า VIP', 'manual'),
('Inactive', '#6B7280', 'ไม่มี activity', 'auto');

SELECT 'Migration completed successfully!' as status;
