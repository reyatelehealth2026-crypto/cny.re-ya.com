<?php
/**
 * Sync Queue API
 * API endpoints สำหรับจัดการ sync queue
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';
require_once __DIR__ . '/../classes/SyncQueue.php';

$db = Database::getInstance()->getConnection();
$queue = new SyncQueue($db);
$cnyApi = new CnyPharmacyAPI($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'stats':
            // Get queue stats
            $stats = $queue->getStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'recent_logs':
            // Get recent logs
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $logs = $queue->getRecentLogs($limit);
            echo json_encode(['success' => true, 'logs' => $logs]);
            break;
            
        case 'create_batch':
            // Create batch from API SKUs
            $batchName = $_POST['batch_name'] ?? 'Sync ' . date('Y-m-d H:i:s');
            $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 5;
            
            // Get SKUs from API
            $skuResult = $cnyApi->getSkuList();
            if (!$skuResult['success']) {
                throw new Exception('Failed to get SKU list: ' . ($skuResult['error'] ?? 'Unknown error'));
            }
            
            $skus = $skuResult['data'];
            
            // Add to queue
            $added = $queue->addJobsBulk($skus, $priority);
            
            // Create batch record
            $stmt = $db->prepare(
                "INSERT INTO sync_batches (batch_name, total_jobs, status) VALUES (?, ?, 'pending')"
            );
            $stmt->execute([$batchName, $added]);
            $batchId = $db->lastInsertId();
            
            // Redirect back to dashboard if from form
            if (isset($_POST['batch_name'])) {
                header('Location: ../sync-dashboard.php?created=' . $added);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'batch_id' => $batchId,
                'jobs_added' => $added,
                'total_skus' => count($skus)
            ]);
            break;
            
        case 'add_job':
            // Add single job
            $sku = $_POST['sku'] ?? '';
            if (empty($sku)) {
                throw new Exception('SKU is required');
            }
            
            $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 5;
            $jobId = $queue->addJob($sku, $priority);
            
            echo json_encode(['success' => true, 'job_id' => $jobId]);
            break;
            
        case 'clear_failed':
            // Clear failed jobs
            $cleared = $queue->clearQueue(true);
            echo json_encode(['success' => true, 'message' => "Cleared {$cleared} failed jobs"]);
            break;
            
        case 'clear_all':
            // Clear all jobs
            $queue->clearQueue(false);
            echo json_encode(['success' => true, 'message' => 'Queue cleared']);
            break;
            
        case 'cleanup_stuck':
            // Cleanup stuck jobs
            $cleaned = $queue->cleanupStuckJobs(30);
            echo json_encode(['success' => true, 'message' => "Cleaned up {$cleaned} stuck jobs"]);
            break;
            
        case 'reset_job':
            // Reset specific job
            $jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
            if ($jobId <= 0) {
                throw new Exception('Job ID is required');
            }
            
            $queue->resetJob($jobId);
            echo json_encode(['success' => true, 'message' => 'Job reset']);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'available_actions' => [
                    'stats' => 'Get queue statistics',
                    'recent_logs' => 'Get recent sync logs',
                    'create_batch' => 'Create batch from API SKUs (POST)',
                    'add_job' => 'Add single job (POST: sku)',
                    'clear_failed' => 'Clear failed jobs (POST)',
                    'clear_all' => 'Clear all jobs (POST)',
                    'cleanup_stuck' => 'Cleanup stuck jobs (POST)',
                    'reset_job' => 'Reset specific job (POST: job_id)'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
