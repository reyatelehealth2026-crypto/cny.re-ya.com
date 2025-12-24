<?php
/**
 * Debug Orders API - ทดสอบการดึงออเดอร์
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$lineUserId = $_GET['line_user_id'] ?? 'Ub85be041ec79c72b08d3ff7143f8d691';
$lineAccountId = $_GET['line_account_id'] ?? 3;

echo "<h2>🔍 Debug Orders API</h2>";
echo "<p><strong>LINE User ID:</strong> {$lineUserId}</p>";
echo "<p><strong>LINE Account ID:</strong> {$lineAccountId}</p>";

// 1. Check user
echo "<h3>1. ตรวจสอบ User</h3>";
$stmt = $db->prepare("SELECT * FROM users WHERE line_user_id = ?");
$stmt->execute([$lineUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p style='color:green'>✓ พบ User: ID={$user['id']}, display_name={$user['display_name']}, line_account_id={$user['line_account_id']}</p>";
} else {
    echo "<p style='color:red'>✗ ไม่พบ User</p>";
    exit;
}

// 2. Check transactions table
echo "<h3>2. ตรวจสอบตาราง transactions</h3>";
try {
    $stmt = $db->query("DESCRIBE transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Columns: " . implode(', ', $columns) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ ไม่พบตาราง transactions: " . $e->getMessage() . "</p>";
}

// 3. Check orders for this user
echo "<h3>3. ตรวจสอบออเดอร์ของ User ID={$user['id']}</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p style='color:orange'>⚠ ไม่พบออเดอร์สำหรับ user_id={$user['id']}</p>";
        
        // Check all transactions
        echo "<h4>ตรวจสอบ transactions ทั้งหมด (10 รายการล่าสุด)</h4>";
        $stmt = $db->query("SELECT id, user_id, order_number, transaction_type, status, grand_total, created_at FROM transactions ORDER BY created_at DESC LIMIT 10");
        $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($allOrders) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Order Number</th><th>Type</th><th>Status</th><th>Total</th><th>Created</th></tr>";
            foreach ($allOrders as $o) {
                $highlight = $o['user_id'] == $user['id'] ? 'background:yellow' : '';
                echo "<tr style='{$highlight}'>";
                echo "<td>{$o['id']}</td>";
                echo "<td>{$o['user_id']}</td>";
                echo "<td>{$o['order_number']}</td>";
                echo "<td>{$o['transaction_type']}</td>";
                echo "<td>{$o['status']}</td>";
                echo "<td>{$o['grand_total']}</td>";
                echo "<td>{$o['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>ไม่มี transactions ในระบบเลย</p>";
        }
    } else {
        echo "<p style='color:green'>✓ พบ " . count($orders) . " ออเดอร์</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Order Number</th><th>Type</th><th>Status</th><th>Total</th><th>Created</th></tr>";
        foreach ($orders as $o) {
            echo "<tr>";
            echo "<td>{$o['id']}</td>";
            echo "<td>{$o['order_number']}</td>";
            echo "<td>{$o['transaction_type']}</td>";
            echo "<td>{$o['status']}</td>";
            echo "<td>{$o['grand_total']}</td>";
            echo "<td>{$o['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// 4. Test API directly
echo "<h3>4. ทดสอบ API โดยตรง</h3>";
$apiUrl = BASE_URL . "/api/orders.php?action=my_orders&line_user_id={$lineUserId}&line_account_id={$lineAccountId}";
echo "<p>API URL: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";

// 5. Check cart_items
echo "<h3>5. ตรวจสอบ cart_items</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Cart items: " . count($cartItems) . " รายการ</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
