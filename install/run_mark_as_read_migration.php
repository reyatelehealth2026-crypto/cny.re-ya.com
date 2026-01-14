<?php
/**
 * Run migration to add mark_as_read_token column
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Mark As Read Token Migration</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'mark_as_read_token'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: blue;'>✓ Column mark_as_read_token already exists</p>";
    } else {
        $db->exec("ALTER TABLE messages ADD COLUMN mark_as_read_token VARCHAR(255) NULL AFTER reply_token");
        echo "<p style='color: green;'>✓ Added mark_as_read_token column</p>";
    }
    
    // Check is_read_on_line column
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'is_read_on_line'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: blue;'>✓ Column is_read_on_line already exists</p>";
    } else {
        $db->exec("ALTER TABLE messages ADD COLUMN is_read_on_line TINYINT(1) DEFAULT 0 AFTER is_read");
        echo "<p style='color: green;'>✓ Added is_read_on_line column</p>";
    }
    
    // Add index
    try {
        $db->exec("ALTER TABLE messages ADD INDEX idx_mark_as_read_token (mark_as_read_token)");
        echo "<p style='color: green;'>✓ Added index for mark_as_read_token</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: blue;'>✓ Index already exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Index error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>Migration completed successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
