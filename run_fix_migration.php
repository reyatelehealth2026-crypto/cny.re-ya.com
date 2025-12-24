<?php
/**
 * Run Fix Migration - แก้ไขปัญหาตารางที่ขาดหายไป
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Running Fix Migration</h1>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $sqlFile = file_get_contents('database/migration_fix_all.sql');
    
    // Split by semicolon and filter empty statements
    $statements = array_filter(
        array_map('trim', explode(';', $sqlFile)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', trim($stmt));
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $sql) {
        if (empty(trim($sql))) continue;
        
        // Skip comments
        if (strpos(trim($sql), '--') === 0) continue;
        
        try {
            $db->exec($sql);
            echo "✅ " . substr(trim($sql), 0, 80) . "...\n";
            $success++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'DUPLICATE') !== false) {
                echo "⏭️ Skipped (already exists): " . substr(trim($sql), 0, 60) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "   SQL: " . substr(trim($sql), 0, 100) . "...\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ Success: $success statements\n";
    echo "❌ Errors: $errors\n";
    echo "========================================\n";
    
    // Verify tables exist
    echo "\n📋 Verifying tables:\n";
    $tables = ['users', 'user_tags', 'user_tag_assignments', 'account_events', 'account_followers', 'account_daily_stats', 'messages', 'orders'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
            $stmt->fetchAll(); // Clear result set
            echo "✅ $table - OK\n";
        } catch (PDOException $e) {
            echo "❌ $table - NOT FOUND\n";
        }
    }
    
    // Check columns in users table
    echo "\n📋 Checking users table columns:\n";
    $columns = ['real_name', 'phone', 'email', 'birthday', 'line_account_id'];
    foreach ($columns as $col) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
            $result = $stmt->fetchAll(); // Use fetchAll to clear buffer
            if (!empty($result)) {
                echo "✅ users.$col - OK\n";
            } else {
                echo "❌ users.$col - NOT FOUND\n";
            }
        } catch (PDOException $e) {
            echo "❌ users.$col - Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Migration completed!\n";
    echo "</pre>";
    
    echo "<p><a href='index.php'>← กลับหน้าหลัก</a></p>";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "</pre>";
}
