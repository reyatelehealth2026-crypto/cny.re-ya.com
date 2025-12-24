<?php
/**
 * Fix: Add is_featured and is_bestseller columns to products table
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<pre>";
echo "Adding promotion columns to products table...\n\n";

$columns = [
    'is_featured' => "ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0",
    'is_bestseller' => "ALTER TABLE products ADD COLUMN is_bestseller TINYINT(1) DEFAULT 0"
];

foreach ($columns as $col => $sql) {
    try {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM products LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Column '$col' already exists\n";
        } else {
            $db->exec($sql);
            echo "✓ Added column '$col'\n";
        }
    } catch (Exception $e) {
        echo "✗ Error with '$col': " . $e->getMessage() . "\n";
    }
}

// Add indexes
echo "\nAdding indexes...\n";
try {
    $db->exec("CREATE INDEX idx_products_featured ON products(is_featured)");
    echo "✓ Added index for is_featured\n";
} catch (Exception $e) {
    echo "Index may already exist: " . $e->getMessage() . "\n";
}

try {
    $db->exec("CREATE INDEX idx_products_bestseller ON products(is_bestseller)");
    echo "✓ Added index for is_bestseller\n";
} catch (Exception $e) {
    echo "Index may already exist: " . $e->getMessage() . "\n";
}

echo "\n✅ Done!\n";
echo "\n<a href='/shop/promotions'>Go to Promotions Page</a>\n";
echo "</pre>";
