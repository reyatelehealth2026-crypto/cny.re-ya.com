<?php
/**
 * Fix user_states table structure
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix user_states Table</h2>";

// Check current structure
echo "<h3>1. Current Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE user_states");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='3'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table><br>";
    
    // Check if user_id is PRIMARY KEY
    $stmt = $db->query("SHOW KEYS FROM user_states WHERE Key_name = 'PRIMARY'");
    $pk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pk && $pk['Column_name'] === 'user_id') {
        echo "✅ user_id is already PRIMARY KEY - no fix needed<br>";
    } else {
        echo "⚠️ user_id is NOT PRIMARY KEY - fixing...<br>";
        
        // Run migration
        $sql = file_get_contents('database/migration_fix_user_states.sql');
        $db->exec($sql);
        echo "✅ Table recreated with correct structure<br>";
    }
} catch (Exception $e) {
    echo "Table doesn't exist - creating...<br>";
    $sql = file_get_contents('database/migration_fix_user_states.sql');
    $db->exec($sql);
    echo "✅ Table created<br>";
}

// Verify
echo "<h3>2. New Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE user_states");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='3'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br><a href='debug_slip.php'>← Back to Debug Slip</a>";
