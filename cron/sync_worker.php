#!/usr/bin/env php
<?php
/**
 * CNY Sync Worker - CLI Script
 * 
 * รัน: php cron/sync_worker.php [options]
 * 
 * Options:
 *   --batch-size=N     จำนวน jobs ต่อ batch (default: 10)
 *   --max-jobs=N       จำนวน jobs สูงสุด (default: 0 = ไม่จำกัด)
 *   --mode=MODE        โหมด: batch|continuous (default: continuous)
 * 
 * Examples:
 *   php cron/sync_worker.php                    # รันจนกว่า queue จะหมด
 *   php cron/sync_worker.php --batch-size=5    # ทำ batch size 5
 *   php cron/sync_worker.php --max-jobs=50     # ทำแค่ 50 jobs
 *   php cron/sync_worker.php --mode=batch      # ทำ 1 batch แล้วหยุด
 * 
 * Cron Example (ทุก 5 นาที):
 *   * / 5 * * * * cd /path/to/project && php cron/sync_worker.php --mode=batch --batch-size=20
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');
set_time_limit(0);

// Change to project root
chdir(dirname(__DIR__));

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';
require_once __DIR__ . '/../classes/SyncWorker.php';

// Parse arguments
$options = getopt('', ['batch-size:', 'max-jobs:', 'mode:']);

$batchSize = isset($options['batch-size']) ? (int) $options['batch-size'] : 10;
$maxJobs = isset($options['max-jobs']) ? (int) $options['max-jobs'] : 0;
$mode = isset($options['mode']) ? $options['mode'] : 'continuous';

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          CNY Pharmacy Sync Worker v2.0                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📋 Configuration:\n";
echo "   Mode: {$mode}\n";
echo "   Batch Size: {$batchSize}\n";
echo "   Max Jobs: " . ($maxJobs > 0 ? $maxJobs : 'unlimited') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $cnyApi = new CnyPharmacyAPI($db);

    // Test API
    echo "🔌 Testing API connection...\n";
    $testResult = $cnyApi->testConnection();

    if (!$testResult['success']) {
        throw new Exception("API connection failed: " . $testResult['message']);
    }

    echo "✓ API connection successful\n\n";

    // Create worker
    $worker = new SyncWorker($db, $cnyApi);

    // Setup signal handlers
    // Setup signal handlers
    if (function_exists('pcntl_signal')) {
        $sigterm = defined('SIGTERM') ? SIGTERM : 15;
        $sigint = defined('SIGINT') ? SIGINT : 2;

        pcntl_signal($sigterm, function () use ($worker) {
            echo "\n⚠ Received SIGTERM, stopping...\n";
            $worker->stop();
        });

        pcntl_signal($sigint, function () use ($worker) {
            echo "\n⚠ Received SIGINT (Ctrl+C), stopping...\n";
            $worker->stop();
        });
    }

    echo "🚀 Starting worker...\n\n";

    if ($mode === 'batch') {
        $stats = $worker->processBatch($batchSize);
    } elseif ($mode === 'continuous') {
        $stats = $worker->processAll($batchSize, $maxJobs);
    } else {
        throw new Exception("Invalid mode: {$mode}");
    }

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

    echo "\n✓ Worker finished\n";
    exit(0);

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
