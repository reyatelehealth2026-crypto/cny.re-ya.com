<?php
/**
 * Fix cart_items table - add missing columns
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🛒 Fix Cart Items Table</h1>";

// Check current structure
echo "<h2>1. โครงสร้างปัจจุบัน</h2>";
try {
    $stmt = $db->query("DESCRIBE cart_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    $hasUserId = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'user_id') $hasUserId = true;
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
    if ($hasUserId) {
        echo "<p style='color:green'>✅ มี user_id แล้ว</p>";
    } else {
        echo "<p style='color:red'>❌ ไม่มี user_id</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Table not found: " . $e->getMessage() . "</p>";
    
    // Create table
    echo "<h2>สร้างตาราง cart_items</h2>";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_product (user_id, product_id),
                INDEX idx_user (user_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color:green'>✅ สร้างตารางสำเร็จ!</p>";
    } catch (Exception $e2) {
        echo "<p style='color:red'>Error: " . $e2->getMessage() . "</p>";
    }
    exit;
}

// Add user_id if missing
if (!$hasUserId) {
    echo "<h2>2. เพิ่ม user_id column</h2>";
    try {
        $db->exec("ALTER TABLE cart_items ADD COLUMN user_id INT NOT NULL AFTER id");
        echo "<p style='color:green'>✅ เพิ่ม user_id สำเร็จ!</p>";
        
        // Add index
        try {
            $db->exec("ALTER TABLE cart_items ADD INDEX idx_user (user_id)");
            echo "<p style='color:green'>✅ เพิ่ม index สำเร็จ!</p>";
        } catch (Exception $e) {}
        
        // Add unique constraint
        try {
            $db->exec("ALTER TABLE cart_items ADD UNIQUE KEY unique_user_product (user_id, product_id)");
            echo "<p style='color:green'>✅ เพิ่ม unique key สำเร็จ!</p>";
        } catch (Exception $e) {}
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}

// Verify
echo "<h2>3. โครงสร้างหลังแก้ไข</h2>";
$stmt = $db->query("DESCRIBE cart_items");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
}
echo "</table>";

echo "<h2>4. ทดสอบ INSERT</h2>";
try {
    $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (1, 1, 1) ON DUPLICATE KEY UPDATE quantity = quantity");
    $stmt->execute();
    echo "<p style='color:green'>✅ INSERT สำเร็จ!</p>";
    
    // Clean up
    $db->exec("DELETE FROM cart_items WHERE user_id = 1 AND product_id = 1 AND quantity = 1");
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>✅ เสร็จสิ้น! <a href='liff-shop.php'>ลองใช้งานร้านค้า</a></p>";
