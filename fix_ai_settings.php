<?php
/**
 * Fix ai_settings table structure
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix AI Settings Table</h2>";
echo "<pre>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Creating ai_settings table...\n";
        $db->exec("CREATE TABLE ai_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT DEFAULT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_setting (line_account_id, setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created successfully!\n";
    } else {
        echo "Table ai_settings exists. Checking columns...\n";
        
        // Check columns
        $stmt = $db->query("DESCRIBE ai_settings");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Current columns: " . implode(', ', $columns) . "\n";
        
        // Check if setting_key exists
        if (!in_array('setting_key', $columns)) {
            echo "Adding setting_key column...\n";
            $db->exec("ALTER TABLE ai_settings ADD COLUMN setting_key VARCHAR(100) NOT NULL AFTER line_account_id");
            echo "✅ setting_key column added!\n";
        }
        
        // Check if setting_value exists
        if (!in_array('setting_value', $columns)) {
            echo "Adding setting_value column...\n";
            $db->exec("ALTER TABLE ai_settings ADD COLUMN setting_value TEXT AFTER setting_key");
            echo "✅ setting_value column added!\n";
        }
        
        // Check if line_account_id exists
        if (!in_array('line_account_id', $columns)) {
            echo "Adding line_account_id column...\n";
            $db->exec("ALTER TABLE ai_settings ADD COLUMN line_account_id INT DEFAULT NULL AFTER id");
            echo "✅ line_account_id column added!\n";
        }
        
        // Try to add unique key if not exists
        try {
            $db->exec("ALTER TABLE ai_settings ADD UNIQUE KEY unique_setting (line_account_id, setting_key)");
            echo "✅ Unique key added!\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠️ Unique key already exists\n";
            } else {
                echo "⚠️ Could not add unique key: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Show final structure
    echo "\n--- Final Table Structure ---\n";
    $stmt = $db->query("DESCRIBE ai_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
