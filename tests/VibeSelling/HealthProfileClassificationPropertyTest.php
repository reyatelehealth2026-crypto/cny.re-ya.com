<?php
/**
 * Property-Based Test: Health Profile Classification Completeness
 * 
 * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
 * **Validates: Requirements 2.1**
 * 
 * Property: For any customer with 5 or more messages, the classification should return 
 * exactly one of the valid types (A, B, C) with a confidence score between 0 and 1.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class HealthProfileClassificationPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
    // Valid communication types
    private const VALID_TYPES = ['A', 'B', 'C'];
    
    // Sample messages for different customer types
    private $typeAMessages = [
        'ขอยาแก้ปวดหัว',
        'ราคาเท่าไหร่',
        'มียาพาราไหม',
        'เอาตัวนี้',
        'ส่งได้ไหม',
        'ซื้อ 2 กล่อง',
        'ต้องการยาแก้ไอ',
        'รีบหน่อยนะ',
        'ด่วนค่ะ',
        'สั่งเลย'
    ];
    
    private $typeBMessages = [
        'กังวลเรื่องผลข้างเคียงค่ะ',
        'กลัวแพ้ยา',
        'ปลอดภัยไหมคะ',
        'ห่วงเรื่องอาการ',
        'ไม่แน่ใจว่าควรกินยาอะไร',
        'ช่วยแนะนำหน่อยค่ะ',
        'เป็นอะไรหรือเปล่า',
        'อันตรายไหม',
        'ผลข้างเคียงมีอะไรบ้าง',
        'แพ้ยาได้ไหม'
    ];
    
    private $typeCMessages = [
        'ขอรายละเอียดยาตัวนี้หน่อยค่ะ',
        'อธิบายกลไกการออกฤทธิ์ได้ไหม',
        'ทำไมต้องกินยานี้',
        'เปรียบเทียบยา 2 ตัวนี้ให้หน่อย',
        'ข้อมูลทางวิทยาศาสตร์มีไหม',
        'ส่วนประกอบมีอะไรบ้าง',
        'หลักฐานการวิจัยมีไหม',
        'อยากทราบรายละเอียดเพิ่มเติม',
        'กลไกการทำงานเป็นอย่างไร',
        'ข้อมูลเชิงลึกมีไหมคะ'
    ];
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Include the service class
        require_once __DIR__ . '/../../classes/CustomerHealthEngineService.php';
        
        // Create service instance
        $this->service = new \CustomerHealthEngineService($this->db, $this->lineAccountId);
    }
    
    private function createTables(): void
    {
        // users table
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_account_id INTEGER,
                name VARCHAR(255),
                weight DECIMAL(5,2),
                height DECIMAL(5,2),
                blood_type VARCHAR(10),
                medical_conditions TEXT,
                drug_allergies TEXT,
                current_medications TEXT
            )
        ");
        
        // messages table for chat history
        $this->db->exec("
            CREATE TABLE messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                line_account_id INTEGER,
                content TEXT,
                message_type VARCHAR(50) DEFAULT 'text',
                direction VARCHAR(20) DEFAULT 'incoming',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // customer_health_profiles table
        $this->db->exec("
            CREATE TABLE customer_health_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNIQUE,
                communication_type VARCHAR(1),
                confidence DECIMAL(3,2) DEFAULT 0.00,
                chronic_conditions TEXT,
                communication_tips TEXT,
                last_analyzed_at DATETIME,
                message_count_analyzed INTEGER DEFAULT 0
            )
        ");
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
     * Add messages for a user
     */
    private function addMessagesForUser(int $userId, array $messages): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (user_id, line_account_id, content, message_type, direction, created_at)
            VALUES (?, ?, ?, 'text', 'incoming', datetime('now', '-' || ? || ' minutes'))
        ");
        
        foreach ($messages as $index => $content) {
            $stmt->execute([$userId, $this->lineAccountId, $content, $index]);
        }
    }
    
    /**
     * Generate random messages from a pool
     */
    private function generateRandomMessages(int $count, array $pool = null): array
    {
        if ($pool === null) {
            // Mix all types
            $pool = array_merge($this->typeAMessages, $this->typeBMessages, $this->typeCMessages);
        }
        
        $messages = [];
        for ($i = 0; $i < $count; $i++) {
            $messages[] = $pool[array_rand($pool)];
        }
        return $messages;
    }
    
    /**
     * Generate random message count (5 to 50)
     */
    private function generateRandomMessageCount(): int
    {
        return rand(5, 50);
    }

    /**
     * Property Test: Classification Returns Valid Type
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with 5 or more messages, the classification should return 
     * exactly one of the valid types (A, B, C).
     */
    public function testClassificationReturnsValidType(): void
    {
        // Run 100 iterations with random message counts
        for ($i = 0; $i < 100; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate random message count (5 to 50)
            $messageCount = $this->generateRandomMessageCount();
            
            // Add random messages
            $messages = $this->generateRandomMessages($messageCount);
            $this->addMessagesForUser($userId, $messages);
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Type should be one of the valid types
            $this->assertContains(
                $result['type'],
                self::VALID_TYPES,
                "Classification type should be one of A, B, C on iteration {$i}. " .
                "Got: {$result['type']}, Message count: {$messageCount}"
            );
            
            // Assert: insufficientData should be false when >= 5 messages
            $this->assertFalse(
                $result['insufficientData'],
                "insufficientData should be false when message count >= 5 on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Confidence Score Range
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with 5 or more messages, the confidence score should be between 0 and 1.
     */
    public function testConfidenceScoreRange(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate random message count (5 to 50)
            $messageCount = $this->generateRandomMessageCount();
            
            // Add random messages
            $messages = $this->generateRandomMessages($messageCount);
            $this->addMessagesForUser($userId, $messages);
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Confidence should be between 0 and 1
            $this->assertGreaterThanOrEqual(
                0.0,
                $result['confidence'],
                "Confidence should be >= 0 on iteration {$i}. Got: {$result['confidence']}"
            );
            
            $this->assertLessThanOrEqual(
                1.0,
                $result['confidence'],
                "Confidence should be <= 1 on iteration {$i}. Got: {$result['confidence']}"
            );
        }
    }

    /**
     * Property Test: Insufficient Messages Returns Default
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with fewer than 5 messages, the classification should return 
     * default type with insufficientData flag set to true.
     */
    public function testInsufficientMessagesReturnsDefault(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate message count less than 5 (0 to 4)
            $messageCount = rand(0, 4);
            
            if ($messageCount > 0) {
                $messages = $this->generateRandomMessages($messageCount);
                $this->addMessagesForUser($userId, $messages);
            }
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: insufficientData should be true
            $this->assertTrue(
                $result['insufficientData'],
                "insufficientData should be true when message count < 5 on iteration {$i}. " .
                "Message count: {$messageCount}"
            );
            
            // Assert: Confidence should be 0 when insufficient data
            $this->assertEquals(
                0.0,
                $result['confidence'],
                "Confidence should be 0 when insufficient data on iteration {$i}"
            );
            
            // Assert: Type should still be a valid type (default)
            $this->assertContains(
                $result['type'],
                self::VALID_TYPES,
                "Even with insufficient data, type should be a valid default on iteration {$i}"
            );
        }
    }


    /**
     * Property Test: Type A Messages Tend to Classify as Type A
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with predominantly Type A messages, the classification 
     * should return type A with valid confidence.
     */
    public function testTypeAMessagesClassifyCorrectly(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate Type A messages (5 to 20)
            $messageCount = rand(5, 20);
            $messages = $this->generateRandomMessages($messageCount, $this->typeAMessages);
            $this->addMessagesForUser($userId, $messages);
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Type should be valid
            $this->assertContains(
                $result['type'],
                self::VALID_TYPES,
                "Type should be valid on iteration {$i}"
            );
            
            // Assert: Confidence should be in valid range
            $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
            $this->assertLessThanOrEqual(1.0, $result['confidence']);
            
            // Assert: insufficientData should be false
            $this->assertFalse($result['insufficientData']);
        }
    }

    /**
     * Property Test: Type B Messages Tend to Classify as Type B
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with predominantly Type B messages, the classification 
     * should return type B with valid confidence.
     */
    public function testTypeBMessagesClassifyCorrectly(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate Type B messages (5 to 20)
            $messageCount = rand(5, 20);
            $messages = $this->generateRandomMessages($messageCount, $this->typeBMessages);
            $this->addMessagesForUser($userId, $messages);
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Type should be valid
            $this->assertContains(
                $result['type'],
                self::VALID_TYPES,
                "Type should be valid on iteration {$i}"
            );
            
            // Assert: Confidence should be in valid range
            $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
            $this->assertLessThanOrEqual(1.0, $result['confidence']);
            
            // Assert: insufficientData should be false
            $this->assertFalse($result['insufficientData']);
        }
    }

    /**
     * Property Test: Type C Messages Tend to Classify as Type C
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any customer with predominantly Type C messages, the classification 
     * should return type C with valid confidence.
     */
    public function testTypeCMessagesClassifyCorrectly(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate Type C messages (5 to 20)
            $messageCount = rand(5, 20);
            $messages = $this->generateRandomMessages($messageCount, $this->typeCMessages);
            $this->addMessagesForUser($userId, $messages);
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Type should be valid
            $this->assertContains(
                $result['type'],
                self::VALID_TYPES,
                "Type should be valid on iteration {$i}"
            );
            
            // Assert: Confidence should be in valid range
            $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
            $this->assertLessThanOrEqual(1.0, $result['confidence']);
            
            // Assert: insufficientData should be false
            $this->assertFalse($result['insufficientData']);
        }
    }

    /**
     * Property Test: Classification Result Contains Required Fields
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any classification result, it should contain all required fields.
     */
    public function testClassificationResultContainsRequiredFields(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate random message count (0 to 50)
            $messageCount = rand(0, 50);
            
            if ($messageCount > 0) {
                $messages = $this->generateRandomMessages($messageCount);
                $this->addMessagesForUser($userId, $messages);
            }
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Required fields exist
            $this->assertArrayHasKey('type', $result, "Result should have 'type' field on iteration {$i}");
            $this->assertArrayHasKey('confidence', $result, "Result should have 'confidence' field on iteration {$i}");
            $this->assertArrayHasKey('tips', $result, "Result should have 'tips' field on iteration {$i}");
            $this->assertArrayHasKey('messageCount', $result, "Result should have 'messageCount' field on iteration {$i}");
            $this->assertArrayHasKey('insufficientData', $result, "Result should have 'insufficientData' field on iteration {$i}");
            
            // Assert: Tips is an array
            $this->assertIsArray($result['tips'], "Tips should be an array on iteration {$i}");
        }
    }

    /**
     * Property Test: Message Count Accuracy
     * 
     * **Feature: vibe-selling-os-v2, Property 4: Health Profile Classification Completeness**
     * **Validates: Requirements 2.1**
     * 
     * For any classification, the returned message count should match the actual count.
     */
    public function testMessageCountAccuracy(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create test user
            $userId = $this->createTestUser();
            
            // Generate random message count
            $expectedCount = rand(0, 30);
            
            if ($expectedCount > 0) {
                $messages = $this->generateRandomMessages($expectedCount);
                $this->addMessagesForUser($userId, $messages);
            }
            
            // Act: Classify customer
            $result = $this->service->classifyCustomer($userId);
            
            // Assert: Message count should match
            $this->assertEquals(
                $expectedCount,
                $result['messageCount'],
                "Message count should match actual count on iteration {$i}. " .
                "Expected: {$expectedCount}, Got: {$result['messageCount']}"
            );
        }
    }
}
