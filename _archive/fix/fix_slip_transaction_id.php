<?php
/**
 * Fix payment_slips transaction_id mapping
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix Payment Slips Transaction ID</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;}</style>";

// 1. Show current state
echo "<h3>1. Current payment_slips data</h3>";
$stmt = $db->query("SELECT ps.*, t.order_number as txn_order_number 
                    FROM payment_slips ps 
                    LEFT JOIN transactions t ON ps.transaction_id = t.id 
                    ORDER BY ps.id DESC LIMIT 10");
$slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>transaction_id</th><th>order_id</th><th>Matched TXN#</th><th>image_url</th><th>status</th></tr>";
foreach ($slips as $s) {
    $matched = $s['txn_order_number'] ? '✅ ' . $s['txn_order_number'] : '❌ No match';
    echo "<tr>";
    echo "<td>{$s['id']}</td>";
    echo "<td>" . ($s['transaction_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($s['order_id'] ?? 'NULL') . "</td>";
    echo "<td>{$matched}</td>";
    echo "<td>" . basename($s['image_url']) . "</td>";
    echo "<td>{$s['status']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Try to match by order_number in filename
echo "<h3>2. Try to match slips by filename</h3>";
$stmt = $db->query("SELECT * FROM payment_slips ORDER BY id DESC");
$slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fixes = [];
foreach ($slips as $slip) {
    $filename = basename($slip['image_url']);
    // Extract order number from filename like slip_TXN202512156305_1765735778.jpg or slip_ORD251213...
    if (preg_match('/slip_(TXN\d+|ORD\d+)_/', $filename, $matches)) {
        $orderNum = $matches[1];
        
        // Find transaction with this order_number
        $stmt2 = $db->prepare("SELECT id, order_number FROM transactions WHERE order_number = ? OR order_number LIKE ?");
        $stmt2->execute([$orderNum, '%' . $orderNum . '%']);
        $txn = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($txn && $txn['id'] != $slip['transaction_id']) {
            $fixes[] = [
                'slip_id' => $slip['id'],
                'old_txn_id' => $slip['transaction_id'],
                'new_txn_id' => $txn['id'],
                'order_number' => $txn['order_number'],
                'filename' => $filename
            ];
            echo "<p class='warn'>⚠️ Slip #{$slip['id']}: filename has '{$orderNum}' → should be transaction_id={$txn['id']} (currently {$slip['transaction_id']})</p>";
        } else if ($txn) {
            echo "<p class='ok'>✅ Slip #{$slip['id']}: correctly linked to transaction #{$txn['id']}</p>";
        } else {
            echo "<p class='error'>❌ Slip #{$slip['id']}: no transaction found for '{$orderNum}'</p>";
        }
    }
}

// 3. Apply fixes
if (isset($_GET['fix']) && !empty($fixes)) {
    echo "<h3>3. Applying fixes...</h3>";
    foreach ($fixes as $fix) {
        $stmt = $db->prepare("UPDATE payment_slips SET transaction_id = ? WHERE id = ?");
        $stmt->execute([$fix['new_txn_id'], $fix['slip_id']]);
        echo "<p class='ok'>✅ Updated slip #{$fix['slip_id']}: transaction_id {$fix['old_txn_id']} → {$fix['new_txn_id']}</p>";
    }
    echo "<p><a href='fix_slip_transaction_id.php'>Refresh to verify</a></p>";
} else if (!empty($fixes)) {
    echo "<h3>3. Ready to fix</h3>";
    echo "<p>Found " . count($fixes) . " slip(s) that need fixing.</p>";
    echo "<p><a href='?fix=1' onclick=\"return confirm('Apply fixes?')\">🔧 Click to apply fixes</a></p>";
} else {
    echo "<h3>3. No fixes needed</h3>";
    echo "<p class='ok'>✅ All slips are correctly linked</p>";
}

// 4. Verify recent transactions have slips
echo "<h3>4. Verify Recent Transactions</h3>";
$stmt = $db->query("SELECT t.id, t.order_number, t.status, 
                    (SELECT COUNT(*) FROM payment_slips WHERE transaction_id = t.id) as slip_count
                    FROM transactions t ORDER BY t.id DESC LIMIT 10");
$txns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>Order#</th><th>Status</th><th>Slip Count</th></tr>";
foreach ($txns as $t) {
    $slipClass = $t['slip_count'] > 0 ? 'ok' : 'warn';
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['order_number']}</td>";
    echo "<td>{$t['status']}</td>";
    echo "<td class='{$slipClass}'>{$t['slip_count']}</td>";
    echo "</tr>";
}
echo "</table>";
