<?php
/**
 * Fix user_wishlist table - add missing columns
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>❤️ Fix User Wishlist Table</h1>";

// Check if table exists
echo "<h2>1. ตรวจสอบตาราง</h2>";
try {
    $stmt = $db->query("DESCRIBE user_wishlist");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>✅ ตารางมีอยู่แล้ว</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    $hasUserId = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'user_id') $hasUserId = true;
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
    if (!$hasUserId) {
        echo "<p style='color:red'>❌ ไม่มี user_id - กำลังเพิ่ม...</p>";
        $db->exec("ALTER TABLE user_wishlist ADD COLUMN user_id INT NOT NULL AFTER id");
        $db->exec("ALTER TABLE user_wishlist ADD INDEX idx_user (user_id)");
        echo "<p style='color:green'>✅ เพิ่ม user_id สำเร็จ!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ ตารางไม่มี - กำลังสร้าง...</p>";
    
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_wishlist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                line_account_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_product (user_id, product_id),
                INDEX idx_user (user_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color:green'>✅ สร้างตารางสำเร็จ!</p>";
    } catch (Exception $e2) {
        echo "<p style='color:red'>Error: " . $e2->getMessage() . "</p>";
    }
}

// Verify
echo "<h2>2. โครงสร้างหลังแก้ไข</h2>";
try {
    $stmt = $db->query("DESCRIBE user_wishlist");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>✅ เสร็จสิ้น! <a href='liff-shop.php'>ลองใช้งานร้านค้า</a></p>";
