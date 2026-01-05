<?php
/**
 * Continuous Sync API
 * API สำหรับ sync ต่อเนื่องผ่าน AJAX - sync ตรงจาก CNY API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';

// Get sync progress from session/file
session_start();

try {
    $db = Database::getInstance()->getConnection();
    $batchSize = isset($_GET['batch_size']) ? intval($_GET['batch_size']) : 10;
    $batchSize = max(1, min(100, $batchSize)); // Limit 1-100
    $reset = isset($_GET['reset']) && $_GET['reset'] === '1';
    
    $cnyApi = new CnyPharmacyAPI($db);
    
    // Get current offset from session
    $offset = $_SESSION['sync_offset'] ?? 0;
    if ($reset) {
        $offset = 0;
    }
    
    // Sync directly from API
    $result = $cnyApi->syncAllProducts([
        'limit' => $batchSize,
        'offset' => $offset,
        'update_existing' => true,
        'auto_category' => true
    ]);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Sync failed');
    }
    
    $stats = $result['stats'];
    
    // Update offset for next batch
    $processed = $stats['total'] ?? 0;
    $_SESSION['sync_offset'] = $offset + $processed;
    
    // Check if we've processed all
    $totalAvailable = $stats['total_available'] ?? 0;
    $isComplete = ($offset + $processed) >= $totalAvailable;
    
    if ($isComplete) {
        $_SESSION['sync_offset'] = 0; // Reset for next full sync
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'processed' => $processed,
            'created' => $stats['created'] ?? 0,
            'updated' => $stats['updated'] ?? 0,
            'skipped' => $stats['skipped'] ?? 0,
            'failed' => count($stats['errors'] ?? [])
        ],
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
