<?php
/**
 * Run Sync Queue Migration
 * สร้าง tables สำหรับระบบ sync queue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "=== Sync Queue Migration ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    $sqlFile = __DIR__ . '/database/migration_sync_queue.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon but handle multi-line statements
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $current .= ' ' . $line;
        
        if (substr($line, -1) === ';') {
            $statements[] = trim($current);
            $current = '';
        }
    }
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $stmt) {
        if (empty(trim($stmt))) continue;
        
        try {
            $db->exec($stmt);
            $success++;
            
            // Extract table/view name for display
            if (preg_match('/CREATE\s+(TABLE|VIEW)\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:OR\s+REPLACE\s+)?(\w+)/i', $stmt, $matches)) {
                echo "✓ Created {$matches[1]}: {$matches[2]}\n";
            } elseif (preg_match('/INSERT/i', $stmt)) {
                echo "✓ Inserted default config\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⏭ Skipped (already exists)\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Success: {$success}\n";
    echo "Errors: {$errors}\n";
    
    // Verify tables
    echo "\n=== Verifying Tables ===\n";
    $tables = ['sync_queue', 'sync_batches', 'sync_logs', 'sync_config'];
    foreach ($tables as $table) {
        try {
            $db->query("SELECT 1 FROM {$table} LIMIT 1");
            echo "✓ {$table} exists\n";
        } catch (Exception $e) {
            echo "✗ {$table} missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
