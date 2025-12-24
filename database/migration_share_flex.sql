-- Migration: Add share flex feature to auto_replies
-- เพิ่มฟีเจอร์แชร์ Flex Message ให้เพื่อน

-- Add columns to auto_replies (ถ้ายังไม่มี)
ALTER TABLE auto_replies ADD COLUMN IF NOT EXISTS quick_reply TEXT DEFAULT NULL;
ALTER TABLE auto_replies ADD COLUMN IF NOT EXISTS enable_share TINYINT(1) DEFAULT 0;
ALTER TABLE auto_replies ADD COLUMN IF NOT EXISTS share_button_label VARCHAR(50) DEFAULT '📤 แชร์ให้เพื่อน';

-- Create shared_flex_messages table
CREATE TABLE IF NOT EXISTS shared_flex_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_code VARCHAR(32) UNIQUE NOT NULL,
    flex_content LONGTEXT NOT NULL,
    alt_text VARCHAR(255) DEFAULT 'Shared Message',
    created_by INT DEFAULT NULL,
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_share_code (share_code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: ต้องตั้งค่า LIFF_SHARE_ID ใน config.php ด้วย
-- define('LIFF_SHARE_ID', 'your-liff-id-here');
