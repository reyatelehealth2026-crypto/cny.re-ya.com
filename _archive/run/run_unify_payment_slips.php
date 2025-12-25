<?php
/**
 * Run migration to unify payment_slips to use transaction_id
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Unify Payment Slips Migration</h2>";

try {
    // 1. Check if transaction_id column exists
    echo "<h3>1. Check transaction_id column</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM payment_slips LIKE 'transaction_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding transaction_id column...<br>";
        $db->exec("ALTER TABLE payment_slips ADD COLUMN transaction_id INT DEFAULT NULL AFTER id");
        echo "✅ Added transaction_id column<br>";
    } else {
        echo "✅ transaction_id column already exists<br>";
    }
    
    // 2. Add index
    echo "<h3>2. Add index for transaction_id</h3>";
    try {
        $db->exec("CREATE INDEX idx_transaction ON payment_slips(transaction_id)");
        echo "✅ Added index<br>";
    } catch (Exception $e) {
        echo "✅ Index already exists<br>";
    }
    
    // 3. Copy order_id to transaction_id
    echo "<h3>3. Copy order_id to transaction_id</h3>";
    $stmt = $db->query("SELECT COUNT(*) FROM payment_slips WHERE transaction_id IS NULL AND order_id IS NOT NULL");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $db->exec("UPDATE payment_slips SET transaction_id = order_id WHERE transaction_id IS NULL AND order_id IS NOT NULL");
        echo "✅ Updated {$count} records<br>";
    } else {
        echo "✅ No records to update<br>";
    }
    
    // 4. Check user_id column
    echo "<h3>4. Check user_id column</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM payment_slips LIKE 'user_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding user_id column...<br>";
        $db->exec("ALTER TABLE payment_slips ADD COLUMN user_id INT DEFAULT NULL AFTER transaction_id");
        echo "✅ Added user_id column<br>";
    } else {
        echo "✅ user_id column already exists<br>";
    }
    
    // 5. Update user_id from transactions
    echo "<h3>5. Update user_id from transactions</h3>";
    $stmt = $db->query("SELECT COUNT(*) FROM payment_slips ps JOIN transactions t ON ps.transaction_id = t.id WHERE ps.user_id IS NULL");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $db->exec("UPDATE payment_slips ps JOIN transactions t ON ps.transaction_id = t.id SET ps.user_id = t.user_id WHERE ps.user_id IS NULL");
        echo "✅ Updated {$count} records<br>";
    } else {
        echo "✅ No records to update<br>";
    }
    
    // 6. Show current state
    echo "<h3>6. Current payment_slips state</h3>";
    $stmt = $db->query("SELECT * FROM payment_slips ORDER BY id DESC LIMIT 5");
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($slips) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>transaction_id</th><th>order_id</th><th>user_id</th><th>status</th><th>created_at</th></tr>";
        foreach ($slips as $s) {
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>" . ($s['transaction_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($s['order_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($s['user_id'] ?? 'NULL') . "</td>";
            echo "<td>{$s['status']}</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No payment slips found<br>";
    }
    
    echo "<h3>✅ Migration completed!</h3>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
