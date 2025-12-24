-- Dynamic Rich Menu Migration
-- สร้างตารางสำหรับ Dynamic Rich Menu System

-- 1. Rich Menu Rules - กฎการกำหนด Rich Menu ตามเงื่อนไข
CREATE TABLE IF NOT EXISTS rich_menu_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'ชื่อกฎ',
    description TEXT COMMENT 'คำอธิบาย',
    rich_menu_id INT NOT NULL COMMENT 'Rich Menu ที่จะใช้',
    priority INT DEFAULT 0 COMMENT 'ลำดับความสำคัญ (สูง = ใช้ก่อน)',
    conditions JSON NOT NULL COMMENT 'เงื่อนไขในรูปแบบ JSON',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_priority (priority DESC),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Rich Menu Assignments - บันทึกการกำหนด Rich Menu ให้ผู้ใช้
CREATE TABLE IF NOT EXISTS user_rich_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50) NOT NULL,
    rich_menu_id INT NOT NULL,
    line_rich_menu_id VARCHAR(100) NOT NULL,
    rule_id INT DEFAULT NULL COMMENT 'กฎที่ใช้กำหนด (NULL = manual)',
    assigned_reason VARCHAR(255) COMMENT 'เหตุผลที่กำหนด',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (line_account_id, user_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_rich_menu (rich_menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Rich Menu Aliases - สำหรับ switch Rich Menu ง่ายๆ
CREATE TABLE IF NOT EXISTS rich_menu_aliases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    alias_id VARCHAR(100) NOT NULL COMMENT 'LINE Rich Menu Alias ID',
    alias_name VARCHAR(50) NOT NULL COMMENT 'ชื่อ Alias (เช่น member, guest)',
    rich_menu_id INT NOT NULL,
    line_rich_menu_id VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_alias (line_account_id, alias_name),
    INDEX idx_alias_id (alias_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Rich Menu Switch Log - บันทึกการเปลี่ยน Rich Menu
CREATE TABLE IF NOT EXISTS rich_menu_switch_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50) NOT NULL,
    from_rich_menu_id INT DEFAULT NULL,
    to_rich_menu_id INT NOT NULL,
    trigger_type ENUM('rule', 'manual', 'event', 'api') DEFAULT 'rule',
    trigger_detail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. เพิ่ม columns ใน rich_menus ถ้ายังไม่มี
ALTER TABLE rich_menus 
    ADD COLUMN IF NOT EXISTS menu_type ENUM('default', 'member', 'guest', 'vip', 'custom') DEFAULT 'custom' AFTER name,
    ADD COLUMN IF NOT EXISTS target_audience VARCHAR(50) DEFAULT NULL COMMENT 'กลุ่มเป้าหมาย',
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER is_default;

-- 6. Sample Rules (ตัวอย่างกฎ)
-- INSERT INTO rich_menu_rules (line_account_id, name, description, rich_menu_id, priority, conditions) VALUES
-- (1, 'VIP Members', 'Rich Menu สำหรับสมาชิก VIP', 1, 100, '{"tags": ["VIP"], "operator": "any"}'),
-- (1, 'Registered Members', 'Rich Menu สำหรับสมาชิกที่ลงทะเบียนแล้ว', 2, 50, '{"is_registered": true}'),
-- (1, 'High Points', 'Rich Menu สำหรับผู้มีแต้มสูง', 3, 80, '{"points_min": 1000}'),
-- (1, 'New Users', 'Rich Menu สำหรับผู้ใช้ใหม่', 4, 30, '{"days_since_follow": {"max": 7}}');
