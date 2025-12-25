<?php
/**
 * Fix transactions table - add missing columns
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Fix Transactions Columns</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;}.ok{color:green;}.warn{color:orange;}</style>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✅ Database connected</p>";
    
    // Columns to add
    $columns = [
        'shipping_tracking' => "ALTER TABLE transactions ADD COLUMN shipping_tracking VARCHAR(100) NULL AFTER delivery_info",
        'shipping_name' => "ALTER TABLE transactions ADD COLUMN shipping_name VARCHAR(255) NULL AFTER shipping_tracking",
        'shipping_phone' => "ALTER TABLE transactions ADD COLUMN shipping_phone VARCHAR(20) NULL AFTER shipping_name",
        'shipping_address' => "ALTER TABLE transactions ADD COLUMN shipping_address TEXT NULL AFTER shipping_phone",
        'discount_amount' => "ALTER TABLE transactions ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER grand_total"
    ];
    
    // Check existing columns
    $stmt = $db->query("DESCRIBE transactions");
    $existingCols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    echo "<h3>Current columns:</h3>";
    echo "<p>" . implode(', ', $existingCols) . "</p>";
    
    echo "<h3>Adding missing columns:</h3>";
    
    foreach ($columns as $col => $sql) {
        if (in_array($col, $existingCols)) {
            echo "<p class='warn'>⏭️ {$col} - already exists</p>";
        } else {
            try {
                $db->exec($sql);
                echo "<p class='ok'>✅ Added: {$col}</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ {$col}: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Done!</h3>";
    echo "<p><a href='shop/orders.php'>→ Go to Orders</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
