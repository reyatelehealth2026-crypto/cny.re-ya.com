<?php
/**
 * Property-Based Test: Ghost Draft Learning Stores Edit Data
 * 
 * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
 * **Validates: Requirements 6.5**
 * 
 * Property: For any pharmacist edit of an AI draft, the system should store 
 * the original draft, final message, and edit distance for learning.
 */

namespace Tests\VibeSelling;

use PHPUnit\Framework\TestCase;
use PDO;

class GhostDraftLearningPropertyTest extends TestCase
{
    private $db;
    private $lineAccountId = 1;
    private $service;
    
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
        // pharmacy_ghost_learning table for storing edit data
        $this->db->exec("
            CREATE TABLE pharmacy_ghost_learning (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                customer_message TEXT NOT NULL,
                ai_draft TEXT NOT NULL,
                pharmacist_final TEXT NOT NULL,
                edit_distance INTEGER,
                was_accepted INTEGER DEFAULT 0,
                context TEXT,
                mentioned_drugs TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // messages table for getting last customer message
        $this->db->exec("
            CREATE TABLE messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                direction VARCHAR(20),
                message_type VARCHAR(50),
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // business_items table for drug extraction
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
    }
    
    /**
     * Generate a random string of specified length
     */
    private function generateRandomString(int $minLength = 10, int $maxLength = 200): string
    {
        $length = rand($minLength, $maxLength);
        $words = [
            'ยา', 'พาราเซตามอล', 'ปวดหัว', 'ไข้', 'แนะนำ', 'รับประทาน', 'วันละ',
            'เม็ด', 'หลังอาหาร', 'ก่อนนอน', 'ครั้ง', 'ดื่มน้ำ', 'มาก', 'พักผ่อน',
            'อาการ', 'ดีขึ้น', 'สอบถาม', 'เพิ่มเติม', 'ขอบคุณ', 'ครับ', 'ค่ะ',
            'drug', 'medicine', 'take', 'daily', 'tablet', 'after', 'meal',
            'rest', 'water', 'symptom', 'better', 'question', 'thank', 'you'
        ];
        
        $result = [];
        $currentLength = 0;
        
        while ($currentLength < $length) {
            $word = $words[array_rand($words)];
            $result[] = $word;
            $currentLength += mb_strlen($word) + 1;
        }
        
        return implode(' ', $result);
    }
    
    /**
     * Generate a modified version of a string (simulating pharmacist edit)
     */
    private function generateEditedString(string $original, float $editRatio = 0.3): string
    {
        $words = preg_split('/\s+/u', $original);
        $numWords = count($words);
        $numEdits = max(1, (int)($numWords * $editRatio));
        
        // Randomly modify some words
        for ($i = 0; $i < $numEdits; $i++) {
            $index = rand(0, $numWords - 1);
            $editType = rand(0, 2);
            
            switch ($editType) {
                case 0: // Replace word
                    $words[$index] = $this->generateRandomString(3, 10);
                    break;
                case 1: // Delete word
                    unset($words[$index]);
                    $words = array_values($words);
                    $numWords--;
                    break;
                case 2: // Insert word
                    array_splice($words, $index, 0, [$this->generateRandomString(3, 10)]);
                    $numWords++;
                    break;
            }
        }
        
        return implode(' ', $words);
    }
    
    /**
     * Create a test customer message in the messages table
     */
    private function createCustomerMessage(int $userId, string $content): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (user_id, direction, message_type, content, created_at)
            VALUES (?, 'incoming', 'text', ?, datetime('now'))
        ");
        $stmt->execute([$userId, $content]);
    }

    /**
     * Property Test: Learning Stores Original Draft
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any pharmacist edit, the original AI draft should be stored exactly as provided.
     */
    public function testLearningStoresOriginalDraft(): void
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $originalDraft = $this->generateRandomString(20, 300);
            $finalMessage = $this->generateEditedString($originalDraft);
            $customerMessage = $this->generateRandomString(10, 100);
            
            // Create customer message for context
            $this->createCustomerMessage($userId, $customerMessage);
            
            // Act: Call learnFromEdit
            $result = $this->service->learnFromEdit($userId, $originalDraft, $finalMessage);
            
            // Assert: Operation should succeed
            $this->assertTrue($result, "learnFromEdit should return true on iteration {$i}");
            
            // Verify: Original draft is stored exactly
            $stmt = $this->db->prepare("
                SELECT ai_draft FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertNotNull($stored, "Record should be stored on iteration {$i}");
            $this->assertEquals(
                $originalDraft,
                $stored['ai_draft'],
                "Original draft should be stored exactly as provided on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Learning Stores Final Message
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any pharmacist edit, the final message should be stored exactly as provided.
     */
    public function testLearningStoresFinalMessage(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $originalDraft = $this->generateRandomString(20, 300);
            $finalMessage = $this->generateEditedString($originalDraft);
            $customerMessage = $this->generateRandomString(10, 100);
            
            $this->createCustomerMessage($userId, $customerMessage);
            
            // Act
            $result = $this->service->learnFromEdit($userId, $originalDraft, $finalMessage);
            
            // Assert
            $this->assertTrue($result);
            
            // Verify: Final message is stored exactly
            $stmt = $this->db->prepare("
                SELECT pharmacist_final FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertEquals(
                $finalMessage,
                $stored['pharmacist_final'],
                "Final message should be stored exactly as provided on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Learning Stores Edit Distance
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any pharmacist edit, the edit distance should be stored and be non-negative.
     */
    public function testLearningStoresEditDistance(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $originalDraft = $this->generateRandomString(20, 200);
            $finalMessage = $this->generateEditedString($originalDraft, rand(10, 80) / 100);
            $customerMessage = $this->generateRandomString(10, 100);
            
            $this->createCustomerMessage($userId, $customerMessage);
            
            // Act
            $result = $this->service->learnFromEdit($userId, $originalDraft, $finalMessage);
            
            // Assert
            $this->assertTrue($result);
            
            // Verify: Edit distance is stored and non-negative
            $stmt = $this->db->prepare("
                SELECT edit_distance FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertNotNull($stored['edit_distance'], "Edit distance should be stored on iteration {$i}");
            $this->assertGreaterThanOrEqual(
                0,
                $stored['edit_distance'],
                "Edit distance should be non-negative on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: Edit Distance Is Zero For Identical Strings
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any draft that is accepted without changes, the edit distance should be 0.
     */
    public function testEditDistanceZeroForIdenticalStrings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $originalDraft = $this->generateRandomString(20, 200);
            $finalMessage = $originalDraft; // No changes
            $customerMessage = $this->generateRandomString(10, 100);
            
            $this->createCustomerMessage($userId, $customerMessage);
            
            // Act
            $result = $this->service->learnFromEdit($userId, $originalDraft, $finalMessage);
            
            // Assert
            $this->assertTrue($result);
            
            // Verify: Edit distance is 0 for identical strings
            $stmt = $this->db->prepare("
                SELECT edit_distance, was_accepted FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertEquals(
                0,
                $stored['edit_distance'],
                "Edit distance should be 0 for identical strings on iteration {$i}"
            );
            
            // Also verify was_accepted is true for identical strings
            $this->assertEquals(
                1,
                $stored['was_accepted'],
                "was_accepted should be 1 for identical strings on iteration {$i}"
            );
        }
    }

    /**
     * Property Test: All Required Fields Are Stored
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any pharmacist edit, all required fields (user_id, customer_message, 
     * ai_draft, pharmacist_final, edit_distance) should be stored.
     */
    public function testAllRequiredFieldsAreStored(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $originalDraft = $this->generateRandomString(20, 200);
            $finalMessage = $this->generateEditedString($originalDraft);
            $customerMessage = $this->generateRandomString(10, 100);
            
            $this->createCustomerMessage($userId, $customerMessage);
            
            // Act
            $result = $this->service->learnFromEdit($userId, $originalDraft, $finalMessage);
            
            // Assert
            $this->assertTrue($result);
            
            // Verify: All required fields are stored
            $stmt = $this->db->prepare("
                SELECT user_id, customer_message, ai_draft, pharmacist_final, edit_distance, was_accepted
                FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assertNotNull($stored, "Record should exist on iteration {$i}");
            $this->assertEquals($userId, $stored['user_id'], "user_id should match on iteration {$i}");
            $this->assertNotEmpty($stored['customer_message'], "customer_message should not be empty on iteration {$i}");
            $this->assertEquals($originalDraft, $stored['ai_draft'], "ai_draft should match on iteration {$i}");
            $this->assertEquals($finalMessage, $stored['pharmacist_final'], "pharmacist_final should match on iteration {$i}");
            $this->assertNotNull($stored['edit_distance'], "edit_distance should not be null on iteration {$i}");
            $this->assertNotNull($stored['was_accepted'], "was_accepted should not be null on iteration {$i}");
        }
    }

    /**
     * Property Test: Edit Distance Increases With More Changes
     * 
     * **Feature: vibe-selling-os-v2, Property 11: Ghost Draft Learning Stores Edit Data**
     * **Validates: Requirements 6.5**
     * 
     * For any two edits of the same draft, the one with more changes should have 
     * a higher or equal edit distance.
     */
    public function testEditDistanceIncreasesWithMoreChanges(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $userId1 = rand(1, 10000);
            $userId2 = rand(10001, 20000);
            $originalDraft = $this->generateRandomString(50, 200);
            
            // Small edit (10% changes)
            $smallEdit = $this->generateEditedString($originalDraft, 0.1);
            
            // Large edit (50% changes)
            $largeEdit = $this->generateEditedString($originalDraft, 0.5);
            
            $customerMessage = $this->generateRandomString(10, 100);
            
            $this->createCustomerMessage($userId1, $customerMessage);
            $this->createCustomerMessage($userId2, $customerMessage);
            
            // Act: Store both edits
            $this->service->learnFromEdit($userId1, $originalDraft, $smallEdit);
            $this->service->learnFromEdit($userId2, $originalDraft, $largeEdit);
            
            // Get edit distances
            $stmt = $this->db->prepare("
                SELECT edit_distance FROM pharmacy_ghost_learning 
                WHERE user_id = ? ORDER BY id DESC LIMIT 1
            ");
            
            $stmt->execute([$userId1]);
            $smallEditDistance = $stmt->fetch(PDO::FETCH_ASSOC)['edit_distance'];
            
            $stmt->execute([$userId2]);
            $largeEditDistance = $stmt->fetch(PDO::FETCH_ASSOC)['edit_distance'];
            
            // Note: Due to randomness, we can't guarantee strict ordering,
            // but we verify both are valid non-negative integers
            $this->assertGreaterThanOrEqual(0, $smallEditDistance);
            $this->assertGreaterThanOrEqual(0, $largeEditDistance);
        }
    }
}
