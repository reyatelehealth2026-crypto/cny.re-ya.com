-- Migration: Add checkout options columns
-- เพิ่ม columns สำหรับ checkout flow ใหม่

-- Add payment_method column to transactions
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'transfer' AFTER payment_status;

-- Add cod_enabled to shop_settings
ALTER TABLE shop_settings 
ADD COLUMN IF NOT EXISTS cod_enabled TINYINT(1) DEFAULT 1 AFTER auto_confirm_payment;

-- Add cod_fee to shop_settings (ค่าธรรมเนียม COD)
ALTER TABLE shop_settings 
ADD COLUMN IF NOT EXISTS cod_fee DECIMAL(10,2) DEFAULT 0 AFTER cod_enabled;

-- Add liff_id to line_accounts for LIFF checkout
ALTER TABLE line_accounts 
ADD COLUMN IF NOT EXISTS liff_id VARCHAR(50) DEFAULT NULL AFTER channel_secret;

-- Add liff_checkout_url to shop_settings
ALTER TABLE shop_settings 
ADD COLUMN IF NOT EXISTS liff_checkout_url VARCHAR(255) DEFAULT NULL AFTER cod_fee;
