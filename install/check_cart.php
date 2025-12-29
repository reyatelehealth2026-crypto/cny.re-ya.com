<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h3>Cart Table Structure</h3>";

// Check if cart table exists
try {
    $stmt = $db->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Cart table error: " . $e->getMessage() . "</p>";
}

echo "<h3>Cart Items for User 28</h3>";
try {
    $stmt = $db->query("SELECT * FROM cart WHERE user_id = 28");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found: " . count($items) . " items</p>";
    if ($items) {
        echo "<pre>" . print_r($items, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
