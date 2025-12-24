<?php
/**
 * SyncWorker - ประมวลผล Sync Jobs จาก Queue
 * ปรับจาก SYNCCPY ให้ใช้กับระบบเดิม
 */

require_once __DIR__ . '/SyncQueue.php';
require_once __DIR__ . '/RateLimiter.php';

class SyncWorker
{
    private $db;
    private $queue;
    private $rateLimiter;
    private $cnyApi;
    private $running = false;
    
    // Config
    const BATCH_SIZE = 5;  // ลดลงเพราะ response ใหญ่
    const DELAY_BETWEEN_JOBS_MS = 1000;  // เพิ่ม delay
    const MAX_REQUESTS_PER_MINUTE = 15;
    const MEMORY_LIMIT = '512M';
    
    private $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'start_time' => null,
        'end_time' => null
    ];
    
    public function __construct($db, $cnyApi)
    {
        $this->db = $db;
        $this->queue = new SyncQueue($db);
        $this->cnyApi = $cnyApi;
        $this->rateLimiter = new RateLimiter('cny_api', self::MAX_REQUESTS_PER_MINUTE, 60);
    }
    
    /**
     * รัน worker แบบ single batch
     */
    public function processBatch($batchSize = null)
    {
        $batchSize = $batchSize ?? self::BATCH_SIZE;
        $this->stats['start_time'] = microtime(true);
        $this->running = true;
        
        try {
            // Cleanup stuck jobs
            $cleaned = $this->queue->cleanupStuckJobs(30);
            if ($cleaned > 0) {
                $this->log("Cleaned up {$cleaned} stuck jobs");
            }
            
            // ดึง jobs
            $jobs = $this->queue->getReadyJobs($batchSize);
            
            if (empty($jobs)) {
                $this->log("No jobs in queue");
                return $this->getStats();
            }
            
            $this->log("Processing " . count($jobs) . " jobs...");
            
            foreach ($jobs as $job) {
                if (!$this->running) {
                    $this->log("Worker stopped by signal");
                    break;
                }
                
                $this->processJob($job);
                
                if (self::DELAY_BETWEEN_JOBS_MS > 0) {
                    usleep(self::DELAY_BETWEEN_JOBS_MS * 1000);
                }
            }
            
        } catch (Exception $e) {
            $this->log("Worker error: " . $e->getMessage(), 'error');
        } finally {
            $this->stats['end_time'] = microtime(true);
            $this->running = false;
        }
        
        return $this->getStats();
    }
    
    /**
     * รัน worker แบบ continuous
     */
    public function processAll($batchSize = null, $maxJobs = 0)
    {
        $batchSize = $batchSize ?? self::BATCH_SIZE;
        $this->stats['start_time'] = microtime(true);
        $this->running = true;
        
        $totalProcessed = 0;
        $memoryLimit = $this->parseMemoryLimit(self::MEMORY_LIMIT);
        
        while ($this->running) {
            // ตรวจสอบ memory
            $memoryUsage = memory_get_usage(true);
            if ($memoryUsage > $memoryLimit * 0.9) {
                $this->log("Memory usage too high, stopping worker", 'warning');
                break;
            }
            
            $jobs = $this->queue->getReadyJobs($batchSize);
            
            if (empty($jobs)) {
                $this->log("Queue is empty");
                break;
            }
            
            foreach ($jobs as $job) {
                if (!$this->running) {
                    break 2;
                }
                
                if ($maxJobs > 0 && $totalProcessed >= $maxJobs) {
                    $this->log("Reached max jobs limit ({$maxJobs})");
                    break 2;
                }
                
                $this->processJob($job);
                $totalProcessed++;
                
                if (self::DELAY_BETWEEN_JOBS_MS > 0) {
                    usleep(self::DELAY_BETWEEN_JOBS_MS * 1000);
                }
            }
        }
        
        $this->stats['end_time'] = microtime(true);
        $this->running = false;
        
        return $this->getStats();
    }
    
    /**
     * ประมวลผล job เดี่ยว
     */
    private function processJob($job)
    {
        $jobId = (int)$job['id'];
        $sku = $job['sku'];
        $startTime = microtime(true);
        
        try {
            // Lock job
            if (!$this->queue->lockJob($jobId)) {
                $this->log("Cannot lock job {$jobId} (SKU: {$sku})", 'warning');
                return false;
            }
            
            $this->log("Processing job {$jobId}: SKU {$sku}");
            
            // Rate limit
            if (!$this->rateLimiter->wait(30)) {
                throw new Exception("Rate limit exceeded");
            }
            
            // ดึงข้อมูลจาก API
            $apiData = null;
            if (isset($job['api_data']) && !empty($job['api_data'])) {
                $apiData = json_decode($job['api_data'], true);
                $this->log("Using cached API data for {$sku}");
            } else {
                $apiData = $this->fetchProductFromApi($sku);
            }
            
            if ($apiData === null) {
                $this->queue->skipJob($jobId, "Product not found in API");
                $this->stats['skipped']++;
                $this->log("Skipped {$sku}: not found in API");
                return false;
            }
            
            // Sync product
            $result = $this->cnyApi->syncProduct($apiData, ['update_existing' => true]);
            
            // Update job status
            $this->queue->completeJob($jobId, $result);
            
            // Update stats
            $this->stats['processed']++;
            if ($result['action'] === 'created') {
                $this->stats['created']++;
            } elseif ($result['action'] === 'updated') {
                $this->stats['updated']++;
            } else {
                $this->stats['skipped']++;
            }
            
            $duration = round((microtime(true) - $startTime) * 1000);
            $this->log("✓ Completed {$sku} ({$result['action']}) in {$duration}ms");
            
            // บันทึก log
            $this->queue->log($jobId, $sku, $result['action'], $duration);
            
            return true;
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $this->queue->failJob($jobId, $errorMsg);
            $this->stats['failed']++;
            
            $this->log("✗ Failed {$sku}: {$errorMsg}", 'error');
            
            $duration = round((microtime(true) - $startTime) * 1000);
            $this->queue->log($jobId, $sku, 'failed', $duration, ['error' => $errorMsg]);
            
            return false;
        }
    }
    
    /**
     * ดึงข้อมูลสินค้าจาก API
     */
    private function fetchProductFromApi($sku)
    {
        try {
            $result = $this->cnyApi->getProductBySku($sku);
            
            if (!$result['success']) {
                return null;
            }
            
            return $result['data'] ?? null;
            
        } catch (Exception $e) {
            $this->log("API error for {$sku}: " . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * ดึงสถิติการทำงาน
     */
    public function getStats()
    {
        $stats = $this->stats;
        
        if ($stats['start_time'] && $stats['end_time']) {
            $stats['duration_seconds'] = round($stats['end_time'] - $stats['start_time'], 2);
            $stats['jobs_per_second'] = $stats['duration_seconds'] > 0 
                ? round($stats['processed'] / $stats['duration_seconds'], 2)
                : 0;
        }
        
        return $stats;
    }
    
    /**
     * หยุด worker
     */
    public function stop()
    {
        $this->running = false;
        $this->log("Worker stop signal received");
    }
    
    /**
     * Log message
     */
    private function log($message, $level = 'info')
    {
        $timestamp = date('Y-m-d H:i:s');
        
        switch ($level) {
            case 'error':
                $prefix = '✗';
                break;
            case 'warning':
                $prefix = '⚠';
                break;
            case 'success':
                $prefix = '✓';
                break;
            default:
                $prefix = 'ℹ';
        }
        
        echo "[{$timestamp}] {$prefix} {$message}\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * แปลง memory limit string เป็น bytes
     */
    private function parseMemoryLimit($value)
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int)substr($value, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return (int)$value;
        }
    }
}
