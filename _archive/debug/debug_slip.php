<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Slip System</h2>";

// 1. Check tables
echo "<h3>1. Tables Check</h3>";
$tables = ['transactions', 'orders', 'transaction_items', 'order_items', 'payment_slips', 'user_states'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "✅ {$table}: {$count} rows<br>";
    } catch (Exception $e) {
        echo "❌ {$table}: NOT FOUND<br>";
    }
}

// 2. Check pending orders in transactions
echo "<h3>2. Pending Orders in transactions</h3>";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status, grand_total FROM transactions WHERE status IN ('pending', 'confirmed') AND payment_status = 'pending'");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($orders) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Payment</th><th>Total</th></tr>";
        foreach ($orders as $o) {
            echo "<tr><td>{$o['id']}</td><td>{$o['order_number']}</td><td>{$o['user_id']}</td><td>{$o['status']}</td><td>{$o['payment_status']}</td><td>{$o['grand_total']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No pending orders in transactions<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// 3. Check pending orders in orders
echo "<h3>3. Pending Orders in orders</h3>";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status, grand_total FROM orders WHERE status IN ('pending', 'confirmed') AND payment_status = 'pending'");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($orders) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Payment</th><th>Total</th></tr>";
        foreach ($orders as $o) {
            echo "<tr><td>{$o['id']}</td><td>{$o['order_number']}</td><td>{$o['user_id']}</td><td>{$o['status']}</td><td>{$o['payment_status']}</td><td>{$o['grand_total']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No pending orders in orders<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// 4. Check user_states
echo "<h3>4. User States</h3>";
try {
    // Show table structure
    echo "<strong>Table Structure:</strong><br>";
    $stmt = $db->query("DESCRIBE user_states");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='3'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table><br>";
    
    $stmt = $db->query("SELECT * FROM user_states ORDER BY updated_at DESC LIMIT 5");
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($states) {
        echo "<pre>" . print_r($states, true) . "</pre>";
    } else {
        echo "No user states<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// 5. Check users
echo "<h3>5. Users</h3>";
try {
    $stmt = $db->query("SELECT id, display_name, line_user_id FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>LINE ID</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['display_name']}</td><td>" . substr($u['line_user_id'], 0, 20) . "...</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// 6. Check shop_settings
echo "<h3>6. Shop Settings</h3>";
try {
    $stmt = $db->query("SELECT * FROM shop_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        echo "PromptPay: " . ($settings['promptpay_number'] ?? 'NOT SET') . "<br>";
        echo "Bank Accounts: " . ($settings['bank_accounts'] ?? 'NOT SET') . "<br>";
    } else {
        echo "No shop settings<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>7. All Transactions</h3>";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status FROM transactions ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($rows, true) . "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
