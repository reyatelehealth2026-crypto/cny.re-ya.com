-- =====================================================
-- Complete Shop/Business Tables Migration
-- รันไฟล์นี้เพื่อสร้างตารางทั้งหมดที่เกี่ยวกับระบบร้านค้า
-- =====================================================

-- 1. Shop Settings - ตั้งค่าร้านค้า
CREATE TABLE IF NOT EXISTS shop_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    shop_name VARCHAR(255) DEFAULT 'LINE Shop',
    shop_logo VARCHAR(500),
    welcome_message TEXT,
    business_type ENUM('physical', 'digital', 'service', 'hybrid') DEFAULT 'hybrid',
    shipping_fee DECIMAL(10,2) DEFAULT 50.00,
    free_shipping_min DECIMAL(10,2) DEFAULT 500.00,
    bank_accounts JSON,
    promptpay_number VARCHAR(20),
    promptpay_name VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    contact_line VARCHAR(50),
    address TEXT,
    is_open TINYINT(1) DEFAULT 1,
    auto_confirm_payment TINYINT(1) DEFAULT 0,
    notify_telegram TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Product Categories - หมวดหมู่สินค้า
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products - สินค้า
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    cost DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(50),
    barcode VARCHAR(50),
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    max_quantity INT DEFAULT NULL,
    weight DECIMAL(10,2) DEFAULT NULL,
    image_url VARCHAR(500),
    images JSON,
    item_type ENUM('physical', 'digital', 'service', 'booking', 'content') DEFAULT 'physical',
    delivery_method ENUM('shipping', 'email', 'line', 'download', 'onsite') DEFAULT 'shipping',
    action_data JSON,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    view_count INT DEFAULT 0,
    sold_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account (line_account_id),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured),
    INDEX idx_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cart Items - ตะกร้าสินค้า
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    options JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders/Transactions - คำสั่งซื้อ
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    status ENUM('pending', 'confirmed', 'paid', 'processing', 'shipping', 'delivered', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    subtotal DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'THB',
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    customer_email VARCHAR(100),
    shipping_address TEXT,
    shipping_method VARCHAR(50),
    tracking_number VARCHAR(100),
    tracking_url VARCHAR(500),
    notes TEXT,
    admin_notes TEXT,
    slip_image VARCHAR(500),
    slip_verified_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order_number (order_number),
    INDEX idx_account (line_account_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment (payment_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Order Items - รายการสินค้าในคำสั่งซื้อ
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(50),
    product_image VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    options JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payment Slips - สลิปการชำระเงิน
CREATE TABLE IF NOT EXISTS payment_slips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    order_id INT,
    transaction_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    image_url VARCHAR(500) NOT NULL,
    amount DECIMAL(10,2),
    bank_name VARCHAR(100),
    transfer_date DATE,
    transfer_time TIME,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    reject_reason TEXT,
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. User States - สถานะผู้ใช้ (สำหรับ checkout flow)
CREATE TABLE IF NOT EXISTS user_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    state VARCHAR(50) NOT NULL,
    state_data JSON,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_state (user_id),
    INDEX idx_state (state),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. User Behaviors - พฤติกรรมผู้ใช้
CREATE TABLE IF NOT EXISTS user_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    behavior_type VARCHAR(50) NOT NULL,
    behavior_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (behavior_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Coupons - คูปองส่วนลด
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255),
    description TEXT,
    discount_type ENUM('fixed', 'percent') DEFAULT 'fixed',
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    user_limit INT DEFAULT 1,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_code (code),
    INDEX idx_account (line_account_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Coupon Usage - การใช้คูปอง
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    discount_amount DECIMAL(10,2),
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert default data
-- =====================================================

-- Default shop settings
INSERT IGNORE INTO shop_settings (id, shop_name, welcome_message, shipping_fee, free_shipping_min) 
VALUES (1, 'LINE Shop', 'ยินดีต้อนรับสู่ร้านค้าของเรา!', 50.00, 500.00);

-- Sample categories
INSERT IGNORE INTO product_categories (id, name, description, sort_order, is_active) VALUES
(1, 'สินค้าทั่วไป', 'สินค้าทั่วไปในร้าน', 1, 1),
(2, 'สินค้าแนะนำ', 'สินค้าแนะนำพิเศษ', 2, 1),
(3, 'โปรโมชั่น', 'สินค้าลดราคา', 3, 1);

-- =====================================================
-- Show created tables
-- =====================================================
SELECT 'Shop tables created successfully!' AS message;
