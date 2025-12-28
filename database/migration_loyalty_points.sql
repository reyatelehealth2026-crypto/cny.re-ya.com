-- Loyalty Points System Migration
-- Version: 1.0
-- Description: Creates tables for loyalty points, rewards, and redemptions

-- Points Settings Table
CREATE TABLE IF NOT EXISTS `points_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_setting` (`line_account_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Transactions Table (History)
CREATE TABLE IF NOT EXISTS `points_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_account_id` INT DEFAULT NULL,
    `points` INT NOT NULL,
    `type` ENUM('earn', 'redeem', 'expire', 'adjust', 'refund', 'bonus') NOT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `description` TEXT,
    `balance_after` INT DEFAULT 0,
    `expires_at` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rewards Catalog Table
CREATE TABLE IF NOT EXISTS `rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `points_required` INT NOT NULL DEFAULT 0,
    `reward_type` ENUM('discount', 'shipping', 'gift', 'product', 'coupon', 'voucher') DEFAULT 'gift',
    `reward_value` VARCHAR(255) DEFAULT NULL,
    `stock` INT DEFAULT -1 COMMENT '-1 = unlimited',
    `max_per_user` INT DEFAULT 0 COMMENT '0 = unlimited',
    `terms` TEXT,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`),
    INDEX `idx_points` (`points_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reward Redemptions Table
CREATE TABLE IF NOT EXISTS `reward_redemptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `reward_id` INT NOT NULL,
    `line_account_id` INT DEFAULT NULL,
    `points_used` INT NOT NULL,
    `redemption_code` VARCHAR(50) NOT NULL,
    `status` ENUM('pending', 'approved', 'delivered', 'cancelled', 'expired') DEFAULT 'pending',
    `notes` TEXT,
    `approved_by` INT DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    `expires_at` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_code` (`redemption_code`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_reward` (`reward_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Tiers Table
CREATE TABLE IF NOT EXISTS `points_tiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `min_points` INT NOT NULL DEFAULT 0,
    `multiplier` DECIMAL(3,2) DEFAULT 1.00,
    `benefits` TEXT,
    `badge_color` VARCHAR(20) DEFAULT '#6B7280',
    `icon` VARCHAR(50) DEFAULT 'fa-medal',
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_min_points` (`min_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Rules Table (for earning configuration)
CREATE TABLE IF NOT EXISTS `points_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `rule_type` ENUM('base', 'campaign', 'category', 'tier') NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `value` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    `conditions` JSON DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`rule_type`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add points columns to users table if not exists
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `total_points` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `available_points` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `used_points` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `tier_id` INT DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `tier_updated_at` TIMESTAMP NULL DEFAULT NULL;

-- Insert default tiers
INSERT IGNORE INTO `points_tiers` (`line_account_id`, `name`, `min_points`, `multiplier`, `badge_color`, `icon`, `sort_order`) VALUES
(NULL, 'Bronze', 0, 1.00, '#CD7F32', 'fa-medal', 1),
(NULL, 'Silver', 1000, 1.25, '#C0C0C0', 'fa-medal', 2),
(NULL, 'Gold', 5000, 1.50, '#FFD700', 'fa-crown', 3),
(NULL, 'Platinum', 15000, 2.00, '#E5E4E2', 'fa-gem', 4);

-- Insert default settings
INSERT IGNORE INTO `points_settings` (`line_account_id`, `setting_key`, `setting_value`) VALUES
(NULL, 'points_per_baht', '1'),
(NULL, 'min_order_for_points', '100'),
(NULL, 'points_expiry_months', '12'),
(NULL, 'enable_tier_system', '1');

-- Insert default base rule
INSERT IGNORE INTO `points_rules` (`line_account_id`, `rule_type`, `name`, `description`, `value`, `is_active`) VALUES
(NULL, 'base', 'อัตราแต้มพื้นฐาน', 'ได้ 1 แต้มต่อทุก 1 บาท', 1.0000, 1);
