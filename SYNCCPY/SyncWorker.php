<?php
declare(strict_types=1);

/**
 * SyncWorker - ประมวลผล Sync Jobs จาก Queue
 * 
 * Worker นี้จะ:
 * - ดึง jobs จาก queue
 * - เรียก CNY API
 * - บันทึกข้อมูลลง database
 * - จัดการ error และ retry
 * 
 * Design Pattern: Worker Pattern + Strategy Pattern
 * Performance: ใช้ rate limiting และ batch processing
 */

namespace CnySync\Worker;

use PDO;
use Exception;
use CnySync\Queue\SyncQueue;
use CnySync\Utils\RateLimiter;
use CnySync\Config\SyncConfig;

final class SyncWorker
{
    private PDO $db;
    private SyncQueue $queue;
    private RateLimiter $rateLimiter;
    private object $cnyApi; // CnyPharmacyAPI instance
    private bool $running = false;
    private array $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'start_time' => null,
        'end_time' => null
    ];
    
    public function __construct(PDO $db, object $cnyApi)
    {
        $this->db = $db;
        $this->queue = new SyncQueue($db);
        $this->cnyApi = $cnyApi;
        
        // สร้าง rate limiter (20 requests/minute ตาม config)
        $this->rateLimiter = new RateLimiter(
            'cny_api',
            SyncConfig::MAX_REQUESTS_PER_MINUTE,
            60
        );
    }
    
    /**
     * รัน worker แบบ single batch
     * 
     * @param int $batchSize จำนวน jobs ที่จะประมวลผล
     * @return array สถิติการทำงาน
     */
    public function processBatch(int $batchSize = null): array
    {
        $batchSize = $batchSize ?? SyncConfig::BATCH_SIZE;
        $this->stats['start_time'] = microtime(true);
        $this->running = true;
        
        try {
            // Cleanup stuck jobs ก่อน (jobs ที่ค้างเกิน 30 นาที)
            $cleaned = $this->queue->cleanupStuckJobs(30);
            if ($cleaned > 0) {
                $this->log("Cleaned up {$cleaned} stuck jobs");
            }
            
            // ดึง jobs ที่พร้อมทำงาน
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
                
                // Delay เพื่อไม่ให้ระบบหนักเกินไป
                if (SyncConfig::DELAY_BETWEEN_JOBS_MS > 0) {
                    usleep(SyncConfig::DELAY_BETWEEN_JOBS_MS * 1000);
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
     * รัน worker แบบ continuous (จนกว่า queue จะหมด)
     * 
     * @param int $batchSize
     * @param int $maxJobs จำนวน jobs สูงสุด (0 = ไม่จำกัด)
     * @return array
     */
    public function processAll(int $batchSize = null, int $maxJobs = 0): array
    {
        $batchSize = $batchSize ?? SyncConfig::BATCH_SIZE;
        $this->stats['start_time'] = microtime(true);
        $this->running = true;
        
        $totalProcessed = 0;
        
        while ($this->running) {
            // ตรวจสอบ memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(SyncConfig::WORKER_MEMORY_LIMIT);
            
            if ($memoryUsage > $memoryLimit * 0.9) {
                $this->log("Memory usage too high, stopping worker", 'warning');
                break;
            }
            
            // ดึง jobs
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
                
                if (SyncConfig::DELAY_BETWEEN_JOBS_MS > 0) {
                    usleep(SyncConfig::DELAY_BETWEEN_JOBS_MS * 1000);
                }
            }
        }
        
        $this->stats['end_time'] = microtime(true);
        $this->running = false;
        
        return $this->getStats();
    }
    
    /**
     * ประมวลผล job เดี่ยว
     * 
     * @param array $job
     * @return bool
     */
    private function processJob(array $job): bool
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
            
            // ตรวจสอบ rate limit
            if (SyncConfig::ENABLE_RATE_LIMITING) {
                if (!$this->rateLimiter->wait(30)) {
                    throw new Exception("Rate limit exceeded, waited too long");
                }
            }
            
            // ดึงข้อมูลจาก API (ถ้ามี cache ใช้ cache)
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
            $this->logSync($jobId, $sku, $result['action'], $duration);
            
            return true;
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $this->queue->failJob($jobId, $errorMsg);
            $this->stats['failed']++;
            
            $this->log("✗ Failed {$sku}: {$errorMsg}", 'error');
            
            $duration = round((microtime(true) - $startTime) * 1000);
            $this->logSync($jobId, $sku, 'failed', $duration, ['error' => $errorMsg]);
            
            return false;
        }
    }
    
    /**
     * ดึงข้อมูลสินค้าจาก API
     * 
     * @param string $sku
     * @return array|null
     */
    private function fetchProductFromApi(string $sku): ?array
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
     * บันทึก sync log
     * 
     * @param int $jobId
     * @param string $sku
     * @param string $action
     * @param int $durationMs
     * @param array|null $details
     */
    private function logSync(int $jobId, string $sku, string $action, int $durationMs, ?array $details = null): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sync_logs (queue_id, sku, action, duration_ms, details) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $jobId,
                $sku,
                $action,
                $durationMs,
                $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
            ]);
        } catch (Exception $e) {
            // Ignore log errors
        }
    }
    
    /**
     * ดึงสถิติการทำงาน
     * 
     * @return array
     */
    public function getStats(): array
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
    public function stop(): void
    {
        $this->running = false;
        $this->log("Worker stop signal received");
    }
    
    /**
     * Log message
     * 
     * @param string $message
     * @param string $level
     */
    private function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = match($level) {
            'error' => '✗',
            'warning' => '⚠',
            'success' => '✓',
            default => 'ℹ'
        };
        
        echo "[{$timestamp}] {$prefix} {$message}\n";
        flush();
    }
    
    /**
     * แปลง memory limit string เป็น bytes
     * 
     * @param string $value
     * @return int
     */
    private function parseMemoryLimit(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int)substr($value, 0, -1);
        
        return match($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int)$value
        };
    }
}
