<?php
/**
 * Debug Slip Issue - ตรวจสอบปัญหาสลิปไม่แสดง
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Slip Issue</h2>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    .ok { color: green; }
    .error { color: red; }
    .warn { color: orange; }
</style>";

// 1. Check payment_slips table structure
echo "<h3>1. payment_slips Table Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE payment_slips");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    $hasTransactionId = false;
    $hasOrderId = false;
    foreach ($cols as $c) {
        if ($c['Field'] === 'transaction_id') $hasTransactionId = true;
        if ($c['Field'] === 'order_id') $hasOrderId = true;
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td><td>{$c['Default']}</td></tr>";
    }
    echo "</table>";
    
    if ($hasTransactionId) {
        echo "<p class='ok'>✅ transaction_id column exists</p>";
    } else {
        echo "<p class='error'>❌ transaction_id column MISSING - need to run migration!</p>";
    }
    if ($hasOrderId) {
        echo "<p class='warn'>⚠️ order_id column exists (legacy)</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 2. Check all payment_slips records
echo "<h3>2. All Payment Slips Records</h3>";
try {
    $stmt = $db->query("SELECT * FROM payment_slips ORDER BY id DESC LIMIT 10");
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($slips)) {
        echo "<p class='warn'>⚠️ No payment slips in database</p>";
    } else {
        echo "<table><tr><th>ID</th><th>transaction_id</th><th>order_id</th><th>user_id</th><th>image_url</th><th>status</th><th>created_at</th></tr>";
        foreach ($slips as $s) {
            $txnId = $s['transaction_id'] ?? 'NULL';
            $ordId = $s['order_id'] ?? 'NULL';
            $txnClass = ($txnId === 'NULL' || $txnId === null) ? 'error' : 'ok';
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td class='{$txnClass}'>{$txnId}</td>";
            echo "<td>{$ordId}</td>";
            echo "<td>" . ($s['user_id'] ?? 'NULL') . "</td>";
            echo "<td><a href='{$s['image_url']}' target='_blank'>" . basename($s['image_url']) . "</a></td>";
            echo "<td>{$s['status']}</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 3. Check recent transactions
echo "<h3>3. Recent Transactions</h3>";
try {
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status, grand_total, created_at FROM transactions ORDER BY id DESC LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Payment</th><th>Total</th><th>Created</th><th>Has Slip?</th></tr>";
    foreach ($orders as $o) {
        // Check if has slip
        $stmt2 = $db->prepare("SELECT COUNT(*) FROM payment_slips WHERE transaction_id = ?");
        $stmt2->execute([$o['id']]);
        $slipCount = $stmt2->fetchColumn();
        
        $slipClass = $slipCount > 0 ? 'ok' : 'warn';
        echo "<tr>";
        echo "<td>{$o['id']}</td>";
        echo "<td>{$o['order_number']}</td>";
        echo "<td>{$o['user_id']}</td>";
        echo "<td>{$o['status']}</td>";
        echo "<td>{$o['payment_status']}</td>";
        echo "<td>฿" . number_format($o['grand_total'], 0) . "</td>";
        echo "<td>{$o['created_at']}</td>";
        echo "<td class='{$slipClass}'>" . ($slipCount > 0 ? "✅ {$slipCount}" : "❌ No") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// 4. Check uploads/slips directory
echo "<h3>4. Uploaded Slip Files</h3>";
$slipDir = __DIR__ . '/uploads/slips/';
if (is_dir($slipDir)) {
    $files = array_diff(scandir($slipDir), ['.', '..']);
    if (empty($files)) {
        echo "<p class='warn'>⚠️ No files in uploads/slips/</p>";
    } else {
        echo "<p class='ok'>✅ Found " . count($files) . " file(s)</p>";
        echo "<ul>";
        foreach (array_slice($files, 0, 5) as $f) {
            $size = filesize($slipDir . $f);
            echo "<li><a href='uploads/slips/{$f}' target='_blank'>{$f}</a> (" . number_format($size) . " bytes)</li>";
        }
        if (count($files) > 5) {
            echo "<li>... and " . (count($files) - 5) . " more</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ Directory uploads/slips/ does not exist</p>";
}

// 5. Test specific order
echo "<h3>5. Test Specific Order</h3>";
$testOrderId = $_GET['order_id'] ?? null;
if ($testOrderId) {
    echo "<p>Testing order_id = {$testOrderId}</p>";
    
    // Get order
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$testOrderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<p class='ok'>✅ Order found: #{$order['order_number']}</p>";
        
        // Get slips with transaction_id
        $stmt = $db->prepare("SELECT * FROM payment_slips WHERE transaction_id = ?");
        $stmt->execute([$testOrderId]);
        $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($slips) {
            echo "<p class='ok'>✅ Found " . count($slips) . " slip(s) with transaction_id = {$testOrderId}</p>";
            echo "<pre>" . print_r($slips, true) . "</pre>";
        } else {
            echo "<p class='error'>❌ No slips found with transaction_id = {$testOrderId}</p>";
            
            // Try order_id
            $stmt = $db->prepare("SELECT * FROM payment_slips WHERE order_id = ?");
            $stmt->execute([$testOrderId]);
            $slipsOld = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($slipsOld) {
                echo "<p class='warn'>⚠️ Found " . count($slipsOld) . " slip(s) with order_id = {$testOrderId} (legacy)</p>";
                echo "<p>Need to migrate: UPDATE payment_slips SET transaction_id = order_id WHERE transaction_id IS NULL</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Order not found</p>";
    }
} else {
    echo "<p>Add ?order_id=XX to test specific order</p>";
}

// 6. Fix button
echo "<h3>6. Quick Fix</h3>";
if (isset($_GET['fix'])) {
    echo "<p>Running fix...</p>";
    
    // Add transaction_id column if not exists
    try {
        $db->exec("ALTER TABLE payment_slips ADD COLUMN transaction_id INT DEFAULT NULL AFTER id");
        echo "<p class='ok'>✅ Added transaction_id column</p>";
    } catch (Exception $e) {
        echo "<p class='warn'>⚠️ transaction_id column already exists or error: " . $e->getMessage() . "</p>";
    }
    
    // Copy order_id to transaction_id
    $stmt = $db->query("SELECT COUNT(*) FROM payment_slips WHERE transaction_id IS NULL AND order_id IS NOT NULL");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $db->exec("UPDATE payment_slips SET transaction_id = order_id WHERE transaction_id IS NULL AND order_id IS NOT NULL");
        echo "<p class='ok'>✅ Migrated {$count} records from order_id to transaction_id</p>";
    } else {
        echo "<p class='ok'>✅ No records need migration</p>";
    }
    
    echo "<p><a href='debug_slip_issue.php'>Refresh to see results</a></p>";
} else {
    echo "<p><a href='?fix=1' onclick=\"return confirm('Run fix migration?')\">🔧 Click here to run fix migration</a></p>";
}
