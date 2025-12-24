-- =====================================================
-- Fix Payment Slips Table - Standardize column names
-- รันไฟล์นี้เพื่อแก้ไขตาราง payment_slips ให้ใช้ชื่อ column ที่ถูกต้อง
-- =====================================================

-- Check if slip_image column exists and rename to image_url
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payment_slips' 
               AND COLUMN_NAME = 'slip_image');

SET @query = IF(@exist > 0,
    'ALTER TABLE payment_slips CHANGE COLUMN slip_image image_url VARCHAR(500) NOT NULL',
    'SELECT "Column slip_image does not exist or already renamed"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add transaction_id column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payment_slips' 
               AND COLUMN_NAME = 'transaction_id');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_slips'),
    'ALTER TABLE payment_slips ADD COLUMN transaction_id INT DEFAULT NULL AFTER order_id',
    'SELECT "Column transaction_id already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add admin_note column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payment_slips' 
               AND COLUMN_NAME = 'admin_note');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_slips'),
    'ALTER TABLE payment_slips ADD COLUMN admin_note TEXT AFTER reject_reason',
    'SELECT "Column admin_note already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for transaction_id if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payment_slips' 
               AND INDEX_NAME = 'idx_transaction');

SET @query = IF(@exist = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_slips'),
    'ALTER TABLE payment_slips ADD INDEX idx_transaction (transaction_id)',
    'SELECT "Index idx_transaction already exists or table not found"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make user_id nullable (for cases where slip is uploaded without user context)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payment_slips' 
               AND COLUMN_NAME = 'user_id'
               AND IS_NULLABLE = 'NO');

SET @query = IF(@exist > 0,
    'ALTER TABLE payment_slips MODIFY COLUMN user_id INT DEFAULT NULL',
    'SELECT "Column user_id is already nullable or does not exist"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Payment slips table migration completed!' AS message;
