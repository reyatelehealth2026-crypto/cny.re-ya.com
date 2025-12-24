<?php
/**
 * Fix Missing Products
 * ตรวจสอบและกู้คืนสินค้าที่หายไป
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Missing Products</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #1E293B; } h2 { color: #475569; }
.success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; } .info { color: #3B82F6; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #E2E8F0; font-size: 13px; }
th { background: #F8FAFC; }
.btn { display: inline-block; padding: 12px 24px; background: #10B981; color: white; text-decoration: none; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; margin: 5px; }
.btn:hover { background: #059669; }
.btn-blue { background: #3B82F6; } .btn-blue:hover { background: #2563EB; }
.btn-red { background: #EF4444; } .btn-red:hover { background: #DC2626; }
pre { background: #1E293B; color: #10B981; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🔧 Fix Missing Products</h1>";

// ========== Current Status ==========
echo "<div class='card'>";
echo "<h2>📊 สถานะปัจจุบัน</h2>";

$stmt = $db->query("SELECT COUNT(*) FROM business_items");
$totalProducts = $stmt->fetchColumn();

$stmt = $db->query("SELECT MIN(id), MAX(id) FROM business_items");
$idRange = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT COUNT(DISTINCT id) FROM business_items");
$uniqueIds = $stmt->fetchColumn();

echo "<table>";
echo "<tr><td>จำนวนสินค้าทั้งหมด</td><td><strong>$totalProducts</strong></td></tr>";
echo "<tr><td>ID ต่ำสุด</td><td>{$idRange['MIN(id)']}</td></tr>";
echo "<tr><td>ID สูงสุด</td><td>{$idRange['MAX(id)']}</td></tr>";
echo "<tr><td>Unique IDs</td><td>$uniqueIds</td></tr>";
echo "</table>";

// Check for gaps in IDs
$expectedCount = $idRange['MAX(id)'] - $idRange['MIN(id)'] + 1;
$missingCount = $expectedCount - $totalProducts;
if ($missingCount > 0) {
    echo "<p class='warning'>⚠️ พบ ID ที่หายไป: ประมาณ $missingCount รายการ</p>";
} else {
    echo "<p class='success'>✓ ไม่พบ ID ที่หายไป</p>";
}
echo "</div>";

// ========== Check CNY API ==========
echo "<div class='card'>";
echo "<h2>🔍 ตรวจสอบ CNY API</h2>";

if (file_exists(__DIR__ . '/classes/CnyPharmacyAPI.php')) {
    require_once __DIR__ . '/classes/CnyPharmacyAPI.php';
    $cnyApi = new CnyPharmacyAPI($db);
    
    // Get SKU list from CNY (memory efficient)
    echo "<p>กำลังดึงรายการ SKU จาก CNY API...</p>";
    $result = $cnyApi->getSkuList();
    
    if ($result['success']) {
        $cnySkus = $result['data'];
        $cnyCount = count($cnySkus);
        echo "<p class='success'>✓ CNY API มีสินค้า: <strong>$cnyCount</strong> รายการ</p>";
        
        // Compare with local
        $stmt = $db->query("SELECT sku FROM business_items");
        $localSkus = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $localCount = count($localSkus);
        
        // Find missing SKUs
        $missingSkus = array_diff($cnySkus, $localSkus);
        $missingCount = count($missingSkus);
        
        echo "<p>สินค้าในระบบ: <strong>$localCount</strong></p>";
        echo "<p class='" . ($missingCount > 0 ? 'warning' : 'success') . "'>SKU ที่หายไป: <strong>$missingCount</strong></p>";
        
        if ($missingCount > 0 && $missingCount < 100) {
            echo "<p>ตัวอย่าง SKU ที่หายไป: " . implode(', ', array_slice($missingSkus, 0, 20)) . "</p>";
        }
        
        // Store for re-sync
        if ($missingCount > 0) {
            $_SESSION['missing_skus'] = array_values($missingSkus);
        }
    } else {
        echo "<p class='error'>❌ ไม่สามารถเชื่อมต่อ CNY API: " . ($result['error'] ?? 'Unknown') . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ ไม่พบไฟล์ CnyPharmacyAPI.php</p>";
}
echo "</div>";

// ========== Re-sync Missing Products ==========
if (isset($_POST['resync_missing']) && isset($_SESSION['missing_skus'])) {
    echo "<div class='card'>";
    echo "<h2>🔄 กำลัง Re-sync สินค้าที่หายไป...</h2>";
    
    $missingSkus = $_SESSION['missing_skus'];
    $synced = 0;
    $errors = 0;
    $batchSize = 50;
    
    // Process in batches
    $batches = array_chunk($missingSkus, $batchSize);
    
    foreach ($batches as $batchIndex => $batch) {
        echo "<p>Processing batch " . ($batchIndex + 1) . "/" . count($batches) . "...</p>";
        flush();
        
        foreach ($batch as $sku) {
            try {
                $result = $cnyApi->getProductBySku($sku);
                if ($result['success'] && !empty($result['data'])) {
                    $syncResult = $cnyApi->syncProduct($result['data'], ['use_cny_id' => false]); // ใช้ auto-increment แทน
                    if ($syncResult['action'] !== 'skipped') {
                        $synced++;
                    }
                }
            } catch (Exception $e) {
                $errors++;
            }
        }
    }
    
    echo "<p class='success'>✓ Sync สำเร็จ: $synced รายการ</p>";
    if ($errors > 0) {
        echo "<p class='warning'>⚠️ Errors: $errors</p>";
    }
    
    unset($_SESSION['missing_skus']);
    echo "</div>";
}

// ========== Full Re-sync from CNY ==========
if (isset($_POST['full_resync'])) {
    echo "<div class='card'>";
    echo "<h2>🔄 Full Re-sync จาก CNY API</h2>";
    echo "<p class='warning'>⚠️ กำลัง sync สินค้าทั้งหมด (ใช้เวลานาน)...</p>";
    
    $offset = (int)($_POST['offset'] ?? 0);
    $limit = 500;
    
    $result = $cnyApi->syncAllProducts([
        'offset' => $offset,
        'limit' => $limit,
        'use_cny_id' => false, // ใช้ auto-increment
        'update_existing' => true
    ]);
    
    if ($result['success']) {
        $stats = $result['stats'];
        echo "<p class='success'>✓ Sync batch สำเร็จ!</p>";
        echo "<table>";
        echo "<tr><td>Total in batch</td><td>{$stats['total']}</td></tr>";
        echo "<tr><td>Created</td><td>{$stats['created']}</td></tr>";
        echo "<tr><td>Updated</td><td>{$stats['updated']}</td></tr>";
        echo "<tr><td>Skipped</td><td>{$stats['skipped']}</td></tr>";
        echo "</table>";
        
        if ($stats['total'] >= $limit) {
            $nextOffset = $offset + $limit;
            echo "<form method='POST'>";
            echo "<input type='hidden' name='offset' value='$nextOffset'>";
            echo "<button type='submit' name='full_resync' class='btn btn-blue'>➡️ Continue (offset: $nextOffset)</button>";
            echo "</form>";
        } else {
            echo "<p class='success'>✓ Sync เสร็จสมบูรณ์!</p>";
        }
    } else {
        echo "<p class='error'>❌ Error: " . ($result['error'] ?? 'Unknown') . "</p>";
    }
    echo "</div>";
}

// ========== Actions ==========
echo "<div class='card'>";
echo "<h2>🚀 Actions</h2>";

if (isset($_SESSION['missing_skus']) && count($_SESSION['missing_skus']) > 0) {
    $missingCount = count($_SESSION['missing_skus']);
    echo "<form method='POST' style='margin-bottom:15px;'>";
    echo "<p>พบ $missingCount SKU ที่หายไป</p>";
    echo "<button type='submit' name='resync_missing' class='btn'>🔄 Re-sync สินค้าที่หายไป ($missingCount รายการ)</button>";
    echo "</form>";
}

echo "<form method='POST'>";
echo "<p>Sync สินค้าทั้งหมดจาก CNY API (ทีละ 500 รายการ)</p>";
echo "<button type='submit' name='full_resync' class='btn btn-blue'>🔄 Full Re-sync from CNY</button>";
echo "</form>";

echo "<hr style='margin:20px 0;'>";
echo "<p><a href='sync_cny_with_id.php' class='btn'>📦 Sync Dashboard</a></p>";
echo "</div>";

// ========== Recent Products ==========
echo "<div class='card'>";
echo "<h2>📦 สินค้าล่าสุด (10 รายการ)</h2>";

$stmt = $db->query("SELECT id, sku, name, created_at FROM business_items ORDER BY id DESC LIMIT 10");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>SKU</th><th>Name</th><th>Created</th></tr>";
foreach ($products as $p) {
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['sku']}</td>";
    echo "<td>" . mb_substr($p['name'], 0, 40) . "</td>";
    echo "<td>" . ($p['created_at'] ?? '-') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<p style='text-align:center;color:#94A3B8;margin-top:20px;'>Generated at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
