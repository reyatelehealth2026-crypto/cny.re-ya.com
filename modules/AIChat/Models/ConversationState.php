<?php
/**
 * ConversationState Model
 * จัดการ state การสนทนาของ user
 */

namespace Modules\AIChat\Models;

use Modules\Core\Database;

class ConversationState
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * ดึง state ปัจจุบันของ user
     */
    public function getState(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM conversation_states WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1",
            [$userId]
        );
    }
    
    /**
     * บันทึก state
     */
    public function setState(int $userId, string $state, array $data = []): bool
    {
        try {
            $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            return $this->db->execute(
                "INSERT INTO conversation_states (user_id, current_state, state_data, updated_at) 
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE current_state = ?, state_data = ?, updated_at = NOW()",
                [$userId, $state, $dataJson, $state, $dataJson]
            );
        } catch (\Exception $e) {
            error_log("ConversationState::setState error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ล้าง state
     */
    public function clearState(int $userId): bool
    {
        try {
            return $this->db->execute(
                "DELETE FROM conversation_states WHERE user_id = ?",
                [$userId]
            );
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * ตรวจสอบว่า state หมดอายุหรือไม่ (default 30 นาที)
     */
    public function isExpired(int $userId, int $minutes = 30): bool
    {
        $state = $this->getState($userId);
        
        if (!$state) return true;
        
        $updatedAt = strtotime($state['updated_at']);
        $expireTime = $updatedAt + ($minutes * 60);
        
        return time() > $expireTime;
    }
}
