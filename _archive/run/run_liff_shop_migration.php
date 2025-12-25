<?php
/**
 * Run LIFF Shop Migration
 * สร้างตารางสำหรับระบบร้านค้า LIFF
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🛒 LIFF Shop Migration</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;}</style>";

$db = Database::getInstance()->getConnection();

$tables = [
    'business_categories' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'business_items' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'cart_items' => "
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_product (user_id, product_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

echo "<h2>1. สร้างตาราง</h2>";

foreach ($tables as $name => $sql) {
    try {
        // Check if exists
        $stmt = $db->query("SHOW TABLES LIKE '{$name}'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            echo "<p class='warn'>⚠️ ตาราง <strong>{$name}</strong> มีอยู่แล้ว</p>";
        } else {
            $db->exec($sql);
            echo "<p class='ok'>✅ สร้างตาราง <strong>{$name}</strong> สำเร็จ</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error creating {$name}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>2. เพิ่มข้อมูลตัวอย่าง</h2>";

// Add sample category
try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_categories");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $db->exec("INSERT INTO business_categories (name, description, sort_order, is_active) VALUES 
            ('สินค้าทั่วไป', 'หมวดหมู่สินค้าทั่วไป', 1, 1),
            ('สินค้าแนะนำ', 'สินค้าแนะนำประจำร้าน', 2, 1)
        ");
        echo "<p class='ok'>✅ เพิ่มหมวดหมู่ตัวอย่าง 2 หมวด</p>";
    } else {
        echo "<p class='warn'>⚠️ มีหมวดหมู่อยู่แล้ว {$count} หมวด</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Add sample products
try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_items");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $db->exec("INSERT INTO business_items (name, description, price, sale_price, stock, category_id, is_active) VALUES 
            ('สินค้าตัวอย่าง 1', 'รายละเอียดสินค้าตัวอย่าง', 199, NULL, 50, 1, 1),
            ('สินค้าลดราคา', 'สินค้าลดราคาพิเศษ', 299, 199, 30, 1, 1),
            ('สินค้าแนะนำ', 'สินค้าขายดีประจำร้าน', 499, NULL, 100, 2, 1)
        ");
        echo "<p class='ok'>✅ เพิ่มสินค้าตัวอย่าง 3 รายการ</p>";
    } else {
        echo "<p class='warn'>⚠️ มีสินค้าอยู่แล้ว {$count} รายการ</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. ตรวจสอบผลลัพธ์</h2>";

// Show categories
try {
    $stmt = $db->query("SELECT * FROM business_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>หมวดหมู่:</strong> " . count($categories) . " หมวด</p>";
    if ($categories) {
        echo "<ul>";
        foreach ($categories as $c) {
            echo "<li>{$c['name']} (ID: {$c['id']})</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {}

// Show products
try {
    $stmt = $db->query("SELECT * FROM business_items WHERE is_active = 1");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>สินค้า:</strong> " . count($products) . " รายการ</p>";
    if ($products) {
        echo "<ul>";
        foreach ($products as $p) {
            $price = $p['sale_price'] ? "<s>฿{$p['price']}</s> ฿{$p['sale_price']}" : "฿{$p['price']}";
            echo "<li>{$p['name']} - {$price}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {}

echo "<hr>";
echo "<h2>✅ Migration เสร็จสิ้น!</h2>";
echo "<p>ขั้นตอนถัดไป:</p>";
echo "<ul>";
echo "<li><a href='debug_liff_shop.php'>ตรวจสอบระบบ LIFF Shop</a></li>";
echo "<li><a href='shop/products.php'>จัดการสินค้า</a></li>";
echo "<li><a href='liff-shop.php?user=Utest'>ทดสอบหน้า LIFF Shop</a></li>";
echo "</ul>";
