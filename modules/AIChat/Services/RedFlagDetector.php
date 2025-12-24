<?php
/**
 * RedFlagDetector - ตรวจจับอาการฉุกเฉินที่ต้องพบแพทย์
 * Version 2.0 - Professional Medical Red Flag Detection
 * 
 * Features:
 * - ตรวจจับอาการฉุกเฉิน (Critical)
 * - ตรวจจับอาการที่ควรระวัง (Warning)
 * - แนะนำ action ที่เหมาะสม
 */

namespace Modules\AIChat\Services;

class RedFlagDetector
{
    // Critical Red Flags - ต้องพบแพทย์ทันที
    private const CRITICAL_FLAGS = [
        [
            'patterns' => ['เจ็บหน้าอก', 'แน่นหน้าอก', 'เจ็บแน่นหน้าอก', 'chest pain'],
            'message' => 'อาการเจ็บ/แน่นหน้าอก อาจเป็นสัญญาณของโรคหัวใจ',
            'action' => '🚨 กรุณาไปพบแพทย์ทันที หรือโทร 1669',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['หายใจไม่ออก', 'หายใจลำบาก', 'หอบหนัก', 'หายใจไม่ทัน'],
            'message' => 'อาการหายใจลำบากรุนแรง',
            'action' => '🚨 กรุณาไปพบแพทย์ทันที หรือโทร 1669',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['อาเจียนเป็นเลือด', 'อาเจียนเลือด', 'ถ่ายเป็นเลือด', 'ถ่ายดำ'],
            'message' => 'อาการเลือดออกในทางเดินอาหาร',
            'action' => '🚨 กรุณาไปพบแพทย์ทันที',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['ชัก', 'หมดสติ', 'เป็นลม', 'ไม่รู้สึกตัว', 'seizure'],
            'message' => 'อาการชักหรือหมดสติ',
            'action' => '🚨 กรุณาโทร 1669 ทันที',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['แขนขาอ่อนแรง', 'พูดไม่ชัด', 'หน้าเบี้ยว', 'ปากเบี้ยว', 'stroke'],
            'message' => 'อาการคล้ายโรคหลอดเลือดสมอง (Stroke)',
            'action' => '🚨 กรุณาไปพบแพทย์ทันที หรือโทร 1669 - ทุกนาทีมีค่า!',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['แพ้รุนแรง', 'หายใจไม่ออก.*แพ้', 'บวมปาก', 'บวมลิ้น', 'anaphylaxis'],
            'message' => 'อาการแพ้รุนแรง (Anaphylaxis)',
            'action' => '🚨 กรุณาไปพบแพทย์ทันที หรือโทร 1669',
            'severity' => 'critical',
        ],
        [
            'patterns' => ['ฆ่าตัวตาย', 'อยากตาย', 'ไม่อยากมีชีวิต', 'ทำร้ายตัวเอง'],
            'message' => 'ความคิดทำร้ายตัวเอง',
            'action' => '🚨 กรุณาโทรสายด่วนสุขภาพจิต 1323 ทันที',
            'severity' => 'critical',
        ],
    ];
    
    // Warning Red Flags - ควรพบแพทย์เร็ว
    private const WARNING_FLAGS = [
        [
            'patterns' => ['ไข้สูง', 'ไข้ 39', 'ไข้ 40', 'ไข้มาก'],
            'message' => 'ไข้สูงมาก (>39°C)',
            'action' => '⚠️ ควรพบแพทย์ภายใน 24 ชั่วโมง',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ปวดหัวรุนแรงมาก', 'ปวดหัวที่สุดในชีวิต', 'ปวดหัวแบบไม่เคยเป็น', 'ปวดหัวฉับพลัน'],
            'message' => 'ปวดศีรษะรุนแรงผิดปกติ',
            'action' => '⚠️ ควรพบแพทย์โดยเร็ว',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ท้องเสียมาก', 'ท้องเสียหลายวัน', 'ท้องเสีย.*เลือด', 'ถ่ายเหลว.*มาก'],
            'message' => 'ท้องเสียรุนแรงหรือมีเลือดปน',
            'action' => '⚠️ ควรพบแพทย์ภายใน 24 ชั่วโมง เสี่ยงขาดน้ำ',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ปวดท้องรุนแรง', 'ปวดท้องมาก', 'ปวดท้องน้อย.*รุนแรง'],
            'message' => 'ปวดท้องรุนแรง',
            'action' => '⚠️ ควรพบแพทย์โดยเร็ว อาจเป็นไส้ติ่งอักเสบ',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ตาแดง.*ปวด', 'ปวดตา.*มาก', 'มองไม่ชัด.*ทันที', 'ตามัว.*ทันที'],
            'message' => 'อาการทางตาที่ผิดปกติ',
            'action' => '⚠️ ควรพบจักษุแพทย์โดยเร็ว',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['บวม.*ขา.*ข้างเดียว', 'ขาบวม.*แดง', 'น่องบวม'],
            'message' => 'ขาบวมข้างเดียว อาจเป็นลิ่มเลือดอุดตัน',
            'action' => '⚠️ ควรพบแพทย์โดยเร็ว',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ไอเป็นเลือด', 'ไอมีเลือด', 'เสมหะเป็นเลือด'],
            'message' => 'ไอเป็นเลือด',
            'action' => '⚠️ ควรพบแพทย์โดยเร็ว',
            'severity' => 'warning',
        ],
        [
            'patterns' => ['ตั้งครรภ์.*เลือดออก', 'ท้อง.*เลือดออก', 'pregnant.*bleed'],
            'message' => 'เลือดออกขณะตั้งครรภ์',
            'action' => '⚠️ ควรพบแพทย์ทันที',
            'severity' => 'warning',
        ],
    ];

