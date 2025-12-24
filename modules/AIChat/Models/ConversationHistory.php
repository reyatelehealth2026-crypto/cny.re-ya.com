<?php
/**
 * Model: Conversation History
 * จัดการประวัติการสนทนาของลูกค้า
 */

namespace Modules\AIChat\Models;

use Modules\Core\Database;

class ConversationHistory
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * ดึงประวัติการสนทนาล่าสุด
     */
    public function getRecentHistory(int $userId, int $limit = 10): array
    {
        $sql = "
            SELECT 
                CASE WHEN direction = 'incoming' THEN 'user' ELSE 'assistant' END as role,
                content,
                message_type,
                created_at
            FROM messages 
            WHERE user_id = ? 
                AND message_type = 'text'
                AND content IS NOT NULL 
                AND content != ''
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $messages = $this->db->fetchAll($sql, [$userId, $limit]);
        
        // Reverse เพื่อให้เรียงจากเก่าไปใหม่
        $messages = array_reverse($messages);
        
        // กรองข้อความที่ไม่ต้องการ
        $history = [];
        foreach ($messages as $msg) {
            $content = $msg['content'];
            
            // Skip ข้อความสั้นเกินไป
            if (mb_strlen($content) < 2) continue;
            
            // Skip ข้อความที่เป็น command
            if (preg_match('/^\[.*\]/', $content)) continue;
            
            $history[] = [
                'role' => $msg['role'],
                'content' => $content,
                'created_at' => $msg['created_at']
            ];
        }
        
        return $history;
    }
    
    /**
     * ดึงข้อมูลลูกค้า (ข้อมูลสุขภาพ)
     */
    public function getCustomerInfo(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT display_name, phone, medical_conditions, drug_allergies, current_medications 
             FROM users WHERE id = ?",
            [$userId]
        );
    }
}
