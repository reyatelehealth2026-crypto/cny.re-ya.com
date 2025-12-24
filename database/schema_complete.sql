-- =============================================
-- LINE CRM Pharmacy - Complete Database Schema
-- Version: 2.0
-- Generated: 2024-12-24
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

-- LINE Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `line_user_id` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(255),
    `picture_url` TEXT,
    `status_message` TEXT,
    `phone` VARCHAR(20),
    `email` VARCHAR(255),
    `address` TEXT,
    `is_blocked` TINYINT(1) DEFAULT 0,
    `is_registered` TINYINT(1) DEFAULT 0,
    `membership_level` VARCHAR(20) DEFAULT 'bronze',
    `loyalty_points` INT DEFAULT 0,
    `total_spent` DECIMAL(12,2) DEFAULT 0,
    `order_count` INT DEFAULT 0,
    `reply_token` VARCHAR(255),
    `reply_token_expires` DATETIME,
    `last_interaction` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_line_user` (`line_account_id`, `line_user_id`),
    INDEX `idx_line_account` (`line_account_id`),
    INDEX `idx_line_user_id` (`line_user_id`)
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

-- Transactions (Orders)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `shipping_fee` DECIMAL(10,2) DEFAULT 0,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `points_used` INT DEFAULT 0,
    `points_discount` DECIMAL(10,2) DEFAULT 0,
    `grand_total` DECIMAL(12,2) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'paid', 'preparing', 'shipping', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    `payment_method` VARCHAR(50),
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `shipping_name` VARCHAR(255),
    `shipping_phone` VARCHAR(20),
    `shipping_address` TEXT,
    `shipping_tracking` VARCHAR(100),
    `shipping_provider` VARCHAR(100),
    `note` TEXT,
    `admin_note` TEXT,
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
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    INDEX `idx_item_transaction` (`transaction_id`),
    INDEX `idx_item_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Slips
CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `transaction_id` INT,
    `user_id` INT,
    `image_url` VARCHAR(500) NOT NULL,
    `amount` DECIMAL(10,2),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_note` TEXT,
    `verified_by` INT,
    `verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slip_transaction` (`transaction_id`),
    INDEX `idx_slip_user` (`user_id`)
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


-- =============================================
-- CRM MODULE
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

-- Loyalty Points History
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `points` INT NOT NULL COMMENT 'บวก=ได้รับ, ลบ=ใช้',
    `type` ENUM('earn', 'redeem', 'expire', 'adjust', 'bonus') NOT NULL,
    `reference_type` VARCHAR(50) COMMENT 'order, reward, manual',
    `reference_id` INT,
    `description` TEXT,
    `balance_after` INT,
    `created_by` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_points_user` (`user_id`),
    INDEX `idx_points_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rewards Catalog
CREATE TABLE IF NOT EXISTS `rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(500),
    `points_required` INT NOT NULL,
    `reward_type` ENUM('discount', 'product', 'voucher', 'shipping') DEFAULT 'discount',
    `reward_value` DECIMAL(10,2),
    `stock` INT DEFAULT -1 COMMENT '-1 = unlimited',
    `is_active` TINYINT(1) DEFAULT 1,
    `start_date` DATE,
    `end_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reward_line_account` (`line_account_id`)
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

-- =============================================
-- APPOINTMENTS MODULE
-- =============================================

-- Appointments
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `line_account_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `pharmacist_id` INT,
    `appointment_type` ENUM('consultation', 'video_call', 'pickup', 'delivery') DEFAULT 'consultation',
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `duration_minutes` INT DEFAULT 30,
    `status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    `notes` TEXT,
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_appt_user` (`user_id`),
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
    `status` ENUM('waiting', 'active', 'ended', 'missed') DEFAULT 'waiting',
    `started_at` TIMESTAMP NULL,
    `ended_at` TIMESTAMP NULL,
    `duration_seconds` INT,
    `notes` TEXT,
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

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- END OF SCHEMA
-- =============================================
