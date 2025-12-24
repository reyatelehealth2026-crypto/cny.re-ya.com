<?php
/**
 * Debug Payment Slips - ตรวจสอบปัญหาสลิปไม่แสดงในหลังบ้าน
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔍 Debug Payment Slips</h2>";
echo "<style>
    body { font-family: sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    .ok { color: green; }
    .warn { color: orange; }
    .error { color: red; }
    .box { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 8px; }
</style>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✅ Database connected</p>";
    
    // 1. Check recent payment_slips
    echo "<h3>1. Recent Payment Slips</h3>";
    $stmt = $db->query("SELECT ps.*, t.order_number, t.status as txn_status, t.line_account_id as txn_bot_id, t.user_id as txn_user_id
                        FROM payment_slips ps 
                        LEFT JOIN transactions t ON ps.transaction_id = t.id 
                        ORDER BY ps.id DESC LIMIT 10");
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($slips) {
        echo "<table>";
        echo "<tr><th>Slip ID</th><th>Transaction ID</th><th>Order#</th><th>User ID</th><th>Slip Status</th><th>Txn Status</th><th>Bot ID</th><th>Image</th><th>Created</th></tr>";
        foreach ($slips as $slip) {
            $hasOrder = $slip['order_number'] ? 'ok' : 'error';
            echo "<tr>";
            echo "<td>{$slip['id']}</td>";
            echo "<td class='{$hasOrder}'>{$slip['transaction_id']}</td>";
            echo "<td class='{$hasOrder}'>" . ($slip['order_number'] ?: '<span class="error">NOT FOUND!</span>') . "</td>";
            echo "<td>{$slip['user_id']}</td>";
            echo "<td>{$slip['status']}</td>";
            echo "<td>" . ($slip['txn_status'] ?: '-') . "</td>";
            echo "<td>" . ($slip['txn_bot_id'] ?: '<span class="warn">NULL</span>') . "</td>";
            echo "<td><a href='{$slip['image_url']}' target='_blank'>View</a></td>";
            echo "<td>{$slip['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warn'>⚠️ No payment slips found</p>";
    }
    
    // 2. Check recent transactions
    echo "<h3>2. Recent Transactions</h3>";
    $stmt = $db->query("SELECT t.id, t.order_number, t.user_id, t.status, t.payment_status, t.grand_total, t.line_account_id, t.created_at,
                        (SELECT COUNT(*) FROM payment_slips WHERE transaction_id = t.id) as slip_count
                        FROM transactions t ORDER BY t.id DESC LIMIT 10");
    $txns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($txns) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Payment</th><th>Total</th><th>Bot ID</th><th>Slips</th><th>Created</th></tr>";
        foreach ($txns as $txn) {
            $botClass = $txn['line_account_id'] ? 'ok' : 'warn';
            $slipClass = $txn['slip_count'] > 0 ? 'ok' : '';
            echo "<tr>";
            echo "<td>{$txn['id']}</td>";
            echo "<td>{$txn['order_number']}</td>";
            echo "<td>{$txn['user_id']}</td>";
            echo "<td>{$txn['status']}</td>";
            echo "<td>{$txn['payment_status']}</td>";
            echo "<td>฿" . number_format($txn['grand_total'], 2) . "</td>";
            echo "<td class='{$botClass}'>" . ($txn['line_account_id'] ?: '<span class="warn">NULL</span>') . "</td>";
            echo "<td class='{$slipClass}'>{$txn['slip_count']}</td>";
            echo "<td>{$txn['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check session bot ID
    echo "<h3>3. Session Info</h3>";
    session_start();
    $sessionBotId = $_SESSION['current_bot_id'] ?? null;
    echo "<div class='box'>";
    echo "<p><strong>Session current_bot_id:</strong> " . ($sessionBotId ?: '<span class="warn">NOT SET</span>') . "</p>";
    
    // 4. Check line_accounts
    echo "</div><h3>4. Line Accounts</h3>";
    $stmt = $db->query("SELECT id, name, is_active, is_default FROM line_accounts ORDER BY id");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Default</th></tr>";
    foreach ($accounts as $acc) {
        $isSelected = ($acc['id'] == $sessionBotId) ? ' style="background:#e8f5e9"' : '';
        echo "<tr{$isSelected}>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td>" . ($acc['is_active'] ? '✅' : '❌') . "</td>";
        echo "<td>" . ($acc['is_default'] ? '⭐' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Problem Analysis
    echo "<h3>5. 🔎 Problem Analysis</h3>";
    echo "<div class='box'>";
    
    // Check if there are slips with mismatched transaction_id
    $stmt = $db->query("SELECT ps.id, ps.transaction_id FROM payment_slips ps 
                        LEFT JOIN transactions t ON ps.transaction_id = t.id 
                        WHERE t.id IS NULL");
    $orphanSlips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($orphanSlips) {
        echo "<p class='error'>❌ Found " . count($orphanSlips) . " slips with invalid transaction_id!</p>";
        echo "<ul>";
        foreach ($orphanSlips as $os) {
            echo "<li>Slip #{$os['id']} → transaction_id={$os['transaction_id']} (NOT EXISTS)</li>";
        }
        echo "</ul>";
    }
    
    // Check transactions without line_account_id
    $stmt = $db->query("SELECT COUNT(*) FROM transactions WHERE line_account_id IS NULL");
    $nullBotCount = $stmt->fetchColumn();
    if ($nullBotCount > 0) {
        echo "<p class='warn'>⚠️ Found {$nullBotCount} transactions with NULL line_account_id</p>";
        echo "<p>These may not show in orders page if filtered by bot ID</p>";
    }
    
    // Check if session bot matches transactions
    if ($sessionBotId) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE line_account_id = ?");
        $stmt->execute([$sessionBotId]);
        $matchCount = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) FROM transactions");
        $totalCount = $stmt->fetchColumn();
        
        echo "<p>📊 Transactions matching session bot (ID={$sessionBotId}): {$matchCount} / {$totalCount}</p>";
        
        if ($matchCount < $totalCount) {
            echo "<p class='warn'>⚠️ Some transactions may not show because line_account_id doesn't match!</p>";
        }
    }
    
    echo "</div>";
    
    // 6. Quick Fix Options
    echo "<h3>6. 🔧 Quick Fix</h3>";
    
    if (isset($_GET['fix']) && $_GET['fix'] === 'bot_id') {
        // Fix NULL line_account_id
        $defaultBotId = 1;
        $stmt = $db->prepare("UPDATE transactions SET line_account_id = ? WHERE line_account_id IS NULL");
        $stmt->execute([$defaultBotId]);
        $affected = $stmt->rowCount();
        echo "<p class='ok'>✅ Fixed {$affected} transactions - set line_account_id to {$defaultBotId}</p>";
        echo "<p><a href='debug_payment_slips.php'>Refresh to see results</a></p>";
    } else {
        echo "<div class='box'>";
        echo "<p>If transactions have NULL line_account_id, click below to fix:</p>";
        echo "<a href='?fix=bot_id' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Fix NULL line_account_id</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
