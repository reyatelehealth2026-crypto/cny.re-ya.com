-- Fix user_states table structure
-- ปัญหา: บาง migration สร้าง user_states ด้วย id แยก ทำให้ REPLACE INTO ไม่ทำงาน

-- Drop and recreate with correct structure
DROP TABLE IF EXISTS user_states;

CREATE TABLE user_states (
    user_id INT PRIMARY KEY,
    state VARCHAR(50) NOT NULL,
    state_data JSON,
    expires_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
