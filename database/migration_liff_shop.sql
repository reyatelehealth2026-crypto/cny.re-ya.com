    -- Migration: LIFF Shop Tables
    -- สร้างตารางสำหรับระบบร้านค้า LIFF

    -- 1. business_categories - หมวดหมู่สินค้า
    CREATE TABLE IF NOT EXISTS business_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_line_account (line_account_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 2. business_items - สินค้า (ถ้ายังไม่มี)
    CREATE TABLE IF NOT EXISTS business_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        category_id INT DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        sale_price DECIMAL(10,2) DEFAULT NULL,
        image_url VARCHAR(500),
        stock INT DEFAULT 999,
        item_type ENUM('physical', 'digital', 'service', 'booking') DEFAULT 'physical',
        action_data JSON,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_line_account (line_account_id),
        INDEX idx_category (category_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 3. cart_items - ตะกร้าสินค้า (ถ้ายังไม่มี)
    CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_product (user_id, product_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 4. Insert sample category
    INSERT IGNORE INTO business_categories (id, name, description, sort_order, is_active) VALUES
    (1, 'สินค้าทั่วไป', 'หมวดหมู่สินค้าทั่วไป', 1, 1);

    -- 5. Insert sample product (if table is empty)
    INSERT INTO business_items (name, description, price, stock, category_id, is_active)
    SELECT 'สินค้าตัวอย่าง', 'สินค้าตัวอย่างสำหรับทดสอบระบบ', 100, 99, 1, 1
    FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM business_items LIMIT 1);
