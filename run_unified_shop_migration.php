<?php
/**
 * Run Unified Shop Migration
 * รันไฟล์นี้เพื่ออัพเกรดระบบ Shop เป็น V3.0
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🛒 Unified Shop Migration V3.0</h1>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute migration SQL
    $sqlFile = __DIR__ . '/database/migration_unified_shop.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $db->exec($statement);
            echo "✅ Executed: " . substr($statement, 0, 60) . "...\n";
            $success++;
        } catch (PDOException $e) {
            // Ignore some expected errors
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️ Skipped (already exists): " . substr($statement, 0, 60) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ Success: {$success} statements\n";
    echo "❌ Errors: {$errors} statements\n";
    echo "========================================\n";
    
    // Test UnifiedShop
    if (file_exists('classes/UnifiedShop.php')) {
        require_once 'classes/UnifiedShop.php';
    }
    $shop = new UnifiedShop($db);
    
    echo "\n📊 System Status:\n";
    echo "- Shop Ready: " . ($shop->isReady() ? '✅ Yes' : '❌ No') . "\n";
    echo "- V2.5 Mode: " . ($shop->isV25() ? '✅ Yes' : '❌ No') . "\n";
    echo "- Items Table: " . ($shop->getItemsTable() ?? 'Not found') . "\n";
    echo "- Categories Table: " . ($shop->getCategoriesTable() ?? 'Not found') . "\n";
    echo "- Orders Table: " . ($shop->getOrdersTable() ?? 'Not found') . "\n";
    
    echo "\n🎉 Migration completed!\n";
    echo "Go to <a href='shop/'>Shop Dashboard</a> to start using the system.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
