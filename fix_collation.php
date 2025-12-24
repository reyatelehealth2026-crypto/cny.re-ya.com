<?php
/**
 * Fix Collation Issues
 * แก้ไข collation ของตาราง users ให้เป็น utf8mb4_unicode_ci ทั้งหมด
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Fix Collation Issues</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get current collation of users table
    $stmt = $db->query("SHOW TABLE STATUS LIKE 'users'");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current table collation: <strong>" . ($table['Collation'] ?? 'unknown') . "</strong></p>";
    
    // Fix table collation
    echo "<h3>Fixing table collation...</h3>";
    $db->exec("ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Table collation fixed</p>";
    
    // Also fix other related tables
    $tables = ['member_tiers', 'points_history', 'user_consents'];
    
    foreach ($tables as $tableName) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
            if ($stmt->rowCount() > 0) {
                $db->exec("ALTER TABLE $tableName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<p>✅ Fixed: $tableName</p>";
            }
        } catch (Exception $e) {
            echo "<p>⚠️ $tableName: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>✅ Done!</h3>";
    echo "<p><a href='liff-register.php?account=1'>ทดสอบสมัครสมาชิก</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
