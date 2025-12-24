-- Migration: Loyalty Points System
-- ระบบสะสมแต้มแลกของรางวัล

-- 1. Points Settings - ตั้งค่าระบบแต้ม
CREATE TABLE IF NOT EXISTS points_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    points_per_baht DECIMAL(10,2) DEFAULT 1.00 COMMENT 'แต้มต่อบาท (เช่น 1 บาท = 1 แต้ม)',
    min_order_for_points DECIMAL(10,2) DEFAULT 0 COMMENT 'ยอดขั้นต่ำที่ได้แต้ม',
    points_expiry_days INT DEFAULT 365 COMMENT 'แต้มหมดอายุกี่วัน (0 = ไม่หมดอายุ)',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Points - แต้มของผู้ใช้
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_points INT DEFAULT 0 COMMENT 'แต้มสะสมทั้งหมด';
ALTER TABLE users ADD COLUMN IF NOT EXISTS available_points INT DEFAULT 0 COMMENT 'แต้มที่ใช้ได้';
ALTER TABLE users ADD COLUMN IF NOT EXISTS used_points INT DEFAULT 0 COMMENT 'แต้มที่ใช้ไปแล้ว';

-- 3. Points Transactions - ประวัติการได้/ใช้แต้ม
CREATE TABLE IF NOT EXISTS points_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_account_id INT,
    type ENUM('earn', 'redeem', 'expire', 'adjust', 'refund') NOT NULL,
    points INT NOT NULL COMMENT 'จำนวนแต้ม (บวก=ได้, ลบ=ใช้)',
    balance_after INT NOT NULL COMMENT 'แต้มคงเหลือหลังทำรายการ',
    reference_type VARCHAR(50) COMMENT 'order, reward, manual, etc.',
    reference_id INT COMMENT 'ID อ้างอิง',
    description VARCHAR(255),
    expires_at TIMESTAMP NULL COMMENT 'วันหมดอายุของแต้ม',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Rewards - ของรางวัลที่แลกได้
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    points_required INT NOT NULL COMMENT 'แต้มที่ต้องใช้แลก',
    reward_type ENUM('product', 'discount', 'coupon', 'gift') DEFAULT 'gift',
    reward_value VARCHAR(255) COMMENT 'มูลค่า/รหัสคูปอง/product_id',
    stock INT DEFAULT -1 COMMENT 'จำนวนคงเหลือ (-1 = ไม่จำกัด)',
    max_per_user INT DEFAULT 0 COMMENT 'จำกัดต่อคน (0 = ไม่จำกัด)',
    is_active TINYINT(1) DEFAULT 1,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Reward Redemptions - ประวัติการแลกของรางวัล
CREATE TABLE IF NOT EXISTS reward_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    line_account_id INT,
    points_used INT NOT NULL,
    status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
    redemption_code VARCHAR(50) UNIQUE,
    notes TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_reward (reward_id),
    INDEX idx_status (status),
    INDEX idx_code (redemption_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Points Tiers - ระดับสมาชิก (Optional)
CREATE TABLE IF NOT EXISTS points_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT,
    name VARCHAR(100) NOT NULL,
    min_points INT NOT NULL COMMENT 'แต้มขั้นต่ำ',
    points_multiplier DECIMAL(3,2) DEFAULT 1.00 COMMENT 'ตัวคูณแต้ม',
    color VARCHAR(20) DEFAULT '#666666',
    icon VARCHAR(50) DEFAULT 'fa-star',
    benefits TEXT COMMENT 'สิทธิประโยชน์ (JSON)',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id),
    INDEX idx_points (min_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT IGNORE INTO points_settings (line_account_id, points_per_baht, min_order_for_points, points_expiry_days)
VALUES (NULL, 1.00, 100, 365);

-- Insert default tiers
INSERT IGNORE INTO points_tiers (line_account_id, name, min_points, points_multiplier, color, icon) VALUES
(NULL, 'Bronze', 0, 1.00, '#CD7F32', 'fa-medal'),
(NULL, 'Silver', 1000, 1.25, '#C0C0C0', 'fa-medal'),
(NULL, 'Gold', 5000, 1.50, '#FFD700', 'fa-crown'),
(NULL, 'Platinum', 15000, 2.00, '#E5E4E2', 'fa-gem');
