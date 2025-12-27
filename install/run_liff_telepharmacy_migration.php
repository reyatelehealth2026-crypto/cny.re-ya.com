<?php
/**
 * LIFF Telepharmacy Migration Runner
 * Run this script to set up all required tables for LIFF Telepharmacy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>🏥 LIFF Telepharmacy Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $migrationFile = __DIR__ . '/../database/migration_liff_telepharmacy.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'SELECT') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $success++;
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE (\w+)/i', $statement, $matches)) {
                echo "✅ Altered table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Ignore "column already exists" errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⏭️ Column already exists, skipping...\n";
            } else {
                echo "⚠️ Warning: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Migration completed!\n";
    echo "✅ Successful: $success statements\n";
    if ($errors > 0) {
        echo "⚠️ Warnings: $errors\n";
    }
    echo "========================================\n";
    
    // Check LIFF configuration
    echo "\n📱 Checking LIFF Configuration...\n\n";
    
    $stmt = $db->query("SELECT id, name, liff_id, is_default, is_active FROM line_accounts ORDER BY is_default DESC, id ASC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "⚠️ No LINE accounts found. Please add a LINE account first.\n";
    } else {
        echo "LINE Accounts:\n";
        foreach ($accounts as $acc) {
            $default = $acc['is_default'] ? ' (DEFAULT)' : '';
            $active = $acc['is_active'] ? '✅' : '❌';
            $liffStatus = !empty($acc['liff_id']) ? '✅' : '❌ Missing';
            echo "  {$active} ID: {$acc['id']} - {$acc['name']}{$default}\n";
            echo "     LIFF ID: {$liffStatus} {$acc['liff_id']}\n";
        }
    }
    
    // Check required tables
    echo "\n📋 Checking Required Tables...\n\n";
    
    $requiredTables = [
        'prescription_approvals',
        'user_health_profiles',
        'user_drug_allergies',
        'user_current_medications',
        'medication_reminders',
        'medication_taken_history',
        'user_notification_preferences',
        'drug_interaction_acknowledgments',
        'video_calls',
        'video_call_signals',
        'video_call_settings'
    ];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? '✅' : '❌';
        echo "  {$status} {$table}\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "🎉 Setup Complete!\n";
    echo "========================================\n";
    echo "\n";
    echo "Next Steps:\n";
    echo "1. Ensure LIFF ID is configured in LINE Developers Console\n";
    echo "2. Set LIFF Endpoint URL to: " . BASE_URL . "liff/index.php\n";
    echo "3. Access LIFF app via: https://liff.line.me/{YOUR_LIFF_ID}\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
