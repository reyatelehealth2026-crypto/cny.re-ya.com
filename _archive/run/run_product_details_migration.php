<?php
/**
 * Run Product Details Migration
 * เพิ่มคอลัมน์สำหรับข้อมูลสินค้าจาก CNY Pharmacy API
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Product Details Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Detect which table to use
    $tables = ['products', 'business_items'];
    
    foreach ($tables as $table) {
        // Check if table exists
        try {
            $db->query("SELECT 1 FROM {$table} LIMIT 1");
        } catch (Exception $e) {
            echo "⏭️ Table '{$table}' not found, skipping...\n";
            continue;
        }
        
        echo "\n📦 Processing table: {$table}\n";
        echo str_repeat("-", 50) . "\n";
        
        // Get existing columns
        $stmt = $db->query("SHOW COLUMNS FROM {$table}");
        $existingColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['Field'];
        }
        
        // Columns to add
        $columnsToAdd = [
            'barcode' => "VARCHAR(100) NULL",
            'manufacturer' => "VARCHAR(255) NULL",
            'generic_name' => "VARCHAR(255) NULL",
            'usage_instructions' => "TEXT NULL",
            'unit' => "VARCHAR(50) DEFAULT 'ชิ้น'",
            'extra_data' => "TEXT NULL"  // Use TEXT instead of JSON for PHP 7 compatibility
        ];
        
        foreach ($columnsToAdd as $column => $definition) {
            if (in_array($column, $existingColumns)) {
                echo "✅ Column '{$column}' already exists\n";
            } else {
                try {
                    $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                    echo "✅ Added column '{$column}'\n";
                } catch (Exception $e) {
                    echo "⚠️ Could not add column '{$column}': " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Add index for barcode
        try {
            $stmt = $db->query("SHOW INDEX FROM {$table} WHERE Key_name = 'idx_barcode'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE {$table} ADD INDEX idx_barcode (barcode)");
                echo "✅ Added index 'idx_barcode'\n";
            } else {
                echo "✅ Index 'idx_barcode' already exists\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Could not add index: " . $e->getMessage() . "\n";
        }
        
        // Add index for sku if not exists
        try {
            $stmt = $db->query("SHOW INDEX FROM {$table} WHERE Key_name = 'idx_sku'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE {$table} ADD INDEX idx_sku (sku)");
                echo "✅ Added index 'idx_sku'\n";
            } else {
                echo "✅ Index 'idx_sku' already exists\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Could not add sku index: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='shop/products.php'>← กลับไปหน้าสินค้า</a></p>";
