<?php
/**
 * Service: Context Analyzer
 * วิเคราะห์อาการจากข้อความลูกค้า
 */

namespace Modules\AIChat\Services;

class ContextAnalyzer
{
    // รายการอาการที่รู้จัก
    private const SYMPTOM_PATTERNS = [
        'ปวดหัว', 'ปวดศีรษะ', 'ปวดคอ', 'ปวดหลัง', 'ปวดท้อง', 
        'ปวดกล้ามเนื้อ', 'ปวดเมื่อย', 'ปวดไหล่', 'ปวดแขน', 'ปวดขา',
        'ปวดข้อ', 'ปวดเข่า', 'ปวดเอว', 'ปวดต้นคอ',
        'ไข้', 'ไอ', 'เจ็บคอ', 'คัดจมูก', 'น้ำมูก', 'หวัด',
        'ท้องเสีย', 'ท้องผูก', 'คลื่นไส้', 'อาเจียน', 
        'ผื่น', 'คัน', 'แพ้', 'เวียนหัว', 'อ่อนเพลีย', 
        'นอนไม่หลับ', 'หายใจไม่สะดวก', 'แน่นหน้าอก',
        'กรดไหลย้อน', 'แสบท้อง', 'จุกเสียด'
    ];
    
    /**
     * วิเคราะห์ข้อความและดึงอาการ
     */
    public function analyze(array $conversationHistory): array
    {
        $info = [];
        $fullText = '';
        
        foreach ($conversationHistory as $msg) {
            if ($msg['role'] === 'user') {
                $fullText .= $msg['content'] . ' ';
            }
        }
        $fullText = mb_strtolower($fullText);
        
        // ดึงอาการ
        $symptoms = $this->extractSymptoms($fullText);
        if (!empty($symptoms)) {
            $info['อาการ'] = implode(', ', array_unique($symptoms));
        }
        
        // ดึงระยะเวลา
        if (preg_match('/(\d+)[-]?(\d+)?\s*วัน/u', $fullText, $m)) {
            $info['ระยะเวลา'] = $m[0];
        }
        
        return $info;
    }
    
    /**
     * ดึงอาการจากข้อความ
     */
    private function extractSymptoms(string $text): array
    {
        $symptoms = [];
        foreach (self::SYMPTOM_PATTERNS as $symptom) {
            if (mb_strpos($text, $symptom) !== false) {
                $symptoms[] = $symptom;
            }
        }
        return $symptoms;
    }
    
    /**
     * ตรวจสอบว่าขาดข้อมูลอะไร (ไม่ใช้แล้ว - แนะนำยาทันที)
     */
    public function getMissingInfo(array $info): array
    {
        // ไม่ต้องถามอะไรเพิ่ม - แนะนำยาทันทีเมื่อมีอาการ
        return [];
    }
}
