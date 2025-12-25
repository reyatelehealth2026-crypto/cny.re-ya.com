<?php
/**
 * CNY Pharmacy Product Sync
 * รัน: php sync_cny_products.php
 * หรือเรียกผ่าน browser
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/CnyPharmacyAPI.php';

$db = Database::getInstance()->getConnection();
$isCli = php_sapi_name() === 'cli';

// Get parameters - default to smaller batch to avoid memory issues
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

function output($message, $isCli) {
    if ($isCli) {
        echo $message . "\n";
    } else {
        echo $message . "<br>\n";
        ob_flush(); flush();
    }
}

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>CNY Product Sync</title></head><body>';
    echo '<h2>🔄 CNY Pharmacy Product Sync</h2><pre>';
}

try {
    output("=== CNY Pharmacy Product Sync ===", $isCli);
    output("Started: " . date('Y-m-d H:i:s'), $isCli);
    output("Limit: {$limit}, Offset: {$offset}", $isCli);
    output("", $isCli);
    
    $cnyApi = new CnyPharmacyAPI($db);
    
    output("Testing API connection...", $isCli);
    $testResult = $cnyApi->testConnection();
    
    if (!$testResult['success']) {
        throw new Exception("API Connection failed: " . $testResult['message']);
    }
    output("✓ API Connection successful", $isCli);
    output("", $isCli);
    
    $options = [
        'update_existing' => true,
        'limit' => $limit,
        'offset' => $offset,
    ];
    
    output("Syncing products (limit: {$limit}, offset: {$offset})...", $isCli);
    $result = $cnyApi->syncAllProducts($options);
    
    if (!$result['success']) {
        throw new Exception("Sync failed: " . ($result['error'] ?? 'Unknown error'));
    }
    
    $stats = $result['stats'];
    
    output("", $isCli);
    output("=== Sync Results ===", $isCli);
    output("Processed: " . $stats['total'], $isCli);
    output("Created: " . $stats['created'], $isCli);
    output("Updated: " . $stats['updated'], $isCli);
    output("Skipped: " . $stats['skipped'], $isCli);
    
    if (!empty($stats['errors'])) {
        output("", $isCli);
        output("Errors (" . count($stats['errors']) . "):", $isCli);
        foreach (array_slice($stats['errors'], 0, 10) as $err) {
            output("  - SKU {$err['sku']}: {$err['error']}", $isCli);
        }
    }
    
    output("", $isCli);
    output("✓ Sync completed: " . date('Y-m-d H:i:s'), $isCli);
    
} catch (Exception $e) {
    output("", $isCli);
    output("❌ ERROR: " . $e->getMessage(), $isCli);
    if (!$isCli) {
        echo '</pre><p style="color:red;">Sync failed.</p>';
    }
    exit(1);
}

if (!$isCli) {
    echo '</pre>';
    echo '<p style="color:green;">✓ Sync completed!</p>';
    
    $nextOffset = $offset + $limit;
    $totalAvailable = $result['stats']['total_available'] ?? 0;
    echo "<p>";
    echo "<strong>Total products in CNY: {$totalAvailable}</strong><br><br>";
    echo "<a href='sync_cny_products.php?limit={$limit}&offset={$nextOffset}'>Sync Next {$limit} (offset: {$nextOffset})</a> | ";
    echo "<a href='sync_cny_products.php?limit=100&offset=0'>Sync 100</a> | ";
    echo "<a href='sync_cny_products.php?limit=200&offset=0'>Sync 200</a> | ";
    echo "<a href='shop/products.php'>Go to Products</a>";
    echo "</p>";
    echo '</body></html>';
}
