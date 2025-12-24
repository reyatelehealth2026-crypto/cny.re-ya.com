<?php
/**
 * Debug Payment Slips
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Payment Slips</h2>";

// 1. Check payment_slips table structure
echo "<h3>1. Table Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE payment_slips");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// 2. All payment slips
echo "<h3>2. All Payment Slips</h3>";
try {
    $stmt = $db->query("SELECT * FROM payment_slips ORDER BY id DESC LIMIT 10");
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($slips) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>order_id</th><th>transaction_id</th><th>image_url</th><th>status</th><th>created_at</th></tr>";
        foreach ($slips as $s) {
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>" . ($s['order_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($s['transaction_id'] ?? 'NULL') . "</td>";
            echo "<td><a href='{$s['image_url']}' target='_blank'>" . substr($s['image_url'], -30) . "</a></td>";
            echo "<td>{$s['status']}</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No payment slips found";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// 3. Recent transactions
echo "<h3>3. Recent Transactions</h3>";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status, grand_total, created_at FROM transactions ORDER BY id DESC LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Payment</th><th>Total</th><th>Created</th></tr>";
    foreach ($orders as $o) {
        echo "<tr><td>{$o['id']}</td><td>{$o['order_number']}</td><td>{$o['user_id']}</td><td>{$o['status']}</td><td>{$o['payment_status']}</td><td>{$o['grand_total']}</td><td>{$o['created_at']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// 4. Check uploads/slips directory
echo "<h3>4. Uploaded Slip Files</h3>";
$slipDir = __DIR__ . '/uploads/slips/';
if (is_dir($slipDir)) {
    $files = scandir($slipDir);
    $files = array_filter($files, fn($f) => !in_array($f, ['.', '..']));
    if ($files) {
        echo "<ul>";
        foreach ($files as $f) {
            $size = filesize($slipDir . $f);
            echo "<li><a href='uploads/slips/{$f}' target='_blank'>{$f}</a> (" . number_format($size) . " bytes)</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ No files in uploads/slips/";
    }
} else {
    echo "❌ Directory uploads/slips/ does not exist";
}

// 5. Test query for order-detail.php (use transaction_id only)
echo "<h3>5. Test Query (transaction_id)</h3>";
$testOrderId = $_GET['order_id'] ?? 12;
echo "Testing with transaction_id = {$testOrderId}<br>";
try {
    $stmt = $db->prepare("SELECT * FROM payment_slips WHERE transaction_id = ? ORDER BY created_at DESC");
    $stmt->execute([$testOrderId]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($slips) {
        echo "✅ Found " . count($slips) . " slip(s)<br>";
        echo "<pre>" . print_r($slips, true) . "</pre>";
    } else {
        echo "❌ No slips found for transaction_id = {$testOrderId}";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
