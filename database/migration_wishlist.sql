-- Wishlist System Migration
-- ระบบรายการโปรดและแจ้งเตือนเมื่อลดราคา

-- ตารางรายการโปรด
CREATE TABLE IF NOT EXISTS user_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50),
    product_id INT NOT NULL,
    line_account_id INT,
    price_when_added DECIMAL(10,2) DEFAULT 0 COMMENT 'ราคาตอนที่เพิ่ม',
    notify_on_sale TINYINT(1) DEFAULT 1 COMMENT 'แจ้งเตือนเมื่อลดราคา',
    notify_on_restock TINYINT(1) DEFAULT 0 COMMENT 'แจ้งเตือนเมื่อมีสินค้า',
    notified_at TIMESTAMP NULL COMMENT 'แจ้งเตือนล่าสุดเมื่อ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_product (product_id),
    INDEX idx_notify (notify_on_sale, notified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางประวัติการแจ้งเตือน
CREATE TABLE IF NOT EXISTS wishlist_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wishlist_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    notification_type ENUM('price_drop', 'promotion', 'restock') NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    discount_percent INT,
    message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wishlist (wishlist_id),
    INDEX idx_user (user_id),
    INDEX idx_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่ม column ใน products สำหรับ track ราคาเดิม
ALTER TABLE products ADD COLUMN IF NOT EXISTS previous_price DECIMAL(10,2) NULL AFTER sale_price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS price_changed_at TIMESTAMP NULL AFTER previous_price;
