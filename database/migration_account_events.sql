-- Migration: Account Events & Detailed Tracking
-- เก็บรายละเอียด event แยกตามบอทแต่ละตัว

-- =============================================
-- ACCOUNT EVENTS - เก็บ event ทุกประเภทแยกตามบอท
-- =============================================
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
);

-- =============================================
-- ACCOUNT FOLLOWERS - ประวัติการ follow/unfollow แยกตามบอท
-- =============================================
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
);

-- =============================================
-- ACCOUNT STATISTICS - สถิติรายวันแยกตามบอท
-- =============================================
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
);

-- =============================================
-- เพิ่ม column ใน analytics สำหรับ line_account_id
-- =============================================
ALTER TABLE analytics ADD COLUMN IF NOT EXISTS line_account_id INT DEFAULT NULL AFTER id;
ALTER TABLE analytics ADD INDEX IF NOT EXISTS idx_analytics_account (line_account_id);
