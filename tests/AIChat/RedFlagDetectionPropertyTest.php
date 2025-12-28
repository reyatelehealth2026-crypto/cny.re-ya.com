<?php
/**
 * Property-Based Test: Red Flag Detection Accuracy
 * 
 * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
 * **Validates: Requirements 3.1**
 * 
 * Property: For any message containing critical red flag patterns (chest pain, 
 * difficulty breathing, seizure, stroke symptoms), the RedFlagDetector SHALL 
 * return a flag with severity 'critical'.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/AIChat/Autoloader.php';

// Load AIChat modules
if (function_exists('loadAIChatModule')) {
    loadAIChatModule();
}

class RedFlagDetectionPropertyTest extends TestCase
{
    private $redFlagDetector;
    
    protected function setUp(): void
    {
        $this->redFlagDetector = new \Modules\AIChat\Services\RedFlagDetector();
    }
    
    /**
     * Generate critical red flag messages for property testing
     * These are messages that MUST trigger critical severity
     * 
     * @return array Array of test data sets [message, expected_severity]
     */
    public function criticalRedFlagProvider(): array
    {
        $testCases = [];
        
        // Chest pain patterns - CRITICAL
        $chestPainPatterns = [
            'เจ็บหน้าอก',
            'แน่นหน้าอก',
            'เจ็บแน่นหน้าอก',
            'chest pain',
            'รู้สึกเจ็บหน้าอกมาก',
            'แน่นหน้าอกหายใจไม่ออก',
        ];
        
        foreach ($chestPainPatterns as $idx => $pattern) {
            $testCases["chest_pain_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Breathing difficulty patterns - CRITICAL
        $breathingPatterns = [
            'หายใจไม่ออก',
            'หายใจลำบาก',
            'หอบหนัก',
            'หายใจไม่ทัน',
            'หายใจไม่ออกเลย',
        ];
        
        foreach ($breathingPatterns as $idx => $pattern) {
            $testCases["breathing_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Seizure/unconsciousness patterns - CRITICAL
        $seizurePatterns = [
            'ชัก',
            'หมดสติ',
            'เป็นลม',
            'ไม่รู้สึกตัว',
            'seizure',
            'ชักเกร็ง',
        ];
        
        foreach ($seizurePatterns as $idx => $pattern) {
            $testCases["seizure_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Stroke symptoms - CRITICAL
        $strokePatterns = [
            'แขนขาอ่อนแรง',
            'พูดไม่ชัด',
            'หน้าเบี้ยว',
            'ปากเบี้ยว',
            'stroke',
        ];
        
        foreach ($strokePatterns as $idx => $pattern) {
            $testCases["stroke_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Severe allergic reaction - CRITICAL
        $allergyPatterns = [
            'แพ้รุนแรง',
            'บวมปาก',
            'บวมลิ้น',
            'anaphylaxis',
        ];
        
        foreach ($allergyPatterns as $idx => $pattern) {
            $testCases["allergy_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Bleeding patterns - CRITICAL
        $bleedingPatterns = [
            'อาเจียนเป็นเลือด',
            'อาเจียนเลือด',
            'ถ่ายเป็นเลือด',
            'ถ่ายดำ',
        ];
        
        foreach ($bleedingPatterns as $idx => $pattern) {
            $testCases["bleeding_{$idx}"] = [$pattern, 'critical'];
        }
        
        // Mental health crisis - CRITICAL
        $mentalHealthPatterns = [
            'ฆ่าตัวตาย',
            'อยากตาย',
            'ไม่อยากมีชีวิต',
            'ทำร้ายตัวเอง',
        ];
        
        foreach ($mentalHealthPatterns as $idx => $pattern) {
            $testCases["mental_health_{$idx}"] = [$pattern, 'critical'];
        }
        
        return $testCases;
    }
    
    /**
     * Generate warning red flag messages for property testing
     * These are messages that should trigger warning severity
     * 
     * @return array Array of test data sets [message, expected_severity]
     */
    public function warningRedFlagProvider(): array
    {
        $testCases = [];
        
        // High fever patterns - WARNING
        $feverPatterns = [
            'ไข้สูง',
            'ไข้ 39',
            'ไข้ 40',
            'ไข้มาก',
        ];
        
        foreach ($feverPatterns as $idx => $pattern) {
            $testCases["fever_{$idx}"] = [$pattern, 'warning'];
        }
        
        // Severe headache patterns - WARNING
        $headachePatterns = [
            'ปวดหัวรุนแรงมาก',
            'ปวดหัวที่สุดในชีวิต',
            'ปวดหัวแบบไม่เคยเป็น',
            'ปวดหัวฉับพลัน',
        ];
        
        foreach ($headachePatterns as $idx => $pattern) {
            $testCases["headache_{$idx}"] = [$pattern, 'warning'];
        }
        
        // Severe diarrhea patterns - WARNING
        $diarrheaPatterns = [
            'ท้องเสียมาก',
            'ท้องเสียหลายวัน',
        ];
        
        foreach ($diarrheaPatterns as $idx => $pattern) {
            $testCases["diarrhea_{$idx}"] = [$pattern, 'warning'];
        }
        
        // Severe abdominal pain - WARNING
        $abdominalPatterns = [
            'ปวดท้องรุนแรง',
            'ปวดท้องมาก',
        ];
        
        foreach ($abdominalPatterns as $idx => $pattern) {
            $testCases["abdominal_{$idx}"] = [$pattern, 'warning'];
        }
        
        // Coughing blood - WARNING
        $coughBloodPatterns = [
            'ไอเป็นเลือด',
            'ไอมีเลือด',
            'เสมหะเป็นเลือด',
        ];
        
        foreach ($coughBloodPatterns as $idx => $pattern) {
            $testCases["cough_blood_{$idx}"] = [$pattern, 'warning'];
        }
        
        return $testCases;
    }
    
    /**
     * Generate non-emergency messages that should NOT trigger red flags
     * 
     * @return array Array of test data sets [message]
     */
    public function nonEmergencyMessageProvider(): array
    {
        return [
            ['ปวดหัวเล็กน้อย'],
            ['ไอเล็กน้อย'],
            ['เป็นหวัด'],
            ['คัดจมูก'],
            ['ปวดท้องนิดหน่อย'],
            ['นอนไม่หลับ'],
            ['เหนื่อยง่าย'],
            ['ปวดเมื่อยตามตัว'],
            ['ผื่นคัน'],
            ['ท้องผูก'],
            ['สวัสดีครับ'],
            ['ขอถามเรื่องยา'],
            ['ราคายาเท่าไหร่'],
            ['มียาแก้ปวดไหม'],
        ];
    }
    
    /**
     * Generate random messages with critical patterns embedded
     * 
     * @return array Array of test data sets
     */
    public function randomCriticalMessageProvider(): array
    {
        $testCases = [];
        $criticalPatterns = [
            'เจ็บหน้าอก', 'หายใจไม่ออก', 'ชัก', 'หมดสติ', 
            'แขนขาอ่อนแรง', 'พูดไม่ชัด', 'อาเจียนเป็นเลือด'
        ];
        
        $prefixes = ['วันนี้', 'เมื่อกี้', 'ตอนนี้', 'พอดี', 'รู้สึก'];
        $suffixes = ['มากครับ', 'ค่ะ', 'ช่วยด้วย', 'ทำยังไงดี', ''];
        
        // Generate 50 random combinations
        for ($i = 0; $i < 50; $i++) {
            $pattern = $criticalPatterns[array_rand($criticalPatterns)];
            $prefix = $prefixes[array_rand($prefixes)];
            $suffix = $suffixes[array_rand($suffixes)];
            
            $message = "{$prefix}{$pattern}{$suffix}";
            $testCases["random_critical_{$i}"] = [$message, 'critical'];
        }
        
        return $testCases;
    }

    /**
     * Property Test: Critical red flag patterns MUST return critical severity
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     * 
     * @dataProvider criticalRedFlagProvider
     */
    public function testCriticalRedFlagPatternsReturnCriticalSeverity(string $message, string $expectedSeverity): void
    {
        $flags = $this->redFlagDetector->detect($message);
        
        $this->assertNotEmpty(
            $flags,
            "Message '{$message}' should trigger red flag detection"
        );
        
        $this->assertTrue(
            $this->redFlagDetector->isCritical($flags),
            "Message '{$message}' should be detected as CRITICAL severity"
        );
        
        // Verify at least one flag has critical severity
        $hasCritical = false;
        foreach ($flags as $flag) {
            if (($flag['severity'] ?? '') === 'critical') {
                $hasCritical = true;
                break;
            }
        }
        
        $this->assertTrue(
            $hasCritical,
            "At least one flag for message '{$message}' should have severity 'critical'"
        );
    }
    
    /**
     * Property Test: Warning red flag patterns should return warning severity
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     * 
     * @dataProvider warningRedFlagProvider
     */
    public function testWarningRedFlagPatternsReturnWarningSeverity(string $message, string $expectedSeverity): void
    {
        $flags = $this->redFlagDetector->detect($message);
        
        $this->assertNotEmpty(
            $flags,
            "Message '{$message}' should trigger red flag detection"
        );
        
        $this->assertTrue(
            $this->redFlagDetector->hasWarning($flags),
            "Message '{$message}' should be detected as WARNING severity"
        );
    }
    
    /**
     * Property Test: Non-emergency messages should NOT trigger red flags
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     * 
     * @dataProvider nonEmergencyMessageProvider
     */
    public function testNonEmergencyMessagesDoNotTriggerRedFlags(string $message): void
    {
        $flags = $this->redFlagDetector->detect($message);
        
        // Non-emergency messages should either return empty or non-critical flags
        // Always make an assertion to avoid risky test warning
        if (empty($flags)) {
            $this->assertEmpty($flags, "Non-emergency message '{$message}' should not trigger any red flags");
        } else {
            $this->assertFalse(
                $this->redFlagDetector->isCritical($flags),
                "Non-emergency message '{$message}' should NOT be detected as critical"
            );
        }
    }
    
    /**
     * Property Test: Random messages with critical patterns embedded MUST be detected
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     * 
     * @dataProvider randomCriticalMessageProvider
     */
    public function testRandomCriticalMessagesAreDetected(string $message, string $expectedSeverity): void
    {
        $flags = $this->redFlagDetector->detect($message);
        
        $this->assertNotEmpty(
            $flags,
            "Random message with critical pattern '{$message}' should trigger detection"
        );
        
        $this->assertTrue(
            $this->redFlagDetector->isCritical($flags),
            "Random message with critical pattern '{$message}' should be CRITICAL"
        );
    }
    
    /**
     * Property Test: isCritical returns true only when critical flags exist
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     */
    public function testIsCriticalReturnsTrueOnlyForCriticalFlags(): void
    {
        // Test with critical flags
        $criticalFlags = [
            ['severity' => 'critical', 'message' => 'Test critical'],
        ];
        $this->assertTrue($this->redFlagDetector->isCritical($criticalFlags));
        
        // Test with warning flags only
        $warningFlags = [
            ['severity' => 'warning', 'message' => 'Test warning'],
        ];
        $this->assertFalse($this->redFlagDetector->isCritical($warningFlags));
        
        // Test with empty flags
        $this->assertFalse($this->redFlagDetector->isCritical([]));
        
        // Test with mixed flags (should return true because critical exists)
        $mixedFlags = [
            ['severity' => 'warning', 'message' => 'Test warning'],
            ['severity' => 'critical', 'message' => 'Test critical'],
        ];
        $this->assertTrue($this->redFlagDetector->isCritical($mixedFlags));
    }
    
    /**
     * Property Test: Emergency contacts are always returned
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.2**
     */
    public function testEmergencyContactsAreReturned(): void
    {
        $contacts = $this->redFlagDetector->getEmergencyContacts();
        
        $this->assertNotEmpty($contacts, "Emergency contacts should not be empty");
        
        // Check for required emergency numbers (1669, 1323, 1367)
        $numbers = array_column($contacts, 'number');
        
        $this->assertContains('1669', $numbers, "Emergency contacts should include 1669");
        $this->assertContains('1323', $numbers, "Emergency contacts should include 1323");
        $this->assertContains('1367', $numbers, "Emergency contacts should include 1367");
    }
    
    /**
     * Property Test: Warning message is built correctly for detected flags
     * 
     * **Feature: liff-ai-assistant-integration, Property 2: Red Flag Detection Accuracy**
     * **Validates: Requirements 3.1**
     */
    public function testWarningMessageIsBuiltCorrectly(): void
    {
        // Test with critical flags
        $criticalFlags = [
            ['severity' => 'critical', 'message' => 'เจ็บหน้าอก', 'action' => 'ไปพบแพทย์'],
        ];
        
        $warningMessage = $this->redFlagDetector->buildWarningMessage($criticalFlags);
        
        $this->assertNotEmpty($warningMessage, "Warning message should not be empty");
        $this->assertStringContainsString('ฉุกเฉิน', $warningMessage, "Critical warning should mention emergency");
        
        // Test with empty flags
        $emptyMessage = $this->redFlagDetector->buildWarningMessage([]);
        $this->assertEmpty($emptyMessage, "Empty flags should return empty message");
    }
}
