<?php
/**
 * Fix business_items table - add missing columns
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Fix business_items Table</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;}</style>";

$db = Database::getInstance()->getConnection();

// Check and add sort_order column
echo "<h2>1. เพิ่ม column sort_order</h2>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM business_items LIKE 'sort_order'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE business_items ADD COLUMN sort_order INT DEFAULT 0 AFTER is_active");
        echo "<p class='ok'>✅ เพิ่ม column sort_order สำเร็จ</p>";
    } else {
        echo "<p class='warn'>⚠️ column sort_order มีอยู่แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Check and add stock column
echo "<h2>2. ตรวจสอบ column stock</h2>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM business_items LIKE 'stock'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE business_items ADD COLUMN stock INT DEFAULT 999");
        echo "<p class='ok'>✅ เพิ่ม column stock สำเร็จ</p>";
    } else {
        echo "<p class='warn'>⚠️ column stock มีอยู่แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Check and add sale_price column
echo "<h2>3. ตรวจสอบ column sale_price</h2>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM business_items LIKE 'sale_price'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE business_items ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL AFTER price");
        echo "<p class='ok'>✅ เพิ่ม column sale_price สำเร็จ</p>";
    } else {
        echo "<p class='warn'>⚠️ column sale_price มีอยู่แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Show current table structure
echo "<h2>4. โครงสร้างตาราง business_items</h2>";
try {
    $stmt = $db->query("DESCRIBE business_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Show products
echo "<h2>5. สินค้าในระบบ</h2>";
try {
    $stmt = $db->query("SELECT id, name, price, sale_price, stock, is_active, line_account_id FROM business_items LIMIT 20");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>พบสินค้า: <strong>" . count($products) . "</strong> รายการ</p>";
    
    if ($products) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Price</th><th>Sale</th><th>Stock</th><th>Active</th><th>Account</th></tr>";
        foreach ($products as $p) {
            echo "<tr>
                <td>{$p['id']}</td>
                <td>{$p['name']}</td>
                <td>฿" . number_format($p['price']) . "</td>
                <td>" . ($p['sale_price'] ? '฿' . number_format($p['sale_price']) : '-') . "</td>
                <td>{$p['stock']}</td>
                <td>" . ($p['is_active'] ? '✅' : '❌') . "</td>
                <td>" . ($p['line_account_id'] ?: 'NULL') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warn'>⚠️ ไม่มีสินค้า - กรุณาเพิ่มสินค้าที่ <a href='shop/products.php'>shop/products.php</a></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>✅ เสร็จสิ้น! <a href='liff-shop.php?debug=1'>ทดสอบ LIFF Shop</a></p>";
