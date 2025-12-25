<?php
/**
 * Debug Orders from LIFF
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Orders from LIFF</h2>";

// 1. Show recent transactions
echo "<h3>1. Recent Transactions (last 10)</h3>";
try {
    $stmt = $db->query("SELECT t.*, u.display_name, u.line_user_id 
                        FROM transactions t 
                        LEFT JOIN users u ON t.user_id = u.id 
                        ORDER BY t.created_at DESC LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p style='color:orange'>No transactions found</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='font-size:12px'>";
        echo "<tr><th>ID</th><th>Order#</th><th>line_account_id</th><th>User</th><th>Total</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
        foreach ($orders as $o) {
            echo "<tr>";
            echo "<td>{$o['id']}</td>";
            echo "<td>{$o['order_number']}</td>";
            echo "<td style='background:" . ($o['line_account_id'] ? 'lightgreen' : 'lightyellow') . "'>" . ($o['line_account_id'] ?: 'NULL') . "</td>";
            echo "<td>{$o['display_name']}<br><small>{$o['line_user_id']}</small></td>";
            echo "<td>฿" . number_format($o['grand_total'], 2) . "</td>";
            echo "<td>{$o['status']}</td>";
            echo "<td>{$o['payment_method']}<br>{$o['payment_status']}</td>";
            echo "<td>" . date('d/m H:i', strtotime($o['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Check line_account_id values
echo "<h3>2. line_account_id Distribution</h3>";
try {
    $stmt = $db->query("SELECT line_account_id, COUNT(*) as cnt FROM transactions GROUP BY line_account_id");
    $dist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>line_account_id</th><th>Count</th></tr>";
    foreach ($dist as $d) {
        echo "<tr><td>" . ($d['line_account_id'] ?: 'NULL') . "</td><td>{$d['cnt']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. Check current session bot ID
echo "<h3>3. Session Info</h3>";
session_start();
echo "<p>current_bot_id in session: <strong>" . ($_SESSION['current_bot_id'] ?? 'NOT SET') . "</strong></p>";

// 4. Check shop_settings
echo "<h3>4. Shop Settings</h3>";
try {
    $stmt = $db->query("SELECT id, line_account_id, shop_name FROM shop_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>line_account_id</th><th>Shop Name</th></tr>";
    foreach ($settings as $s) {
        echo "<tr><td>{$s['id']}</td><td>" . ($s['line_account_id'] ?: 'NULL') . "</td><td>{$s['shop_name']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 5. Check payment_slips
echo "<h3>5. Recent Payment Slips</h3>";
try {
    $stmt = $db->query("SELECT * FROM payment_slips ORDER BY created_at DESC LIMIT 5");
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($slips)) {
        echo "<p>No payment slips found</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        $cols = array_keys($slips[0]);
        echo "<tr>";
        foreach ($cols as $c) echo "<th>$c</th>";
        echo "</tr>";
        foreach ($slips as $s) {
            echo "<tr>";
            foreach ($s as $v) {
                $display = strlen($v) > 50 ? substr($v, 0, 50) . '...' : $v;
                echo "<td>" . htmlspecialchars($display) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><br><a href='shop/orders.php'>← Go to Orders Page</a>";
