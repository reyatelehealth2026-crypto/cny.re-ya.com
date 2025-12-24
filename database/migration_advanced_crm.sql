-- =============================================
-- ADVANCED CRM SYSTEM - Data Collection, Segmentation
-- =============================================

-- 1. USER BEHAVIORS - เก็บพฤติกรรมลูกค้าละเอียด
CREATE TABLE IF NOT EXISTS user_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    user_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL COMMENT 'click, view, purchase, cart_add, cart_abandon, search, share',
    behavior_category VARCHAR(100) COMMENT 'product, link, menu, campaign',
    behavior_data JSON COMMENT 'รายละเอียดเพิ่มเติม เช่น product_id, link_url, amount',
    source VARCHAR(50) COMMENT 'rich_menu, flex, broadcast, auto_reply',
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_behavior (user_id, behavior_type),
    INDEX idx_account_behavior (line_account_id, behavior_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. USER TAGS - ระบบ Tag ขั้นสูง
CREATE TABLE IF NOT EXISTS user_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    description TEXT,
    tag_type ENUM('manual', 'auto', 'system') DEFAULT 'manual',
    auto_assign_rules JSON COMMENT 'เงื่อนไขการติด Tag อัตโนมัติ',
    auto_remove_rules JSON COMMENT 'เงื่อนไขการถอด Tag อัตโนมัติ',
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_tag (line_account_id),
    UNIQUE KEY unique_tag_name (line_account_id, name)
);

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
    INDEX idx_tag (tag_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES user_tags(id) ON DELETE CASCADE
);

-- 4. LINK TRACKING - ติดตามการคลิกลิงก์
CREATE TABLE IF NOT EXISTS tracked_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    short_code VARCHAR(20) UNIQUE NOT NULL,
    original_url TEXT NOT NULL,
    title VARCHAR(255),
    campaign_id INT NULL,
    auto_tag_id INT NULL COMMENT 'ติด Tag อัตโนมัติเมื่อคลิก',
    click_count INT DEFAULT 0,
    unique_clicks INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_short_code (short_code),
    INDEX idx_account (line_account_id)
);

-- 5. LINK CLICKS - บันทึกการคลิกลิงก์
CREATE TABLE IF NOT EXISTS link_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    user_id INT,
    line_user_id VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer TEXT,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_link (link_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (link_id) REFERENCES tracked_links(id) ON DELETE CASCADE
);

-- 6. CUSTOMER SEGMENTS - กลุ่มลูกค้าอัจฉริยะ
CREATE TABLE IF NOT EXISTS customer_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    segment_type ENUM('static', 'dynamic') DEFAULT 'dynamic',
    conditions JSON NOT NULL COMMENT 'เงื่อนไขการแบ่งกลุ่ม',
    user_count INT DEFAULT 0,
    last_calculated_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id)
);

-- 7. SEGMENT MEMBERS - สมาชิกในกลุ่ม
CREATE TABLE IF NOT EXISTS segment_members (
    segment_id INT NOT NULL,
    user_id INT NOT NULL,
    score DECIMAL(10,2) DEFAULT 0 COMMENT 'คะแนนความเหมาะสม',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (segment_id, user_id),
    FOREIGN KEY (segment_id) REFERENCES customer_segments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. USER PROFILES EXTENDED - ข้อมูลลูกค้าเพิ่มเติม
CREATE TABLE IF NOT EXISTS user_profiles_extended (
    user_id INT PRIMARY KEY,
    birthday DATE NULL,
    gender ENUM('male', 'female', 'other', 'unknown') DEFAULT 'unknown',
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    province VARCHAR(100),
    postal_code VARCHAR(10),
    customer_type ENUM('new', 'returning', 'vip', 'inactive') DEFAULT 'new',
    lifetime_value DECIMAL(12,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0,
    last_purchase_at TIMESTAMP NULL,
    first_purchase_at TIMESTAMP NULL,
    preferred_categories JSON,
    interests JSON,
    custom_fields JSON,
    engagement_score INT DEFAULT 0 COMMENT '0-100',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 9. BIRTHDAY CAMPAIGNS - แคมเปญวันเกิด
CREATE TABLE IF NOT EXISTS birthday_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    name VARCHAR(255) NOT NULL,
    days_before INT DEFAULT 0 COMMENT 'ส่งก่อนวันเกิดกี่วัน',
    send_time TIME DEFAULT '09:00:00',
    message_type ENUM('text', 'flex') DEFAULT 'flex',
    message_content TEXT NOT NULL,
    coupon_code VARCHAR(50),
    discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
    discount_value DECIMAL(10,2),
    is_active TINYINT(1) DEFAULT 1,
    sent_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id)
);

-- =============================================
-- DEFAULT DATA
-- =============================================

-- Default System Tags
INSERT IGNORE INTO user_tags (name, color, description, tag_type, auto_assign_rules) VALUES
('New Customer', '#10B981', 'ลูกค้าใหม่ที่เพิ่งเพิ่มเพื่อน', 'system', '{"trigger": "follow"}'),
('VIP', '#F59E0B', 'ลูกค้า VIP ซื้อ 5 ครั้งขึ้นไป', 'auto', '{"condition": "purchase_count >= 5"}'),
('Inactive', '#6B7280', 'ไม่มี activity 30 วัน', 'auto', '{"condition": "inactive_days >= 30"}'),
('High Spender', '#8B5CF6', 'ยอดซื้อรวม 10,000+ บาท', 'auto', '{"condition": "lifetime_value >= 10000"}'),
('Engaged', '#3B82F6', 'มี engagement สูง', 'auto', '{"condition": "engagement_score >= 70"}'),
('Birthday This Month', '#EC4899', 'วันเกิดเดือนนี้', 'system', '{"trigger": "birthday_month"}');

-- Default Segments
INSERT IGNORE INTO customer_segments (name, description, segment_type, conditions) VALUES
('Active Customers', 'ลูกค้าที่มี activity ใน 7 วันที่ผ่านมา', 'dynamic', '{"last_activity_days": {"<=": 7}}'),
('High Value Customers', 'ลูกค้าที่มียอดซื้อรวม 5,000+ บาท', 'dynamic', '{"lifetime_value": {">=": 5000}}'),
('At Risk', 'ลูกค้าที่เสี่ยงจะหายไป (ไม่มี activity 14-30 วัน)', 'dynamic', '{"last_activity_days": {">=": 14, "<=": 30}}'),
('New This Week', 'ลูกค้าใหม่ในสัปดาห์นี้', 'dynamic', '{"created_days": {"<=": 7}}'),
('Repeat Buyers', 'ลูกค้าที่ซื้อซ้ำ 2 ครั้งขึ้นไป', 'dynamic', '{"purchase_count": {">=": 2}}');