    // Age-specific Red Flags
    private const AGE_FLAGS = [
        'infant' => [ // < 1 ปี
            [
                'patterns' => ['ไข้', 'ตัวร้อน'],
                'message' => 'ทารกมีไข้ ต้องพบแพทย์',
                'action' => '⚠️ ทารกอายุต่ำกว่า 3 เดือนมีไข้ ควรพบแพทย์ทันที',
                'severity' => 'warning',
            ],
            [
                'patterns' => ['ไม่ดูดนม', 'ไม่กินนม', 'ซึม'],
                'message' => 'ทารกซึม ไม่ดูดนม',
                'action' => '⚠️ ควรพบแพทย์ทันที',
                'severity' => 'warning',
            ],
        ],
        'elderly' => [ // > 65 ปี
            [
                'patterns' => ['ล้ม', 'หกล้ม', 'ตกบันได'],
                'message' => 'ผู้สูงอายุหกล้ม',
                'action' => '⚠️ ควรตรวจดูอาการบาดเจ็บ อาจมีกระดูกหัก',
                'severity' => 'warning',
            ],
            [
                'patterns' => ['สับสน', 'จำไม่ได้', 'หลงลืม.*ทันที'],
                'message' => 'อาการสับสนเฉียบพลัน',
                'action' => '⚠️ ควรพบแพทย์โดยเร็ว',
                'severity' => 'warning',
            ],
        ],
    ];
    
    /**
     * ตรวจจับ Red Flags จากข้อความ
     */
    public function detect(string $message, ?string $ageGroup = null): array
    {
        $flags = [];
        $messageLower = mb_strtolower($message);
        
        // ตรวจ Critical Flags
        foreach (self::CRITICAL_FLAGS as $flag) {
            if ($this->matchPatterns($messageLower, $flag['patterns'])) {
                $flags[] = $flag;
            }
        }
        
        // ตรวจ Warning Flags
        foreach (self::WARNING_FLAGS as $flag) {
            if ($this->matchPatterns($messageLower, $flag['patterns'])) {
                $flags[] = $flag;
            }
        }
        
        // ตรวจ Age-specific Flags
        if ($ageGroup && isset(self::AGE_FLAGS[$ageGroup])) {
            foreach (self::AGE_FLAGS[$ageGroup] as $flag) {
                if ($this->matchPatterns($messageLower, $flag['patterns'])) {
                    $flags[] = $flag;
                }
            }
        }
        
        return $flags;
    }
    
    /**
     * ตรวจสอบว่ามี Critical Red Flag หรือไม่
     */
    public function isCritical(array $flags): bool
    {
        foreach ($flags as $flag) {
            if ($flag['severity'] === 'critical') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * ตรวจสอบว่ามี Warning Red Flag หรือไม่
     */
    public function hasWarning(array $flags): bool
    {
        foreach ($flags as $flag) {
            if ($flag['severity'] === 'warning') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Match patterns (รองรับ regex)
     */
    private function matchPatterns(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // ถ้ามี regex special chars ให้ใช้ preg_match
            if (preg_match('/[.*+?^${}()|[\]\\\\]/', $pattern)) {
                if (preg_match('/' . $pattern . '/iu', $text)) {
                    return true;
                }
            } else {
                // Simple string match
                if (mb_strpos($text, $pattern) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * สร้างข้อความเตือน
     */
    public function buildWarningMessage(array $flags): string
    {
        if (empty($flags)) return '';
        
        $criticalFlags = array_filter($flags, fn($f) => $f['severity'] === 'critical');
        $warningFlags = array_filter($flags, fn($f) => $f['severity'] === 'warning');
        
        $message = '';
        
        if (!empty($criticalFlags)) {
            $message .= "🚨 พบอาการฉุกเฉิน!\n\n";
            foreach ($criticalFlags as $flag) {
                $message .= "⚠️ {$flag['message']}\n";
                $message .= "👉 {$flag['action']}\n\n";
            }
        }
        
        if (!empty($warningFlags)) {
            $message .= "⚠️ อาการที่ควรระวัง:\n\n";
            foreach ($warningFlags as $flag) {
                $message .= "• {$flag['message']}\n";
                $message .= "  {$flag['action']}\n\n";
            }
        }
        
        return $message;
    }
    
    /**
     * Get emergency contacts
     */
    public function getEmergencyContacts(): array
    {
        return [
            ['name' => 'สายด่วนฉุกเฉิน', 'number' => '1669', 'description' => 'กู้ชีพ/ฉุกเฉิน'],
            ['name' => 'สายด่วนสุขภาพจิต', 'number' => '1323', 'description' => 'ปรึกษาปัญหาสุขภาพจิต'],
            ['name' => 'สายด่วนยาเสพติด', 'number' => '1165', 'description' => 'ปรึกษาปัญหายาเสพติด'],
            ['name' => 'ศูนย์พิษวิทยา', 'number' => '1367', 'description' => 'กินยาเกินขนาด/สารพิษ'],
        ];
    }
}
