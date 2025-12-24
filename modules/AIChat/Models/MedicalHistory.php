<?php
/**
 * MedicalHistory Model
 * จัดการประวัติการรักษาของลูกค้า
 */

namespace Modules\AIChat\Models;

use Modules\Core\Database;

class MedicalHistory
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * บันทึกประวัติการรักษา
     */
    public function save(int $userId, array $data): ?int
    {
        try {
            $this->db->execute(
                "INSERT INTO medical_history 
                 (user_id, triage_session_id, symptoms, diagnosis, medications_prescribed, pharmacist_notes, follow_up_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $data['triage_session_id'] ?? null,
                    json_encode($data['symptoms'] ?? [], JSON_UNESCAPED_UNICODE),
                    $data['diagnosis'] ?? null,
                    json_encode($data['medications'] ?? [], JSON_UNESCAPED_UNICODE),
                    $data['notes'] ?? null,
                    $data['follow_up_date'] ?? null,
                ]
            );
            
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("MedicalHistory::save error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ดึงประวัติการรักษาของ user
     */
    public function getHistory(int $userId, int $limit = 10): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT mh.*, ts.triage_data 
                 FROM medical_history mh
                 LEFT JOIN triage_sessions ts ON mh.triage_session_id = ts.id
                 WHERE mh.user_id = ?
                 ORDER BY mh.created_at DESC
                 LIMIT ?",
                [$userId, $limit]
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * ดึงประวัติการใช้ยา
     */
    public function getMedicationHistory(int $userId): array
    {
        try {
            $history = $this->db->fetchAll(
                "SELECT medications_prescribed, created_at 
                 FROM medical_history 
                 WHERE user_id = ? AND medications_prescribed IS NOT NULL
                 ORDER BY created_at DESC
                 LIMIT 20",
                [$userId]
            );
            
            $medications = [];
            foreach ($history as $record) {
                $meds = json_decode($record['medications_prescribed'], true);
                if ($meds) {
                    foreach ($meds as $med) {
                        $medications[] = [
                            'name' => $med['name'] ?? $med,
                            'date' => $record['created_at'],
                        ];
                    }
                }
            }
            
            return $medications;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * ดึงอาการที่เคยมี
     */
    public function getPastSymptoms(int $userId): array
    {
        try {
            $history = $this->db->fetchAll(
                "SELECT symptoms, created_at 
                 FROM medical_history 
                 WHERE user_id = ? AND symptoms IS NOT NULL
                 ORDER BY created_at DESC
                 LIMIT 10",
                [$userId]
            );
            
            $symptoms = [];
            foreach ($history as $record) {
                $syms = json_decode($record['symptoms'], true);
                if ($syms) {
                    $symptoms = array_merge($symptoms, $syms);
                }
            }
            
            return array_unique($symptoms);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * ตรวจสอบว่าเคยมีอาการซ้ำหรือไม่
     */
    public function checkRecurringSymptoms(int $userId, array $currentSymptoms, int $days = 30): array
    {
        try {
            $history = $this->db->fetchAll(
                "SELECT symptoms, created_at 
                 FROM medical_history 
                 WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 ORDER BY created_at DESC",
                [$userId, $days]
            );
            
            $recurring = [];
            foreach ($history as $record) {
                $pastSymptoms = json_decode($record['symptoms'], true) ?: [];
                foreach ($currentSymptoms as $symptom) {
                    if (in_array($symptom, $pastSymptoms)) {
                        $recurring[] = [
                            'symptom' => $symptom,
                            'last_occurrence' => $record['created_at'],
                        ];
                    }
                }
            }
            
            return $recurring;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * สร้างสรุปประวัติสำหรับ AI
     */
    public function generateSummaryForAI(int $userId): string
    {
        $summary = '';
        
        // Past symptoms
        $symptoms = $this->getPastSymptoms($userId);
        if (!empty($symptoms)) {
            $summary .= "อาการที่เคยมี: " . implode(', ', array_slice($symptoms, 0, 5)) . "\n";
        }
        
        // Past medications
        $medications = $this->getMedicationHistory($userId);
        if (!empty($medications)) {
            $medNames = array_unique(array_column($medications, 'name'));
            $summary .= "ยาที่เคยใช้: " . implode(', ', array_slice($medNames, 0, 5)) . "\n";
        }
        
        return $summary;
    }
}
