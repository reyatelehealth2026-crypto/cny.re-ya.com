<?php
/**
 * Run Health Articles Migration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Health Articles Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    $sqlFile = __DIR__ . '/../database/migration_health_articles.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $db->exec($statement);
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Inserted data into: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Altered table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️ Table already exists, skipping...\n";
            } elseif (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠️ Data already exists, skipping...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Go to Landing Settings > บทความ to manage articles\n";
    echo "2. View articles at: " . BASE_URL . "articles.php\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='../admin/landing-settings.php?tab=articles'>← Go to Article Management</a></p>";
