<?php
/**
 * Debug LIFF Shop - ตรวจสอบสินค้าและการตั้งค่า
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug LIFF Shop</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;} .ok{color:green;} .error{color:red;} img{max-width:80px;max-height:80px;}</style>";

// 1. Check business_items table
echo "<h2>1. ตาราง business_items</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_items");
    $total = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE is_active = 1");
    $active = $stmt->fetchColumn();
    
    echo "<p>สินค้าทั้งหมด: <strong>{$total}</strong> รายการ</p>";
    echo "<p>สินค้าที่เปิดใช้งาน (is_active=1): <strong class='" . ($active > 0 ? 'ok' : 'error') . "'>{$active}</strong> รายการ</p>";
    
    if ($active == 0) {
        echo "<p class='error'>⚠️ ไม่มีสินค้าที่เปิดใช้งาน! กรุณาเพิ่มสินค้าที่ shop/products.php</p>";
    }
    
    // Show products
    $stmt = $db->query("SELECT id, name, price, sale_price, stock, is_active, line_account_id, image_url FROM business_items ORDER BY id DESC LIMIT 20");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($products) {
        echo "<table><tr><th>ID</th><th>รูป</th><th>ชื่อ</th><th>ราคา</th><th>ราคาลด</th><th>Stock</th><th>Active</th><th>Account ID</th></tr>";
        foreach ($products as $p) {
            $activeClass = $p['is_active'] ? 'ok' : 'error';
            $img = $p['image_url'] ? "<img src='{$p['image_url']}'>" : '-';
            echo "<tr>
                <td>{$p['id']}</td>
                <td>{$img}</td>
                <td>{$p['name']}</td>
                <td>฿" . number_format($p['price']) . "</td>
                <td>" . ($p['sale_price'] ? '฿' . number_format($p['sale_price']) : '-') . "</td>
                <td>{$p['stock']}</td>
                <td class='{$activeClass}'>" . ($p['is_active'] ? '✅' : '❌') . "</td>
                <td>{$p['line_account_id']}</td>
            </tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>ตาราง business_items อาจยังไม่มี - ต้อง run migration</p>";
}

// 2. Check business_categories
echo "<h2>2. ตาราง business_categories</h2>";
try {
    $stmt = $db->query("SELECT * FROM business_categories WHERE is_active = 1 ORDER BY sort_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>หมวดหมู่ที่เปิดใช้งาน: <strong>" . count($categories) . "</strong> หมวด</p>";
    
    if ($categories) {
        echo "<table><tr><th>ID</th><th>ชื่อ</th><th>Account ID</th></tr>";
        foreach ($categories as $c) {
            echo "<tr><td>{$c['id']}</td><td>{$c['name']}</td><td>{$c['line_account_id']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 3. Check line_accounts and LIFF ID
echo "<h2>3. LINE Accounts & LIFF ID</h2>";
try {
    $stmt = $db->query("SELECT id, name, liff_id, is_default, is_active FROM line_accounts ORDER BY is_default DESC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>ชื่อ</th><th>LIFF ID</th><th>Default</th><th>Active</th></tr>";
    foreach ($accounts as $a) {
        $liffClass = $a['liff_id'] ? 'ok' : 'error';
        echo "<tr>
            <td>{$a['id']}</td>
            <td>{$a['name']}</td>
            <td class='{$liffClass}'>" . ($a['liff_id'] ?: '❌ ไม่มี') . "</td>
            <td>" . ($a['is_default'] ? '✅' : '') . "</td>
            <td>" . ($a['is_active'] ? '✅' : '❌') . "</td>
        </tr>";
    }
    echo "</table>";
    
    $hasLiff = false;
    foreach ($accounts as $a) {
        if ($a['liff_id']) $hasLiff = true;
    }
    if (!$hasLiff) {
        echo "<p class='error'>⚠️ ไม่มี LIFF ID! กรุณาตั้งค่าที่ line-accounts.php</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 4. Check shop_settings
echo "<h2>4. Shop Settings</h2>";
try {
    $stmt = $db->query("SELECT * FROM shop_settings LIMIT 5");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "<table><tr><th>Account ID</th><th>Shop Name</th><th>Shipping Fee</th><th>Free Shipping Min</th><th>Is Open</th></tr>";
        foreach ($settings as $s) {
            echo "<tr>
                <td>{$s['line_account_id']}</td>
                <td>{$s['shop_name']}</td>
                <td>฿{$s['shipping_fee']}</td>
                <td>฿{$s['free_shipping_min']}</td>
                <td>" . ($s['is_open'] ? '✅ เปิด' : '❌ ปิด') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>⚠️ ไม่มี shop_settings!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 5. Test LIFF Shop URL
echo "<h2>5. ทดสอบ LIFF Shop</h2>";
$testUserId = 'Utest123';
echo "<p>URL ทดสอบ: <a href='liff-shop.php?user={$testUserId}' target='_blank'>liff-shop.php?user={$testUserId}</a></p>";

// Get first active account with LIFF
try {
    $stmt = $db->query("SELECT liff_id FROM line_accounts WHERE liff_id IS NOT NULL AND liff_id != '' AND is_active = 1 LIMIT 1");
    $liffId = $stmt->fetchColumn();
    if ($liffId) {
        echo "<p>LIFF URL: <a href='https://liff.line.me/{$liffId}' target='_blank'>https://liff.line.me/{$liffId}</a></p>";
    }
} catch (Exception $e) {}

echo "<hr><p>📝 <strong>สรุป:</strong></p>";
echo "<ul>";
echo "<li>เพิ่มสินค้าที่: <a href='shop/products.php'>shop/products.php</a></li>";
echo "<li>ตั้งค่า LIFF ID ที่: <a href='line-accounts.php'>line-accounts.php</a></li>";
echo "<li>ตั้งค่าร้านค้าที่: <a href='shop/index.php'>shop/index.php</a></li>";
echo "</ul>";
