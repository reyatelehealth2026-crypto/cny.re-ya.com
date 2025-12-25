<?php
/**
 * Fix cart_items foreign key constraint
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Fix Cart Items Foreign Key</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected<br><br>";
    
    // Check current foreign keys
    $stmt = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                        WHERE CONSTRAINT_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'cart_items' 
                        AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($fks)) {
        echo "ℹ️ No foreign keys found on cart_items<br>";
    } else {
        echo "Found foreign keys: " . implode(', ', $fks) . "<br><br>";
        
        foreach ($fks as $fk) {
            try {
                $db->exec("ALTER TABLE cart_items DROP FOREIGN KEY `{$fk}`");
                echo "✅ Dropped foreign key: {$fk}<br>";
            } catch (Exception $e) {
                echo "⚠️ Could not drop {$fk}: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Check if business_items table exists
    $hasBusinessItems = false;
    try {
        $db->query("SELECT 1 FROM business_items LIMIT 1");
        $hasBusinessItems = true;
        echo "<br>ℹ️ business_items table exists<br>";
        
        // Count items
        $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE is_active = 1");
        $count = $stmt->fetchColumn();
        echo "   Active items in business_items: {$count}<br>";
    } catch (Exception $e) {
        echo "<br>ℹ️ business_items table does not exist<br>";
    }
    
    // Check products table
    try {
        $db->query("SELECT 1 FROM products LIMIT 1");
        $stmt = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $count = $stmt->fetchColumn();
        echo "   Active items in products: {$count}<br>";
    } catch (Exception $e) {
        echo "⚠️ products table does not exist<br>";
    }
    
    echo "<br><h3>✅ Done!</h3>";
    echo "<p>ลูกค้าควรจะเพิ่มสินค้าลงตะกร้าได้แล้ว</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
