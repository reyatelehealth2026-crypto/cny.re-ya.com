<?php
/**
 * Update video_calls status column to include 'ringing'
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Updating video_calls status column</h2>";

try {
    $db = Database::getInstance()->getConnection();

    // Check table exists
    try {
        $db->query("SELECT 1 FROM video_calls LIMIT 1");
    } catch (Exception $e) {
        die("<p style='color: red;'>❌ Table video_calls does not exist.</p>");
    }

    echo "<p>Modifying status column...</p>";

    // Alter table to include 'ringing' in ENUM
    // Note: We include all possible statuses we might need
    $sql = "ALTER TABLE video_calls MODIFY COLUMN status ENUM('pending', 'ringing', 'active', 'reconnecting', 'completed', 'ended', 'rejected', 'error', 'timeout') DEFAULT 'pending'";

    $db->exec($sql);

    echo "<p style='color: green;'>✅ Successfully updated status column ENUM!</p>";

    // Show current columns
    echo "<h3>Current video_calls columns:</h3>";
    echo "<pre>";
    $cols = $db->query("DESCRIBE video_calls")->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
