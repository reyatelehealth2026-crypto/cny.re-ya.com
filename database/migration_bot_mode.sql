-- Migration: Add bot_mode to line_accounts
-- Date: 2025-01-14
-- Description: เพิ่ม column bot_mode เพื่อเลือกโหมดการทำงานของบอท

-- เพิ่ม column bot_mode
ALTER TABLE line_accounts 
ADD COLUMN IF NOT EXISTS bot_mode ENUM('shop', 'general', 'auto_reply_only') DEFAULT 'shop' 
COMMENT 'โหมดบอท: shop=ร้านค้าเต็มรูปแบบ, general=ทั่วไป(ไม่มีร้านค้า), auto_reply_only=ตอบกลับอัตโนมัติเท่านั้น'
AFTER is_default;

-- สำหรับ MySQL ที่ไม่รองรับ IF NOT EXISTS ใน ALTER
-- ใช้ procedure แทน
DELIMITER //
DROP PROCEDURE IF EXISTS add_bot_mode_column//
CREATE PROCEDURE add_bot_mode_column()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'line_accounts' 
        AND COLUMN_NAME = 'bot_mode'
    ) THEN
        ALTER TABLE line_accounts 
        ADD COLUMN bot_mode ENUM('shop', 'general', 'auto_reply_only') DEFAULT 'shop' 
        COMMENT 'โหมดบอท: shop=ร้านค้าเต็มรูปแบบ, general=ทั่วไป(ไม่มีร้านค้า), auto_reply_only=ตอบกลับอัตโนมัติเท่านั้น'
        AFTER is_default;
    END IF;
END//
DELIMITER ;

CALL add_bot_mode_column();
DROP PROCEDURE IF EXISTS add_bot_mode_column;
