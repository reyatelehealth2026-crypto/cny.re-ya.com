-- Migration: Broadcast Tracking & Auto Tag
-- ระบบติดตาม Broadcast และติด Tag อัตโนมัติเมื่อกดสินค้า

-- ตาราง broadcast_campaigns - เก็บข้อมูล broadcast
CREATE TABLE IF NOT EXISTS broadcast_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    message_type ENUM('text', 'flex', 'image', 'product_carousel') DEFAULT 'text',
    content LONGTEXT,
    auto_tag_enabled TINYINT(1) DEFAULT 0 COMMENT 'เปิดใช้ auto tag เมื่อกด',
    tag_prefix VARCHAR(50) DEFAULT NULL COMMENT 'prefix สำหรับ tag เช่น "สนใจ_"',
    sent_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง broadcast_items - สินค้าใน broadcast
CREATE TABLE IF NOT EXISTS broadcast_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_image VARCHAR(500),
    item_price DECIMAL(10,2) DEFAULT 0,
    postback_data VARCHAR(255) NOT NULL COMMENT 'data สำหรับ postback',
    tag_id INT DEFAULT NULL COMMENT 'tag ที่จะติดเมื่อกด',
    click_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (broadcast_id) REFERENCES broadcast_campaigns(id) ON DELETE CASCADE,
    INDEX idx_broadcast (broadcast_id),
    INDEX idx_postback (postback_data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง broadcast_clicks - เก็บ log การกด
CREATE TABLE IF NOT EXISTS broadcast_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT NOT NULL,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50),
    tag_assigned TINYINT(1) DEFAULT 0,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (broadcast_id) REFERENCES broadcast_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES broadcast_items(id) ON DELETE CASCADE,
    INDEX idx_broadcast (broadcast_id),
    INDEX idx_user (user_id),
    INDEX idx_clicked (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่ม column ใน user_tags สำหรับ broadcast
ALTER TABLE user_tags 
ADD COLUMN IF NOT EXISTS source_type ENUM('manual', 'auto', 'broadcast', 'system') DEFAULT 'manual',
ADD COLUMN IF NOT EXISTS source_id INT DEFAULT NULL COMMENT 'ID ของ broadcast หรือ rule';
