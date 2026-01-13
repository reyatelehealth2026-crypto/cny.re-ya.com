<?php
/**
 * Property-Based Test: Prescription Drug Disclaimer
 * 
 * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
 * **Validates: Requirements 6.6**
 * 
 * Property: For any draft mentioning prescription-only drugs, the system should 
 * automatically add a disclaimer about consulting a doctor.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class PrescriptionDrugDisclaimerPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
    // Prescription drug keywords (from PharmacyGhostDraftService)
    private $prescriptionKeywords = [
        // Thai
        'ยาอันตราย', 'ยาควบคุมพิเศษ', 'ยาแพทย์สั่ง', 'ต้องมีใบสั่งยา',
        'ยาปฏิชีวนะ', 'ยาฆ่าเชื้อ', 'ยาสเตียรอยด์', 'ยากดภูมิ',
        'ยานอนหลับ', 'ยาคลายกังวล', 'ยาแก้ปวดกลุ่มโอปิออยด์',
        'ยาลดความดัน', 'ยาเบาหวาน', 'ยาหัวใจ', 'ยาไทรอยด์',
        'ยาต้านไวรัส', 'ยาเคมีบำบัด', 'ยาฮอร์โมน',
        // English
        'prescription', 'controlled', 'antibiotic', 'steroid',
        'sedative', 'opioid', 'antihypertensive', 'antidiabetic',
        'cardiac', 'thyroid', 'antiviral', 'chemotherapy', 'hormone'
    ];
    
    // Common prescription drug names
    private $prescriptionDrugs = [
        // Antibiotics
        'amoxicillin', 'azithromycin', 'ciprofloxacin', 'doxycycline',
        'อะม็อกซีซิลลิน', 'อะซิโธรมัยซิน',
        // Blood pressure
        'amlodipine', 'losartan', 'enalapril', 'metoprolol',
        'แอมโลดิปีน', 'โลซาร์แทน',
        // Diabetes
        'metformin', 'glipizide', 'insulin',
        'เมทฟอร์มิน', 'อินซูลิน',
        // Pain (controlled)
        'tramadol', 'codeine', 'morphine',
        'ทรามาดอล', 'โคเดอีน',
        // Sedatives
        'diazepam', 'alprazolam', 'lorazepam',
        'ไดอะซีแพม', 'อัลปราโซแลม',
        // Steroids
        'prednisolone', 'dexamethasone',
        'เพรดนิโซโลน', 'เดกซาเมทาโซน'
    ];
    
    // OTC (non-prescription) drugs for negative testing
    private $otcDrugs = [
        'พาราเซตามอล', 'paracetamol', 'ไทลินอล', 'tylenol',
        'ไอบูโพรเฟน', 'ibuprofen', 'แอสไพริน', 'aspirin',
        'ยาแก้แพ้', 'ยาแก้ไอ', 'ยาลดน้ำมูก',
        'ยาธาตุน้ำขาว', 'ยาธาตุน้ำแดง', 'ยาหม่อง', 'ยาดม',
        'วิตามินซี', 'vitamin c', 'วิตามินบี', 'vitamin b'
    ];
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Include the service class
        require_once __DIR__ . '/../../classes/PharmacyGhostDraftService.php';
        
        // Create service instance
        $this->service = new \PharmacyGhostDraftService($this->db, $this->lineAccountId);
    }
    
    private function createTables(): void
    {
        // business_items table for drug lookup
        $this->db->exec("
            CREATE TABLE business_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                name VARCHAR(255),
                sku VARCHAR(100),
                is_active INTEGER DEFAULT 1,
                is_prescription INTEGER DEFAULT 0
            )
        ");
        
        // ai_settings table (required by service constructor)
        $this->db->exec("
            CREATE TABLE ai_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                gemini_api_key VARCHAR(255),
                model VARCHAR(100)
            )
        ");
    }

    /**
     * Generate a random draft message without prescription drugs
     */
    private function generateOtcDraft(): string
    {
        $templates = [
            'แนะนำให้รับประทาน %s วันละ 2 ครั้ง หลังอาหาร',
            'สำหรับอาการนี้ ลองใช้ %s ดูนะคะ',
            'ยา %s ช่วยบรรเทาอาการได้ดีค่ะ',
            'For this symptom, I recommend %s twice daily',
            'You can take %s after meals',
        ];
        
        $template = $templates[array_rand($templates)];
        $drug = $this->otcDrugs[array_rand($this->otcDrugs)];
        
        return sprintf($template, $drug);
    }
    
    /**
     * Generate a random draft message with prescription drugs
     */
    private function generatePrescriptionDraft(): string
    {
        $templates = [
            'แนะนำให้รับประทาน %s วันละ 2 ครั้ง หลังอาหาร',
            'สำหรับอาการนี้ ต้องใช้ %s ค่ะ',
            'ยา %s จะช่วยรักษาอาการได้ค่ะ',
            'For this condition, you need %s twice daily',
            'The doctor may prescribe %s for you',
        ];
        
        $template = $templates[array_rand($templates)];
        $drug = $this->prescriptionDrugs[array_rand($this->prescriptionDrugs)];
        
        return sprintf($template, $drug);
    }
    
    /**
     * Generate a draft with prescription keyword
     */
    private function generateDraftWithKeyword(): string
    {
        $templates = [
            'นี่เป็น%sที่ต้องใช้ตามคำสั่งแพทย์',
            'ยานี้จัดเป็น%s ต้องมีใบสั่งยา',
            'This is a %s medication',
            'You need a %s for this drug',
        ];
        
        $template = $templates[array_rand($templates)];
        $keyword = $this->prescriptionKeywords[array_rand($this->prescriptionKeywords)];
        
        return sprintf($template, $keyword);
    }
    
    /**
     * Check if text contains disclaimer
     */
    private function containsDisclaimer(string $text): bool
    {
        return mb_strpos($text, 'ปรึกษาแพทย์') !== false || 
               mb_strpos($text, 'พบแพทย์') !== false ||
               mb_strpos($text, 'ใบสั่งยา') !== false ||
               mb_strpos($text, 'คำสั่งแพทย์') !== false;
    }

    /**
     * Property Test: Prescription Drug Triggers Disclaimer
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft mentioning a prescription drug name, the system should add a disclaimer.
     */
    public function testPrescriptionDrugTriggersDisclaimer(): void
    {
        // Run 100 iterations with random prescription drugs
        for ($i = 0; $i < 100; $i++) {
            $draft = $this->generatePrescriptionDraft();
            $mentionedDrugs = []; // Empty array - detection should be from draft text
            
            // Act: Call addDisclaimer
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Result should contain disclaimer
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Draft with prescription drug should have disclaimer added on iteration {$i}. " .
                "Original: '{$draft}', Result: '{$result}'"
            );
            
            // Assert: Result should be longer than original (disclaimer added)
            $this->assertGreaterThanOrEqual(
                mb_strlen($draft),
                mb_strlen($result),
                "Result should be at least as long as original on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Prescription Keyword Triggers Disclaimer
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft containing prescription keywords, the system should add a disclaimer.
     */
    public function testPrescriptionKeywordTriggersDisclaimer(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $draft = $this->generateDraftWithKeyword();
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Result should contain disclaimer
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Draft with prescription keyword should have disclaimer on iteration {$i}. " .
                "Original: '{$draft}', Result: '{$result}'"
            );
        }
    }

    /**
     * Property Test: OTC Drug Does Not Trigger Disclaimer
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft mentioning only OTC drugs, the system should NOT add a disclaimer.
     */
    public function testOtcDrugDoesNotTriggerDisclaimer(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $draft = $this->generateOtcDraft();
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Result should be unchanged (no disclaimer added)
            $this->assertEquals(
                $draft,
                $result,
                "Draft with only OTC drugs should not have disclaimer on iteration {$i}. " .
                "Original: '{$draft}', Result: '{$result}'"
            );
        }
    }

    /**
     * Property Test: Mentioned Drugs Array Triggers Disclaimer
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft with prescription drugs in the mentionedDrugs array, 
     * the system should add a disclaimer.
     */
    public function testMentionedDrugsArrayTriggersDisclaimer(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Use a generic draft without prescription keywords
            $draft = 'แนะนำยาตามรายการนี้ค่ะ';
            
            // Add prescription drug to mentioned drugs array
            $prescriptionDrug = $this->prescriptionDrugs[array_rand($this->prescriptionDrugs)];
            $mentionedDrugs = [
                ['name' => $prescriptionDrug, 'sku' => 'TEST-' . $i]
            ];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Result should contain disclaimer
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Draft with prescription drug in mentionedDrugs should have disclaimer on iteration {$i}. " .
                "Drug: '{$prescriptionDrug}', Result: '{$result}'"
            );
        }
    }


    /**
     * Property Test: Existing Disclaimer Not Duplicated
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft that already contains a disclaimer, the system should NOT add another.
     */
    public function testExistingDisclaimerNotDuplicated(): void
    {
        $existingDisclaimers = [
            'กรุณาปรึกษาแพทย์ก่อนใช้ยา',
            'ควรพบแพทย์เพื่อรับการวินิจฉัย',
            'ต้องมีใบสั่งยาจากแพทย์',
        ];
        
        for ($i = 0; $i < 100; $i++) {
            // Create draft with prescription drug AND existing disclaimer
            $prescriptionDrug = $this->prescriptionDrugs[array_rand($this->prescriptionDrugs)];
            $existingDisclaimer = $existingDisclaimers[array_rand($existingDisclaimers)];
            $draft = "แนะนำ {$prescriptionDrug} สำหรับอาการนี้ {$existingDisclaimer}";
            
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Result should be unchanged (no additional disclaimer)
            $this->assertEquals(
                $draft,
                $result,
                "Draft with existing disclaimer should not have another added on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Disclaimer Contains Required Warning Text
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any added disclaimer, it should contain text about consulting a doctor.
     */
    public function testDisclaimerContainsRequiredWarning(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $draft = $this->generatePrescriptionDraft();
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // If disclaimer was added (result is different from draft)
            if ($result !== $draft) {
                // Assert: Disclaimer should mention consulting doctor/pharmacist
                $hasConsultDoctor = mb_strpos($result, 'ปรึกษาแพทย์') !== false ||
                                    mb_strpos($result, 'เภสัชกร') !== false;
                
                $this->assertTrue(
                    $hasConsultDoctor,
                    "Disclaimer should mention consulting doctor or pharmacist on iteration {$i}. " .
                    "Result: '{$result}'"
                );
            }
        }
    }

    /**
     * Property Test: Case Insensitive Drug Detection
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any prescription drug name regardless of case, the system should detect it.
     */
    public function testCaseInsensitiveDrugDetection(): void
    {
        $englishDrugs = ['amoxicillin', 'metformin', 'tramadol', 'diazepam', 'prednisolone'];
        
        for ($i = 0; $i < 100; $i++) {
            $drug = $englishDrugs[array_rand($englishDrugs)];
            
            // Randomly change case
            $caseVariant = rand(0, 2);
            switch ($caseVariant) {
                case 0:
                    $drugVariant = strtoupper($drug);
                    break;
                case 1:
                    $drugVariant = ucfirst($drug);
                    break;
                default:
                    $drugVariant = strtolower($drug);
            }
            
            $draft = "I recommend {$drugVariant} for your condition";
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Should detect regardless of case
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Should detect prescription drug '{$drugVariant}' regardless of case on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Mixed OTC and Prescription Triggers Disclaimer
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any draft containing both OTC and prescription drugs, 
     * the system should add a disclaimer.
     */
    public function testMixedDrugsTriggersDisclaimer(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $otcDrug = $this->otcDrugs[array_rand($this->otcDrugs)];
            $prescriptionDrug = $this->prescriptionDrugs[array_rand($this->prescriptionDrugs)];
            
            $draft = "แนะนำ {$otcDrug} สำหรับอาการเบื้องต้น และ {$prescriptionDrug} สำหรับอาการรุนแรง";
            $mentionedDrugs = [];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Should have disclaimer due to prescription drug
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Draft with mixed OTC and prescription drugs should have disclaimer on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Empty Draft Returns Empty
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For an empty draft, the system should return empty without error.
     */
    public function testEmptyDraftReturnsEmpty(): void
    {
        $draft = '';
        $mentionedDrugs = [];
        
        // Act
        $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
        
        // Assert: Should return empty string
        $this->assertEquals('', $result, "Empty draft should return empty string");
    }

    /**
     * Property Test: Database Prescription Flag Detection
     * 
     * **Feature: vibe-selling-os-v2, Property 12: Prescription Drug Disclaimer**
     * **Validates: Requirements 6.6**
     * 
     * For any drug marked as prescription in the database, 
     * the system should add a disclaimer when mentioned.
     */
    public function testDatabasePrescriptionFlagDetection(): void
    {
        // Insert prescription drugs into database
        $stmt = $this->db->prepare("
            INSERT INTO business_items (line_account_id, name, sku, is_active, is_prescription)
            VALUES (?, ?, ?, 1, 1)
        ");
        
        for ($i = 0; $i < 50; $i++) {
            $drugName = 'TestPrescriptionDrug' . $i;
            $sku = 'RX-' . $i;
            $stmt->execute([$this->lineAccountId, $drugName, $sku]);
            
            // Use a generic draft
            $draft = 'แนะนำยาตามรายการค่ะ';
            $mentionedDrugs = [
                ['name' => $drugName, 'sku' => $sku]
            ];
            
            // Act
            $result = $this->service->addDisclaimer($draft, $mentionedDrugs);
            
            // Assert: Should have disclaimer due to database flag
            $this->assertTrue(
                $this->containsDisclaimer($result),
                "Drug marked as prescription in database should trigger disclaimer on iteration {$i}"
            );
        }
    }
}
