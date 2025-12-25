<?php
/**
 * Migration Runner: Unify products and business_items
 * ยึด products เป็นตารางหลัก
 * 
 * วิธีใช้: php run_unify_products_migration.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

function getDB() {
    return Database::getInstance()->getConnection();
}

echo "===========================================\n";
echo "Migration: Unify products & business_items\n";
echo "===========================================\n\n";

try {
    $pdo = getDB();
    
    // Check if business_items exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'business_items'");
    if ($stmt->rowCount() == 0) {
        echo "⚠️  Table 'business_items' not found. Migration may have already run.\n";
        exit(0);
    }
    
    // Count records
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $businessItemCount = $pdo->query("SELECT COUNT(*) FROM business_items")->fetchColumn();
    
    echo "📊 Current Status:\n";
    echo "   - products: {$productCount} records\n";
    echo "   - business_items: {$businessItemCount} records\n\n";
    
    if ($businessItemCount == 0) {
        echo "✅ No data in business_items. Skipping migration.\n";
        exit(0);
    }
    
    // Confirm before proceeding
    echo "⚠️  This will:\n";
    echo "   1. Add columns to products table\n";
    echo "   2. Migrate data from business_items to products\n";
    echo "   3. Update cart_items, order_items references\n";
    echo "   4. Backup business_items table\n\n";
    
    echo "Continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }
    fclose($handle);
    
    echo "\n🚀 Starting migration...\n\n";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/migration_unify_products.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($s) { 
            return !empty($s) && strpos($s, '--') !== 0; 
        }
    );
    
    $pdo->beginTransaction();
    
    $count = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        // Skip comments
        if (preg_match('/^--/', trim($statement))) continue;
        if (preg_match('/^\/\*/', trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
            echo ".";
        } catch (PDOException $e) {
            // Ignore "column already exists" errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "s"; // skip
                continue;
            }
            // Ignore "table doesn't exist" for backup rename
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                echo "s";
                continue;
            }
            throw $e;
        }
    }
    
    $pdo->commit();
    
    echo "\n\n✅ Migration completed! ({$count} statements executed)\n\n";
    
    // Show results
    $newProductCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $migratedCount = $pdo->query("SELECT COUNT(*) FROM products WHERE old_business_item_id IS NOT NULL")->fetchColumn();
    
    echo "📊 Results:\n";
    echo "   - products now: {$newProductCount} records\n";
    echo "   - migrated from business_items: {$migratedCount} records\n";
    
    // Check if backup exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'business_items_backup%'");
    if ($stmt->rowCount() > 0) {
        $backupTable = $stmt->fetchColumn();
        echo "   - backup table: {$backupTable}\n";
    }
    
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}