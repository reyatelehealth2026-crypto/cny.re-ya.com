-- Migration: Promotions System
-- เพิ่ม columns สำหรับระบบสินค้าเด่น/โปรโมชั่น

-- 1. เพิ่ม is_featured ใน business_items
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'is_featured');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active',
    'SELECT "Column is_featured already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. เพิ่ม is_bestseller ใน business_items (Best Seller ในแต่ละหมวด)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'is_bestseller');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN is_bestseller TINYINT(1) DEFAULT 0 AFTER is_featured',
    'SELECT "Column is_bestseller already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. เพิ่ม is_promotion ใน business_items
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'is_promotion');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN is_promotion TINYINT(1) DEFAULT 0 AFTER is_bestseller',
    'SELECT "Column is_promotion already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. เพิ่ม promotion_start ใน business_items
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'promotion_start');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN promotion_start DATETIME NULL AFTER is_promotion',
    'SELECT "Column promotion_start already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. เพิ่ม promotion_end ใน business_items
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'promotion_end');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN promotion_end DATETIME NULL AFTER promotion_start',
    'SELECT "Column promotion_end already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. เพิ่ม featured_order สำหรับเรียงลำดับสินค้าเด่น
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND COLUMN_NAME = 'featured_order');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD COLUMN featured_order INT DEFAULT 0 AFTER promotion_end',
    'SELECT "Column featured_order already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. เพิ่ม Index สำหรับ is_featured
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND INDEX_NAME = 'idx_featured');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD INDEX idx_featured (is_featured)',
    'SELECT "Index idx_featured already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. เพิ่ม Index สำหรับ is_bestseller
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'business_items' 
               AND INDEX_NAME = 'idx_bestseller');

SET @query = IF(@exist = 0,
    'ALTER TABLE business_items ADD INDEX idx_bestseller (is_bestseller, category_id)',
    'SELECT "Index idx_bestseller already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. เพิ่ม cny_code ใน item_categories (ถ้ายังไม่มี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'item_categories' 
               AND COLUMN_NAME = 'cny_code');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'item_categories'),
    'ALTER TABLE item_categories ADD COLUMN cny_code VARCHAR(10) NULL AFTER name',
    'SELECT "Column cny_code already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 10. เพิ่ม icon ใน item_categories (ถ้ายังไม่มี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'item_categories' 
               AND COLUMN_NAME = 'icon');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'item_categories'),
    'ALTER TABLE item_categories ADD COLUMN icon VARCHAR(50) NULL AFTER cny_code',
    'SELECT "Column icon already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration completed successfully!' as status;
