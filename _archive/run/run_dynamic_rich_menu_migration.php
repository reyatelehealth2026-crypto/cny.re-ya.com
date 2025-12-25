<?php
/**
 * Run Dynamic Rich Menu Migration
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🎨 Dynamic Rich Menu Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute migration
    $sql = file_get_contents(__DIR__ . '/database/migration_dynamic_rich_menu.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $db->exec($statement);
            // Extract table name for display
            if (preg_match('/CREATE TABLE.*?(\w+)/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "✅ Altered table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "⏭️ Skipped (already exists)\n";
            } else {
                echo "⚠️ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\n<a href='dynamic-rich-menu.php'>➡️ ไปหน้า Dynamic Rich Menu</a>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
