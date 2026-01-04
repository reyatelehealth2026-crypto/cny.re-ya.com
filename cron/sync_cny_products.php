<?php
/**
 * Background script to sync CNY Pharmacy products to database
 * Run this via cron or manually to update product cache
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// CNY API Configuration
define('CNY_API_BASE', 'https://manager.cnypharmacy.com/api/');
define('CNY_API_TOKEN', '90xcKekelCqCAjmgkpI1saJF6N55eiNexcI4hdcYM2M');

echo "Starting CNY product sync...\n";

// Fetch from API
$url = CNY_API_BASE . 'get_product_all';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . CNY_API_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("API Error: HTTP {$httpCode}\n");
}

echo "API response received, parsing...\n";

$products = json_decode($response, true);

if (!$products || !is_array($products)) {
    die("Failed to parse JSON response\n");
}

echo "Found " . count($products) . " products\n";

// Create table if not exists
$db->exec("
    CREATE TABLE IF NOT EXISTS cny_products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sku VARCHAR(100) UNIQUE NOT NULL,
        barcode VARCHAR(100),
        name TEXT,
        name_en TEXT,
        spec_name TEXT,
        description TEXT,
        properties_other TEXT,
        how_to_use TEXT,
        photo_path TEXT,
        qty DECIMAL(10,2) DEFAULT 0,
        qty_incoming DECIMAL(10,2) DEFAULT 0,
        enable CHAR(1) DEFAULT '1',
        product_price JSON,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sku (sku),
        INDEX idx_enable (enable),
        INDEX idx_name (name(100))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Table ready, inserting products...\n";

$inserted = 0;
$updated = 0;

foreach ($products as $product) {
    try {
        $stmt = $db->prepare("
            INSERT INTO cny_products (
                sku, barcode, name, name_en, spec_name, description,
                properties_other, how_to_use, photo_path, qty, qty_incoming,
                enable, product_price
            ) VALUES (
                :sku, :barcode, :name, :name_en, :spec_name, :description,
                :properties_other, :how_to_use, :photo_path, :qty, :qty_incoming,
                :enable, :product_price
            )
            ON DUPLICATE KEY UPDATE
                barcode = VALUES(barcode),
                name = VALUES(name),
                name_en = VALUES(name_en),
                spec_name = VALUES(spec_name),
                description = VALUES(description),
                properties_other = VALUES(properties_other),
                how_to_use = VALUES(how_to_use),
                photo_path = VALUES(photo_path),
                qty = VALUES(qty),
                qty_incoming = VALUES(qty_incoming),
                enable = VALUES(enable),
                product_price = VALUES(product_price)
        ");
        
        $stmt->execute([
            ':sku' => $product['sku'] ?? '',
            ':barcode' => $product['barcode'] ?? '',
            ':name' => $product['name'] ?? '',
            ':name_en' => $product['name_en'] ?? '',
            ':spec_name' => $product['spec_name'] ?? '',
            ':description' => $product['description'] ?? '',
            ':properties_other' => $product['properties_other'] ?? '',
            ':how_to_use' => $product['how_to_use'] ?? '',
            ':photo_path' => $product['photo_path'] ?? '',
            ':qty' => $product['qty'] ?? 0,
            ':qty_incoming' => $product['qty_incoming'] ?? 0,
            ':enable' => $product['enable'] ?? '1',
            ':product_price' => json_encode($product['product_price'] ?? [])
        ]);
        
        if ($stmt->rowCount() > 0) {
            $inserted++;
        } else {
            $updated++;
        }
        
        if (($inserted + $updated) % 100 == 0) {
            echo "Processed " . ($inserted + $updated) . " products...\n";
        }
        
    } catch (Exception $e) {
        echo "Error with SKU {$product['sku']}: " . $e->getMessage() . "\n";
    }
}

echo "\nSync complete!\n";
echo "Inserted: $inserted\n";
echo "Updated: $updated\n";
echo "Total: " . ($inserted + $updated) . "\n";
