<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h2>Cart Tables Check</h2>";

// Check cart table
echo "<h3>Table: cart</h3>";
try {
    $stmt = $db->query("DESCRIBE cart");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM cart");
    echo "<p>Total rows: " . $stmt->fetchColumn() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Table 'cart' does not exist: " . $e->getMessage() . "</p>";
}

// Check cart_items table
echo "<h3>Table: cart_items</h3>";
try {
    $stmt = $db->query("DESCRIBE cart_items");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td></tr>";
    }
    echo "</table>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM cart_items");
    echo "<p>Total rows: " . $stmt->fetchColumn() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Table 'cart_items' does not exist: " . $e->getMessage() . "</p>";
}

// Show cart data for user 28
echo "<h3>Cart data for User 28 (JX4)</h3>";
echo "<h4>From 'cart' table:</h4>";
try {
    $stmt = $db->prepare("SELECT c.*, bi.name FROM cart c LEFT JOIN business_items bi ON c.product_id = bi.id WHERE c.user_id = ?");
    $stmt->execute([28]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found: " . count($items) . " items</p>";
    if ($items) {
        echo "<pre>" . print_r($items, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h4>From 'cart_items' table:</h4>";
try {
    $stmt = $db->prepare("SELECT c.*, bi.name FROM cart_items c LEFT JOIN business_items bi ON c.product_id = bi.id WHERE c.user_id = ?");
    $stmt->execute([28]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found: " . count($items) . " items</p>";
    if ($items) {
        echo "<pre>" . print_r($items, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
