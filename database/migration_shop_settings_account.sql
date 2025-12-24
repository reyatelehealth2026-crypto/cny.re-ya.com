-- เพิ่ม line_account_id ในตาราง shop_settings
ALTER TABLE shop_settings ADD COLUMN line_account_id INT DEFAULT NULL AFTER id;
ALTER TABLE shop_settings ADD INDEX idx_shop_line_account (line_account_id);

-- อัพเดท shop_settings ที่มีอยู่ให้เป็นของ default account
UPDATE shop_settings SET line_account_id = (SELECT id FROM line_accounts WHERE is_default = 1 LIMIT 1) WHERE line_account_id IS NULL;
