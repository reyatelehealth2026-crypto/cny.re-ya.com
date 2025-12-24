<?php
/**
 * Sync Worker Runner
 * รัน worker ผ่าน browser หรือ CLI
 */

ini_set('memory_limit', '1024M');
set_time_limit(600); // 10 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/CnyPharmacyAPI.php';
require_once __DIR__ . '/classes/SyncWorker.php';

$isCli = php_sapi_name() === 'cli';
$batchSize = isset($_GET['batch_size']) ? intval($_GET['batch_size']) : 10;
$maxJobs = isset($_GET['max_jobs']) ? intval($_GET['max_jobs']) : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'batch'; // batch or continuous

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sync Worker</title>';
    echo '<style>body{font-family:monospace;background:#1a202c;color:#e2e8f0;padding:20px;}</style>';
    echo '</head><body><pre>';
}

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          CNY Pharmacy Sync Worker                        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📋 Configuration:\n";
echo "   Mode: {$mode}\n";
echo "   Batch Size: {$batchSize}\n";
echo "   Max Jobs: " . ($maxJobs > 0 ? $maxJobs : 'unlimited') . "\n\n";

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
    
    echo "🚀 Starting worker...\n\n";
    
    // Run worker
    if ($mode === 'continuous') {
        $stats = $worker->processAll($batchSize, $maxJobs);
    } else {
        $stats = $worker->processBatch($batchSize);
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
    
    echo "\n✓ Worker finished successfully\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

if (!$isCli) {
    echo '</pre>';
    echo '<p><a href="sync-dashboard.php" style="color:#68d391;">← Back to Dashboard</a></p>';
    echo '</body></html>';
}
