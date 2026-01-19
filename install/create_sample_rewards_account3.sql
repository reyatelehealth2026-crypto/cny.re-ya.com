-- Create sample rewards for line_account_id = 3
-- Run this on production database

-- First, check if rewards already exist
SELECT COUNT(*) as existing_rewards FROM rewards WHERE line_account_id = 3;

-- Insert sample rewards
INSERT INTO rewards (line_account_id, name, description, image_url, points_required, reward_type, reward_value, stock, max_per_user, is_active, created_at, updated_at)
VALUES
(3, 'ส่วนลด 50 บาท', 'รับส่วนลด 50 บาท สำหรับการซื้อครั้งถัดไป', NULL, 500, 'discount', 50, -1, 0, 1, NOW(), NOW()),
(3, 'ส่วนลด 100 บาท', 'รับส่วนลด 100 บาท สำหรับการซื้อครั้งถัดไป', NULL, 1000, 'discount', 100, -1, 0, 1, NOW(), NOW()),
(3, 'จัดส่งฟรี', 'รับบริการจัดส่งฟรีสำหรับคำสั่งซื้อครั้งถัดไป', NULL, 300, 'free_shipping', 0, -1, 0, 1, NOW(), NOW()),
(3, 'ของขวัญพิเศษ', 'รับของขวัญพิเศษจากร้าน (จำนวนจำกัด)', NULL, 2000, 'gift', 0, 10, 1, 1, NOW(), NOW());

-- Verify insertion
SELECT id, name, points_required, stock, is_active FROM rewards WHERE line_account_id = 3;
