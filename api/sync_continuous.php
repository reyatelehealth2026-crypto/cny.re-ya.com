<?php
/**
 * Continuous Sync API
 * API สำหรับ sync ต่อเนื่องผ่าน AJAX - sync ตรงจาก CNY API
 */

// Increase limits for large data
ini_set('memory_limit', '512M');
set_time_limit(120);

header('Content-Type: application/json; charset=utf-8');

// Disable output buffering to prevent memory issues
if (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';

// Get sync progress from file (more reliable than session)
$progressFile = sys_get_temp_dir() . '/cny_sync_progress.json';

function getProgress() {
    global $progressFile;
    if (file_exists($progressFile)) {
        $data = json_decode(file_get_contents($progressFile), true);
        return $data['offset'] ?? 0;
    }
    return 0;
}

function saveProgress($offset) {
    global $progressFile;
    file_put_contents($progressFile, json_encode(['offset' => $offset, 'updated' => date('Y-m-d H:i:s')]));
}

try {
    $db = Database::getInstance()->getConnection();
    $batchSize = isset($_GET['batch_size']) ? intval($_GET['batch_size']) : 10;
    $batchSize = max(1, min(50, $batchSize)); // Limit 1-50 for safety
    $reset = isset($_GET['reset']) && $_GET['reset'] === '1';
    
    $cnyApi = new CnyPharmacyAPI($db);
    
    // Get current offset
    $offset = $reset ? 0 : getProgress();
    
    // Get all products from cache first
    $cacheResult = $cnyApi->getAllProductsCached();
    if (!$cacheResult['success']) {
        throw new Exception('Cannot get products from API: ' . ($cacheResult['error'] ?? 'Unknown error'));
    }
    
    $allProducts = $cacheResult['data'];
    $totalAvailable = count($allProducts);
    
    // Check if already complete
    if ($offset >= $totalAvailable) {
        saveProgress(0);
        echo json_encode([
            'success' => true,
            'stats' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'progress' => ['offset' => 0, 'batch_size' => $batchSize, 'total_available' => $totalAvailable, 'is_complete' => true],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Get batch of products
    $batchProducts = array_slice($allProducts, $offset, $batchSize);
    unset($allProducts); // Free memory
    
    $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    
    foreach ($batchProducts as $product) {
        try {
            $result = $cnyApi->syncProduct($product, [
                'update_existing' => true,
                'auto_category' => true
            ]);
            
            $stats['processed']++;
            if ($result['action'] === 'created') $stats['created']++;
            elseif ($result['action'] === 'updated') $stats['updated']++;
            else $stats['skipped']++;
            
        } catch (Exception $e) {
            $stats['failed']++;
        }
    }
    
    // Update progress
    $newOffset = $offset + $stats['processed'];
    $isComplete = $newOffset >= $totalAvailable;
    
    if ($isComplete) {
        saveProgress(0); // Reset for next sync
    } else {
        saveProgress($newOffset);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'progress' => [
            'offset' => $offset,
            'batch_size' => $batchSize,
            'total_available' => $totalAvailable,
            'is_complete' => $isComplete
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
