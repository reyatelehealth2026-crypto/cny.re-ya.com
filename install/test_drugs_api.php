<?php
/**
 * Test drugs API
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Test Drugs API</h2>";

// Count all items
$stmt = $db->query("SELECT COUNT(*) FROM business_items");
$total = $stmt->fetchColumn();
echo "<p><strong>Total items in business_items:</strong> {$total}</p>";

// Count active items
$stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE is_active = 1");
$active = $stmt->fetchColumn();
echo "<p><strong>Active items:</strong> {$active}</p>";

// Show first 20 items
echo "<h3>First 20 Active Items:</h3>";
$stmt = $db->query("SELECT id, name, price, is_active FROM business_items WHERE is_active = 1 ORDER BY name LIMIT 20");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Active</th></tr>";
foreach ($items as $item) {
    echo "<tr>";
    echo "<td>{$item['id']}</td>";
    echo "<td>" . htmlspecialchars($item['name']) . "</td>";
    echo "<td>฿{$item['price']}</td>";
    echo "<td>{$item['is_active']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test API call
echo "<h3>API Response:</h3>";
$apiUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/pharmacist.php';
echo "<p>Testing: POST {$apiUrl}</p>";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['action' => 'get_drugs'])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: {$httpCode}</p>";

$data = json_decode($response, true);
if ($data) {
    echo "<p>Success: " . ($data['success'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Count: " . ($data['count'] ?? count($data['drugs'] ?? [])) . "</p>";
    if (!empty($data['error'])) {
        echo "<p style='color:red'>Error: {$data['error']}</p>";
    }
} else {
    echo "<p style='color:red'>Invalid JSON response</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
}
