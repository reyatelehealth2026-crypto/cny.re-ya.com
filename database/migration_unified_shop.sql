-- =====================================================
-- Unified Shop Migration V3.0
-- รวมระบบ Shop + Business เข้าด้วยกัน
-- รันไฟล์นี้เพื่อให้ระบบรองรับทั้ง 2 โครงสร้าง
-- =====================================================

-- 1. เพิ่ม columns ที่ขาดใน products table (ถ้ามี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'products' 
               AND COLUMN_NAME = 'item_type');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'),
    'ALTER TABLE products ADD COLUMN item_type ENUM(''physical'', ''digital'', ''service'', ''booking'', ''content'') DEFAULT ''physical'' AFTER image_url',
    'SELECT "Column item_type already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. เพิ่ม delivery_method ใน products
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'products' 
               AND COLUMN_NAME = 'delivery_method');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'),
    'ALTER TABLE products ADD COLUMN delivery_method ENUM(''shipping'', ''email'', ''line'', ''download'', ''onsite'') DEFAULT ''shipping'' AFTER item_type',
    'SELECT "Column delivery_method already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. เพิ่ม action_data ใน products
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'products' 
               AND COLUMN_NAME = 'action_data');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'),
    'ALTER TABLE products ADD COLUMN action_data JSON DEFAULT NULL AFTER delivery_method',
    'SELECT "Column action_data already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 4. เพิ่ม is_featured ใน products
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'products' 
               AND COLUMN_NAME = 'is_featured');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'),
    'ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active',
    'SELECT "Column is_featured already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. เพิ่ม max_quantity ใน products
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'products' 
               AND COLUMN_NAME = 'max_quantity');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'),
    'ALTER TABLE products ADD COLUMN max_quantity INT DEFAULT NULL AFTER stock',
    'SELECT "Column max_quantity already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. สร้าง user_states table (สำหรับ checkout flow)
CREATE TABLE IF NOT EXISTS user_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    state VARCHAR(50) NOT NULL,
    state_data JSON,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_state (user_id),
    INDEX idx_state (state),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. สร้าง user_behaviors table (สำหรับ tracking)
CREATE TABLE IF NOT EXISTS user_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL,
    behavior_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (behavior_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. สร้าง payment_slips table
CREATE TABLE IF NOT EXISTS payment_slips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    user_id INT NOT NULL,
    slip_image VARCHAR(500) NOT NULL,
    amount DECIMAL(10,2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    reject_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. เพิ่ม columns ใน orders table (ถ้ามี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'orders' 
               AND COLUMN_NAME = 'slip_image');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'),
    'ALTER TABLE orders ADD COLUMN slip_image VARCHAR(500) AFTER notes',
    'SELECT "Column slip_image already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 10. เพิ่ม paid_at ใน orders
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'orders' 
               AND COLUMN_NAME = 'paid_at');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'),
    'ALTER TABLE orders ADD COLUMN paid_at TIMESTAMP NULL AFTER slip_image',
    'SELECT "Column paid_at already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- สรุป: Migration นี้จะ:
-- 1. เพิ่ม columns ใหม่ใน products table เพื่อรองรับ item_type
-- 2. สร้างตารางเสริมที่จำเป็น (user_states, user_behaviors, payment_slips)
-- 3. เพิ่ม columns ใน orders table
-- 
-- ระบบ UnifiedShop จะ auto-detect ว่าใช้ตารางไหน:
-- - products หรือ business_items
-- - orders หรือ transactions
-- - product_categories หรือ item_categories
-- =====================================================

SELECT 'Unified Shop Migration completed!' AS message;
