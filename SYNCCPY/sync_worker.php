#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * CNY Sync Worker - CLI Script
 * 
 * รัน: php sync_worker.php [options]
 * 
 * Options:
 *   --batch-size=N     จำนวน jobs ต่อ batch (default: 10)
 *   --max-jobs=N       จำนวน jobs สูงสุด (default: 0 = ไม่จำกัด)
 *   --mode=MODE        โหมดการทำงาน: batch|continuous (default: continuous)
 * 
 * Examples:
 *   php sync_worker.php                    # รันจนกว่า queue จะหมด
 *   php sync_worker.php --batch-size=5     # ทำ batch size 5
 *   php sync_worker.php --max-jobs=50      # ทำแค่ 50 jobs แล้วหยุด
 *   php sync_worker.php --mode=batch       # ทำ 1 batch แล้วหยุด
 */

// ตรวจสอบว่ารันผ่าน CLI หรือไม่
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// เพิ่ม error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ตั้งค่า memory & timeout
ini_set('memory_limit', '256M');
set_time_limit(0); // ไม่จำกัดเวลา

// เปลี่ยน directory ไปที่ root ของ project
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Load dependencies
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/CnyPharmacyAPI.php';
require_once __DIR__ . '/../config/sync_config.php';
require_once __DIR__ . '/../src/SyncQueue.php';
require_once __DIR__ . '/../src/RateLimiter.php';
require_once __DIR__ . '/../src/SyncWorker.php';

use CnySync\Worker\SyncWorker;
use CnySync\Config\SyncConfig;

// Parse command line arguments
$options = getopt('', ['batch-size:', 'max-jobs:', 'mode:']);

$batchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : SyncConfig::BATCH_SIZE;
$maxJobs = isset($options['max-jobs']) ? (int)$options['max-jobs'] : 0;
$mode = $options['mode'] ?? 'continuous';

// Output header
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          CNY Pharmacy Sync Worker v1.0                  ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📋 Configuration:\n";
echo "   Mode: {$mode}\n";
echo "   Batch Size: {$batchSize}\n";
echo "   Max Jobs: " . ($maxJobs > 0 ? $maxJobs : 'unlimited') . "\n";
echo "   Rate Limit: " . SyncConfig::MAX_REQUESTS_PER_MINUTE . " req/min\n";
echo "\n";

// Initialize
try {
    $db = Database::getInstance()->getConnection();
    $cnyApi = new CnyPharmacyAPI($db);
    
    // Test API connection
    echo "🔌 Testing API connection...\n";
    $testResult = $cnyApi->testConnection();
    
    if (!$testResult['success']) {
        throw new Exception("API connection failed: " . $testResult['message']);
    }
    
    echo "✓ API connection successful\n\n";
    
    // Create worker
    $worker = new SyncWorker($db, $cnyApi);
    
    // Setup signal handler (for graceful shutdown)
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, function() use ($worker) {
            echo "\n⚠ Received SIGTERM signal, stopping worker...\n";
            $worker->stop();
        });
        
        pcntl_signal(SIGINT, function() use ($worker) {
            echo "\n⚠ Received SIGINT signal (Ctrl+C), stopping worker...\n";
            $worker->stop();
        });
    }
    
    // Run worker
    echo "🚀 Starting worker...\n\n";
    
    $stats = match($mode) {
        'batch' => $worker->processBatch($batchSize),
        'continuous' => $worker->processAll($batchSize, $maxJobs),
        default => throw new Exception("Invalid mode: {$mode}")
    };
    
    // Output results
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║                    SYNC COMPLETED                        ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 Statistics:\n";
    echo "   Processed: {$stats['processed']}\n";
    echo "   Created: {$stats['created']}\n";
    echo "   Updated: {$stats['updated']}\n";
    echo "   Skipped: {$stats['skipped']}\n";
    echo "   Failed: {$stats['failed']}\n";
    
    if (isset($stats['duration_seconds'])) {
        echo "   Duration: {$stats['duration_seconds']} seconds\n";
        echo "   Speed: {$stats['jobs_per_second']} jobs/sec\n";
    }
    
    echo "\n✓ Worker finished successfully\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
