<?php
/**
 * Debug Orders/Transactions
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Debug Orders/Transactions</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected<br><br>";
    
    // Check which tables exist
    echo "<h3>1. Tables Check</h3>";
    
    $tables = ['orders', 'order_items', 'transactions', 'transaction_items', 'payment_slips'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "✅ {$table}: {$count} rows<br>";
        } catch (Exception $e) {
            echo "❌ {$table}: NOT FOUND<br>";
        }
    }
    
    // Check payment_slips structure
    echo "<br><h3>2. payment_slips Structure</h3>";
    try {
        $stmt = $db->query("DESCRIBE payment_slips");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "❌ Cannot describe payment_slips: " . $e->getMessage();
    }
    
    // Check recent orders/transactions
    echo "<br><h3>3. Recent Orders/Transactions</h3>";
    
    // Try transactions first
    try {
        $stmt = $db->query("SELECT id, order_number, user_id, status, grand_total, line_account_id, created_at FROM transactions ORDER BY id DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "<strong>transactions table:</strong><br>";
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Total</th><th>Bot ID</th><th>Created</th></tr>";
            foreach ($rows as $row) {
                echo "<tr><td>{$row['id']}</td><td>{$row['order_number']}</td><td>{$row['user_id']}</td><td>{$row['status']}</td><td>{$row['grand_total']}</td><td>{$row['line_account_id']}</td><td>{$row['created_at']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "transactions: " . $e->getMessage() . "<br>";
    }
    
    // Try orders
    try {
        $stmt = $db->query("SELECT id, order_number, user_id, status, grand_total, line_account_id, created_at FROM orders ORDER BY id DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "<br><strong>orders table:</strong><br>";
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Total</th><th>Bot ID</th><th>Created</th></tr>";
            foreach ($rows as $row) {
                echo "<tr><td>{$row['id']}</td><td>{$row['order_number']}</td><td>{$row['user_id']}</td><td>{$row['status']}</td><td>{$row['grand_total']}</td><td>{$row['line_account_id']}</td><td>{$row['created_at']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "orders: " . $e->getMessage() . "<br>";
    }
    
    // Check payment_slips
    echo "<br><h3>4. Recent Payment Slips</h3>";
    try {
        $stmt = $db->query("SELECT * FROM payment_slips ORDER BY id DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "<pre>" . print_r($rows, true) . "</pre>";
        } else {
            echo "No payment slips found<br>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
    // Check session
    echo "<br><h3>5. Session</h3>";
    session_start();
    echo "current_bot_id: " . ($_SESSION['current_bot_id'] ?? 'NOT SET') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
