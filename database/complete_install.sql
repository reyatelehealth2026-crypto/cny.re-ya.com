-- =============================================
-- LINE OA Manager - Complete Installation SQL
-- Version: 3.0
-- Date: 2025-12-14
-- 
-- คำแนะนำ:
-- 1. สร้าง Database ก่อน: CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Import ไฟล์นี้ผ่าน phpMyAdmin หรือ command line
-- 3. แก้ไข config/config.php ให้ตรงกับ database
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =============================================
-- 1. CORE TABLES - ตารางหลัก
-- =============================================

-- LINE Accounts (หลายบัญชี LINE OA)
CREATE TABLE IF NOT EXISTS `line_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'ชื่อบัญชี LINE OA',
    `channel_id` VARCHAR(100) COMMENT 'Channel ID',
    `channel_secret` VARCHAR(100) NOT NULL COMMENT 'Channel Secret',
    `channel_access_token` TEXT NOT NULL COMMENT 'Channel Access Token',
    `webhook_url` VARCHAR(500) COMMENT 'Webhook URL',
    `basic_id` VARCHAR(50) COMMENT 'LINE Basic ID (@xxx)',
    `picture_url` VARCHAR(500) COMMENT 'รูปโปรไฟล์',
    `is_active` TINYINT(1) DEFAULT 1,
    `is_default` TINYINT(1) DEFAULT 0 COMMENT 'บัญชีหลัก',
    `settings` JSON COMMENT 'ตั้งค่าเพิ่มเติม',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_channel_secret` (`channel_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin/User accounts for login
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255),
    `avatar_url` VARCHAR(500),
    `role` ENUM('admin', 'user') DEFAULT 'user' COMMENT 'admin=จัดการทุกอย่าง, user=ใช้งานได้แค่ 1 LINE Account',
    `line_account_id` INT DEFAULT NULL COMMENT 'สำหรับ role=user ใช้ได้แค่ 1 บัญชี',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_users_role` (`role`),
    INDEX `idx_admin_users_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users (LINE Users/Customers)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(255),
    `picture_url` TEXT,
    `status_message` TEXT,
    `real_name` VARCHAR(255) NULL COMMENT 'ชื่อจริง',
    `phone` VARCHAR(20) NULL COMMENT 'เบอร์โทร',
    `email` VARCHAR(255) NULL COMMENT 'อีเมล',
    `birthday` DATE NULL COMMENT 'วันเกิด',
    `address` TEXT NULL COMMENT 'ที่อยู่',
    `province` VARCHAR(100) NULL COMMENT 'จังหวัด',
    `postal_code` VARCHAR(10) NULL COMMENT 'รหัสไปรษณีย์',
    `note` TEXT NULL COMMENT 'หมายเหตุ',
    `total_orders` INT DEFAULT 0 COMMENT 'จำนวนออเดอร์ทั้งหมด',
    `total_spent` DECIMAL(12,2) DEFAULT 0 COMMENT 'ยอดซื้อรวม',
    `last_order_at` TIMESTAMP NULL,
    `last_message_at` TIMESTAMP NULL,
    `customer_score` INT DEFAULT 0 COMMENT 'คะแนนลูกค้า 0-100',
    `is_blocked` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_line_user` (`line_account_id`, `line_user_id`),
    INDEX `idx_line_account` (`line_account_id`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_email` (`email`),
    INDEX `idx_birthday` (`birthday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups (User Groups/Tags - Legacy)
CREATE TABLE IF NOT EXISTS `groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `color` VARCHAR(7) DEFAULT '#3B82F6',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_group_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User-Group Relationship
CREATE TABLE IF NOT EXISTS `user_groups` (
    `user_id` INT,
    `group_id` INT,
    PRIMARY KEY (`user_id`, `group_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_msg_line_account` (`line_account_id`),
    INDEX `idx_msg_user` (`user_id`),
    INDEX `idx_msg_created` (`created_at`),
    INDEX `idx_is_read` (`is_read`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-Reply Rules
CREATE TABLE IF NOT EXISTS `auto_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `keyword` VARCHAR(255) NOT NULL,
    `match_type` ENUM('exact', 'contains', 'starts_with', 'regex') DEFAULT 'contains',
    `reply_type` VARCHAR(50) DEFAULT 'text',
    `reply_content` TEXT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reply_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcasts
CREATE TABLE IF NOT EXISTS `broadcasts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT NOT NULL,
    `target_type` ENUM('all', 'group', 'tag', 'segment') DEFAULT 'all',
    `target_group_id` INT NULL,
    `sent_count` INT DEFAULT 0,
    `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    `scheduled_at` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_broadcast_line_account` (`line_account_id`),
    INDEX `idx_broadcast_status` (`status`)
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_richmenu_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates
CREATE TABLE IF NOT EXISTS `templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100),
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled Messages
CREATE TABLE IF NOT EXISTS `scheduled_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT NOT NULL,
    `target_type` ENUM('all', 'group', 'user') DEFAULT 'all',
    `target_id` INT NULL,
    `scheduled_at` TIMESTAMP NOT NULL,
    `repeat_type` ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
    `status` ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_scheduled_line_account` (`line_account_id`),
    INDEX `idx_scheduled_status` (`status`)
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
    INDEX `idx_welcome_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Settings
CREATE TABLE IF NOT EXISTS `ai_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 0,
    `system_prompt` TEXT,
    `model` VARCHAR(50) DEFAULT 'gpt-3.5-turbo',
    `max_tokens` INT DEFAULT 500,
    `temperature` DECIMAL(2,1) DEFAULT 0.7,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics Events
CREATE TABLE IF NOT EXISTS `analytics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `event_data` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_analytics_line_account` (`line_account_id`),
    INDEX `idx_analytics_created` (`created_at`)
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
    `notify_unfollow` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- 2. SHOP MODULE - ระบบร้านค้า
-- =============================================

-- Product Categories
CREATE TABLE IF NOT EXISTS `product_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(500),
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cat_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `category_id` INT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `sale_price` DECIMAL(10,2) NULL,
    `image_url` VARCHAR(500),
    `stock` INT DEFAULT 0,
    `sku` VARCHAR(100),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `product_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_product_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Images
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shopping Cart
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `shipping_fee` DECIMAL(10,2) DEFAULT 0,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `grand_total` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'paid', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    `payment_method` VARCHAR(50),
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `shipping_name` VARCHAR(255),
    `shipping_phone` VARCHAR(20),
    `shipping_address` TEXT,
    `shipping_tracking` VARCHAR(100),
    `note` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_line_account` (`line_account_id`),
    INDEX `idx_order_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT,
    `product_name` VARCHAR(255) NOT NULL,
    `product_price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Slips
CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `amount` DECIMAL(10,2),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_note` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shop Settings
CREATE TABLE IF NOT EXISTS `shop_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `shop_name` VARCHAR(255) DEFAULT 'LINE Shop',
    `shop_logo` VARCHAR(500),
    `welcome_message` TEXT,
    `shipping_fee` DECIMAL(10,2) DEFAULT 50,
    `free_shipping_min` DECIMAL(10,2) DEFAULT 500,
    `bank_accounts` TEXT,
    `promptpay_number` VARCHAR(20),
    `contact_phone` VARCHAR(20),
    `is_open` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_shop_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User States (for conversation flow)
CREATE TABLE IF NOT EXISTS `user_states` (
    `user_id` INT PRIMARY KEY,
    `state` VARCHAR(50) DEFAULT NULL,
    `state_data` JSON,
    `expires_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook Events (deduplication)
CREATE TABLE IF NOT EXISTS `webhook_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_webhook_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. CRM MODULE - ระบบ CRM
-- =============================================

-- User Tags
CREATE TABLE IF NOT EXISTS `user_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#3B82F6',
    `description` TEXT,
    `tag_type` ENUM('manual', 'auto', 'system', 'broadcast') DEFAULT 'manual',
    `auto_assign_rules` JSON COMMENT 'เงื่อนไขการติด Tag อัตโนมัติ',
    `auto_remove_rules` JSON COMMENT 'เงื่อนไขการถอด Tag อัตโนมัติ',
    `source_type` ENUM('manual', 'auto', 'broadcast', 'system') DEFAULT 'manual',
    `source_id` INT DEFAULT NULL,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_account_tag` (`line_account_id`),
    UNIQUE KEY `unique_tag_name` (`line_account_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Tag Assignments
CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `assigned_by` VARCHAR(50) DEFAULT 'manual' COMMENT 'manual, auto, system, campaign',
    `assigned_reason` TEXT,
    `score` INT DEFAULT 0 COMMENT 'คะแนนความสนใจ',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL COMMENT 'Tag หมดอายุเมื่อไหร่',
    UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
    INDEX `idx_tag` (`tag_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `user_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto Tag Rules
CREATE TABLE IF NOT EXISTS `auto_tag_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `tag_id` INT NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `trigger_type` ENUM('follow', 'message', 'purchase', 'inactivity', 'birthday', 'order_count', 'total_spent', 'custom') NOT NULL,
    `conditions` JSON NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tag_id`) REFERENCES `user_tags`(`id`) ON DELETE CASCADE,
    INDEX `idx_trigger` (`trigger_type`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto Tag Logs
CREATE TABLE IF NOT EXISTS `auto_tag_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `rule_id` INT DEFAULT NULL,
    `action` ENUM('assign', 'remove') NOT NULL,
    `trigger_type` VARCHAR(50),
    `trigger_data` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_tag` (`tag_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Segments
CREATE TABLE IF NOT EXISTS `customer_segments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `segment_type` ENUM('static', 'dynamic') DEFAULT 'dynamic',
    `conditions` JSON NOT NULL,
    `user_count` INT DEFAULT 0,
    `last_calculated_at` TIMESTAMP NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Segment Members
CREATE TABLE IF NOT EXISTS `segment_members` (
    `segment_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `score` DECIMAL(10,2) DEFAULT 0,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`segment_id`, `user_id`),
    FOREIGN KEY (`segment_id`) REFERENCES `customer_segments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link Tracking
CREATE TABLE IF NOT EXISTS `tracked_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `short_code` VARCHAR(20) UNIQUE NOT NULL,
    `original_url` TEXT NOT NULL,
    `title` VARCHAR(255),
    `campaign_id` INT NULL,
    `auto_tag_id` INT NULL,
    `click_count` INT DEFAULT 0,
    `unique_clicks` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_short_code` (`short_code`),
    INDEX `idx_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link Clicks
CREATE TABLE IF NOT EXISTS `link_clicks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `link_id` INT NOT NULL,
    `user_id` INT,
    `line_user_id` VARCHAR(50),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `referer` TEXT,
    `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_link` (`link_id`),
    INDEX `idx_user` (`user_id`),
    FOREIGN KEY (`link_id`) REFERENCES `tracked_links`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Behaviors
CREATE TABLE IF NOT EXISTS `user_behaviors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT,
    `user_id` INT NOT NULL,
    `behavior_type` VARCHAR(50) NOT NULL,
    `behavior_category` VARCHAR(100),
    `behavior_data` JSON,
    `source` VARCHAR(50),
    `session_id` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_behavior` (`user_id`, `behavior_type`),
    INDEX `idx_account_behavior` (`line_account_id`, `behavior_type`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaigns
CREATE TABLE IF NOT EXISTS `drip_campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `trigger_type` ENUM('follow', 'tag_added', 'purchase', 'manual') DEFAULT 'follow',
    `trigger_tag_id` INT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `total_enrolled` INT DEFAULT 0,
    `total_completed` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_drip_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Steps
CREATE TABLE IF NOT EXISTS `drip_campaign_steps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `step_order` INT NOT NULL,
    `delay_minutes` INT DEFAULT 0,
    `message_type` ENUM('text', 'flex', 'image') DEFAULT 'text',
    `message_content` TEXT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `drip_campaigns`(`id`) ON DELETE CASCADE,
    INDEX `idx_campaign_step` (`campaign_id`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Progress
CREATE TABLE IF NOT EXISTS `drip_campaign_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `current_step` INT DEFAULT 0,
    `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    `next_send_at` TIMESTAMP NULL,
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`campaign_id`) REFERENCES `drip_campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_user` (`campaign_id`, `user_id`),
    INDEX `idx_next_send` (`next_send_at`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- 4. BROADCAST TRACKING MODULE
-- =============================================

-- Broadcast Campaigns
CREATE TABLE IF NOT EXISTS `broadcast_campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `message_type` ENUM('text', 'flex', 'image', 'product_carousel') DEFAULT 'text',
    `content` LONGTEXT,
    `auto_tag_enabled` TINYINT(1) DEFAULT 0,
    `tag_prefix` VARCHAR(50) DEFAULT NULL,
    `sent_count` INT DEFAULT 0,
    `click_count` INT DEFAULT 0,
    `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    `scheduled_at` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_account` (`line_account_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcast Items
CREATE TABLE IF NOT EXISTS `broadcast_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `broadcast_id` INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `item_image` VARCHAR(500),
    `item_price` DECIMAL(10,2) DEFAULT 0,
    `postback_data` VARCHAR(255) NOT NULL,
    `tag_id` INT DEFAULT NULL,
    `click_count` INT DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`broadcast_id`) REFERENCES `broadcast_campaigns`(`id`) ON DELETE CASCADE,
    INDEX `idx_broadcast` (`broadcast_id`),
    INDEX `idx_postback` (`postback_data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcast Clicks
CREATE TABLE IF NOT EXISTS `broadcast_clicks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `broadcast_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `line_user_id` VARCHAR(50),
    `tag_assigned` TINYINT(1) DEFAULT 0,
    `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`broadcast_id`) REFERENCES `broadcast_campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `broadcast_items`(`id`) ON DELETE CASCADE,
    INDEX `idx_broadcast` (`broadcast_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_clicked` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. ACCOUNT EVENTS & ANALYTICS
-- =============================================

-- Account Events
CREATE TABLE IF NOT EXISTS `account_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `event_data` JSON,
    `webhook_event_id` VARCHAR(100),
    `source_type` VARCHAR(20) DEFAULT 'user',
    `source_id` VARCHAR(50),
    `reply_token` VARCHAR(255),
    `timestamp` BIGINT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_account_events_account` (`line_account_id`),
    INDEX `idx_account_events_user` (`line_user_id`),
    INDEX `idx_account_events_type` (`event_type`),
    INDEX `idx_account_events_created` (`created_at`),
    INDEX `idx_account_events_webhook` (`webhook_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account Followers
CREATE TABLE IF NOT EXISTS `account_followers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `display_name` VARCHAR(255),
    `picture_url` TEXT,
    `status_message` TEXT,
    `is_following` TINYINT(1) DEFAULT 1,
    `followed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `unfollowed_at` TIMESTAMP NULL,
    `follow_count` INT DEFAULT 1,
    `last_interaction_at` TIMESTAMP NULL,
    `total_messages` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_account_follower` (`line_account_id`, `line_user_id`),
    INDEX `idx_followers_account` (`line_account_id`),
    INDEX `idx_followers_user` (`line_user_id`),
    INDEX `idx_followers_following` (`is_following`),
    INDEX `idx_followers_date` (`followed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account Daily Stats
CREATE TABLE IF NOT EXISTS `account_daily_stats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `stat_date` DATE NOT NULL,
    `new_followers` INT DEFAULT 0,
    `unfollowers` INT DEFAULT 0,
    `total_messages` INT DEFAULT 0,
    `incoming_messages` INT DEFAULT 0,
    `outgoing_messages` INT DEFAULT 0,
    `unique_users` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_account_date` (`line_account_id`, `stat_date`),
    INDEX `idx_stats_account` (`line_account_id`),
    INDEX `idx_stats_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. LINE GROUPS MODULE
-- =============================================

-- LINE Groups
CREATE TABLE IF NOT EXISTS `line_groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT NOT NULL,
    `group_id` VARCHAR(50) NOT NULL,
    `group_type` ENUM('group', 'room') DEFAULT 'group',
    `group_name` VARCHAR(255),
    `picture_url` TEXT,
    `member_count` INT DEFAULT 0,
    `invited_by` VARCHAR(50),
    `invited_by_name` VARCHAR(255),
    `is_active` TINYINT(1) DEFAULT 1,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `left_at` TIMESTAMP NULL,
    `last_activity_at` TIMESTAMP NULL,
    `total_messages` INT DEFAULT 0,
    `settings` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_account_group` (`line_account_id`, `group_id`),
    INDEX `idx_groups_account` (`line_account_id`),
    INDEX `idx_groups_active` (`is_active`),
    INDEX `idx_groups_type` (`group_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LINE Group Members
CREATE TABLE IF NOT EXISTS `line_group_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT NOT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `display_name` VARCHAR(255),
    `picture_url` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `left_at` TIMESTAMP NULL,
    `total_messages` INT DEFAULT 0,
    `last_message_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_group_member` (`group_id`, `line_user_id`),
    INDEX `idx_members_group` (`group_id`),
    INDEX `idx_members_user` (`line_user_id`),
    INDEX `idx_members_active` (`is_active`),
    FOREIGN KEY (`group_id`) REFERENCES `line_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LINE Group Messages
CREATE TABLE IF NOT EXISTS `line_group_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT NOT NULL,
    `line_user_id` VARCHAR(50),
    `message_type` VARCHAR(50) DEFAULT 'text',
    `content` TEXT,
    `message_id` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gmsg_group` (`group_id`),
    INDEX `idx_gmsg_user` (`line_user_id`),
    INDEX `idx_gmsg_created` (`created_at`),
    FOREIGN KEY (`group_id`) REFERENCES `line_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. DEV & SYSTEM TABLES
-- =============================================

-- Dev Logs
CREATE TABLE IF NOT EXISTS `dev_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `log_type` ENUM('error', 'warning', 'info', 'debug', 'webhook') DEFAULT 'info',
    `source` VARCHAR(100),
    `message` TEXT,
    `data` JSON,
    `user_id` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`log_type`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shared Flex Messages
CREATE TABLE IF NOT EXISTS `shared_flex_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `share_code` VARCHAR(20) UNIQUE NOT NULL,
    `title` VARCHAR(255),
    `flex_content` JSON NOT NULL,
    `view_count` INT DEFAULT 0,
    `share_count` INT DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_share_code` (`share_code`),
    INDEX `idx_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. DEFAULT DATA
-- =============================================

-- Default Admin User (password: admin123)
INSERT INTO `admin_users` (`username`, `email`, `password`, `display_name`, `role`) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default AI Settings
INSERT INTO `ai_settings` (`system_prompt`) VALUES 
('คุณเป็นผู้ช่วยที่เป็นมิตรและช่วยเหลือลูกค้า ตอบคำถามอย่างสุภาพและกระชับ')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Telegram Settings
INSERT INTO `telegram_settings` (`is_enabled`) VALUES (0)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Welcome Settings
INSERT INTO `welcome_settings` (`is_enabled`, `message_type`, `text_content`) VALUES 
(1, 'text', 'สวัสดีค่ะ ยินดีต้อนรับ! 🎉\n\nขอบคุณที่เพิ่มเราเป็นเพื่อน\nหากต้องการความช่วยเหลือ สามารถพิมพ์ข้อความมาได้เลยค่ะ')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Shop Settings
INSERT INTO `shop_settings` (`shop_name`, `welcome_message`, `bank_accounts`) VALUES 
('LINE Shop', 'ยินดีต้อนรับสู่ร้านค้าของเรา!', '{"banks":[{"name":"กสิกรไทย","account":"xxx-x-xxxxx-x","holder":"ชื่อบัญชี"}]}')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Product Categories
INSERT INTO `product_categories` (`name`, `description`, `sort_order`) VALUES
('สินค้าแนะนำ', 'สินค้าขายดีและแนะนำ', 1),
('สินค้าใหม่', 'สินค้ามาใหม่', 2),
('โปรโมชั่น', 'สินค้าลดราคา', 3)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default User Tags
INSERT INTO `user_tags` (`name`, `color`, `description`, `tag_type`, `auto_assign_rules`) VALUES
('New Customer', '#10B981', 'ลูกค้าใหม่ที่เพิ่งเพิ่มเพื่อน', 'system', '{"trigger": "follow"}'),
('VIP', '#F59E0B', 'ลูกค้า VIP ซื้อ 5 ครั้งขึ้นไป', 'auto', '{"condition": "purchase_count >= 5"}'),
('Inactive', '#6B7280', 'ไม่มี activity 30 วัน', 'auto', '{"condition": "inactive_days >= 30"}'),
('High Spender', '#8B5CF6', 'ยอดซื้อรวม 10,000+ บาท', 'auto', '{"condition": "lifetime_value >= 10000"}'),
('Engaged', '#3B82F6', 'มี engagement สูง', 'auto', '{"condition": "engagement_score >= 70"}'),
('Birthday This Month', '#EC4899', 'วันเกิดเดือนนี้', 'system', '{"trigger": "birthday_month"}')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Customer Segments
INSERT INTO `customer_segments` (`name`, `description`, `segment_type`, `conditions`) VALUES
('Active Customers', 'ลูกค้าที่มี activity ใน 7 วันที่ผ่านมา', 'dynamic', '{"last_activity_days": {"<=": 7}}'),
('High Value Customers', 'ลูกค้าที่มียอดซื้อรวม 5,000+ บาท', 'dynamic', '{"lifetime_value": {">=": 5000}}'),
('At Risk', 'ลูกค้าที่เสี่ยงจะหายไป (ไม่มี activity 14-30 วัน)', 'dynamic', '{"last_activity_days": {">=": 14, "<=": 30}}'),
('New This Week', 'ลูกค้าใหม่ในสัปดาห์นี้', 'dynamic', '{"created_days": {"<=": 7}}'),
('Repeat Buyers', 'ลูกค้าที่ซื้อซ้ำ 2 ครั้งขึ้นไป', 'dynamic', '{"purchase_count": {">=": 2}}')
ON DUPLICATE KEY UPDATE `id`=`id`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- INSTALLATION COMPLETE!
-- 
-- Next Steps:
-- 1. แก้ไข config/config.php ให้ตรงกับ database
-- 2. เข้าสู่ระบบด้วย admin / admin123
-- 3. เพิ่ม LINE Account ที่เมนู LINE Accounts
-- 4. ตั้งค่า Webhook URL ใน LINE Developers Console
-- =============================================
