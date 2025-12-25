<?php
/**
 * Fix triage_sessions table - Add missing columns for TriageEngine
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix triage_sessions Table</h2>";

$columns = [
    'current_state' => "ALTER TABLE triage_sessions ADD COLUMN current_state VARCHAR(50) DEFAULT 'greeting' AFTER user_id",
    'triage_data' => "ALTER TABLE triage_sessions ADD COLUMN triage_data LONGTEXT AFTER current_state",
    'status' => "ALTER TABLE triage_sessions ADD COLUMN status ENUM('active','completed','escalated','expired') DEFAULT 'active' AFTER triage_data",
    'updated_at' => "ALTER TABLE triage_sessions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
];

foreach ($columns as $col => $sql) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM triage_sessions LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec($sql);
            echo "<p style='color:green'>✅ Added column: $col</p>";
        } else {
            echo "<p style='color:blue'>ℹ️ Column '$col' already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error adding $col: " . $e->getMessage() . "</p>";
    }
}

// Add index on status
try {
    $db->exec("ALTER TABLE triage_sessions ADD INDEX idx_status (status)");
    echo "<p style='color:green'>✅ Added index on status</p>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "<p style='color:blue'>ℹ️ Index already exists</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Index: " . $e->getMessage() . "</p>";
    }
}

// Add unique constraint for active session per user
try {
    // First, close any duplicate active sessions
    $db->exec("UPDATE triage_sessions SET status = 'expired' 
               WHERE id NOT IN (
                   SELECT id FROM (
                       SELECT MAX(id) as id FROM triage_sessions 
                       WHERE status = 'active' GROUP BY user_id
                   ) as t
               ) AND status = 'active'");
    echo "<p style='color:green'>✅ Cleaned up duplicate active sessions</p>";
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ Cleanup: " . $e->getMessage() . "</p>";
}

echo "<h3>Updated Table Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE triage_sessions");
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ " . $e->getMessage() . "</p>";
}

echo "<p style='color:green; font-weight:bold; margin-top:20px'>✅ Done! Now triage sessions should work correctly.</p>";
