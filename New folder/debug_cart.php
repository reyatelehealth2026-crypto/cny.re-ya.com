<?php
/**
 * Debug Cart - ตรวจสอบตะกร้าสินค้า
 */
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance()->getConnection();

echo "<h1>🛒 Debug Cart</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f5f5f5;} .ok{color:green;} .error{color:red;}</style>";

$lineUserId = $_GET['user'] ?? null;

echo "<h2>1. Parameters</h2>";
echo "<p>LINE User ID: <strong>" . ($lineUserId ?: 'ไม่ระบุ (ใส่ ?user=Uxxxx)') . "</strong></p>";

if (!$lineUserId) {
    echo "<p class='error'>กรุณาระบุ user เช่น ?user=U1234567890</p>";
    exit;
}

// Find user in DB
echo "<h2>2. ค้นหา User ในระบบ</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE line_user_id = ?");
    $stmt->execute([$lineUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='ok'>✅ พบ User</p>";
        echo "<ul>";
        echo "<li>DB User ID: <strong>{$user['id']}</strong></li>";
        echo "<li>LINE User ID: {$user['line_user_id']}</li>";
        echo "<li>Display Name: {$user['display_name']}</li>";
        echo "<li>Line Account ID: {$user['line_account_id']}</li>";
        echo "</ul>";
        $dbUserId = $user['id'];
    } else {
        echo "<p class='error'>❌ ไม่พบ User ในระบบ</p>";
        echo "<p>User จะถูกสร้างอัตโนมัติเมื่อเพิ่มสินค้าลงตะกร้า</p>";
        $dbUserId = null;
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    $dbUserId = null;
}

// Check cart_items table
echo "<h2>3. ตาราง cart_items</h2>";
try {
    $stmt = $db->query("DESCRIBE cart_items");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Columns: " . implode(', ', $columns) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ ตาราง cart_items ไม่มี: " . $e->getMessage() . "</p>";
}

// Show cart items
echo "<h2>4. สินค้าในตะกร้า</h2>";
if ($dbUserId) {
    try {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.sale_price, p.image_url
            FROM cart_items c
            LEFT JOIN business_items p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$dbUserId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>พบ <strong>" . count($cartItems) . "</strong> รายการในตะกร้า</p>";
        
        if ($cartItems) {
            echo "<table><tr><th>Cart ID</th><th>Product ID</th><th>Name</th><th>Price</th><th>Sale Price</th><th>Qty</th><th>Subtotal</th></tr>";
            $total = 0;
            foreach ($cartItems as $item) {
                $price = $item['sale_price'] ?? $item['price'];
                $subtotal = $price * $item['quantity'];
                $total += $subtotal;
                echo "<tr>
                    <td>{$item['id']}</td>
                    <td>{$item['product_id']}</td>
                    <td>" . ($item['name'] ?: '<span class="error">สินค้าไม่พบ!</span>') . "</td>
                    <td>฿" . number_format($item['price'] ?? 0) . "</td>
                    <td>" . ($item['sale_price'] ? '฿' . number_format($item['sale_price']) : '-') . "</td>
                    <td>{$item['quantity']}</td>
                    <td>฿" . number_format($subtotal) . "</td>
                </tr>";
            }
            echo "<tr><td colspan='6'><strong>รวม</strong></td><td><strong>฿" . number_format($total) . "</strong></td></tr>";
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ไม่มี User ID - ไม่สามารถดูตะกร้าได้</p>";
}

// Test API
echo "<h2>5. ทดสอบ API</h2>";
echo "<p>API URL: <a href='api/checkout.php?action=cart&line_user_id={$lineUserId}' target='_blank'>api/checkout.php?action=cart&line_user_id={$lineUserId}</a></p>";

// Test add to cart
if (isset($_GET['add']) && $dbUserId) {
    $productId = intval($_GET['add']);
    echo "<h3>🛒 ทดสอบเพิ่มสินค้า ID: {$productId}</h3>";
    try {
        $stmt = $db->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ");
        $stmt->execute([$dbUserId, $productId]);
        echo "<p class='ok'>✅ เพิ่มสินค้าสำเร็จ! <a href='debug_cart.php?user={$lineUserId}'>Refresh</a></p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Show available products to add
echo "<h3>สินค้าที่สามารถเพิ่มได้:</h3>";
try {
    $stmt = $db->query("SELECT id, name, price FROM business_items WHERE is_active = 1 LIMIT 5");
    $availableProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($availableProducts) {
        echo "<ul>";
        foreach ($availableProducts as $p) {
            echo "<li>{$p['name']} (฿{$p['price']}) - <a href='debug_cart.php?user={$lineUserId}&add={$p['id']}'>เพิ่มลงตะกร้า</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>ไม่มีสินค้าในระบบ</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Show all cart items in system
echo "<h2>6. ตะกร้าทั้งหมดในระบบ (ล่าสุด 20 รายการ)</h2>";
try {
    $stmt = $db->query("
        SELECT c.*, u.line_user_id, u.display_name, p.name as product_name
        FROM cart_items c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN business_items p ON c.product_id = p.id
        ORDER BY c.id DESC
        LIMIT 20
    ");
    $allCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allCarts) {
        echo "<table><tr><th>ID</th><th>User ID</th><th>LINE User</th><th>Display Name</th><th>Product</th><th>Qty</th></tr>";
        foreach ($allCarts as $c) {
            echo "<tr>
                <td>{$c['id']}</td>
                <td>{$c['user_id']}</td>
                <td>" . substr($c['line_user_id'] ?? '', 0, 10) . "...</td>
                <td>{$c['display_name']}</td>
                <td>{$c['product_name']}</td>
                <td>{$c['quantity']}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ไม่มีสินค้าในตะกร้าทั้งระบบ</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>Links:</p>";
echo "<ul>";
echo "<li><a href='liff-shop.php?user={$lineUserId}&debug=1'>LIFF Shop (debug)</a></li>";
echo "<li><a href='liff-checkout.php?user={$lineUserId}&action=address'>LIFF Checkout</a></li>";
echo "</ul>";
