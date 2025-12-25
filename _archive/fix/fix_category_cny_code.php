<?php
/**
 * Add cny_code column to product_categories table
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Add cny_code column to product_categories</h2><pre>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected\n\n";
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM product_categories LIKE 'cny_code'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'cny_code' already exists\n";
    } else {
        // Add column
        $db->exec("ALTER TABLE product_categories ADD COLUMN cny_code VARCHAR(100) NULL AFTER name");
        echo "✅ Added column 'cny_code'\n";
        
        // Add index
        try {
            $db->exec("ALTER TABLE product_categories ADD INDEX idx_cny_code (cny_code)");
            echo "✅ Added index 'idx_cny_code'\n";
        } catch (Exception $e) {
            echo "⚠️ Index might already exist\n";
        }
    }
    
    // Show current columns
    echo "\nCurrent columns:\n";
    $stmt = $db->query("SHOW COLUMNS FROM product_categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='sync_update_categories.php'>→ ไปหน้า Sync Categories</a></p>";
