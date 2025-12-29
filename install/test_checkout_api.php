<?php
/**
 * Test checkout API cart retrieval
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h2>Test Checkout API Cart</h2>";

$userId = 28;
$lineUserId = 'U72acba5ac10532ec7a4b9721bfe9f5ed';

echo "<p>User ID: {$userId}</p>";
echo "<p>LINE User ID: {$lineUserId}</p>";

// Direct query like checkout API does
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.sale_price, p.image_url, p.is_active,
           (COALESCE(p.sale_price, p.price) * c.quantity) as subtotal
    FROM cart_items c
    LEFT JOIN business_items p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Cart Items from cart_items table (user_id={$userId}):</h3>";
echo "<p>Found: " . count($items) . " items</p>";

if ($items) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Name</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Active</th></tr>";
    foreach ($items as $item) {
        $active = $item['is_active'] ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['product_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name'] ?? 'NULL') . "</td>";
        echo "<td>{$item['price']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>{$item['subtotal']}</td>";
        echo "<td>{$active}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No items found!</p>";
}

// Also test API URL
echo "<h3>API Test URL:</h3>";
echo "<p><a href='../api/checkout.php?action=cart&user_id={$userId}&debug=1' target='_blank'>Test API with user_id</a></p>";
echo "<p><a href='../api/checkout.php?action=cart&line_user_id={$lineUserId}&debug=1' target='_blank'>Test API with line_user_id</a></p>";
