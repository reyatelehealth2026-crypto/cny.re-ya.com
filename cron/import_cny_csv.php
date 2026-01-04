<?php
/**
 * Import CNY products from CSV file
 * Alternative method when API response is too large
 */
ini_set('memory_limit', '256M');
set_time_limit(300);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "Importing CNY products from CSV...\n";

$csvFile = __DIR__ . '/../CNY API for AI - AI.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found: {$csvFile}\n");
}

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

echo "Table ready, reading CSV...\n";

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Cannot open CSV file\n");
}

// Read header
$header = fgetcsv($handle);
echo "CSV columns: " . implode(', ', $header) . "\n\n";

$inserted = 0;
$updated = 0;
$errors = 0;
$line = 1;

while (($data = fgetcsv($handle)) !== false) {
    $line++;
    
    try {
        // Map CSV columns to database fields
        $product = array_combine($header, $data);
        
        // Parse product_price JSON if it's a string
        $productPrice = $product['product_price'] ?? '[]';
        if (is_string($productPrice)) {
            $productPrice = json_decode($productPrice, true) ?: [];
        }
        
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
            ':product_price' => is_string($productPrice) ? $productPrice : json_encode($productPrice)
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
        $errors++;
        echo "Error on line {$line}: " . $e->getMessage() . "\n";
    }
}

fclose($handle);

echo "\nImport complete!\n";
echo "Inserted: $inserted\n";
echo "Updated: $updated\n";
echo "Errors: $errors\n";
echo "Total: " . ($inserted + $updated) . "\n";
