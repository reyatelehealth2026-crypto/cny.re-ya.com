<?php
/**
 * Fix points_history table - เพิ่ม columns ที่ขาด
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix points_history Table</h2><pre>";

$columnsToAdd = [
    'reference_type' => "ALTER TABLE points_history ADD COLUMN reference_type VARCHAR(50) NULL AFTER description",
    'reference_id' => "ALTER TABLE points_history ADD COLUMN reference_id INT NULL AFTER reference_type",
    'balance_after' => "ALTER TABLE points_history ADD COLUMN balance_after INT DEFAULT 0 AFTER reference_id"
];

foreach ($columnsToAdd as $column => $sql) {
    try {
        $check = $db->query("SHOW COLUMNS FROM points_history LIKE '{$column}'");
        if ($check->rowCount() == 0) {
            $db->exec($sql);
            echo "✅ Added column: {$column}\n";
        } else {
            echo "⏭️ Column exists: {$column}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error adding {$column}: " . $e->getMessage() . "\n";
    }
}

// Add index for reference lookup
try {
    $db->exec("CREATE INDEX IF NOT EXISTS idx_points_history_reference ON points_history(reference_type, reference_id)");
    echo "✅ Added index: idx_points_history_reference\n";
} catch (Exception $e) {
    // Index might already exist
}

echo "\n✅ Done!</pre>";
