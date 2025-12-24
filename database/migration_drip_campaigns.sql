-- =============================================
-- DRIP CAMPAIGNS - ระบบส่งข้อความอัตโนมัติตามลำดับ
-- =============================================

-- Drip Campaigns - แคมเปญหลัก
CREATE TABLE IF NOT EXISTS drip_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อแคมเปญ',
    description TEXT COMMENT 'รายละเอียด',
    trigger_type ENUM('follow', 'purchase', 'inactivity', 'tag', 'manual', 'birthday') NOT NULL COMMENT 'เงื่อนไขเริ่มต้น',
    trigger_config JSON COMMENT 'ค่าเพิ่มเติมสำหรับ trigger',
    is_active TINYINT(1) DEFAULT 1,
    sent_count INT DEFAULT 0 COMMENT 'จำนวนที่ส่งแล้ว',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_drip_account (line_account_id),
    INDEX idx_drip_trigger (trigger_type),
    INDEX idx_drip_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Steps - ขั้นตอนในแคมเปญ
CREATE TABLE IF NOT EXISTS drip_campaign_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    step_order INT NOT NULL COMMENT 'ลำดับขั้นตอน',
    step_name VARCHAR(100) COMMENT 'ชื่อขั้นตอน',
    delay_minutes INT DEFAULT 0 COMMENT 'รอกี่นาทีก่อนส่ง',
    delay_type ENUM('minutes', 'hours', 'days') DEFAULT 'minutes',
    message_type ENUM('text', 'flex', 'image', 'template') DEFAULT 'text',
    message_content TEXT NOT NULL COMMENT 'เนื้อหาข้อความ',
    condition_rules JSON COMMENT 'เงื่อนไขเพิ่มเติม',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_step_campaign (campaign_id),
    INDEX idx_step_order (step_order),
    FOREIGN KEY (campaign_id) REFERENCES drip_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Progress - ติดตามความคืบหน้าของผู้ใช้
CREATE TABLE IF NOT EXISTS drip_campaign_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    current_step INT DEFAULT 0 COMMENT 'ขั้นตอนปัจจุบัน',
    status ENUM('active', 'completed', 'cancelled', 'paused') DEFAULT 'active',
    next_send_at TIMESTAMP NULL COMMENT 'เวลาส่งข้อความถัดไป',
    last_sent_at TIMESTAMP NULL COMMENT 'เวลาส่งล่าสุด',
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_campaign (campaign_id, user_id),
    INDEX idx_progress_status (status),
    INDEX idx_progress_next_send (next_send_at),
    INDEX idx_progress_user (user_id),
    FOREIGN KEY (campaign_id) REFERENCES drip_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Logs - ประวัติการส่ง
CREATE TABLE IF NOT EXISTS drip_campaign_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    step_id INT NOT NULL,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50),
    status ENUM('sent', 'failed', 'skipped') NOT NULL,
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_campaign (campaign_id),
    INDEX idx_log_user (user_id),
    INDEX idx_log_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER BEHAVIORS - พฤติกรรมผู้ใช้
-- =============================================

CREATE TABLE IF NOT EXISTS user_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL COMMENT 'click, view, purchase, cart_add, cart_abandon, search, share, message',
    behavior_category VARCHAR(100) COMMENT 'product, link, menu, campaign, page',
    behavior_data JSON COMMENT 'รายละเอียดเพิ่มเติม',
    source VARCHAR(50) COMMENT 'rich_menu, flex, broadcast, auto_reply, webhook',
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_behavior_user (user_id),
    INDEX idx_behavior_account (line_account_id),
    INDEX idx_behavior_type (behavior_type),
    INDEX idx_behavior_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DEFAULT DATA
-- =============================================

-- ตัวอย่าง Drip Campaign สำหรับ New Followers
INSERT IGNORE INTO drip_campaigns (name, description, trigger_type, trigger_config, is_active) VALUES
('Welcome Series', 'ส่งข้อความต้อนรับลูกค้าใหม่', 'follow', '{}', 1),
('Re-engagement', 'ดึงลูกค้าที่ไม่ active กลับมา', 'inactivity', '{"days": 14}', 0),
('Post-Purchase', 'ติดตามหลังซื้อสินค้า', 'purchase', '{}', 0);

-- ตัวอย่าง Steps สำหรับ Welcome Series
INSERT IGNORE INTO drip_campaign_steps (campaign_id, step_order, step_name, delay_minutes, message_type, message_content) VALUES
((SELECT id FROM drip_campaigns WHERE name = 'Welcome Series' LIMIT 1), 1, 'Welcome', 0, 'text', 'ยินดีต้อนรับสู่ร้านของเรา! 🎉\n\nพิมพ์ "shop" เพื่อดูสินค้า'),
((SELECT id FROM drip_campaigns WHERE name = 'Welcome Series' LIMIT 1), 2, 'Introduce Products', 1440, 'text', '📦 สินค้าแนะนำวันนี้!\n\nพิมพ์ "สินค้า" เพื่อดูรายการทั้งหมด'),
((SELECT id FROM drip_campaigns WHERE name = 'Welcome Series' LIMIT 1), 3, 'Special Offer', 4320, 'text', '🎁 พิเศษสำหรับคุณ!\n\nใช้โค้ด WELCOME10 รับส่วนลด 10%');

SELECT 'Drip Campaigns migration completed!' as status;
