<?php
/**
 * Debug Order Detail - ตรวจสอบปัญหาหน้ารายละเอียดออเดอร์
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Order Detail</h2>";
echo "<pre>";

// 1. Test require files
echo "1. Testing require files...\n";

try {
    require_once __DIR__ . '/../config/config.php';
    echo "✅ config.php loaded\n";
} catch (Exception $e) {
    echo "❌ config.php error: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/../config/database.php';
    echo "✅ database.php loaded\n";
} catch (Exception $e) {
    echo "❌ database.php error: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/../classes/LineAPI.php';
    echo "✅ LineAPI.php loaded\n";
} catch (Exception $e) {
    echo "❌ LineAPI.php error: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/../classes/LineAccountManager.php';
    echo "✅ LineAccountManager.php loaded\n";
} catch (Exception $e) {
    echo "❌ LineAccountManager.php error: " . $e->getMessage() . "\n";
}

// 2. Test database connection
echo "\n2. Testing database connection...\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit;
}

// 3. Test session
echo "\n3. Testing session...\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentBotId = $_SESSION['current_bot_id'] ?? 1;
echo "current_bot_id: {$currentBotId}\n";

// 4. Test order ID
echo "\n4. Testing order ID...\n";
$orderId = (int)($_GET['id'] ?? 0);
echo "Order ID from URL: {$orderId}\n";

if (!$orderId) {
    // Get latest order
    $stmt = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
    $orderId = $stmt->fetchColumn();
    echo "Using latest order ID: {$orderId}\n";
}

// 5. Test tables
echo "\n5. Testing tables...\n";
$useTransactions = false;
$ordersTable = 'orders';

try {
    $db->query("SELECT 1 FROM transactions LIMIT 1");
    $useTransactions = true;
    $ordersTable = 'transactions';
    echo "Using: transactions table\n";
} catch (Exception $e) {
    echo "Using: orders table\n";
}

// 6. Test order query
echo "\n6. Testing order query...\n";
try {
    $sql = "SELECT o.*, u.display_name, u.picture_url, u.line_user_id 
            FROM {$ordersTable} o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?";
    echo "SQL: {$sql}\n";
    echo "Params: [{$orderId}]\n";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "✅ Order found!\n";
        echo "Order Number: {$order['order_number']}\n";
        echo "Status: {$order['status']}\n";
        echo "User: {$order['display_name']}\n";
    } else {
        echo "❌ Order NOT found!\n";
        
        // Try without bot filter
        $stmt = $db->prepare("SELECT * FROM {$ordersTable} WHERE id = ?");
        $stmt->execute([$orderId]);
        $order2 = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order2) {
            echo "Order exists but user join failed\n";
            echo "user_id in order: {$order2['user_id']}\n";
            
            // Check user
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$order2['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "User found: {$user['display_name']}\n";
            } else {
                echo "❌ User NOT found with id: {$order2['user_id']}\n";
            }
        } else {
            echo "Order does not exist with id: {$orderId}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "\n";
}

// 7. Test header include
echo "\n7. Testing header include...\n";
$headerPath = __DIR__ . '/../includes/header.php';
echo "Header path: {$headerPath}\n";
echo "File exists: " . (file_exists($headerPath) ? 'Yes' : 'No') . "\n";

// 8. List all orders
echo "\n8. All orders in database:\n";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, line_account_id FROM {$ordersTable} ORDER BY id DESC LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders as $o) {
        echo "  ID:{$o['id']} | #{$o['order_number']} | user:{$o['user_id']} | status:{$o['status']} | bot:{$o['line_account_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n</pre>";

// 9. Try to load actual page
echo "<hr><h3>ลองโหลดหน้าจริง:</h3>";
echo "<p><a href='order-detail.php?id={$orderId}' target='_blank'>เปิด order-detail.php?id={$orderId}</a></p>";

// 10. Show PHP errors
echo "<h3>PHP Error Log (last 20 lines):</h3>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background:#fee;padding:10px;font-size:11px;'>";
    echo htmlspecialchars(implode('', $lastLines));
    echo "</pre>";
} else {
    echo "<p>Error log not accessible</p>";
}
