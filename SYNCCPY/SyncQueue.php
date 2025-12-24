<?php
declare(strict_types=1);

/**
 * SyncQueue - จัดการ Queue ของ Sync Jobs
 * 
 * Class นี้รับผิดชอบ:
 * - เพิ่ม jobs เข้า queue
 * - ดึง jobs ที่พร้อมประมวลผล
 * - Update status ของ jobs
 * - จัดการ retry logic
 * 
 * Design Pattern: Repository Pattern สำหรับ Queue Management
 * Security: Prepared statements ทั้งหมด, Type safety ด้วย strict types
 */

namespace CnySync\Queue;

use PDO;
use PDOException;
use CnySync\Config\SyncConfig;

final class SyncQueue
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * เพิ่ม job เข้า queue (แบบเดี่ยว)
     * 
     * @param string $sku
     * @param int $priority
     * @param array|null $apiData Optional: cache data จาก API
     * @return int Queue ID
     */
    public function addJob(string $sku, int $priority = SyncConfig::PRIORITY_NORMAL, ?array $apiData = null): int
    {
        // ตรวจสอบว่ามี job นี้อยู่แล้วหรือไม่
        $existing = $this->findJob($sku);
        
        if ($existing !== null) {
            // ถ้า job เดิมล้มเหลว หรือ pending ให้ reset เป็น pending ใหม่
            if (in_array($existing['status'], ['failed', 'pending'])) {
                $this->resetJob((int)$existing['id']);
                return (int)$existing['id'];
            }
            // ถ้า job เดิมสำเร็จแล้ว ไม่เพิ่มซ้ำ
            return (int)$existing['id'];
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO sync_queue (sku, priority, api_data, max_attempts) 
             VALUES (?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $sku,
            $priority,
            $apiData !== null ? json_encode($apiData, JSON_UNESCAPED_UNICODE) : null,
            SyncConfig::MAX_RETRY_ATTEMPTS
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * เพิ่ม jobs เข้า queue แบบ bulk (เร็วกว่า)
     * 
     * @param array $skus Array ของ SKU strings
     * @param int $priority
     * @return int จำนวน jobs ที่เพิ่ม
     */
    public function addJobsBulk(array $skus, int $priority = SyncConfig::PRIORITY_NORMAL): int
    {
        if (empty($skus)) {
            return 0;
        }
        
        // กรอง SKU ที่มีอยู่แล้ว
        $existingSkus = $this->getExistingSkus($skus);
        $newSkus = array_diff($skus, $existingSkus);
        
        if (empty($newSkus)) {
            return 0;
        }
        
        // เตรียม values สำหรับ multi-row insert
        $values = [];
        $params = [];
        
        foreach ($newSkus as $sku) {
            $values[] = "(?, ?, ?)";
            $params[] = $sku;
            $params[] = $priority;
            $params[] = SyncConfig::MAX_RETRY_ATTEMPTS;
        }
        
        $sql = "INSERT INTO sync_queue (sku, priority, max_attempts) VALUES " . implode(', ', $values);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return count($newSkus);
    }
    
    /**
     * ดึง jobs ที่พร้อมทำงาน
     * 
     * @param int $limit จำนวน jobs สูงสุด
     * @return array
     */
    public function getReadyJobs(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sync_queue 
             WHERE status = 'pending' 
             AND attempts < max_attempts
             ORDER BY priority ASC, created_at ASC
             LIMIT ?"
        );
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lock job สำหรับประมวลผล (ป้องกัน race condition)
     * 
     * @param int $jobId
     * @return bool
     */
    public function lockJob(int $jobId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = 'processing',
                 processing_started_at = NOW(),
                 attempts = attempts + 1,
                 updated_at = NOW()
             WHERE id = ? 
             AND status = 'pending'"
        );
        
        $stmt->execute([$jobId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Update job เป็น completed
     * 
     * @param int $jobId
     * @param array $result ผลลัพธ์การ sync
     * @return bool
     */
    public function completeJob(int $jobId, array $result): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = 'completed',
                 result = ?,
                 processing_completed_at = NOW(),
                 error_message = NULL,
                 updated_at = NOW()
             WHERE id = ?"
        );
        
        return $stmt->execute([
            json_encode($result, JSON_UNESCAPED_UNICODE),
            $jobId
        ]);
    }
    
    /**
     * Update job เป็น failed
     * 
     * @param int $jobId
     * @param string $errorMessage
     * @return bool
     */
    public function failJob(int $jobId, string $errorMessage): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = IF(attempts >= max_attempts, 'failed', 'pending'),
                 error_message = ?,
                 processing_completed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?"
        );
        
        return $stmt->execute([$errorMessage, $jobId]);
    }
    
    /**
     * Skip job (เช่น ไม่มีข้อมูลใน API)
     * 
     * @param int $jobId
     * @param string $reason
     * @return bool
     */
    public function skipJob(int $jobId, string $reason): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = 'skipped',
                 error_message = ?,
                 processing_completed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?"
        );
        
        return $stmt->execute([$reason, $jobId]);
    }
    
    /**
     * Reset job กลับเป็น pending (สำหรับ retry)
     * 
     * @param int $jobId
     * @return bool
     */
    public function resetJob(int $jobId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = 'pending',
                 attempts = 0,
                 error_message = NULL,
                 processing_started_at = NULL,
                 processing_completed_at = NULL,
                 updated_at = NOW()
             WHERE id = ?"
        );
        
        return $stmt->execute([$jobId]);
    }
    
    /**
     * ล้าง queue ทั้งหมด
     * 
     * @param bool $onlyFailed ล้างเฉพาะ failed jobs
     * @return int จำนวน jobs ที่ลบ
     */
    public function clearQueue(bool $onlyFailed = false): int
    {
        if ($onlyFailed) {
            $stmt = $this->db->prepare("DELETE FROM sync_queue WHERE status = 'failed'");
        } else {
            $stmt = $this->db->prepare("TRUNCATE TABLE sync_queue");
        }
        
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * ดึงสถิติของ queue
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stmt = $this->db->query("SELECT * FROM v_queue_summary");
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        foreach ($summary as $row) {
            $status = $row['status'];
            $count = (int)$row['count'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }
        
        return $stats;
    }
    
    /**
     * ค้นหา job จาก SKU
     * 
     * @param string $sku
     * @return array|null
     */
    private function findJob(string $sku): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sync_queue WHERE sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false ? $result : null;
    }
    
    /**
     * ดึง SKU ที่มีอยู่ใน queue แล้ว
     * 
     * @param array $skus
     * @return array
     */
    private function getExistingSkus(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($skus), '?'));
        $stmt = $this->db->prepare("SELECT sku FROM sync_queue WHERE sku IN ({$placeholders})");
        $stmt->execute($skus);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Cleanup jobs ที่ค้างนานเกินไป (stuck jobs)
     * 
     * @param int $timeoutMinutes
     * @return int จำนวน jobs ที่ reset
     */
    public function cleanupStuckJobs(int $timeoutMinutes = 30): int
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_queue 
             SET status = 'pending',
                 processing_started_at = NULL
             WHERE status = 'processing'
             AND processing_started_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        
        $stmt->execute([$timeoutMinutes]);
        return $stmt->rowCount();
    }
}
