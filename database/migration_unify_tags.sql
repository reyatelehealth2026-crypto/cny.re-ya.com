-- =====================================================
-- Migration: Unify Tags System
-- Version: 1.0
-- Date: 2025-12-16
-- Description: รวมระบบ Tags ให้ใช้ user_tags และ user_tag_assignments เป็นหลัก
-- =====================================================

-- 1. สร้างตาราง user_tags (สำหรับเก็บ tag definitions)
CREATE TABLE IF NOT EXISTS `user_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL COMMENT 'LINE Account ID (NULL = global tag)',
    `name` VARCHAR(100) NOT NULL COMMENT 'ชื่อ Tag',
    `color` VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'สี Hex Code',
    `description` TEXT COMMENT 'คำอธิบาย Tag',
    `auto_assign_rules` JSON DEFAULT NULL COMMENT 'กฎการ assign อัตโนมัติ',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. สร้างตาราง user_tag_assignments (สำหรับ assign tags ให้ users)
CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'User ID จากตาราง users',
    `tag_id` INT NOT NULL COMMENT 'Tag ID จากตาราง user_tags',
    `assigned_by` VARCHAR(50) DEFAULT 'manual' COMMENT 'วิธีการ assign (manual, auto, webhook, broadcast)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
    INDEX idx_user_id (user_id),
    INDEX idx_tag_id (tag_id),
    INDEX idx_assigned_by (assigned_by),
    CONSTRAINT fk_uta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_uta_tag FOREIGN KEY (tag_id) REFERENCES user_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. สร้าง Default Tags (ถ้ายังไม่มี)
INSERT IGNORE INTO `user_tags` (`name`, `color`, `description`) VALUES
('ลูกค้าใหม่', '#10B981', 'ลูกค้าที่เพิ่งเพิ่มเพื่อน'),
('VIP', '#EF4444', 'ลูกค้า VIP'),
('รอชำระเงิน', '#F59E0B', 'รอการชำระเงิน'),
('ชำระแล้ว', '#3B82F6', 'ชำระเงินแล้ว'),
('ส่งแล้ว', '#8B5CF6', 'จัดส่งสินค้าแล้ว'),
('สนใจสินค้า', '#EC4899', 'สนใจสินค้า');

-- 4. Migrate ข้อมูลจากตาราง tags เก่า (ถ้ามี)
-- หมายเหตุ: ต้องรัน PHP script เพื่อแปลง color name เป็น hex
-- INSERT IGNORE INTO user_tags (name, color, created_at)
-- SELECT name, 
--        CASE color 
--            WHEN 'gray' THEN '#6B7280'
--            WHEN 'blue' THEN '#3B82F6'
--            WHEN 'green' THEN '#10B981'
--            WHEN 'red' THEN '#EF4444'
--            WHEN 'yellow' THEN '#F59E0B'
--            ELSE CONCAT('#', color)
--        END,
--        created_at
-- FROM tags;

-- 5. สร้าง View สำหรับดู tags พร้อม user count
CREATE OR REPLACE VIEW `v_user_tags_with_count` AS
SELECT 
    t.*,
    COALESCE(COUNT(DISTINCT uta.user_id), 0) as user_count
FROM user_tags t
LEFT JOIN user_tag_assignments uta ON t.id = uta.tag_id
GROUP BY t.id;

-- 6. สร้าง View สำหรับดู users พร้อม tags
CREATE OR REPLACE VIEW `v_users_with_tags` AS
SELECT 
    u.id,
    u.display_name,
    u.line_user_id,
    u.line_account_id,
    GROUP_CONCAT(t.name SEPARATOR ', ') as tags,
    GROUP_CONCAT(t.color SEPARATOR ', ') as tag_colors
FROM users u
LEFT JOIN user_tag_assignments uta ON u.id = uta.user_id
LEFT JOIN user_tags t ON uta.tag_id = t.id
GROUP BY u.id;
