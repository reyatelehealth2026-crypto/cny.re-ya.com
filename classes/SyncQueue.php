<?php
/**
 * SyncQueue - จัดการ Queue ของ Sync Jobs
 * ปรับจาก SYNCCPY ให้ใช้กับระบบเดิม
 */

class SyncQueue
{
    private $db;
    
    // Config constants
    const BATCH_SIZE = 10;
    const MAX_RETRY_ATTEMPTS = 3;
    const PRIORITY_NORMAL = 5;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * เพิ่ม job เข้า queue (แบบเดี่ยว)
     */
    public function addJob($sku, $priority = self::PRIORITY_NORMAL, $apiData = null)
    {
        // ตรวจสอบว่ามี job นี้อยู่แล้วหรือไม่
        $existing = $this->findJob($sku);
        
        if ($existing !== null) {
            if (in_array($existing['status'], ['failed', 'pending'])) {
                $this->resetJob((int)$existing['id']);
                return (int)$existing['id'];
            }
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
            self::MAX_RETRY_ATTEMPTS
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * เพิ่ม jobs เข้า queue แบบ bulk
     */
    public function addJobsBulk($skus, $priority = self::PRIORITY_NORMAL)
    {
        if (empty($skus)) {
            return 0;
        }
        
        $existingSkus = $this->getExistingSkus($skus);
        $newSkus = array_diff($skus, $existingSkus);
        
        if (empty($newSkus)) {
            return 0;
        }
        
        $values = [];
        $params = [];
        
        foreach ($newSkus as $sku) {
            $values[] = "(?, ?, ?)";
            $params[] = $sku;
            $params[] = $priority;
            $params[] = self::MAX_RETRY_ATTEMPTS;
        }
        
        $sql = "INSERT INTO sync_queue (sku, priority, max_attempts) VALUES " . implode(', ', $values);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return count($newSkus);
    }
    
    /**
     * ดึง jobs ที่พร้อมทำงาน
     */
    public function getReadyJobs($limit = 10)
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
     * Lock job สำหรับประมวลผล
     */
    public function lockJob($jobId)
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
     */
    public function completeJob($jobId, $result)
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
     */
    public function failJob($jobId, $errorMessage)
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
     * Skip job
     */
    public function skipJob($jobId, $reason)
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
     * Reset job กลับเป็น pending
     */
    public function resetJob($jobId)
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
     * ล้าง queue
     */
    public function clearQueue($onlyFailed = false)
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
     */
    public function getStats()
    {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) as count FROM sync_queue GROUP BY status"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        foreach ($rows as $row) {
            $status = $row['status'];
            $count = (int)$row['count'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }
        
        return $stats;
    }
    
    /**
     * ค้นหา job จาก SKU
     */
    private function findJob($sku)
    {
        $stmt = $this->db->prepare("SELECT * FROM sync_queue WHERE sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false ? $result : null;
    }
    
    /**
     * ดึง SKU ที่มีอยู่ใน queue แล้ว
     */
    private function getExistingSkus($skus)
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
     * Cleanup jobs ที่ค้างนานเกินไป
     */
    public function cleanupStuckJobs($timeoutMinutes = 30)
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
    
    /**
     * ดึง recent logs
     */
    public function getRecentLogs($limit = 50)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * บันทึก log
     */
    public function log($queueId, $sku, $action, $durationMs, $details = null)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sync_logs (queue_id, sku, action, duration_ms, details) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $queueId,
                $sku,
                $action,
                $durationMs,
                $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
