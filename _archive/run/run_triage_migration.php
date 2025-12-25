<?php
/**
 * Run Triage System Migration
 * สร้างตารางสำหรับระบบ Triage AI
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>🏥 Triage System Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $sqlFile = __DIR__ . '/database/migration_triage_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            echo "✅ Executed: " . substr($statement, 0, 60) . "...\n";
            $success++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⏭️ Skipped (already exists): " . substr($statement, 0, 60) . "...\n";
            } else {
                echo "❌ Failed: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ Success: $success statements\n";
    echo "❌ Failed: $failed statements\n";
    echo "========================================\n";
    
    echo "\n🎉 Migration completed!\n";
    echo "\nNext steps:\n";
    echo "1. Go to ai-chat-settings.php to configure AI\n";
    echo "2. Go to pharmacist-dashboard.php to manage requests\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
