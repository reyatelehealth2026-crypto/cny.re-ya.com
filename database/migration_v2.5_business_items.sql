-- =============================================
-- LINE OA Manager V2.5 Migration
-- Phase 1: Core Refactoring - Business Items
-- สำหรับฐานข้อมูลที่ยังไม่มี Shop Module
-- =============================================

-- =============================================
-- 1.0 Business Items (สินค้า/บริการ)
-- =============================================
CREATE TABLE IF NOT EXISTS business_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    item_type ENUM('physical', 'digital', 'service', 'booking', 'content') DEFAULT 'physical',
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    image_url VARCHAR(500),
    action_data JSON DEFAULT NULL COMMENT 'ข้อมูลเฉพาะประเภท: game_code, download_url, etc.',
    delivery_method ENUM('shipping', 'email', 'line', 'download', 'onsite') DEFAULT 'shipping',
    validity_days INT DEFAULT NULL COMMENT 'อายุการใช้งาน (สำหรับ digital/service)',
    max_quantity INT DEFAULT NULL COMMENT 'จำนวนสูงสุดต่อออเดอร์',
    stock INT DEFAULT 0,
    sku VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_item_type (item_type),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 1.1 Item Categories
-- =============================================
CREATE TABLE IF NOT EXISTS item_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    category_type ENUM('physical', 'digital', 'service', 'booking', 'content', 'mixed') DEFAULT 'mixed',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 1.2 Item Images
-- =============================================
CREATE TABLE IF NOT EXISTS item_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- 2.0 Transactions (คำสั่งซื้อ/การจอง)
-- =============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    transaction_type ENUM('purchase', 'booking', 'subscription', 'redemption') DEFAULT 'purchase',
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'paid', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    fulfillment_status ENUM('pending', 'processing', 'fulfilled', 'failed') DEFAULT 'pending',
    fulfilled_at TIMESTAMP NULL,
    fulfillment_data JSON DEFAULT NULL,
    delivery_info JSON DEFAULT NULL COMMENT 'ที่อยู่/Email/ข้อมูลจัดส่ง',
    shipping_name VARCHAR(255),
    shipping_phone VARCHAR(20),
    shipping_address TEXT,
    shipping_tracking VARCHAR(100),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.1 Transaction Items
-- =============================================
CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(20) DEFAULT 'physical',
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    item_data JSON DEFAULT NULL COMMENT 'ข้อมูลที่ส่งมอบ: code, link',
    delivered_at TIMESTAMP NULL,
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.2 Payment Proofs (สลิป)
-- =============================================
CREATE TABLE IF NOT EXISTS payment_proofs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    amount DECIMAL(10,2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.3 Cart Items
-- =============================================
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.4 Business Settings
-- =============================================
CREATE TABLE IF NOT EXISTS business_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_type ENUM('retail', 'digital', 'service', 'hybrid') DEFAULT 'hybrid',
    shop_name VARCHAR(255) DEFAULT 'LINE Business',
    shop_logo VARCHAR(500),
    welcome_message TEXT,
    shipping_fee DECIMAL(10,2) DEFAULT 50,
    free_shipping_min DECIMAL(10,2) DEFAULT 500,
    bank_accounts JSON DEFAULT NULL,
    promptpay_number VARCHAR(20),
    digital_settings JSON DEFAULT NULL,
    service_settings JSON DEFAULT NULL,
    contact_phone VARCHAR(20),
    is_open TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO business_settings (shop_name, welcome_message, bank_accounts) VALUES 
('LINE Business', 'ยินดีต้อนรับ!', '{"banks":[]}')
ON DUPLICATE KEY UPDATE id=id;


-- =============================================
-- 3.0 CRM Tables - User Tags & Behavior
-- =============================================

-- User Tags
CREATE TABLE IF NOT EXISTS user_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    description TEXT,
    auto_assign_rules JSON DEFAULT NULL COMMENT 'Rules for auto-tagging',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User-Tag Relationship
CREATE TABLE IF NOT EXISTS user_tag_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tag_id INT NOT NULL,
    assigned_by ENUM('manual', 'auto', 'behavior') DEFAULT 'manual',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tag (user_id, tag_id),
    INDEX idx_user (user_id),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Behavior Tracking
CREATE TABLE IF NOT EXISTS user_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL COMMENT 'menu_click, keyword, purchase, view_item',
    behavior_data JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (behavior_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rich Menu Assignments (for personalization)
CREATE TABLE IF NOT EXISTS user_rich_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL,
    user_id INT NOT NULL,
    rich_menu_id INT NOT NULL,
    line_rich_menu_id VARCHAR(100) NOT NULL,
    assigned_reason VARCHAR(100) DEFAULT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_menu (line_account_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4.0 Marketing Automation Tables
-- =============================================

-- Drip Campaigns
CREATE TABLE IF NOT EXISTS drip_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    trigger_type ENUM('follow', 'tag_added', 'purchase', 'no_purchase', 'inactivity') NOT NULL,
    trigger_config JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_line_account (line_account_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign Steps
CREATE TABLE IF NOT EXISTS drip_campaign_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    step_order INT NOT NULL,
    delay_minutes INT DEFAULT 0 COMMENT 'Delay from previous step',
    message_type VARCHAR(50) DEFAULT 'text',
    message_content TEXT NOT NULL,
    condition_rules JSON DEFAULT NULL COMMENT 'Conditions to send this step',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drip Campaign User Progress
CREATE TABLE IF NOT EXISTS drip_campaign_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    current_step INT DEFAULT 0,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    next_send_at TIMESTAMP NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY unique_campaign_user (campaign_id, user_id),
    INDEX idx_next_send (next_send_at, status),
    INDEX idx_campaign (campaign_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcast Queue (for large broadcasts)
CREATE TABLE IF NOT EXISTS broadcast_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_broadcast (broadcast_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5.0 Insert Default Tags
-- =============================================
INSERT INTO user_tags (name, color, description, auto_assign_rules) VALUES
('New Customer', '#10B981', 'ลูกค้าใหม่', '{"trigger": "follow", "remove_after_days": 30}'),
('VIP', '#F59E0B', 'ลูกค้า VIP', '{"trigger": "purchase_count", "min_count": 5}'),
('Inactive', '#EF4444', 'ไม่มี interaction 30 วัน', '{"trigger": "inactivity", "days": 30}'),
('Gamer', '#8B5CF6', 'สนใจเกม/ไอที', '{"trigger": "keyword", "keywords": ["เกม", "game", "โค้ด", "code"]}'),
('Shopper', '#3B82F6', 'ชอบช้อปปิ้ง', '{"trigger": "behavior", "action": "view_products"}')
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- 6.0 Sample Categories
-- =============================================
INSERT INTO item_categories (name, category_type, description, sort_order) VALUES
('สินค้าทั่วไป', 'physical', 'สินค้าจัดส่ง', 1),
('สินค้าดิจิทัล', 'digital', 'โค้ดเกม, คูปอง, E-Voucher', 2),
('บริการ', 'service', 'บริการต่างๆ', 3),
('จองคิว', 'booking', 'จองนัดหมาย', 4)
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- 7.0 Add is_read to messages (if not exists)
-- =============================================
-- Check and add is_read column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'messages' 
               AND COLUMN_NAME = 'is_read');

SET @query = IF(@exist = 0, 
    'ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER content',
    'SELECT "Column is_read already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
