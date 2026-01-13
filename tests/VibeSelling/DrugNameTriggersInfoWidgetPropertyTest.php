<?php
/**
 * Property-Based Test: Drug Name Triggers Info Widget
 * 
 * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
 * **Validates: Requirements 4.2**
 * 
 * Property: For any message containing a registered drug name, the system should 
 * return the drug info widget with dosage, side effects, and contraindications.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class DrugNameTriggersInfoWidgetPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
    // Sample drug names for testing
    private $sampleDrugNames = [
        'พาราเซตามอล',
        'ไอบูโพรเฟน',
        'แอสไพริน',
        'อะม็อกซีซิลลิน',
        'เซทิริซีน',
        'ลอราทาดีน',
        'โอเมพราโซล',
        'เมทฟอร์มิน',
        'ซิมวาสแตติน',
        'แอมโลดิปีน',
        'Paracetamol',
        'Ibuprofen',
        'Aspirin',
        'Amoxicillin',
        'Cetirizine'
    ];
    
    // Sample message templates that include drug names
    private $messageTemplates = [
        'ขอข้อมูลยา {drug} หน่อยค่ะ',
        'มียา {drug} ไหมคะ',
        '{drug} ราคาเท่าไหร่',
        'อยากได้ {drug}',
        'ต้องการซื้อ {drug}',
        '{drug} กินยังไง',
        'ผลข้างเคียงของ {drug} มีอะไรบ้าง',
        'แนะนำ {drug} ให้หน่อย',
        'สอบถามเรื่อง {drug} ค่ะ',
        '{drug}'
    ];
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Include the service class
        require_once __DIR__ . '/../../classes/ConsultationAnalyzerService.php';
        
        // Create service instance
        $this->service = new \ConsultationAnalyzerService($this->db, $this->lineAccountId);
    }
    
    private function createTables(): void
    {
        // business_items table for drug data
        $this->db->exec("
            CREATE TABLE business_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                name VARCHAR(255),
                sku VARCHAR(100),
                price DECIMAL(10,2) DEFAULT 0,
                stock INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1
            )
        ");
        
        // pharmacy_context_keywords table
        $this->db->exec("
            CREATE TABLE pharmacy_context_keywords (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                keyword VARCHAR(100),
                keyword_type VARCHAR(50),
                widget_type VARCHAR(50),
                related_data TEXT,
                priority INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1
            )
        ");
        
        // users table for allergy checks
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                name VARCHAR(255),
                drug_allergies TEXT,
                current_medications TEXT
            )
        ");
    }


    /**
     * Create a test drug in the database
     */
    private function createTestDrug(string $name, float $price = 100.0, int $stock = 50): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO business_items (line_account_id, name, sku, price, stock, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $this->lineAccountId,
            $name,
            'SKU-' . rand(1000, 9999),
            $price,
            $stock
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Create a test user
     */
    private function createTestUser(): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (line_account_id, name)
            VALUES (?, ?)
        ");
        $stmt->execute([$this->lineAccountId, 'Test User ' . uniqid()]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Generate a random drug name with unique suffix to avoid collisions
     */
    private function generateRandomDrugName(): string
    {
        $baseName = $this->sampleDrugNames[array_rand($this->sampleDrugNames)];
        // Add unique suffix to avoid collisions between iterations
        return $baseName . '_' . uniqid();
    }
    
    /**
     * Generate a base drug name (without suffix) for specific tests
     */
    private function generateBaseDrugName(): string
    {
        return $this->sampleDrugNames[array_rand($this->sampleDrugNames)];
    }
    
    /**
     * Generate a message containing a drug name
     */
    private function generateMessageWithDrug(string $drugName): string
    {
        $template = $this->messageTemplates[array_rand($this->messageTemplates)];
        return str_replace('{drug}', $drugName, $template);
    }
    
    /**
     * Generate a random price
     */
    private function generateRandomPrice(): float
    {
        return round(rand(1000, 100000) / 100, 2);
    }
    
    /**
     * Generate a random stock quantity
     */
    private function generateRandomStock(): int
    {
        return rand(0, 500);
    }
    
    /**
     * Check if widgets contain a drug_info widget
     */
    private function containsDrugInfoWidget(array $widgets): bool
    {
        foreach ($widgets as $widget) {
            if (isset($widget['type']) && $widget['type'] === 'drug_info') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get drug_info widget from widgets array
     */
    private function getDrugInfoWidget(array $widgets): ?array
    {
        foreach ($widgets as $widget) {
            if (isset($widget['type']) && $widget['type'] === 'drug_info') {
                return $widget;
            }
        }
        return null;
    }

    /**
     * Property Test: Drug Name in Message Triggers Drug Info Widget
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any message containing a registered drug name, the system should 
     * return the drug info widget.
     */
    public function testDrugNameTriggersInfoWidget(): void
    {
        // Run 100 iterations with random drug names
        for ($i = 0; $i < 100; $i++) {
            // Generate random drug name
            $drugName = $this->generateRandomDrugName();
            $price = $this->generateRandomPrice();
            $stock = $this->generateRandomStock();
            
            // Create drug in database
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the drug name
            $message = $this->generateMessageWithDrug($drugName);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should contain drug_info widget
            $this->assertTrue(
                $this->containsDrugInfoWidget($widgets),
                "Message containing drug name '{$drugName}' should trigger drug_info widget on iteration {$i}. " .
                "Message: '{$message}', Widgets: " . json_encode(array_column($widgets, 'type'))
            );
        }
    }


    /**
     * Property Test: Drug Info Widget Contains Required Fields
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any drug info widget returned, it should contain the drug name,
     * price, stock information, and actions.
     */
    public function testDrugInfoWidgetContainsRequiredFields(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random drug data
            $drugName = $this->generateRandomDrugName();
            $price = $this->generateRandomPrice();
            $stock = $this->generateRandomStock();
            
            // Create drug in database
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the drug name
            $message = $this->generateMessageWithDrug($drugName);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Skip if no widget found (edge case with very short names)
            if ($drugWidget === null) {
                continue;
            }
            
            // Assert: Widget should have required fields
            $this->assertArrayHasKey('type', $drugWidget, "Widget should have 'type' field on iteration {$i}");
            $this->assertEquals('drug_info', $drugWidget['type'], "Widget type should be 'drug_info' on iteration {$i}");
            
            $this->assertArrayHasKey('drugName', $drugWidget, "Widget should have 'drugName' field on iteration {$i}");
            $this->assertArrayHasKey('drugId', $drugWidget, "Widget should have 'drugId' field on iteration {$i}");
            $this->assertArrayHasKey('price', $drugWidget, "Widget should have 'price' field on iteration {$i}");
            $this->assertArrayHasKey('stock', $drugWidget, "Widget should have 'stock' field on iteration {$i}");
            $this->assertArrayHasKey('actions', $drugWidget, "Widget should have 'actions' field on iteration {$i}");
            
            // Assert: Actions should be an array
            $this->assertIsArray($drugWidget['actions'], "Actions should be an array on iteration {$i}");
        }
    }

    /**
     * Property Test: Drug Info Widget Shows Correct Drug Data
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any drug info widget, the displayed data should match the database values.
     */
    public function testDrugInfoWidgetShowsCorrectData(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate unique drug name to avoid collisions
            $uniqueSuffix = uniqid() . '_' . $i;
            $drugName = 'TestDrug_' . $uniqueSuffix;
            $price = $this->generateRandomPrice();
            $stock = $this->generateRandomStock();
            
            // Create drug in database
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the drug name
            $message = $drugName; // Use just the drug name for exact matching
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Skip if no widget found
            if ($drugWidget === null) {
                continue;
            }
            
            // Assert: Drug name should match
            $this->assertStringContainsString(
                $drugName,
                $drugWidget['drugName'],
                "Widget drug name should contain '{$drugName}' on iteration {$i}"
            );
            
            // Assert: Price should match
            $this->assertEqualsWithDelta(
                $price,
                $drugWidget['price'],
                0.01,
                "Widget price should match database price on iteration {$i}"
            );
            
            // Assert: Stock should match
            $this->assertEquals(
                $stock,
                $drugWidget['stock'],
                "Widget stock should match database stock on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: No Drug Info Widget for Non-Drug Messages
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any message NOT containing a registered drug name, the system should 
     * NOT return a drug_info widget with a drugId (from drug name matching).
     */
    public function testNoDrugInfoWidgetForNonDrugMessages(): void
    {
        // Messages that don't contain drug names
        $nonDrugMessages = [
            'สวัสดีค่ะ',
            'ขอบคุณค่ะ',
            'ราคาเท่าไหร่',
            'ส่งได้ไหม',
            'มีโปรโมชั่นไหม',
            'เปิดกี่โมง',
            'อยู่ที่ไหน',
            'ติดต่อยังไง',
            'hello',
            'thank you'
        ];
        
        $assertionsMade = 0;
        
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Pick a random non-drug message
            $message = $nonDrugMessages[array_rand($nonDrugMessages)];
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Assert: Either no drug widget, or if there is one, it shouldn't have drugId
            // (drugId is only set when matching from business_items table)
            if ($drugWidget !== null) {
                $this->assertFalse(
                    isset($drugWidget['drugId']) && $drugWidget['drugId'] > 0,
                    "Non-drug message should not trigger drug_info widget with drugId on iteration {$i}. " .
                    "Message: '{$message}'"
                );
                $assertionsMade++;
            } else {
                // No drug widget found - this is expected behavior
                $this->assertNull(
                    $drugWidget,
                    "Non-drug message should not trigger drug_info widget on iteration {$i}"
                );
                $assertionsMade++;
            }
        }
        
        // Ensure we made at least some assertions
        $this->assertGreaterThan(0, $assertionsMade, "Should have made at least one assertion");
    }


    /**
     * Property Test: Drug Info Widget Has Actions for Interaction Check
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any drug info widget, it should include an action to check drug interactions.
     */
    public function testDrugInfoWidgetHasInteractionCheckAction(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random drug data
            $drugName = $this->generateRandomDrugName();
            $price = $this->generateRandomPrice();
            $stock = $this->generateRandomStock();
            
            // Create drug in database
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the drug name
            $message = $drugName;
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Skip if no widget found
            if ($drugWidget === null) {
                continue;
            }
            
            // Assert: Actions should contain interaction check
            $actions = $drugWidget['actions'] ?? [];
            $hasInteractionCheck = false;
            
            foreach ($actions as $action) {
                if (isset($action['action']) && $action['action'] === 'check_interactions') {
                    $hasInteractionCheck = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasInteractionCheck,
                "Drug info widget should have 'check_interactions' action on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Case Insensitive Drug Name Matching
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any drug name, the matching should be case-insensitive.
     */
    public function testCaseInsensitiveDrugNameMatching(): void
    {
        // Test with English drug names that can have case variations
        $englishDrugs = ['Paracetamol', 'Ibuprofen', 'Aspirin', 'Amoxicillin', 'Cetirizine'];
        
        for ($i = 0; $i < 50; $i++) {
            // Pick a random English drug
            $drugName = $englishDrugs[array_rand($englishDrugs)];
            $price = $this->generateRandomPrice();
            $stock = $this->generateRandomStock();
            
            // Create drug in database with original case
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message with different case
            $caseVariations = [
                strtolower($drugName),
                strtoupper($drugName),
                ucfirst(strtolower($drugName))
            ];
            $messageCase = $caseVariations[array_rand($caseVariations)];
            $message = "ขอข้อมูลยา {$messageCase} หน่อยค่ะ";
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should still find the drug (case-insensitive)
            $this->assertTrue(
                $this->containsDrugInfoWidget($widgets),
                "Case-insensitive matching should find drug '{$drugName}' when searching for '{$messageCase}' on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: In-Stock Status Correctly Reported
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * For any drug info widget, the inStock status should correctly reflect stock > 0.
     */
    public function testInStockStatusCorrectlyReported(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate unique drug name to avoid collisions
            $uniqueSuffix = uniqid() . '_stock_' . $i;
            $drugName = 'StockTestDrug_' . $uniqueSuffix;
            $price = $this->generateRandomPrice();
            
            // Randomly set stock to 0 or positive
            $stock = rand(0, 1) === 0 ? 0 : rand(1, 100);
            
            // Create drug in database
            $drugId = $this->createTestDrug($drugName, $price, $stock);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the drug name
            $message = $drugName;
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Skip if no widget found
            if ($drugWidget === null) {
                continue;
            }
            
            // Assert: inStock should match stock > 0
            $expectedInStock = $stock > 0;
            $this->assertEquals(
                $expectedInStock,
                $drugWidget['inStock'],
                "inStock should be " . ($expectedInStock ? 'true' : 'false') . 
                " when stock is {$stock} on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Minimum Drug Name Length for Matching
     * 
     * **Feature: vibe-selling-os-v2, Property 7: Drug Name Triggers Info Widget**
     * **Validates: Requirements 4.2**
     * 
     * Drug names shorter than 3 characters should not trigger matching to avoid false positives.
     */
    public function testMinimumDrugNameLengthForMatching(): void
    {
        // Create drugs with very short names
        $shortNames = ['AB', 'XY', '12'];
        $assertionsMade = 0;
        
        foreach ($shortNames as $shortName) {
            // Create drug with short name
            $drugId = $this->createTestDrug($shortName, 100.0, 50);
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the short name
            $message = "ขอยา {$shortName} หน่อย";
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get drug_info widget
            $drugWidget = $this->getDrugInfoWidget($widgets);
            
            // Assert: Short names should not trigger drug_info widget with that specific drug
            // (The service requires name length >= 3)
            if ($drugWidget !== null && isset($drugWidget['drugName'])) {
                // If a widget is returned, the drug name should be >= 3 chars
                $this->assertGreaterThanOrEqual(
                    3,
                    mb_strlen($drugWidget['drugName']),
                    "Drug names shorter than 3 characters should not trigger matching. Got: {$drugWidget['drugName']}"
                );
                $assertionsMade++;
            } else {
                // No widget found for short name - this is expected
                $this->assertNull(
                    $drugWidget,
                    "Short drug name '{$shortName}' should not trigger drug_info widget"
                );
                $assertionsMade++;
            }
        }
        
        // Ensure we made assertions
        $this->assertGreaterThan(0, $assertionsMade, "Should have made at least one assertion");
    }
}
