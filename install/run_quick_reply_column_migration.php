<?php
/**
 * Migration: Add quick_reply column to quick_reply_templates table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Add quick_reply Column Migration ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM quick_reply_templates LIKE 'quick_reply'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'quick_reply' already exists\n";
    } else {
        // Add the column
        $db->exec("
            ALTER TABLE quick_reply_templates 
            ADD COLUMN quick_reply TEXT NULL COMMENT 'JSON array of LINE Quick Reply items' 
            AFTER category
        ");
        echo "✓ Added 'quick_reply' column to quick_reply_templates table\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
