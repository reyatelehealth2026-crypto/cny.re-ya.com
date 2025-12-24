-- Migration: Auto Tags System
-- ระบบติด Tag อัตโนมัติตามเงื่อนไข

-- เพิ่ม columns ใน user_tags สำหรับ auto rules
ALTER TABLE user_tags 
ADD COLUMN IF NOT EXISTS auto_assign_rules JSON DEFAULT NULL COMMENT 'กฎสำหรับติด tag อัตโนมัติ',
ADD COLUMN IF NOT EXISTS auto_remove_rules JSON DEFAULT NULL COMMENT 'กฎสำหรับลบ tag อัตโนมัติ',
ADD COLUMN IF NOT EXISTS tag_type ENUM('manual', 'auto', 'system') DEFAULT 'manual' COMMENT 'ประเภท tag';

-- ตาราง auto_tag_rules - กฎติด tag อัตโนมัติ
CREATE TABLE IF NOT EXISTS auto_tag_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    tag_id INT NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    trigger_type ENUM('follow', 'message', 'purchase', 'inactivity', 'birthday', 'order_count', 'total_spent', 'custom') NOT NULL,
    conditions JSON NOT NULL COMMENT 'เงื่อนไขในการติด tag',
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tag_id) REFERENCES user_tags(id) ON DELETE CASCADE,
    INDEX idx_trigger (trigger_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง auto_tag_logs - ประวัติการติด/ลบ tag อัตโนมัติ
CREATE TABLE IF NOT EXISTS auto_tag_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tag_id INT NOT NULL,
    rule_id INT DEFAULT NULL,
    action ENUM('assign', 'remove') NOT NULL,
    trigger_type VARCHAR(50),
    trigger_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_tag (tag_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่ม columns ใน users สำหรับ CRM
ALTER TABLE users
ADD COLUMN IF NOT EXISTS total_orders INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_spent DECIMAL(12,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_order_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS last_message_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS customer_score INT DEFAULT 0 COMMENT 'คะแนนลูกค้า 0-100';

-- สร้าง default tags
INSERT IGNORE INTO user_tags (name, color, description, tag_type, auto_assign_rules) VALUES
('New Customer', '#10B981', 'ลูกค้าใหม่ที่เพิ่งเพิ่มเพื่อน', 'system', '{"trigger": "follow"}'),
('VIP', '#F59E0B', 'ลูกค้า VIP (ซื้อ 5 ครั้งขึ้นไป)', 'auto', '{"trigger": "order_count", "min_orders": 5}'),
('Inactive', '#EF4444', 'ไม่มีกิจกรรม 30 วัน', 'auto', '{"trigger": "inactivity", "days": 30}'),
('Birthday This Month', '#EC4899', 'วันเกิดเดือนนี้', 'auto', '{"trigger": "birthday", "month": "current"}'),
('High Spender', '#8B5CF6', 'ยอดซื้อรวม 10,000+ บาท', 'auto', '{"trigger": "total_spent", "min_amount": 10000}'),
('Repeat Customer', '#3B82F6', 'ซื้อซ้ำ 2 ครั้งขึ้นไป', 'auto', '{"trigger": "order_count", "min_orders": 2}'),
('First Purchase', '#06B6D4', 'ซื้อครั้งแรก', 'auto', '{"trigger": "order_count", "min_orders": 1, "max_orders": 1}');

-- สร้าง default auto tag rules
INSERT IGNORE INTO auto_tag_rules (tag_id, rule_name, trigger_type, conditions) VALUES
((SELECT id FROM user_tags WHERE name = 'New Customer' LIMIT 1), 'ติด tag เมื่อ follow', 'follow', '{}'),
((SELECT id FROM user_tags WHERE name = 'VIP' LIMIT 1), 'ติด tag เมื่อซื้อ 5 ครั้ง', 'order_count', '{"min_orders": 5}'),
((SELECT id FROM user_tags WHERE name = 'Inactive' LIMIT 1), 'ติด tag เมื่อไม่มีกิจกรรม 30 วัน', 'inactivity', '{"days": 30}'),
((SELECT id FROM user_tags WHERE name = 'High Spender' LIMIT 1), 'ติด tag เมื่อยอดซื้อ 10,000+', 'total_spent', '{"min_amount": 10000}'),
((SELECT id FROM user_tags WHERE name = 'Repeat Customer' LIMIT 1), 'ติด tag เมื่อซื้อซ้ำ', 'order_count', '{"min_orders": 2}');
