<?php
/**
 * Run migration to add HTML content fields to business_items
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Migration: Add HTML Fields to business_items</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check current columns
    $stmt = $db->query("SHOW COLUMNS FROM business_items");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns: " . implode(', ', array_keys($columns)) . "</p>";
    
    $changes = [];
    
    // Add description if not exists
    if (!isset($columns['description'])) {
        $db->exec("ALTER TABLE business_items ADD COLUMN description LONGTEXT NULL AFTER name_en");
        $changes[] = "Added 'description' column";
    } else {
        // Modify to LONGTEXT if needed
        if (strpos(strtolower($columns['description']['Type']), 'longtext') === false) {
            $db->exec("ALTER TABLE business_items MODIFY COLUMN description LONGTEXT NULL");
            $changes[] = "Modified 'description' to LONGTEXT";
        }
    }
    
    // Add usage_instructions if not exists
    if (!isset($columns['usage_instructions'])) {
        $afterCol = isset($columns['description']) ? 'description' : 'name_en';
        $db->exec("ALTER TABLE business_items ADD COLUMN usage_instructions LONGTEXT NULL AFTER {$afterCol}");
        $changes[] = "Added 'usage_instructions' column";
    } else {
        if (strpos(strtolower($columns['usage_instructions']['Type']), 'longtext') === false) {
            $db->exec("ALTER TABLE business_items MODIFY COLUMN usage_instructions LONGTEXT NULL");
            $changes[] = "Modified 'usage_instructions' to LONGTEXT";
        }
    }
    
    // Modify properties_other to LONGTEXT if exists
    if (isset($columns['properties_other'])) {
        if (strpos(strtolower($columns['properties_other']['Type']), 'longtext') === false) {
            $db->exec("ALTER TABLE business_items MODIFY COLUMN properties_other LONGTEXT NULL");
            $changes[] = "Modified 'properties_other' to LONGTEXT";
        }
    } else {
        $db->exec("ALTER TABLE business_items ADD COLUMN properties_other LONGTEXT NULL");
        $changes[] = "Added 'properties_other' column";
    }
    
    if (empty($changes)) {
        echo "<p style='color:blue;'>✓ All columns already exist with correct types</p>";
    } else {
        echo "<ul>";
        foreach ($changes as $change) {
            echo "<li style='color:green;'>✓ {$change}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>Migration Complete!</h3>";
    echo "<p><a href='/sync-dashboard.php'>Go to Sync Dashboard</a> to re-import CSV</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
