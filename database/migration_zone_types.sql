-- =============================================
-- Zone Types Management
-- =============================================

-- Zone types table for custom zone type definitions
CREATE TABLE IF NOT EXISTS `zone_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT 1,
    `code` VARCHAR(50) NOT NULL COMMENT 'Unique code for zone type',
    `label` VARCHAR(100) NOT NULL COMMENT 'Display label',
    `color` VARCHAR(20) DEFAULT 'gray' COMMENT 'Tailwind color name',
    `icon` VARCHAR(50) DEFAULT 'fa-box' COMMENT 'FontAwesome icon class',
    `description` TEXT NULL COMMENT 'Description of zone type',
    `storage_requirements` TEXT NULL COMMENT 'Special storage requirements',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Is system default type',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_zone_type_code` (`line_account_id`, `code`),
    INDEX `idx_zone_type_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default zone types
INSERT INTO `zone_types` (`code`, `label`, `color`, `icon`, `description`, `is_default`, `sort_order`) VALUES
('general', 'ทั่วไป', 'blue', 'fa-box', 'โซนจัดเก็บสินค้าทั่วไป อุณหภูมิห้อง', 1, 1),
('cold_storage', 'ห้องเย็น', 'cyan', 'fa-snowflake', 'โซนควบคุมอุณหภูมิ 2-8°C สำหรับยาที่ต้องแช่เย็น', 1, 2),
('controlled', 'ยาควบคุม (RX)', 'red', 'fa-lock', 'โซนเก็บยาควบคุมพิเศษ ต้องมีการล็อคและบันทึกการเข้าถึง', 1, 3),
('hazardous', 'วัตถุอันตราย', 'orange', 'fa-exclamation-triangle', 'โซนเก็บสารเคมีหรือวัตถุอันตราย', 1, 4)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
