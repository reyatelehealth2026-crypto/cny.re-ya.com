-- =====================================================
-- LINE CRM - Complete Migration SQL
-- รันไฟล์นี้เพื่อสร้างตารางทั้งหมดที่จำเป็น
-- =====================================================

-- =====================================================
-- 1. TAGS SYSTEM
-- =====================================================

-- Simple tags table
CREATE TABLE IF NOT EXISTS `tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `color` varchar(20) DEFAULT 'gray',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Complex user_tags with rules (for auto-tagging)
CREATE TABLE IF NOT EXISTS `user_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(50) NOT NULL,
    `color` varchar(20) DEFAULT 'gray',
    `description` text,
    `auto_assign` tinyint(1) DEFAULT 0,
    `conditions` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Junction table for user-tag assignments
CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    `assigned_by` varchar(50) DEFAULT 'manual',
    `assigned_reason` text,
    `score` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. LOYALTY POINTS SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_points` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `total_points` int(11) DEFAULT 0,
    `available_points` int(11) DEFAULT 0,
    `used_points` int(11) DEFAULT 0,
    `tier` varchar(20) DEFAULT 'member',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_account` (`user_id`, `line_account_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `points_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `points` int(11) NOT NULL,
    `type` enum('earn','use','expire','adjust') NOT NULL,
    `source` varchar(50) DEFAULT NULL,
    `source_id` int(11) DEFAULT NULL,
    `description` varchar(255) DEFAULT NULL,
    `balance_after` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rewards` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `points_required` int(11) NOT NULL,
    `stock` int(11) DEFAULT -1,
    `image_url` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reward_redemptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `reward_id` int(11) NOT NULL,
    `points_used` int(11) NOT NULL,
    `status` enum('pending','completed','cancelled') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. SHOP SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `business_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `image_url` varchar(500) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `business_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `name` varchar(200) NOT NULL,
    `description` text,
    `price` decimal(10,2) NOT NULL DEFAULT 0,
    `sale_price` decimal(10,2) DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `stock` int(11) DEFAULT -1,
    `sku` varchar(50) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shop_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `shop_name` varchar(100) DEFAULT 'LINE Shop',
    `shop_description` text,
    `shop_logo` varchar(500) DEFAULT NULL,
    `is_open` tinyint(1) DEFAULT 1,
    `shipping_fee` decimal(10,2) DEFAULT 0,
    `free_shipping_min` decimal(10,2) DEFAULT 0,
    `payment_methods` json DEFAULT NULL,
    `bank_accounts` json DEFAULT NULL,
    `checkout_fields` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. TRANSACTIONS SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `user_id` int(11) NOT NULL,
    `order_number` varchar(50) NOT NULL,
    `status` enum('pending','confirmed','paid','shipping','delivered','cancelled') DEFAULT 'pending',
    `subtotal` decimal(10,2) DEFAULT 0,
    `discount` decimal(10,2) DEFAULT 0,
    `shipping_fee` decimal(10,2) DEFAULT 0,
    `grand_total` decimal(10,2) DEFAULT 0,
    `points_earned` int(11) DEFAULT 0,
    `points_used` int(11) DEFAULT 0,
    `shipping_name` varchar(100) DEFAULT NULL,
    `shipping_phone` varchar(20) DEFAULT NULL,
    `shipping_address` text,
    `shipping_province` varchar(50) DEFAULT NULL,
    `shipping_postal_code` varchar(10) DEFAULT NULL,
    `tracking_number` varchar(100) DEFAULT NULL,
    `note` text,
    `admin_note` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_order_number` (`order_number`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `product_name` varchar(200) DEFAULT NULL,
    `product_image` varchar(500) DEFAULT NULL,
    `price` decimal(10,2) DEFAULT 0,
    `quantity` int(11) DEFAULT 1,
    `total` decimal(10,2) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `slip_url` varchar(500) DEFAULT NULL,
    `amount` decimal(10,2) DEFAULT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `admin_note` text,
    `verified_at` timestamp NULL DEFAULT NULL,
    `verified_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. RICH MENU SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `rich_menus` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `line_rich_menu_id` varchar(100) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `chat_bar_text` varchar(50) DEFAULT 'เมนู',
    `size_width` int(11) DEFAULT 2500,
    `size_height` int(11) DEFAULT 1686,
    `areas` json DEFAULT NULL,
    `image_path` varchar(255) DEFAULT NULL,
    `is_default` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. BROADCAST SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `broadcasts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `message_type` varchar(50) DEFAULT 'text',
    `message_content` text,
    `flex_content` json DEFAULT NULL,
    `target_type` enum('all','segment','tags') DEFAULT 'all',
    `target_tags` json DEFAULT NULL,
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `status` enum('draft','scheduled','sending','sent','failed') DEFAULT 'draft',
    `total_recipients` int(11) DEFAULT 0,
    `success_count` int(11) DEFAULT 0,
    `fail_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `broadcast_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `broadcast_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `action_type` varchar(50) DEFAULT NULL,
    `action_data` text,
    `clicked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_broadcast_id` (`broadcast_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. DRIP CAMPAIGNS
-- =====================================================

CREATE TABLE IF NOT EXISTS `drip_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `trigger_type` enum('new_follower','tag_added','purchase','manual') DEFAULT 'new_follower',
    `trigger_tag_id` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `drip_campaign_steps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `step_order` int(11) DEFAULT 1,
    `delay_minutes` int(11) DEFAULT 0,
    `message_type` varchar(50) DEFAULT 'text',
    `message_content` text,
    `flex_content` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `drip_campaign_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `step_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `scheduled_at` timestamp NOT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `status` enum('pending','sent','failed','cancelled') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_scheduled` (`scheduled_at`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. AUTO REPLY / BOT RULES
-- =====================================================

CREATE TABLE IF NOT EXISTS `auto_replies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `keyword` varchar(255) NOT NULL,
    `match_type` enum('exact','contains','starts','regex') DEFAULT 'contains',
    `reply_type` varchar(50) DEFAULT 'text',
    `reply_content` text,
    `flex_content` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `priority` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. USER STATES (for conversation flow)
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_states` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `state` varchar(50) DEFAULT 'idle',
    `state_data` json DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_account` (`user_id`, `line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 10. LINK TRACKING
-- =====================================================

CREATE TABLE IF NOT EXISTS `tracked_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `short_code` varchar(20) NOT NULL,
    `original_url` text NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `click_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_short_code` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `link_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `link_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `clicked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_link_id` (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 11. ADD MISSING COLUMNS TO USERS TABLE
-- =====================================================

-- These will fail silently if columns already exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS `real_name` VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `birthday` DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `address` TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `province` VARCHAR(50) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(10) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `note` TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `total_spent` DECIMAL(12,2) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS `order_count` INT DEFAULT 0;

-- =====================================================
-- 12. DEFAULT DATA
-- =====================================================

-- Default tags
INSERT IGNORE INTO `tags` (`id`, `name`, `color`) VALUES 
(1, 'ลูกค้าใหม่', 'green'),
(2, 'รอชำระเงิน', 'yellow'),
(3, 'VIP', 'red'),
(4, 'ส่งแล้ว', 'blue'),
(5, 'ลูกค้าประจำ', 'purple');

-- Default shop settings
INSERT INTO `shop_settings` (`shop_name`, `is_open`, `shipping_fee`, `free_shipping_min`) 
SELECT 'LINE Shop', 1, 50, 500 
WHERE NOT EXISTS (SELECT 1 FROM shop_settings LIMIT 1);

-- =====================================================
-- END OF MIGRATION
-- =====================================================
