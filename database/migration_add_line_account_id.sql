-- Migration: เพิ่ม line_account_id ในตารางต่างๆ
-- รันคำสั่งนี้ถ้าฐานข้อมูลมีอยู่แล้ว

-- สร้างตาราง line_accounts (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS line_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อบัญชี LINE OA',
    channel_id VARCHAR(100) COMMENT 'Channel ID',
    channel_secret VARCHAR(100) NOT NULL COMMENT 'Channel Secret',
    channel_access_token TEXT NOT NULL COMMENT 'Channel Access Token',
    webhook_url VARCHAR(500) COMMENT 'Webhook URL',
    basic_id VARCHAR(50) COMMENT 'LINE Basic ID (@xxx)',
    picture_url VARCHAR(500) COMMENT 'รูปโปรไฟล์',
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0 COMMENT 'บัญชีหลัก',
    settings JSON COMMENT 'ตั้งค่าเพิ่มเติม',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_channel_secret (channel_secret)
);

-- เพิ่ม line_account_id ในตาราง users (ถ้ายังไม่มี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column line_account_id already exists in users"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม index (ถ้ายังไม่มี)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD INDEX idx_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง messages
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND INDEX_NAME = 'idx_msg_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE messages ADD INDEX idx_msg_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง orders
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE orders ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_order_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE orders ADD INDEX idx_order_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง auto_replies
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auto_replies' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE auto_replies ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auto_replies' AND INDEX_NAME = 'idx_reply_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE auto_replies ADD INDEX idx_reply_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง broadcasts
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'broadcasts' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE broadcasts ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'broadcasts' AND INDEX_NAME = 'idx_broadcast_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE broadcasts ADD INDEX idx_broadcast_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง products
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE products ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_product_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE products ADD INDEX idx_product_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง product_categories
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_categories' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE product_categories ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_categories' AND INDEX_NAME = 'idx_cat_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE product_categories ADD INDEX idx_cat_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่ม line_account_id ในตาราง scheduled_messages
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scheduled_messages' AND COLUMN_NAME = 'line_account_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE scheduled_messages ADD COLUMN line_account_id INT DEFAULT NULL AFTER id', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scheduled_messages' AND INDEX_NAME = 'idx_scheduled_line_account');
SET @sql := IF(@exist = 0, 'ALTER TABLE scheduled_messages ADD INDEX idx_scheduled_line_account (line_account_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration completed!' as status;
