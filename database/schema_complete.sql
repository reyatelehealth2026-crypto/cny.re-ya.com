-- =============================================
-- LINE CRM Pharmacy - Complete Database Schema
-- Version: 3.0
-- Generated: 2024-12-28
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =============================================
-- CORE TABLES
-- =============================================

-- LINE Accounts (Multi-bot support)
CREATE TABLE IF NOT EXISTS `line_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'ชื่อบัญชี LINE OA',
    `channel_id` VARCHAR(100) COMMENT 'Channel ID',
    `channel_secret` VARCHAR(100) NOT NULL COMMENT 'Channel Secret',
    `channel_access_token` TEXT NOT NULL COMMENT 'Channel Access Token',
    `webhook_url` VARCHAR(500) COMMENT 'Webhook URL',
    `basic_id` VARCHAR(50) COMMENT 'LINE Basic ID (@xxx)',
    `picture_url` VARCHAR(500) COMMENT 'รูปโปรไฟล์',
    `liff_id` VARCHAR(100) COMMENT 'LIFF ID',
    `is_active` TINYINT(1) DEFAULT 1,
    `is_default` TINYINT(1) DEFAULT 0 COMMENT 'บัญชีหลัก',
    `settings` JSON COMMENT 'ตั้งค่าเพิ่มเติม',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_channel_secret` (`channel_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255),
    `avatar_url` VARCHAR(500),
    `role` ENUM('super_admin', 'admin', 'pharmacist', 'staff', 'user') DEFAULT 'user',
    `line_account_id` INT DEFAULT NULL,
    `permissions` JSON COMMENT 'สิทธิ์เพิ่มเติม',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_role` (`role`),
    INDEX `idx_admin_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- LINE Users (ตาราง users ครบทุก field)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(255),
    `picture_url` TEXT,
    `status_message` TEXT,
    
    -- ข้อมูลส่วนตัว
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `gender` VARCHAR(10) DEFAULT NULL,
    
    -- ข้อมูลร่างกาย
    `weight` DECIMAL(5,2) DEFAULT NULL COMMENT 'น้ำหนัก (กก.)',
    `height` DECIMAL(5,2) DEFAULT NULL COMMENT 'ส่วนสูง (ซม.)',
    
    -- ที่อยู่
    `address` TEXT DEFAULT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    
    -- สถานะสมาชิก
    `member_id` VARCHAR(20) DEFAULT NULL,
    `is_registered` TINYINT(1) DEFAULT 0,
    `registered_at` DATETIME DEFAULT NULL,
    `is_blocked` TINYINT(1) DEFAULT 0,
    
    -- ระดับสมาชิกและแต้ม
    `membership_level` VARCHAR(20) DEFAULT 'bronze',
    `tier` VARCHAR(20) DEFAULT 'silver',
    `points` INT DEFAULT 0,
    `total_points` INT DEFAULT 0,
    `available_points` INT DEFAULT 0,
    `used_points` INT DEFAULT 0,
    `loyalty_points` INT DEFAULT 0,
    
    -- สถิติการซื้อ
    `total_spent` DECIMAL(12,2) DEFAULT 0,
    `order_count` INT DEFAULT 0,
    
    -- ข้อมูลสุขภาพ/การแพทย์
    `drug_allergies` TEXT DEFAULT NULL COMMENT 'ยาที่แพ้',
    `chronic_diseases` TEXT DEFAULT NULL COMMENT 'โรคประจำตัว',
    `current_medications` TEXT DEFAULT NULL COMMENT 'ยาที่ใช้ประจำ',
    `medical_conditions` TEXT DEFAULT NULL COMMENT 'ข้อมูลการแพทย์เพิ่มเติม',
    
    -- LINE Reply Token
    `reply_token` VARCHAR(255),
    `reply_token_expires` DATETIME,
    
    -- Timestamps
    `last_interaction` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY `unique_line_user` (`line_account_id`, `line_user_id`),
    UNIQUE KEY `unique_member_id` (`member_id`),
    INDEX `idx_line_account` (`line_account_id`),
    INDEX `idx_line_user_id` (`line_user_id`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_email` (`email`),
    INDEX `idx_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `user_id` INT,
    `direction` ENUM('incoming', 'outgoing') NOT NULL,
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT,
    `reply_token` VARCHAR(255),
    `is_read` TINYINT(1) DEFAULT 0,
    `sent_by` VARCHAR(100) DEFAULT NULL COMMENT 'admin username or AI',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_msg_line_account` (`line_account_id`),
    INDEX `idx_msg_user` (`user_id`),
    INDEX `idx_msg_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- SHOP MODULE
-- =============================================

-- Item Categories
CREATE TABLE IF NOT EXISTS `item_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `description` TEXT,
    `image_url` VARCHAR(500),
    `cny_code` VARCHAR(50) COMMENT 'CNY Category Code',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cat_line_account` (`line_account_id`),
    INDEX `idx_cat_parent` (`parent_id`),
    INDEX `idx_cat_cny_code` (`cny_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business Items (Products)
CREATE TABLE IF NOT EXISTS `business_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `category_id` INT,
    `sku` VARCHAR(100),
    `sku_id` VARCHAR(100) COMMENT 'CNY SKU ID',
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `description` TEXT,
    `short_description` VARCHAR(500),
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sale_price` DECIMAL(10,2) NULL,
    `cost_price` DECIMAL(10,2) NULL,
    `image_url` VARCHAR(500),
    `images` JSON COMMENT 'Additional images',
    `stock` INT DEFAULT 0,
    `min_stock` INT DEFAULT 5,
    `unit` VARCHAR(50) DEFAULT 'ชิ้น',
    `barcode` VARCHAR(100),
    `manufacturer` VARCHAR(255),
    `active_ingredient` TEXT COMMENT 'ตัวยาสำคัญ',
    `dosage_form` VARCHAR(100) COMMENT 'รูปแบบยา',
    `drug_category` VARCHAR(50) COMMENT 'ประเภทยา: otc, dangerous, controlled',
    `requires_prescription` TINYINT(1) DEFAULT 0,
    `is_prescription` TINYINT(1) DEFAULT 0 COMMENT 'Requires pharmacist approval',
    `prescription_warning` TEXT DEFAULT NULL COMMENT 'Warning text for Rx products',
    `storage_condition` VARCHAR(255),
    `is_active` TINYINT(1) DEFAULT 1,
    `is_featured` TINYINT(1) DEFAULT 0,
    `view_count` INT DEFAULT 0,
    `sold_count` INT DEFAULT 0,
    `cny_product_id` VARCHAR(100) COMMENT 'CNY Product ID',
    `last_sync` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_product_line_account` (`line_account_id`),
    INDEX `idx_product_category` (`category_id`),
    INDEX `idx_product_sku` (`sku`),
    INDEX `idx_product_sku_id` (`sku_id`),
    INDEX `idx_product_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shopping Cart
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`),
    INDEX `idx_cart_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Items (Alternative)
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_account_id` INT DEFAULT 1,
    `product_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `price` DECIMAL(10,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_product` (`product_id`),
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Transactions (Orders)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `transaction_type` ENUM('purchase', 'refund', 'exchange') DEFAULT 'purchase',
    `total_amount` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(10,2) DEFAULT 0,
    `shipping_fee` DECIMAL(10,2) DEFAULT 0,
    `discount` DECIMAL(10,2) DEFAULT 0,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `points_used` INT DEFAULT 0,
    `points_discount` DECIMAL(10,2) DEFAULT 0,
    `grand_total` DECIMAL(12,2) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'paid', 'preparing', 'processing', 'shipped', 'shipping', 'delivered', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    `payment_method` VARCHAR(50),
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `shipping_name` VARCHAR(255),
    `shipping_phone` VARCHAR(20),
    `shipping_address` TEXT,
    `shipping_tracking` VARCHAR(100),
    `tracking_number` VARCHAR(100),
    `shipping_provider` VARCHAR(100),
    `delivery_info` JSON,
    `note` TEXT,
    `notes` TEXT,
    `admin_note` TEXT,
    `has_prescription_items` TINYINT(1) DEFAULT 0,
    `prescription_approval_id` INT NULL,
    `pharmacist_approved_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_line_account` (`line_account_id`),
    INDEX `idx_order_user` (`user_id`),
    INDEX `idx_order_status` (`status`),
    INDEX `idx_order_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Items
CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `product_id` INT,
    `product_name` VARCHAR(255) NOT NULL,
    `product_sku` VARCHAR(100),
    `product_price` DECIMAL(10,2) NOT NULL,
    `price` DECIMAL(10,2) DEFAULT 0,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) DEFAULT 0,
    `image_url` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_item_transaction` (`transaction_id`),
    INDEX `idx_item_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Slips
CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `order_id` INT,
    `transaction_id` INT,
    `user_id` INT,
    `image_url` VARCHAR(500) NOT NULL,
    `amount` DECIMAL(10,2),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_note` TEXT,
    `notes` TEXT,
    `verified_by` INT,
    `verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slip_order` (`order_id`),
    INDEX `idx_slip_transaction` (`transaction_id`),
    INDEX `idx_slip_user` (`user_id`),
    INDEX `idx_slip_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shop Settings
CREATE TABLE IF NOT EXISTS `shop_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `shop_name` VARCHAR(255) DEFAULT 'LINE Shop',
    `shop_logo` VARCHAR(500),
    `shop_description` TEXT,
    `welcome_message` TEXT,
    `shipping_fee` DECIMAL(10,2) DEFAULT 50,
    `free_shipping_min` DECIMAL(10,2) DEFAULT 500,
    `bank_accounts` JSON,
    `promptpay_number` VARCHAR(20),
    `promptpay_name` VARCHAR(255),
    `contact_phone` VARCHAR(20),
    `contact_email` VARCHAR(255),
    `address` TEXT,
    `is_open` TINYINT(1) DEFAULT 1,
    `require_address` TINYINT(1) DEFAULT 1,
    `require_phone` TINYINT(1) DEFAULT 1,
    `allow_cod` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_shop_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_product` (`product_id`),
    UNIQUE KEY `unique_wishlist` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- CRM & LOYALTY MODULE
-- =============================================

-- User Tags
CREATE TABLE IF NOT EXISTS `user_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#3B82F6',
    `description` TEXT,
    `is_auto` TINYINT(1) DEFAULT 0 COMMENT 'Auto-assigned tag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tag_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Tag Assignments
CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `assigned_by` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
    INDEX `idx_assignment_user` (`user_id`),
    INDEX `idx_assignment_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Settings
CREATE TABLE IF NOT EXISTS `points_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT,
    `points_per_baht` DECIMAL(10,2) DEFAULT 1.00 COMMENT 'แต้มต่อบาท',
    `min_order_for_points` DECIMAL(10,2) DEFAULT 0 COMMENT 'ยอดขั้นต่ำที่ได้แต้ม',
    `points_expiry_days` INT DEFAULT 365 COMMENT 'แต้มหมดอายุกี่วัน (0 = ไม่หมดอายุ)',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Transactions
CREATE TABLE IF NOT EXISTS `points_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_account_id` INT,
    `type` ENUM('earn', 'redeem', 'expire', 'adjust', 'refund') NOT NULL,
    `points` INT NOT NULL COMMENT 'จำนวนแต้ม (บวก=ได้, ลบ=ใช้)',
    `balance_after` INT NOT NULL COMMENT 'แต้มคงเหลือหลังทำรายการ',
    `reference_type` VARCHAR(50) COMMENT 'order, reward, manual, etc.',
    `reference_id` INT COMMENT 'ID อ้างอิง',
    `description` VARCHAR(255),
    `expires_at` TIMESTAMP NULL COMMENT 'วันหมดอายุของแต้ม',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points History
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_account_id` INT DEFAULT 1,
    `points` INT NOT NULL COMMENT 'บวก=ได้รับ, ลบ=ใช้',
    `type` ENUM('earn', 'redeem', 'expire', 'adjust', 'bonus') NOT NULL,
    `reference_type` VARCHAR(50) COMMENT 'order, reward, manual',
    `reference_id` INT,
    `description` TEXT,
    `balance_after` INT,
    `created_by` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_points_user` (`user_id`),
    INDEX `idx_points_type` (`type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rewards Catalog
CREATE TABLE IF NOT EXISTS `rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(500),
    `points_required` INT NOT NULL COMMENT 'แต้มที่ต้องใช้แลก',
    `reward_type` ENUM('discount', 'product', 'voucher', 'shipping', 'coupon', 'gift') DEFAULT 'discount',
    `reward_value` VARCHAR(255) COMMENT 'มูลค่า/รหัสคูปอง/product_id',
    `stock` INT DEFAULT -1 COMMENT '-1 = unlimited',
    `max_per_user` INT DEFAULT 0 COMMENT 'จำกัดต่อคน (0 = ไม่จำกัด)',
    `is_active` TINYINT(1) DEFAULT 1,
    `start_date` DATE,
    `end_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_reward_line_account` (`line_account_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reward Redemptions
CREATE TABLE IF NOT EXISTS `reward_redemptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `reward_id` INT NOT NULL,
    `line_account_id` INT,
    `points_used` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
    `redemption_code` VARCHAR(50) UNIQUE,
    `notes` TEXT,
    `approved_by` INT NULL,
    `approved_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_reward` (`reward_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_code` (`redemption_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points Tiers
CREATE TABLE IF NOT EXISTS `points_tiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT,
    `name` VARCHAR(100) NOT NULL,
    `min_points` INT NOT NULL COMMENT 'แต้มขั้นต่ำ',
    `points_multiplier` DECIMAL(3,2) DEFAULT 1.00 COMMENT 'ตัวคูณแต้ม',
    `color` VARCHAR(20) DEFAULT '#666666',
    `icon` VARCHAR(50) DEFAULT 'fa-star',
    `benefits` TEXT COMMENT 'สิทธิประโยชน์ (JSON)',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_account` (`line_account_id`),
    INDEX `idx_points` (`min_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point Rewards (Alternative)
CREATE TABLE IF NOT EXISTS `point_rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `points_required` INT NOT NULL,
    `type` ENUM('discount', 'shipping', 'gift', 'coupon') DEFAULT 'discount',
    `value` DECIMAL(10,2) DEFAULT 0,
    `image_url` VARCHAR(500),
    `stock` INT DEFAULT NULL COMMENT 'NULL = unlimited',
    `is_active` TINYINT(1) DEFAULT 1,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`),
    INDEX `idx_points` (`points_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- MESSAGING MODULE
-- =============================================

-- Auto Replies
CREATE TABLE IF NOT EXISTS `auto_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `keyword` VARCHAR(255) NOT NULL,
    `match_type` ENUM('exact', 'contains', 'starts_with', 'regex') DEFAULT 'contains',
    `reply_type` VARCHAR(50) DEFAULT 'text',
    `reply_content` TEXT NOT NULL,
    `flex_json` JSON,
    `is_active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 0,
    `use_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reply_line_account` (`line_account_id`),
    INDEX `idx_reply_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcast Messages
CREATE TABLE IF NOT EXISTS `broadcast_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT NOT NULL,
    `flex_json` JSON,
    `target_type` ENUM('all', 'tag', 'segment') DEFAULT 'all',
    `target_tags` JSON,
    `target_segment` JSON,
    `sent_count` INT DEFAULT 0,
    `success_count` INT DEFAULT 0,
    `fail_count` INT DEFAULT 0,
    `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    `scheduled_at` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_broadcast_line_account` (`line_account_id`),
    INDEX `idx_broadcast_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Flex Templates
CREATE TABLE IF NOT EXISTS `flex_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100),
    `description` TEXT,
    `flex_json` JSON NOT NULL,
    `thumbnail_url` VARCHAR(500),
    `is_public` TINYINT(1) DEFAULT 0,
    `use_count` INT DEFAULT 0,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_flex_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rich Menus
CREATE TABLE IF NOT EXISTS `rich_menus` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `line_rich_menu_id` VARCHAR(100),
    `name` VARCHAR(255) NOT NULL,
    `chat_bar_text` VARCHAR(50),
    `size_width` INT DEFAULT 2500,
    `size_height` INT DEFAULT 1686,
    `areas` JSON,
    `image_path` VARCHAR(255),
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_richmenu_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Welcome Settings
CREATE TABLE IF NOT EXISTS `welcome_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 1,
    `message_type` ENUM('text', 'flex') DEFAULT 'text',
    `text_content` TEXT,
    `flex_content` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_welcome_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- MEDICAL/PHARMACY MODULE
-- =============================================

-- Pharmacists
CREATE TABLE IF NOT EXISTS `pharmacists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT 1,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(100),
    `specialty` VARCHAR(255) DEFAULT 'เภสัชกรทั่วไป',
    `sub_specialty` VARCHAR(255),
    `hospital` VARCHAR(255),
    `license_no` VARCHAR(50),
    `bio` TEXT,
    `consulting_areas` TEXT,
    `work_experience` TEXT,
    `image_url` VARCHAR(500),
    `rating` DECIMAL(2,1) DEFAULT 5.0,
    `review_count` INT DEFAULT 0,
    `consultation_fee` DECIMAL(10,2) DEFAULT 0,
    `consultation_duration` INT DEFAULT 15,
    `is_available` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`),
    INDEX `idx_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacist Schedules
CREATE TABLE IF NOT EXISTS `pharmacist_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacist_id` INT NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pharmacist` (`pharmacist_id`),
    UNIQUE KEY `unique_schedule` (`pharmacist_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacist Holidays
CREATE TABLE IF NOT EXISTS `pharmacist_holidays` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacist_id` INT NOT NULL,
    `holiday_date` DATE NOT NULL,
    `reason` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pharmacist` (`pharmacist_id`),
    UNIQUE KEY `unique_holiday` (`pharmacist_id`, `holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Symptom Assessments
CREATE TABLE IF NOT EXISTS `symptom_assessments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `session_id` VARCHAR(100),
    `symptoms` JSON,
    `duration` VARCHAR(100),
    `severity` INT COMMENT '1-10',
    `medical_history` JSON,
    `allergies` JSON,
    `current_medications` JSON,
    `ai_assessment` TEXT,
    `ai_recommendations` JSON,
    `triage_level` ENUM('green', 'yellow', 'orange', 'red') DEFAULT 'green',
    `red_flags` JSON,
    `status` ENUM('in_progress', 'completed', 'referred') DEFAULT 'in_progress',
    `pharmacist_id` INT,
    `pharmacist_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assessment_user` (`user_id`),
    INDEX `idx_assessment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triage Sessions
CREATE TABLE IF NOT EXISTS `triage_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `assessment_id` INT,
    `triage_level` ENUM('green', 'yellow', 'orange', 'red') NOT NULL,
    `chief_complaint` TEXT,
    `vital_signs` JSON,
    `red_flags_detected` JSON,
    `ai_recommendation` TEXT,
    `pharmacist_action` TEXT,
    `outcome` ENUM('self_care', 'otc_recommended', 'refer_doctor', 'emergency') DEFAULT 'self_care',
    `follow_up_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_triage_user` (`user_id`),
    INDEX `idx_triage_level` (`triage_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacist Consultations
CREATE TABLE IF NOT EXISTS `pharmacist_consultations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `pharmacist_id` INT,
    `assessment_id` INT,
    `consultation_type` ENUM('chat', 'video', 'phone') DEFAULT 'chat',
    `status` ENUM('waiting', 'in_progress', 'completed', 'cancelled') DEFAULT 'waiting',
    `notes` TEXT,
    `recommendations` JSON,
    `prescribed_products` JSON,
    `follow_up_required` TINYINT(1) DEFAULT 0,
    `started_at` TIMESTAMP NULL,
    `ended_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_consult_user` (`user_id`),
    INDEX `idx_consult_pharmacist` (`pharmacist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescription Approvals
CREATE TABLE IF NOT EXISTS `prescription_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `pharmacist_id` INT NULL,
    `approved_items` JSON NOT NULL COMMENT 'List of approved prescription items',
    `status` ENUM('pending', 'approved', 'rejected', 'expired', 'used') DEFAULT 'pending',
    `video_call_id` INT NULL COMMENT 'Link to video call consultation',
    `notes` TEXT NULL,
    `line_account_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL COMMENT '24-hour expiry from creation',
    `used_at` DATETIME NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_line_account_id` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- User Health Profiles
CREATE TABLE IF NOT EXISTS `user_health_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(50) NOT NULL,
    `line_account_id` INT DEFAULT 0,
    `age` INT DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
    `weight` DECIMAL(5,2) DEFAULT NULL COMMENT 'Weight in kg',
    `height` DECIMAL(5,2) DEFAULT NULL COMMENT 'Height in cm',
    `blood_type` ENUM('A', 'B', 'AB', 'O', 'unknown') DEFAULT 'unknown',
    `medical_conditions` JSON DEFAULT NULL COMMENT 'Array of medical conditions',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user` (`line_user_id`, `line_account_id`),
    INDEX `idx_line_user` (`line_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Drug Allergies
CREATE TABLE IF NOT EXISTS `user_drug_allergies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(50) NOT NULL,
    `line_account_id` INT DEFAULT 0,
    `drug_name` VARCHAR(255) NOT NULL,
    `drug_id` INT DEFAULT NULL COMMENT 'Link to product if exists',
    `reaction_type` ENUM('rash', 'breathing', 'swelling', 'other') DEFAULT 'other',
    `reaction_notes` TEXT DEFAULT NULL,
    `severity` ENUM('mild', 'moderate', 'severe') DEFAULT 'moderate',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_line_user` (`line_user_id`),
    INDEX `idx_drug` (`drug_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Current Medications
CREATE TABLE IF NOT EXISTS `user_current_medications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(50) NOT NULL,
    `line_account_id` INT DEFAULT 0,
    `medication_name` VARCHAR(255) NOT NULL,
    `product_id` INT DEFAULT NULL COMMENT 'Link to product if exists',
    `dosage` VARCHAR(100) DEFAULT NULL,
    `frequency` VARCHAR(100) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_line_user` (`line_user_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medication Reminders
CREATE TABLE IF NOT EXISTS `medication_reminders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_user_id` VARCHAR(50),
    `line_account_id` INT DEFAULT 1,
    `medication_name` VARCHAR(255) NOT NULL,
    `dosage` VARCHAR(100) COMMENT 'e.g., 1 tablet, 5ml',
    `frequency` VARCHAR(50) COMMENT 'daily, twice_daily, custom',
    `reminder_times` JSON COMMENT 'Array of times like ["08:00", "20:00"]',
    `times` JSON COMMENT 'Alternative: ["08:00", "20:00"]',
    `start_date` DATE,
    `end_date` DATE,
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `product_id` INT COMMENT 'Link to product if from order',
    `order_id` INT COMMENT 'Link to order if from order history',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_line_user` (`line_user_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medication Taken History
CREATE TABLE IF NOT EXISTS `medication_taken_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reminder_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `scheduled_time` TIME,
    `taken_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('taken', 'skipped', 'missed') DEFAULT 'taken',
    `notes` TEXT,
    INDEX `idx_reminder` (`reminder_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`taken_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drug Interaction Acknowledgments
CREATE TABLE IF NOT EXISTS `drug_interaction_acknowledgments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_user_id` VARCHAR(50),
    `drug1_id` INT NOT NULL,
    `drug2_id` INT NOT NULL,
    `drug1_name` VARCHAR(255),
    `drug2_name` VARCHAR(255),
    `severity` ENUM('mild', 'moderate', 'severe') DEFAULT 'moderate',
    `acknowledged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `order_id` INT NULL COMMENT 'Link to order if acknowledged during checkout',
    INDEX `idx_user` (`user_id`),
    INDEX `idx_drugs` (`drug1_id`, `drug2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- APPOINTMENTS MODULE
-- =============================================

-- Appointments
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `appointment_id` VARCHAR(50) UNIQUE,
    `user_id` INT NOT NULL,
    `pharmacist_id` INT,
    `appointment_type` ENUM('consultation', 'video_call', 'pickup', 'delivery', 'instant', 'scheduled') DEFAULT 'consultation',
    `type` ENUM('instant', 'scheduled') DEFAULT 'scheduled',
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `end_time` TIME,
    `duration` INT DEFAULT 15,
    `duration_minutes` INT DEFAULT 30,
    `symptoms` TEXT,
    `consultation_fee` DECIMAL(10,2) DEFAULT 0,
    `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    `notes` TEXT,
    `video_room_id` VARCHAR(100),
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_appt_user` (`user_id`),
    INDEX `idx_appt_pharmacist` (`pharmacist_id`),
    INDEX `idx_appt_date` (`appointment_date`),
    INDEX `idx_appt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video Calls
CREATE TABLE IF NOT EXISTS `video_calls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `appointment_id` INT,
    `user_id` INT NOT NULL,
    `pharmacist_id` INT,
    `room_id` VARCHAR(100) UNIQUE,
    `consultation_type` ENUM('general', 'prescription', 'symptom', 'follow_up') DEFAULT 'general',
    `status` ENUM('waiting', 'active', 'ended', 'missed') DEFAULT 'waiting',
    `started_at` TIMESTAMP NULL,
    `ended_at` TIMESTAMP NULL,
    `duration_seconds` INT,
    `notes` TEXT,
    `prescription_approval_id` INT NULL COMMENT 'Link to prescription approval if created',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_video_user` (`user_id`),
    INDEX `idx_video_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- AI MODULE
-- =============================================

-- AI Settings
CREATE TABLE IF NOT EXISTS `ai_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 0,
    `ai_provider` ENUM('gemini', 'openai', 'claude') DEFAULT 'gemini',
    `gemini_api_key` VARCHAR(255),
    `openai_api_key` VARCHAR(255),
    `model` VARCHAR(50) DEFAULT 'gemini-1.5-flash',
    `system_prompt` TEXT,
    `pharmacy_mode` TINYINT(1) DEFAULT 0 COMMENT 'เปิดโหมดเภสัชกร AI',
    `max_tokens` INT DEFAULT 1000,
    `temperature` DECIMAL(2,1) DEFAULT 0.7,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_ai_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Conversation History
CREATE TABLE IF NOT EXISTS `ai_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `line_account_id` INT DEFAULT NULL,
    `role` ENUM('user', 'assistant', 'system') NOT NULL,
    `content` TEXT NOT NULL,
    `tokens_used` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_conv_user` (`user_id`),
    INDEX `idx_ai_conv_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emergency Alerts (Red Flag Detection Logging)
-- Requirements: 3.4 - Log red flags to database for pharmacist review
CREATE TABLE IF NOT EXISTS `emergency_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `line_account_id` INT NULL,
    `message` TEXT COMMENT 'Original message that triggered the alert',
    `red_flags` JSON COMMENT 'Detected red flags array',
    `severity` ENUM('warning', 'high', 'critical') DEFAULT 'warning',
    `emergency_info` JSON COMMENT 'Additional emergency information',
    `status` ENUM('pending', 'reviewed', 'handled', 'dismissed') DEFAULT 'pending',
    `reviewed_by` INT NULL COMMENT 'Admin user who reviewed',
    `reviewed_at` TIMESTAMP NULL,
    `notes` TEXT COMMENT 'Pharmacist notes',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_line_account` (`line_account_id`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacist Notifications
-- Requirements: 3.4, 4.1, 4.2 - Notify pharmacist for critical red flags and escalations
CREATE TABLE IF NOT EXISTS `pharmacist_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NULL,
    `type` VARCHAR(50) DEFAULT 'emergency_alert' COMMENT 'emergency_alert, triage_alert, escalation',
    `title` VARCHAR(255),
    `message` TEXT,
    `notification_data` JSON,
    `reference_id` INT COMMENT 'ID of related record (emergency_alert, triage_session, etc)',
    `reference_type` VARCHAR(50) COMMENT 'Type of related record',
    `user_id` INT COMMENT 'User who triggered the notification',
    `triage_session_id` INT NULL,
    `priority` ENUM('normal', 'urgent') DEFAULT 'normal',
    `status` ENUM('pending', 'handled', 'dismissed') DEFAULT 'pending',
    `is_read` TINYINT(1) DEFAULT 0,
    `handled_by` INT NULL COMMENT 'Admin user who handled',
    `handled_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_line_account` (`line_account_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User States (Conversation Flow)
CREATE TABLE IF NOT EXISTS `user_states` (
    `user_id` INT PRIMARY KEY,
    `state` VARCHAR(50) DEFAULT NULL,
    `state_data` JSON,
    `ai_mode` VARCHAR(50) DEFAULT NULL COMMENT 'ai, mims, triage, human',
    `ai_mode_expires` DATETIME DEFAULT NULL,
    `expires_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- NOTIFICATION MODULE
-- =============================================

-- User Notification Preferences
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(50) NOT NULL,
    `line_account_id` INT DEFAULT 0,
    `promotions` TINYINT(1) DEFAULT 1 COMMENT 'Promotional messages',
    `order_updates` TINYINT(1) DEFAULT 1 COMMENT 'Order status updates',
    `medication_reminders` TINYINT(1) DEFAULT 1 COMMENT 'Medication reminders',
    `health_tips` TINYINT(1) DEFAULT 1 COMMENT 'Health tips and articles',
    `appointment_reminders` TINYINT(1) DEFAULT 1 COMMENT 'Appointment reminders',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user` (`line_user_id`, `line_account_id`),
    INDEX `idx_line_user` (`line_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Notification Settings (Alternative)
CREATE TABLE IF NOT EXISTS `user_notification_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `order_updates` TINYINT(1) DEFAULT 1,
    `promotions` TINYINT(1) DEFAULT 1,
    `appointment_reminders` TINYINT(1) DEFAULT 1,
    `drug_reminders` TINYINT(1) DEFAULT 1,
    `health_tips` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- SYSTEM MODULE
-- =============================================

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT,
    `type` VARCHAR(20) DEFAULT 'string',
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LIFF Apps
CREATE TABLE IF NOT EXISTS `liff_apps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `liff_id` VARCHAR(100) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `endpoint_url` VARCHAR(500),
    `view_type` ENUM('full', 'tall', 'compact') DEFAULT 'full',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_liff_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LIFF Message Logs
CREATE TABLE IF NOT EXISTS `liff_message_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(50) NOT NULL,
    `line_account_id` INT,
    `action_type` VARCHAR(50) NOT NULL COMMENT 'order_placed, consultation_request, etc.',
    `message_data` JSON,
    `sent_via` ENUM('liff', 'api') DEFAULT 'liff',
    `status` ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`line_user_id`),
    INDEX `idx_action` (`action_type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled Reports
CREATE TABLE IF NOT EXISTS `scheduled_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `report_type` VARCHAR(50) NOT NULL,
    `schedule` VARCHAR(50) NOT NULL COMMENT 'daily, weekly, monthly',
    `recipients` JSON,
    `parameters` JSON,
    `last_run` TIMESTAMP NULL,
    `next_run` TIMESTAMP NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_report_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Queue (CNY Integration)
CREATE TABLE IF NOT EXISTS `sync_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `sync_type` VARCHAR(50) NOT NULL COMMENT 'products, categories, orders',
    `direction` ENUM('push', 'pull') DEFAULT 'pull',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `data` JSON,
    `result` JSON,
    `error_message` TEXT,
    `attempts` INT DEFAULT 0,
    `scheduled_at` TIMESTAMP NULL,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sync_status` (`status`),
    INDEX `idx_sync_type` (`sync_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dev Logs
CREATE TABLE IF NOT EXISTS `dev_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `level` ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR') DEFAULT 'INFO',
    `source` VARCHAR(100),
    `type` VARCHAR(100),
    `message` TEXT,
    `data` JSON,
    `user_id` VARCHAR(100),
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_level` (`level`),
    INDEX `idx_log_source` (`source`),
    INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook Events (Deduplication)
CREATE TABLE IF NOT EXISTS `webhook_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_webhook_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Telegram Settings
CREATE TABLE IF NOT EXISTS `telegram_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 0,
    `bot_token` VARCHAR(255),
    `chat_id` VARCHAR(100),
    `notify_new_follower` TINYINT(1) DEFAULT 1,
    `notify_new_message` TINYINT(1) DEFAULT 1,
    `notify_new_order` TINYINT(1) DEFAULT 1,
    `notify_payment` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_telegram_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- DEFAULT DATA
-- =============================================

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('site_name', 'LINE CRM Pharmacy', 'string', 'ชื่อเว็บไซต์'),
('timezone', 'Asia/Bangkok', 'string', 'Timezone'),
('currency', 'THB', 'string', 'สกุลเงิน'),
('points_per_baht', '1', 'number', 'แต้มต่อบาท'),
('points_value', '0.1', 'number', 'มูลค่าแต้ม (บาท)'),
('min_redeem_points', '100', 'number', 'แต้มขั้นต่ำในการแลก')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Insert default categories
INSERT INTO `item_categories` (`name`, `name_en`, `sort_order`, `is_active`) VALUES
('ยาสามัญประจำบ้าน', 'OTC Medicines', 1, 1),
('วิตามินและอาหารเสริม', 'Vitamins & Supplements', 2, 1),
('เวชสำอาง', 'Cosmeceuticals', 3, 1),
('อุปกรณ์การแพทย์', 'Medical Devices', 4, 1),
('ผลิตภัณฑ์ดูแลสุขภาพ', 'Health Care Products', 5, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert default points settings
INSERT INTO `points_settings` (`line_account_id`, `points_per_baht`, `min_order_for_points`, `points_expiry_days`)
VALUES (NULL, 1.00, 100, 365)
ON DUPLICATE KEY UPDATE `points_per_baht` = VALUES(`points_per_baht`);

-- Insert default tiers
INSERT INTO `points_tiers` (`line_account_id`, `name`, `min_points`, `points_multiplier`, `color`, `icon`) VALUES
(NULL, 'Bronze', 0, 1.00, '#CD7F32', 'fa-medal'),
(NULL, 'Silver', 1000, 1.25, '#C0C0C0', 'fa-medal'),
(NULL, 'Gold', 5000, 1.50, '#FFD700', 'fa-crown'),
(NULL, 'Platinum', 15000, 2.00, '#E5E4E2', 'fa-gem')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert default rewards
INSERT INTO `point_rewards` (`id`, `name`, `description`, `points_required`, `type`, `value`, `is_active`) VALUES
(1, 'ส่วนลด 50 บาท', 'คูปองส่วนลด 50 บาท สำหรับการสั่งซื้อครั้งถัดไป', 100, 'discount', 50, 1),
(2, 'ส่วนลด 100 บาท', 'คูปองส่วนลด 100 บาท สำหรับการสั่งซื้อครั้งถัดไป', 200, 'discount', 100, 1),
(3, 'จัดส่งฟรี', 'ฟรีค่าจัดส่ง 1 ครั้ง', 150, 'shipping', 0, 1),
(4, 'ของขวัญพิเศษ', 'รับของขวัญพิเศษจากร้าน', 500, 'gift', 0, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- END OF SCHEMA
-- =============================================