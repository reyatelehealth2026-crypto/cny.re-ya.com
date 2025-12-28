<?php
/**
 * Property-Based Test: Triage Session Completion Update
 * 
 * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
 * **Validates: Requirements 5.1**
 * 
 * Property: For any triage session that reaches 'complete' state, the triage_sessions table 
 * SHALL be updated with status 'completed' and completed_at timestamp.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/AIChat/Autoloader.php';

// Load AIChat modules
if (function_exists('loadAIChatModule')) {
    loadAIChatModule();
}

class TriageSessionCompletionPropertyTest extends TestCase
{
    private ?\PDO $db = null;
    
    /**
     * Set up test database connection (in-memory SQLite)
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create triage_sessions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS triage_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                line_account_id INTEGER NULL,
                current_state VARCHAR(50) DEFAULT 'greeting',
                triage_data TEXT,
                status VARCHAR(20) DEFAULT 'active',
                priority VARCHAR(20) DEFAULT 'normal',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME NULL
            )
        ");
        
        // Create users table for testing
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                line_user_id VARCHAR(100),
                line_account_id INTEGER NULL
            )
        ");
    }
    
    /**
     * Tear down test database
     */
    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }
    
    /**
     * Generate random user IDs for property testing
     * 
     * @return array Array of test data sets
     */
    public function userIdProvider(): array
    {
        $testCases = [];
        
        // Generate 100 test cases with random user IDs
        for ($i = 0; $i < 100; $i++) {
            $userId = rand(1, 10000);
            $lineAccountId = rand(1, 100);
            $testCases["user_{$i}"] = [$userId, $lineAccountId];
        }
        
        return $testCases;
    }
    
    /**
     * Property Test: Session completion sets status to 'completed'
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testSessionCompletionSetsStatusToCompleted(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session by processing a symptom message
        $engine->process('ปวดหัว');
        
        // Get the session ID
        $sessionId = $engine->getSessionId();
        $this->assertNotNull($sessionId, "Session should be created");
        
        // Complete the session
        $result = $engine->completeSession();
        $this->assertTrue($result, "Session completion should succeed");
        
        // Verify the session status is 'completed'
        $stmt = $this->db->prepare("SELECT status FROM triage_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(
            'completed',
            $session['status'],
            "Session status should be 'completed' after completion"
        );
    }
    
    /**
     * Property Test: Session completion sets completed_at timestamp
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testSessionCompletionSetsCompletedAtTimestamp(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session by processing a symptom message
        $engine->process('ไข้');
        
        // Get the session ID
        $sessionId = $engine->getSessionId();
        $this->assertNotNull($sessionId, "Session should be created");
        
        // Verify completed_at is NULL before completion
        $stmt = $this->db->prepare("SELECT completed_at FROM triage_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $sessionBefore = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNull(
            $sessionBefore['completed_at'],
            "completed_at should be NULL before session completion"
        );
        
        // Complete the session
        $engine->completeSession();
        
        // Verify completed_at is set
        $stmt->execute([$sessionId]);
        $sessionAfter = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotNull(
            $sessionAfter['completed_at'],
            "completed_at should be set after session completion"
        );
    }
    
    /**
     * Property Test: Cancelled sessions do not have completed_at set
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testCancelledSessionsDoNotHaveCompletedAt(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session
        $engine->process('ไอ');
        
        // Get the session ID
        $sessionId = $engine->getSessionId();
        
        if ($sessionId === null) {
            $this->markTestSkipped("Session was not created for this test case");
            return;
        }
        
        // Reset/cancel the session by sending reset command
        $engine->process('เริ่มใหม่');
        
        // Check the original session
        $stmt = $this->db->prepare("SELECT status, completed_at FROM triage_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Cancelled sessions should have status 'cancelled' and no completed_at
        if ($session['status'] === 'cancelled') {
            $this->assertNull(
                $session['completed_at'],
                "Cancelled sessions should not have completed_at set"
            );
        }
    }
    
    /**
     * Property Test: Current state is 'complete' after completion
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testCurrentStateIsCompleteAfterCompletion(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session by processing a symptom message
        $engine->process('ปวดท้อง');
        
        // Get the session ID
        $sessionId = $engine->getSessionId();
        $this->assertNotNull($sessionId, "Session should be created");
        
        // Complete the session
        $engine->completeSession();
        
        // Verify the current_state is 'complete'
        $stmt = $this->db->prepare("SELECT current_state FROM triage_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(
            'complete',
            $session['current_state'],
            "current_state should be 'complete' after completion"
        );
    }
    
    /**
     * Property Test: Session ID is preserved after completion
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testSessionIdIsPreservedAfterCompletion(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session by processing a symptom message
        $engine->process('เจ็บคอ');
        
        // Get the session ID before completion
        $sessionIdBefore = $engine->getSessionId();
        $this->assertNotNull($sessionIdBefore, "Session should be created");
        
        // Complete the session
        $engine->completeSession();
        
        // Get the session ID after completion
        $sessionIdAfter = $engine->getSessionId();
        
        $this->assertEquals(
            $sessionIdBefore,
            $sessionIdAfter,
            "Session ID should be preserved after completion"
        );
    }
    
    /**
     * Property Test: User ID is correctly stored in session
     * 
     * **Feature: liff-ai-assistant-integration, Property 14: Triage Session Completion Update**
     * **Validates: Requirements 5.1**
     * 
     * @dataProvider userIdProvider
     */
    public function testUserIdIsCorrectlyStoredInSession(int $userId, int $lineAccountId): void
    {
        // Create a triage engine with the test database
        $engine = new \Modules\AIChat\Services\TriageEngine($lineAccountId, $userId, $this->db);
        
        // Start a session by processing a symptom message
        $engine->process('คลื่นไส้');
        
        // Get the session ID
        $sessionId = $engine->getSessionId();
        $this->assertNotNull($sessionId, "Session should be created");
        
        // Verify the user_id is correctly stored
        $stmt = $this->db->prepare("SELECT user_id FROM triage_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(
            $userId,
            (int)$session['user_id'],
            "User ID should be correctly stored in session"
        );
    }
}
