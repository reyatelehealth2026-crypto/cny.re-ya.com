<?php
/**
 * Property-Based Test: Symptom Keyword Triggers Drug Widget
 * 
 * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
 * **Validates: Requirements 4.1**
 * 
 * Property: For any message containing a registered symptom keyword, the system should 
 * return the drug recommendation widget with appropriate OTC medications.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class SymptomKeywordTriggersDrugWidgetPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
    // Sample symptom keywords (Thai and English) that should trigger drug widget
    private $symptomKeywords = [
        // Thai symptoms
        ['keyword' => 'ปวดหัว', 'category' => 'pain'],
        ['keyword' => 'ไข้', 'category' => 'fever'],
        ['keyword' => 'ไอ', 'category' => 'respiratory'],
        ['keyword' => 'ท้องเสีย', 'category' => 'digestive'],
        ['keyword' => 'ผื่น', 'category' => 'skin'],
        ['keyword' => 'คัน', 'category' => 'skin'],
        ['keyword' => 'ปวดท้อง', 'category' => 'digestive'],
        ['keyword' => 'เจ็บคอ', 'category' => 'respiratory'],
        ['keyword' => 'น้ำมูก', 'category' => 'respiratory'],
        ['keyword' => 'คลื่นไส้', 'category' => 'digestive'],
        // English symptoms
        ['keyword' => 'headache', 'category' => 'pain'],
        ['keyword' => 'fever', 'category' => 'fever'],
        ['keyword' => 'cough', 'category' => 'respiratory'],
        ['keyword' => 'diarrhea', 'category' => 'digestive'],
        ['keyword' => 'rash', 'category' => 'skin'],
        ['keyword' => 'sore throat', 'category' => 'respiratory'],
        ['keyword' => 'nausea', 'category' => 'digestive']
    ];
    
    // Sample message templates that include symptom keywords
    private $messageTemplates = [
        'มีอาการ {symptom} ค่ะ',
        '{symptom} มา 2 วันแล้ว',
        'รู้สึก {symptom}',
        'เป็น {symptom} ควรกินยาอะไร',
        '{symptom} ต้องทำยังไง',
        'ลูกมีอาการ {symptom}',
        'I have {symptom}',
        '{symptom} for 2 days',
        'suffering from {symptom}'
    ];
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Insert default symptom keywords
        $this->insertDefaultKeywords();
        
        // Include the service class
        require_once __DIR__ . '/../../classes/ConsultationAnalyzerService.php';
        
        // Create service instance
        $this->service = new \ConsultationAnalyzerService($this->db, $this->lineAccountId);
    }

    
    private function createTables(): void
    {
        // pharmacy_context_keywords table for symptom keywords
        $this->db->exec("
            CREATE TABLE pharmacy_context_keywords (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                keyword VARCHAR(100) NOT NULL,
                keyword_type VARCHAR(50) NOT NULL,
                widget_type VARCHAR(50) NOT NULL,
                related_data TEXT,
                priority INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1
            )
        ");
        
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
    
    private function insertDefaultKeywords(): void
    {
        // Insert symptom keywords that should trigger the symptom widget
        $keywords = [
            ['ปวดหัว', 'symptom', 'symptom', '{"category": "pain", "severity": "mild"}', 10],
            ['headache', 'symptom', 'symptom', '{"category": "pain", "severity": "mild"}', 10],
            ['ไข้', 'symptom', 'symptom', '{"category": "fever", "severity": "moderate"}', 15],
            ['fever', 'symptom', 'symptom', '{"category": "fever", "severity": "moderate"}', 15],
            ['ไอ', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['cough', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['ท้องเสีย', 'symptom', 'symptom', '{"category": "digestive", "severity": "moderate"}', 12],
            ['diarrhea', 'symptom', 'symptom', '{"category": "digestive", "severity": "moderate"}', 12],
            ['ผื่น', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10],
            ['rash', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10],
            ['คัน', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10],
            ['itch', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10],
            ['ปวดท้อง', 'symptom', 'symptom', '{"category": "digestive", "severity": "mild"}', 10],
            ['stomach pain', 'symptom', 'symptom', '{"category": "digestive", "severity": "mild"}', 10],
            ['เจ็บคอ', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['sore throat', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['น้ำมูก', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['runny nose', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10],
            ['คลื่นไส้', 'symptom', 'symptom', '{"category": "digestive", "severity": "mild"}', 10],
            ['nausea', 'symptom', 'symptom', '{"category": "digestive", "severity": "mild"}', 10]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO pharmacy_context_keywords (keyword, keyword_type, widget_type, related_data, priority, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        foreach ($keywords as $kw) {
            $stmt->execute($kw);
        }
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
     * Generate a random symptom keyword from the list
     */
    private function generateRandomSymptomKeyword(): array
    {
        return $this->symptomKeywords[array_rand($this->symptomKeywords)];
    }
    
    /**
     * Generate a message containing a symptom keyword
     */
    private function generateMessageWithSymptom(string $symptom): string
    {
        $template = $this->messageTemplates[array_rand($this->messageTemplates)];
        return str_replace('{symptom}', $symptom, $template);
    }
    
    /**
     * Check if widgets contain a symptom widget
     */
    private function containsSymptomWidget(array $widgets): bool
    {
        foreach ($widgets as $widget) {
            if (isset($widget['type']) && $widget['type'] === 'symptom') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get symptom widget from widgets array
     */
    private function getSymptomWidget(array $widgets): ?array
    {
        foreach ($widgets as $widget) {
            if (isset($widget['type']) && $widget['type'] === 'symptom') {
                return $widget;
            }
        }
        return null;
    }


    /**
     * Property Test: Symptom Keyword in Message Triggers Drug Recommendation Widget
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any message containing a registered symptom keyword, the system should 
     * return the drug recommendation widget (symptom widget type).
     */
    public function testSymptomKeywordTriggersDrugWidget(): void
    {
        // Run 100 iterations with random symptom keywords
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should contain symptom widget (drug recommendation widget)
            $this->assertTrue(
                $this->containsSymptomWidget($widgets),
                "Message containing symptom keyword '{$symptom}' should trigger symptom widget on iteration {$i}. " .
                "Message: '{$message}', Widgets: " . json_encode(array_column($widgets, 'type'))
            );
        }
    }

    /**
     * Property Test: Symptom Widget Contains Drug Recommendations
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget returned, it should contain drug recommendations
     * appropriate for the symptom category.
     */
    public function testSymptomWidgetContainsDrugRecommendations(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found (edge case)
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Widget should have recommendations field
            $this->assertArrayHasKey(
                'recommendations',
                $symptomWidget,
                "Symptom widget should have 'recommendations' field on iteration {$i}"
            );
            
            // Assert: Recommendations should be a non-empty array
            $this->assertIsArray(
                $symptomWidget['recommendations'],
                "Recommendations should be an array on iteration {$i}"
            );
            
            $this->assertNotEmpty(
                $symptomWidget['recommendations'],
                "Recommendations should not be empty for symptom '{$symptom}' on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Symptom Widget Contains Required Fields
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget returned, it should contain all required fields:
     * type, title, keyword, category, recommendations, and actions.
     */
    public function testSymptomWidgetContainsRequiredFields(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Widget should have all required fields
            $requiredFields = ['type', 'title', 'keyword', 'category', 'recommendations', 'actions'];
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $symptomWidget,
                    "Symptom widget should have '{$field}' field on iteration {$i}"
                );
            }
            
            // Assert: Type should be 'symptom'
            $this->assertEquals(
                'symptom',
                $symptomWidget['type'],
                "Widget type should be 'symptom' on iteration {$i}"
            );
            
            // Assert: Actions should be an array
            $this->assertIsArray(
                $symptomWidget['actions'],
                "Actions should be an array on iteration {$i}"
            );
        }
    }


    /**
     * Property Test: Symptom Category Matches Keyword Category
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget, the category should match the expected category
     * for the detected symptom keyword.
     */
    public function testSymptomCategoryMatchesKeywordCategory(): void
    {
        // Test specific symptom-category mappings
        $symptomCategoryMap = [
            'ปวดหัว' => 'pain',
            'headache' => 'pain',
            'ไข้' => 'fever',
            'fever' => 'fever',
            'ไอ' => 'respiratory',
            'cough' => 'respiratory',
            'ท้องเสีย' => 'digestive',
            'diarrhea' => 'digestive',
            'ผื่น' => 'skin',
            'rash' => 'skin'
        ];
        
        foreach ($symptomCategoryMap as $symptom => $expectedCategory) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Category should match expected
            $this->assertEquals(
                $expectedCategory,
                $symptomWidget['category'],
                "Symptom '{$symptom}' should have category '{$expectedCategory}', got '{$symptomWidget['category']}'"
            );
        }
    }

    /**
     * Property Test: No Symptom Widget for Non-Symptom Messages
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any message NOT containing a registered symptom keyword, the system should 
     * NOT return a symptom widget.
     */
    public function testNoSymptomWidgetForNonSymptomMessages(): void
    {
        // Messages that don't contain symptom keywords
        $nonSymptomMessages = [
            'สวัสดีค่ะ',
            'ขอบคุณค่ะ',
            'ราคาเท่าไหร่',
            'ส่งได้ไหม',
            'มีโปรโมชั่นไหม',
            'เปิดกี่โมง',
            'อยู่ที่ไหน',
            'ติดต่อยังไง',
            'hello',
            'thank you',
            'how much',
            'delivery available'
        ];
        
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Pick a random non-symptom message
            $message = $nonSymptomMessages[array_rand($nonSymptomMessages)];
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should NOT contain symptom widget
            $this->assertFalse(
                $this->containsSymptomWidget($widgets),
                "Non-symptom message should not trigger symptom widget on iteration {$i}. " .
                "Message: '{$message}', Widgets: " . json_encode(array_column($widgets, 'type'))
            );
        }
    }

    /**
     * Property Test: Symptom Widget Has View Recommendations Action
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget, it should include an action to view drug recommendations.
     */
    public function testSymptomWidgetHasViewRecommendationsAction(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Actions should contain view_recommendations
            $actions = $symptomWidget['actions'] ?? [];
            $hasViewRecommendations = false;
            
            foreach ($actions as $action) {
                if (isset($action['action']) && $action['action'] === 'view_recommendations') {
                    $hasViewRecommendations = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasViewRecommendations,
                "Symptom widget should have 'view_recommendations' action on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Symptom Widget Has Interaction Check Action
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget, it should include an action to check drug interactions.
     */
    public function testSymptomWidgetHasInteractionCheckAction(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Actions should contain check_interactions
            $actions = $symptomWidget['actions'] ?? [];
            $hasInteractionCheck = false;
            
            foreach ($actions as $action) {
                if (isset($action['action']) && $action['action'] === 'check_interactions') {
                    $hasInteractionCheck = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasInteractionCheck,
                "Symptom widget should have 'check_interactions' action on iteration {$i}"
            );
        }
    }


    /**
     * Property Test: Case Insensitive Symptom Keyword Matching
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom keyword, the matching should be case-insensitive.
     */
    public function testCaseInsensitiveSymptomKeywordMatching(): void
    {
        // Test with English symptom keywords that can have case variations
        $englishSymptoms = ['headache', 'fever', 'cough', 'diarrhea', 'rash', 'nausea'];
        
        for ($i = 0; $i < 50; $i++) {
            // Pick a random English symptom
            $symptom = $englishSymptoms[array_rand($englishSymptoms)];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message with different case
            $caseVariations = [
                strtolower($symptom),
                strtoupper($symptom),
                ucfirst(strtolower($symptom))
            ];
            $messageCase = $caseVariations[array_rand($caseVariations)];
            $message = "I have {$messageCase}";
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should still find the symptom (case-insensitive)
            $this->assertTrue(
                $this->containsSymptomWidget($widgets),
                "Case-insensitive matching should find symptom '{$symptom}' when searching for '{$messageCase}' on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Multiple Symptoms in Message Returns Widget
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any message containing multiple symptom keywords, the system should 
     * return at least one symptom widget.
     */
    public function testMultipleSymptomsInMessageReturnsWidget(): void
    {
        // Combinations of symptoms
        $symptomCombinations = [
            ['ปวดหัว', 'ไข้'],
            ['ไอ', 'น้ำมูก'],
            ['ท้องเสีย', 'คลื่นไส้'],
            ['headache', 'fever'],
            ['cough', 'sore throat']
        ];
        
        foreach ($symptomCombinations as $symptoms) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message with multiple symptoms
            $message = 'มีอาการ ' . implode(' และ ', $symptoms);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Assert: Should contain at least one symptom widget
            $this->assertTrue(
                $this->containsSymptomWidget($widgets),
                "Message with multiple symptoms should trigger symptom widget. " .
                "Message: '{$message}', Widgets: " . json_encode(array_column($widgets, 'type'))
            );
        }
    }

    /**
     * Property Test: Symptom Widget Keyword Matches Input
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget returned, the keyword field should match
     * one of the symptom keywords in the original message.
     */
    public function testSymptomWidgetKeywordMatchesInput(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Widget keyword should be present in the original message
            $widgetKeyword = $symptomWidget['keyword'] ?? '';
            $this->assertStringContainsStringIgnoringCase(
                $widgetKeyword,
                $message,
                "Widget keyword '{$widgetKeyword}' should be found in message '{$message}' on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Symptom Widget Title is Non-Empty
     * 
     * **Feature: vibe-selling-os-v2, Property 6: Symptom Keyword Triggers Drug Widget**
     * **Validates: Requirements 4.1**
     * 
     * For any symptom widget returned, the title should be non-empty.
     */
    public function testSymptomWidgetTitleIsNonEmpty(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random symptom keyword
            $symptomData = $this->generateRandomSymptomKeyword();
            $symptom = $symptomData['keyword'];
            
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message containing the symptom keyword
            $message = $this->generateMessageWithSymptom($symptom);
            
            // Act: Get context widgets
            $widgets = $this->service->getContextWidgets($message, $userId);
            
            // Get symptom widget
            $symptomWidget = $this->getSymptomWidget($widgets);
            
            // Skip if no widget found
            if ($symptomWidget === null) {
                continue;
            }
            
            // Assert: Title should be non-empty
            $this->assertNotEmpty(
                $symptomWidget['title'],
                "Symptom widget title should not be empty on iteration {$i}"
            );
        }
    }
}
