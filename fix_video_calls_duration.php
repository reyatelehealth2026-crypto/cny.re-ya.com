<?php
/**
 * Fix: Add missing 'duration' column to video_calls table
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix video_calls table - Add duration column</h2>";

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM video_calls LIKE 'duration'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE video_calls ADD COLUMN duration INT DEFAULT 0 COMMENT 'Duration in seconds' AFTER status");
        echo "<p style='color:green'>✅ Added 'duration' column to video_calls table</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Column 'duration' already exists</p>";
    }
    
    // Also check for ended_at column
    $stmt = $db->query("SHOW COLUMNS FROM video_calls LIKE 'ended_at'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE video_calls ADD COLUMN ended_at TIMESTAMP NULL AFTER answered_at");
        echo "<p style='color:green'>✅ Added 'ended_at' column to video_calls table</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Column 'ended_at' already exists</p>";
    }
    
    echo "<p style='color:green; font-weight:bold'>✅ Done!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
