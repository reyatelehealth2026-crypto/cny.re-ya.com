<?php
declare(strict_types=1);

/**
 * BatchManager - จัดการ Sync Batches
 * 
 * ใช้สำหรับ:
 * - สร้าง batch จาก SKU list
 * - ติดตาม progress ของ batch
 * - อัพเดทสถิติ batch
 */

namespace CnySync\Batch;

use PDO;
use CnySync\Queue\SyncQueue;

final class BatchManager
{
    private PDO $db;
    private SyncQueue $queue;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->queue = new SyncQueue($db);
    }
    
    /**
     * สร้าง batch ใหม่จาก SKU list
     * 
     * @param string $batchName
     * @param array $skus
     * @param int $priority
     * @return int Batch ID
     */
    public function createBatch(string $batchName, array $skus, int $priority = 5): int
    {
        // สร้าง batch record
        $stmt = $this->db->prepare(
            "INSERT INTO sync_batches (batch_name, total_jobs, status) 
             VALUES (?, ?, 'pending')"
        );
        
        $stmt->execute([$batchName, count($skus)]);
        $batchId = (int)$this->db->lastInsertId();
        
        // เพิ่ม jobs เข้า queue
        $addedJobs = $this->queue->addJobsBulk($skus, $priority);
        
        // อัพเดท total_jobs ตามจำนวนจริงที่เพิ่ม
        if ($addedJobs !== count($skus)) {
            $stmt = $this->db->prepare("UPDATE sync_batches SET total_jobs = ? WHERE id = ?");
            $stmt->execute([$addedJobs, $batchId]);
        }
        
        return $batchId;
    }
    
    /**
     * เริ่มการทำงานของ batch
     * 
     * @param int $batchId
     * @return bool
     */
    public function startBatch(int $batchId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sync_batches 
             SET status = 'running', started_at = NOW() 
             WHERE id = ? AND status = 'pending'"
        );
        
        $stmt->execute([$batchId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * อัพเดทความคืบหน้าของ batch
     * 
     * @param int $batchId
     * @return bool
     */
    public function updateProgress(int $batchId): bool
    {
        // นับสถิติจาก queue
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'skipped' THEN 1 END) as skipped
             FROM sync_queue"
        );
        
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // อัพเดท batch
        $stmt = $this->db->prepare(
            "UPDATE sync_batches 
             SET completed_jobs = ?,
                 failed_jobs = ?,
                 skipped_jobs = ?
             WHERE id = ?"
        );
        
        return $stmt->execute([
            $stats['completed'],
            $stats['failed'],
            $stats['skipped'],
            $batchId
        ]);
    }
    
    /**
     * จบการทำงานของ batch
     * 
     * @param int $batchId
     * @param string $status 'completed' หรือ 'failed'
     * @return bool
     */
    public function completeBatch(int $batchId, string $status = 'completed'): bool
    {
        $this->updateProgress($batchId);
        
        $stmt = $this->db->prepare(
            "UPDATE sync_batches 
             SET status = ?, completed_at = NOW() 
             WHERE id = ?"
        );
        
        return $stmt->execute([$status, $batchId]);
    }
    
    /**
     * ดึงข้อมูล batch
     * 
     * @param int $batchId
     * @return array|null
     */
    public function getBatch(int $batchId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM v_batch_progress WHERE id = ?");
        $stmt->execute([$batchId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false ? $result : null;
    }
    
    /**
     * ดึง batches ทั้งหมด
     * 
     * @param int $limit
     * @return array
     */
    public function getAllBatches(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM v_batch_progress 
             ORDER BY id DESC 
             LIMIT ?"
        );
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ลบ batch
     * 
     * @param int $batchId
     * @return bool
     */
    public function deleteBatch(int $batchId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sync_batches WHERE id = ?");
        return $stmt->execute([$batchId]);
    }
}
