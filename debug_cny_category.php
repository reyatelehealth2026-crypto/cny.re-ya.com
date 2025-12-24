<?php
/**
 * Debug CNY API - ดูว่ามี field อะไรบ้างที่เกี่ยวกับ category
 * Version: Memory-safe - ดึงแค่ 1 สินค้า
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/CnyPharmacyAPI.php';

$db = Database::getInstance()->getConnection();
$cnyApi = new CnyPharmacyAPI($db);

echo "<h1>Debug CNY API - Category Fields</h1>";
echo "<pre>";

// ดึงสินค้าตัวอย่าง 1 ตัวจาก SKU
$testSku = $_GET['sku'] ?? '0001';
echo "Testing SKU: $testSku\n\n";

$result = $cnyApi->getProductBySku($testSku);

if (!$result['success']) {
    echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
    echo "HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
} else {
    echo "=== Product Data from CNY API ===\n\n";
    print_r($result['data']);
    
    // List all keys
    if (is_array($result['data'])) {
        echo "\n=== All Fields ===\n";
        $keys = array_keys($result['data']);
        sort($keys);
        foreach ($keys as $key) {
            $val = $result['data'][$key];
            if (is_array($val)) {
                echo "$key: [array with " . count($val) . " items]\n";
            } else {
                $display = mb_substr((string)$val, 0, 100);
                echo "$key: $display\n";
            }
        }
    }
}

// Check extra_data in local DB
echo "\n\n=== Check extra_data in business_items (local DB) ===\n";
$stmt = $db->query("SELECT id, sku, name, extra_data FROM business_items WHERE extra_data IS NOT NULL AND extra_data != '' AND extra_data != '{}' LIMIT 5");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) == 0) {
    echo "No items with extra_data found\n";
} else {
    foreach ($items as $item) {
        echo "\nID: {$item['id']}, SKU: {$item['sku']}, Name: " . mb_substr($item['name'], 0, 30) . "\n";
        $extra = json_decode($item['extra_data'], true);
        if ($extra) {
            echo "extra_data: " . json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }
    }
}

echo "\n\n=== Try different SKU ===\n";
echo "Add ?sku=XXXX to URL to test different SKU\n";

echo "</pre>";
