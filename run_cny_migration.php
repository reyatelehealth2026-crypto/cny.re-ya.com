<?php
/**
 * Run CNY Sync Migration
 * เพิ่ม columns ที่จำเป็นสำหรับ sync สินค้าจาก CNY Pharmacy
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// For browser output
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

echo "=== CNY Sync Migration ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";
ob_flush(); flush();

$tables = ['business_items', 'products'];
$columnsToAdd = [
    'sku' => "VARCHAR(100) DEFAULT NULL COMMENT 'รหัสสินค้า SKU'",
    'barcode' => "VARCHAR(100) DEFAULT NULL COMMENT 'บาร์โค้ด'",
    'manufacturer' => "VARCHAR(255) DEFAULT NULL COMMENT 'ผู้ผลิต/บริษัท'",
    'generic_name' => "VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อสามัญยา'",
    'usage_instructions' => "TEXT DEFAULT NULL COMMENT 'วิธีใช้/ขนาดรับประทาน'",
    'unit' => "VARCHAR(50) DEFAULT 'ชิ้น' COMMENT 'หน่วยนับ'",
    'extra_data' => "JSON DEFAULT NULL COMMENT 'ข้อมูลเพิ่มเติมจาก API'"
];

$indexesToAdd = [
    'idx_sku' => 'sku',
    'idx_barcode' => 'barcode'
];

function tableExists($db, $table) {
    try {
        $db->query("SELECT 1 FROM {$table} LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function columnExists($db, $table, $column) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function indexExists($db, $table, $indexName) {
    try {
        $stmt = $db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

try {
    foreach ($tables as $table) {
        if (!tableExists($db, $table)) {
            echo "Table '{$table}' not found, skipping...\n";
            continue;
        }
        
        echo "Processing table: {$table}\n";
        
        // Add columns
        foreach ($columnsToAdd as $column => $definition) {
            if (columnExists($db, $table, $column)) {
                echo "  - Column '{$column}' already exists\n";
            } else {
                try {
                    $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                    echo "  + Added column '{$column}'\n";
                } catch (Exception $e) {
                    echo "  ! Error adding '{$column}': " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Add indexes
        foreach ($indexesToAdd as $indexName => $column) {
            if (!columnExists($db, $table, $column)) continue;
            
            if (indexExists($db, $table, $indexName)) {
                echo "  - Index '{$indexName}' already exists\n";
            } else {
                try {
                    $db->exec("ALTER TABLE {$table} ADD INDEX {$indexName} ({$column})");
                    echo "  + Added index '{$indexName}'\n";
                } catch (Exception $e) {
                    echo "  ! Error adding index '{$indexName}': " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n";
    }
    
    echo "✓ Migration completed: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
    echo '<p><a href="sync_cny_products.php">➡️ Run Product Sync</a></p>';
}
