<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Orders Query</h2>";

// Check user exists
echo "<h3>1. Check User ID 4</h3>";
$stmt = $db->query("SELECT id, display_name, line_user_id FROM users WHERE id = 4");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "✅ User found: " . print_r($user, true) . "<br>";
} else {
    echo "❌ User ID 4 NOT FOUND in users table<br>";
    
    // Show all users
    echo "<br>All users:<br>";
    $stmt = $db->query("SELECT id, display_name FROM users LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- ID {$row['id']}: {$row['display_name']}<br>";
    }
}

// Test the exact query from orders.php
echo "<h3>2. Test Orders Query</h3>";
$currentBotId = 1;

$sql = "SELECT o.*, u.display_name, u.picture_url,
        (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = o.id) as item_count
        FROM transactions o 
        JOIN users u ON o.user_id = u.id
        WHERE (o.line_account_id = ? OR o.line_account_id IS NULL)
        ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$currentBotId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($orders) . " orders<br>";
if ($orders) {
    echo "<pre>" . print_r($orders, true) . "</pre>";
}

// Test without JOIN
echo "<h3>3. Test Without JOIN</h3>";
$stmt = $db->query("SELECT * FROM transactions");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Direct query found " . count($rows) . " transactions<br>";
if ($rows) {
    echo "<pre>" . print_r($rows, true) . "</pre>";
}
