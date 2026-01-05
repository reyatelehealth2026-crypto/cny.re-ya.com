<?php
/**
 * Run Zone Types Migration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Zone Types Migration</h2>";

try {
    $sql = file_get_contents(__DIR__ . '/../database/migration_zone_types.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->exec($statement);
            echo "<p style='color:green'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
        }
    }
    
    echo "<h3 style='color:green'>Migration completed successfully!</h3>";
    
    // Show created zone types
    $stmt = $db->query("SELECT * FROM zone_types ORDER BY sort_order");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Zone Types:</h4><ul>";
    foreach ($types as $type) {
        echo "<li><strong>{$type['code']}</strong>: {$type['label']} ({$type['color']})</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
