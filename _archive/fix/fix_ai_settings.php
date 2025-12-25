<?php
/**
 * Fix ai_settings table structure
 * Uses column-based structure (NOT key-value)
 * Columns: id, line_account_id, is_enabled, system_prompt, model, max_tokens, temperature, gemini_api_key, created_at
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix AI Settings Table (Column-Based Structure)</h2>";
echo "<pre>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Creating ai_settings table with column-based structure...\n";
        $db->exec("CREATE TABLE ai_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT DEFAULT NULL,
            is_enabled TINYINT(1) DEFAULT 0,
            system_prompt TEXT,
            model VARCHAR(50) DEFAULT 'gpt-3.5-turbo',
            max_tokens INT DEFAULT 500,
            temperature DECIMAL(2,1) DEFAULT 0.7,
            gemini_api_key VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_account (line_account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created successfully!\n";
    } else {
        echo "Table ai_settings exists. Checking and fixing columns...\n";
        
        // Get current columns
        $stmt = $db->query("DESCRIBE ai_settings");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        echo "Current columns: " . implode(', ', array_keys($columns)) . "\n\n";
        
        // Required columns for column-based structure
        $requiredColumns = [
            'line_account_id' => "INT DEFAULT NULL",
            'is_enabled' => "TINYINT(1) DEFAULT 0",
            'system_prompt' => "TEXT",
            'model' => "VARCHAR(50) DEFAULT 'gpt-3.5-turbo'",
            'max_tokens' => "INT DEFAULT 500",
            'temperature' => "DECIMAL(2,1) DEFAULT 0.7",
            'gemini_api_key' => "VARCHAR(255) DEFAULT NULL",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        
        // Add missing columns
        foreach ($requiredColumns as $col => $definition) {
            if (!isset($columns[$col])) {
                echo "Adding column: {$col}...\n";
                try {
                    $db->exec("ALTER TABLE ai_settings ADD COLUMN {$col} {$definition}");
                    echo "✅ {$col} added!\n";
                } catch (Exception $e) {
                    echo "⚠️ Could not add {$col}: " . $e->getMessage() . "\n";
                }
            } else {
                echo "✓ Column {$col} exists\n";
            }
        }
        
        // Check if old key-value columns exist and migrate data
        if (isset($columns['setting_key']) && isset($columns['setting_value'])) {
            echo "\n--- Migrating from key-value to column-based structure ---\n";
            
            // Get all unique line_account_ids
            $stmt = $db->query("SELECT DISTINCT line_account_id FROM ai_settings WHERE setting_key IS NOT NULL");
            $accountIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($accountIds as $accountId) {
                $accountId = $accountId ?: 'NULL';
                echo "Migrating data for line_account_id: {$accountId}\n";
                
                // Get key-value pairs
                if ($accountId === 'NULL') {
                    $stmt = $db->query("SELECT setting_key, setting_value FROM ai_settings WHERE line_account_id IS NULL AND setting_key IS NOT NULL");
                } else {
                    $stmt = $db->prepare("SELECT setting_key, setting_value FROM ai_settings WHERE line_account_id = ? AND setting_key IS NOT NULL");
                    $stmt->execute([$accountId]);
                }
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                if (!empty($settings)) {
                    // Map old keys to new columns
                    $geminiKey = $settings['gemini_api_key'] ?? null;
                    $isEnabled = isset($settings['ai_enabled']) ? ($settings['ai_enabled'] == '1' ? 1 : 0) : 0;
                    $systemPrompt = $settings['system_prompt'] ?? null;
                    $model = $settings['model'] ?? 'gpt-3.5-turbo';
                    
                    // Update or insert with new structure
                    if ($accountId === 'NULL') {
                        $db->exec("UPDATE ai_settings SET 
                            gemini_api_key = " . ($geminiKey ? "'" . addslashes($geminiKey) . "'" : "NULL") . ",
                            is_enabled = {$isEnabled},
                            system_prompt = " . ($systemPrompt ? "'" . addslashes($systemPrompt) . "'" : "NULL") . ",
                            model = '{$model}'
                            WHERE line_account_id IS NULL LIMIT 1");
                    } else {
                        $stmt = $db->prepare("UPDATE ai_settings SET 
                            gemini_api_key = ?,
                            is_enabled = ?,
                            system_prompt = ?,
                            model = ?
                            WHERE line_account_id = ? LIMIT 1");
                        $stmt->execute([$geminiKey, $isEnabled, $systemPrompt, $model, $accountId]);
                    }
                    echo "✅ Migrated settings for account {$accountId}\n";
                }
            }
            
            // Remove old key-value columns (optional - comment out if you want to keep them)
            // echo "\nRemoving old key-value columns...\n";
            // try {
            //     $db->exec("ALTER TABLE ai_settings DROP COLUMN setting_key");
            //     $db->exec("ALTER TABLE ai_settings DROP COLUMN setting_value");
            //     echo "✅ Old columns removed!\n";
            // } catch (Exception $e) {
            //     echo "⚠️ Could not remove old columns: " . $e->getMessage() . "\n";
            // }
        }
        
        // Try to add unique key on line_account_id if not exists
        try {
            $db->exec("ALTER TABLE ai_settings ADD UNIQUE KEY unique_account (line_account_id)");
            echo "\n✅ Unique key on line_account_id added!\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "\n⚠️ Unique key already exists\n";
            }
        }
    }
    
    // Show final structure
    echo "\n--- Final Table Structure ---\n";
    $stmt = $db->query("DESCRIBE ai_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    
    // Show current data
    echo "\n--- Current Data ---\n";
    $stmt = $db->query("SELECT id, line_account_id, is_enabled, LEFT(system_prompt, 50) as prompt_preview, model, gemini_api_key IS NOT NULL as has_api_key FROM ai_settings LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($data)) {
        echo "No data found\n";
    } else {
        foreach ($data as $row) {
            echo "ID: {$row['id']}, Account: {$row['line_account_id']}, Enabled: {$row['is_enabled']}, Model: {$row['model']}, Has API Key: " . ($row['has_api_key'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
