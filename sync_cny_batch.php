<?php
/**
 * CNY Pharmacy Batch Sync
 * Sync ทีละ SKU เพื่อหลีกเลี่ยง memory/timeout issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
set_time_limit(600);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/CnyPharmacyAPI.php';

$db = Database::getInstance()->getConnection();
$cnyApi = new CnyPharmacyAPI($db);

$action = $_GET['action'] ?? 'status';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CNY Batch Sync</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2563EB; }
        .btn-success { background: #10B981; }
        .btn-warning { background: #F59E0B; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: #10B981; }
        .error { color: #EF4444; }
        .info { color: #3B82F6; }
        .progress { background: #e5e5e5; border-radius: 10px; height: 20px; margin: 10px 0; }
        .progress-bar { background: #10B981; height: 100%; border-radius: 10px; transition: width 0.3s; }
    </style>
</head>
<body>
    <h2>🔄 CNY Pharmacy Batch Sync</h2>
    
    <?php
    try {
        switch ($action) {
            case 'get_skus':
                echo "<h3>📋 Getting SKU List...</h3>";
                echo "<pre>";
                
                $result = $cnyApi->getSkuList();
                if (!$result['success']) {
                    throw new Exception($result['error'] ?? 'Failed to get SKU list');
                }
                
                $skus = $result['data'];
                $total = count($skus);
                $cached = $result['cached'] ? ' (cached)' : ' (fresh)';
                
                echo "✓ Found {$total} SKUs{$cached}\n";
                echo "\nFirst 10 SKUs:\n";
                foreach (array_slice($skus, 0, 10) as $sku) {
                    echo "  - {$sku}\n";
                }
                echo "</pre>";
                
                echo "<p><a class='btn btn-success' href='?action=sync&limit=20&offset=0'>Start Sync (20 at a time)</a></p>";
                break;
                
            case 'sync':
                echo "<h3>⚡ Syncing Products...</h3>";
                
                // ใช้ cached data แทนการเรียก API ทีละ SKU
                $allResult = $cnyApi->getAllProductsCached();
                if (!$allResult['success']) {
                    throw new Exception($allResult['error'] ?? 'Failed to get products');
                }
                
                $allProducts = $allResult['data'];
                $totalProducts = count($allProducts);
                $cached = $allResult['cached'] ? ' (cached)' : ' (fresh)';
                
                // Get batch
                $batchProducts = array_slice($allProducts, $offset, $limit);
                
                if (empty($batchProducts)) {
                    echo "<p class='success'>✓ All products synced!</p>";
                    echo "<p><a class='btn' href='shop/products.php'>View Products</a></p>";
                    break;
                }
                
                $endOffset = $offset + count($batchProducts);
                $progress = round(($endOffset / $totalProducts) * 100);
                
                echo "<p>Syncing products {$offset} - {$endOffset} of {$totalProducts}{$cached}</p>";
                echo "<div class='progress'><div class='progress-bar' style='width: {$progress}%'></div></div>";
                
                echo "<pre>";
                $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
                
                foreach ($batchProducts as $product) {
                    $sku = $product['sku'] ?? 'N/A';
                    $name = mb_substr($product['name'] ?? $product['name_en'] ?? 'Unknown', 0, 30);
                    echo "Syncing [{$sku}] {$name}... ";
                    flush();
                    
                    try {
                        if (empty($product['sku'])) {
                            echo "⏭ skipped (no SKU)\n";
                            $stats['skipped']++;
                            continue;
                        }
                        
                        $syncResult = $cnyApi->syncProduct($product, ['update_existing' => true]);
                        
                        if ($syncResult['action'] === 'created') {
                            echo "✓ created\n";
                            $stats['created']++;
                        } elseif ($syncResult['action'] === 'updated') {
                            echo "✓ updated\n";
                            $stats['updated']++;
                        } else {
                            echo "⏭ skipped ({$syncResult['reason']})\n";
                            $stats['skipped']++;
                        }
                    } catch (Exception $e) {
                        echo "✗ error: " . $e->getMessage() . "\n";
                        $stats['errors'][] = $sku;
                    }
                }
                
                echo "\n--- Batch Results ---\n";
                echo "Created: {$stats['created']}\n";
                echo "Updated: {$stats['updated']}\n";
                echo "Skipped: {$stats['skipped']}\n";
                echo "Errors: " . count($stats['errors']) . "\n";
                echo "</pre>";
                
                $nextOffset = $offset + $limit;
                if ($nextOffset < $totalProducts) {
                    echo "<p>";
                    echo "<a class='btn btn-success' href='?action=sync&limit={$limit}&offset={$nextOffset}'>Continue Sync (Next {$limit})</a> ";
                    echo "<a class='btn btn-warning' href='?action=sync&limit=50&offset={$nextOffset}'>Sync 50</a> ";
                    echo "<a class='btn' href='?action=sync&limit=100&offset={$nextOffset}'>Sync 100</a> ";
                    echo "</p>";
                    
                    // Auto-continue option
                    echo "<p><label><input type='checkbox' id='autoContinue'> Auto-continue (2 sec delay)</label></p>";
                    echo "<script>
                        if (document.getElementById('autoContinue').checked || localStorage.getItem('autoContinue') === 'true') {
                            setTimeout(() => { window.location.href = '?action=sync&limit={$limit}&offset={$nextOffset}'; }, 2000);
                        }
                        document.getElementById('autoContinue').addEventListener('change', function() {
                            localStorage.setItem('autoContinue', this.checked);
                            if (this.checked) {
                                setTimeout(() => { window.location.href = '?action=sync&limit={$limit}&offset={$nextOffset}'; }, 2000);
                            }
                        });
                    </script>";
                } else {
                    echo "<p class='success'>✓ All products synced!</p>";
                }
                
                echo "<p><a class='btn' href='shop/products.php'>View Products</a></p>";
                break;
                
            default: // status
                echo "<h3>📊 Sync Status</h3>";
                
                // Test connection
                $testResult = $cnyApi->testConnection();
                echo "<p>API Connection: " . ($testResult['success'] ? "<span class='success'>✓ Connected</span>" : "<span class='error'>✗ Failed</span>") . "</p>";
                
                // Local stats
                $localStats = $cnyApi->getSyncStats();
                if ($localStats) {
                    echo "<p>Local Products: <strong>{$localStats['total']}</strong></p>";
                    echo "<p>With SKU (synced): <strong>" . ($localStats['with_sku'] ?? 0) . "</strong></p>";
                    echo "<p>Active: <strong>{$localStats['active']}</strong></p>";
                }
                
                echo "<hr>";
                echo "<p><a class='btn' href='?action=get_skus'>Step 1: Get SKU List</a></p>";
                echo "<p><a class='btn btn-success' href='?action=sync&limit=20&offset=0'>Start Sync (20 at a time)</a></p>";
                echo "<p><a class='btn btn-warning' href='?action=sync&limit=50&offset=0'>Start Sync (50 at a time)</a></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <hr>
    <p><a href="sync_cny_batch.php">← Back to Status</a> | <a href="shop/products.php">View Products</a></p>
</body>
</html>
