-- Migration: Add line_account_id to shop_settings for Multi-bot support
-- V2.5 - แยกการตั้งค่าร้านค้าตาม LINE Account

-- เพิ่ม column line_account_id
ALTER TABLE shop_settings ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
ALTER TABLE shop_settings ADD INDEX idx_shop_line_account (line_account_id);

-- ลบ UNIQUE constraint ของ id=1 (ถ้ามี) เพื่อให้สร้างหลาย settings ได้
-- (ไม่จำเป็นถ้าใช้ line_account_id เป็น key)

-- เพิ่ม UNIQUE constraint สำหรับ line_account_id
ALTER TABLE shop_settings ADD UNIQUE KEY unique_shop_line_account (line_account_id);
