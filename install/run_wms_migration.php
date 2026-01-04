<?php
/**
 * Run WMS (Warehouse Management System) Migration
 * สร้างตารางและ fields สำหรับระบบ Pick-Pack-Ship
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>📦 WMS (Pick-Pack-Ship) Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Define WMS tables to check
    $wmsTables = [
        'wms_activity_logs',
        'wms_batch_picks',
        'wms_batch_pick_orders',
        'wms_pick_items'
    ];
    
    // Define WMS columns to add to transactions table
    $wmsColumns = [
        'wms_status' => "ENUM('pending_pick','picking','picked','packing','packed','ready_to_ship','shipped','on_hold') DEFAULT NULL",
        'picker_id' => 'INT NULL',
        'packer_id' => 'INT NULL',
        'pick_started_at' => 'DATETIME NULL',
        'pick_completed_at' => 'DATETIME NULL',
        'pack_started_at' => 'DATETIME NULL',
        'pack_completed_at' => 'DATETIME NULL',
        'shipped_at' => 'DATETIME NULL',
        'carrier' => 'VARCHAR(50) NULL',
        'package_weight' => 'DECIMAL(10,2) NULL',
        'package_dimensions' => 'VARCHAR(50) NULL',
        'wms_exception' => 'VARCHAR(255) NULL',
        'wms_exception_resolved_at' => 'DATETIME NULL',
        'wms_exception_resolved_by' => 'INT NULL',
        'label_printed_at' => 'DATETIME NULL'
    ];
    
    // Check if tables already exist
    echo "📋 Checking existing WMS tables:\n";
    foreach ($wmsTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "⚠️ Table '$table' already exists\n";
        } else {
            echo "➡️ Table '$table' will be created\n";
        }
    }
    echo "\n";
    
    // Check existing columns in transactions table
    echo "📋 Checking WMS columns in transactions table:\n";
    $stmt = $db->query("DESCRIBE transactions");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach (array_keys($wmsColumns) as $col) {
        if (in_array($col, $existingColumns)) {
            echo "⚠️ Column '$col' already exists\n";
        } else {
            echo "➡️ Column '$col' will be added\n";
        }
    }
    echo "\n";
    
    $success = 0;
    $skipped = 0;
    $errors = 0;
    
    echo "🔄 Running WMS migration...\n\n";
    
    // Step 1: Add columns to transactions table
    echo "📝 Adding WMS columns to transactions table:\n";
    foreach ($wmsColumns as $colName => $colDef) {
        if (!in_array($colName, $existingColumns)) {
            try {
                $sql = "ALTER TABLE `transactions` ADD COLUMN `$colName` $colDef";
                $db->exec($sql);
                echo "✅ Added column: $colName\n";
                $success++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠️ Skipped (already exists): $colName\n";
                    $skipped++;
                } else {
                    echo "❌ Error adding $colName: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        } else {
            echo "⚠️ Skipped (already exists): $colName\n";
            $skipped++;
        }
    }
    echo "\n";
    
    // Step 2: Add indexes to transactions table
    echo "📝 Adding indexes to transactions table:\n";
    $indexes = [
        'idx_wms_status' => 'wms_status',
        'idx_picker' => 'picker_id',
        'idx_packer' => 'packer_id'
    ];
    
    // Get existing indexes
    $stmt = $db->query("SHOW INDEX FROM transactions");
    $existingIndexes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIndexes[] = $row['Key_name'];
    }
    
    foreach ($indexes as $indexName => $columnName) {
        if (!in_array($indexName, $existingIndexes)) {
            try {
                $sql = "ALTER TABLE `transactions` ADD INDEX `$indexName` (`$columnName`)";
                $db->exec($sql);
                echo "✅ Added index: $indexName\n";
                $success++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠️ Skipped (already exists): $indexName\n";
                    $skipped++;
                } else {
                    echo "❌ Error adding index $indexName: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        } else {
            echo "⚠️ Skipped (already exists): $indexName\n";
            $skipped++;
        }
    }
    echo "\n";
    
    // Step 3: Create WMS tables
    echo "📝 Creating WMS tables:\n";
    
    // Read and execute table creation from SQL file
    $sqlFile = __DIR__ . '/../database/migration_wms.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Extract only CREATE TABLE statements
    preg_match_all('/CREATE TABLE IF NOT EXISTS[^;]+;/s', $sql, $matches);
    
    foreach ($matches[0] as $createStmt) {
        try {
            $db->exec($createStmt);
            // Extract table name for display
            preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/', $createStmt, $tableMatch);
            $tableName = $tableMatch[1] ?? 'unknown';
            echo "✅ Created table: $tableName\n";
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/', $createStmt, $tableMatch);
                $tableName = $tableMatch[1] ?? 'unknown';
                echo "⚠️ Skipped (already exists): $tableName\n";
                $skipped++;
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ Success: $success operations\n";
    if ($skipped > 0) {
        echo "⚠️ Skipped: $skipped operations\n";
    }
    if ($errors > 0) {
        echo "❌ Errors: $errors operations\n";
    }
    echo "========================================\n";
    
    // Verify WMS tables
    echo "\n📋 Verifying WMS tables:\n";
    foreach ($wmsTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "✅ Table '$table' exists ($count rows)\n";
        } else {
            echo "❌ Table '$table' NOT found\n";
        }
    }
    
    // Verify WMS columns in transactions
    echo "\n📋 Verifying WMS columns in transactions table:\n";
    $stmt = $db->query("DESCRIBE transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach (array_keys($wmsColumns) as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists\n";
        } else {
            echo "❌ Column '$col' NOT found\n";
        }
    }
    
    // Show WMS status enum values
    echo "\n📋 WMS Status values:\n";
    $stmt = $db->query("SHOW COLUMNS FROM transactions LIKE 'wms_status'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
        if (isset($matches[1])) {
            $values = explode("','", $matches[1]);
            foreach ($values as $val) {
                echo "   - $val\n";
            }
        }
    }
    
    echo "\n🎉 WMS Migration completed!\n";
    echo "\n<a href='../inventory/index.php'>👉 Go to Inventory (WMS Tab)</a>\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
