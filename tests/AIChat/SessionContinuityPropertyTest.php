<?php
/**
 * Property-Based Test: Session Continuity - No Greeting During Active Session
 * 
 * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
 * **Validates: Requirements 9.1**
 * 
 * Property: For any triage session with state not equal to 'complete' or 'escalate', 
 * the AI response SHALL NOT contain greeting phrases like "สวัสดี" or "ยินดีให้บริการ".
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/AIChat/Autoloader.php';

// Load AIChat modules
if (function_exists('loadAIChatModule')) {
    loadAIChatModule();
}

class SessionContinuityPropertyTest extends TestCase
{
    /**
     * Greeting phrases that should NOT appear during active sessions
     */
    private const GREETING_PHRASES = [
        'สวัสดีค่ะ',
        'สวัสดีครับ',
        'ยินดีให้บริการ',
        'ยินดีต้อนรับ',
        'มีอะไรให้ช่วยคะ',
        'มีอะไรให้ช่วยครับ',
    ];
    
    /**
     * Active triage states (not complete or escalate)
     */
    private const ACTIVE_STATES = [
        'symptom',
        'duration', 
        'severity',
        'associated',
        'allergy',
        'medical_history',
        'current_meds',
        'recommend',
    ];
    
    /**
     * Generate random active states for property testing
     * 
     * @return array Array of test data sets
     */
    public function activeStateProvider(): array
    {
        $testCases = [];
        
        // Generate 100 test cases with random active states
        for ($i = 0; $i < 100; $i++) {
            $state = self::ACTIVE_STATES[array_rand(self::ACTIVE_STATES)];
            $testCases["active_state_{$i}"] = [$state];
        }
        
        return $testCases;
    }
    
    /**
     * Generate random user messages for property testing
     * 
     * @return array Array of test data sets
     */
    public function userMessageProvider(): array
    {
        $testCases = [];
        
        // Common user responses during triage
        $messages = [
            // Numeric responses (severity)
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
            // Duration responses
            '1 วัน', '2 วัน', '3 วัน', '1 สัปดาห์', '2 สัปดาห์', '1 เดือน',
            // Yes/No responses
            'ใช่', 'ไม่ใช่', 'ไม่', 'ไม่มี', 'มี', 'ไม่แพ้',
            // Symptom descriptions
            'ปวดหัว', 'ไข้', 'ไอ', 'เจ็บคอ', 'ปวดท้อง',
            // Associated symptoms
            'คลื่นไส้', 'อาเจียน', 'เวียนหัว', 'อ่อนเพลีย',
        ];
        
        // Generate 100 test cases
        for ($i = 0; $i < 100; $i++) {
            $state = self::ACTIVE_STATES[array_rand(self::ACTIVE_STATES)];
            $message = $messages[array_rand($messages)];
            $testCases["message_{$i}"] = [$state, $message];
        }
        
        return $testCases;
    }
    
    /**
     * Create a properly mocked PDO that handles prepare() calls
     */
    private function createMockedPdo(): \PDO
    {
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn(false);
        $mockStmt->method('fetchAll')->willReturn([]);
        $mockStmt->method('fetchColumn')->willReturn(0);
        
        $mockDb = $this->createMock(\PDO::class);
        $mockDb->method('prepare')->willReturn($mockStmt);
        $mockDb->method('query')->willReturn($mockStmt);
        
        return $mockDb;
    }
    
    /**
     * Property Test: isSessionActive returns true for active states
     * 
     * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
     * **Validates: Requirements 9.1**
     * 
     * @dataProvider activeStateProvider
     */
    public function testIsSessionActiveReturnsTrueForActiveStates(string $state): void
    {
        // Create adapter with properly mocked database
        $mockDb = $this->createMockedPdo();
        $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($mockDb, null);
        
        // Set the triage state
        $adapter->setTriageState($state);
        
        // Verify session is considered active
        $this->assertTrue(
            $adapter->isSessionActive(),
            "State '{$state}' should be considered an active session"
        );
    }
    
    /**
     * Property Test: isSessionActive returns false for terminal states
     * 
     * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
     * **Validates: Requirements 9.1**
     */
    public function testIsSessionActiveReturnsFalseForTerminalStates(): void
    {
        $terminalStates = ['greeting', 'complete', 'escalate', null];
        
        foreach ($terminalStates as $state) {
            $mockDb = $this->createMockedPdo();
            $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($mockDb, null);
            
            if ($state !== null) {
                $adapter->setTriageState($state);
            }
            
            $this->assertFalse(
                $adapter->isSessionActive(),
                "State '{$state}' should NOT be considered an active session"
            );
        }
    }
    
    /**
     * Property Test: Greeting phrases should not appear in active session responses
     * 
     * This test verifies that the system prompt instructs the AI to not greet
     * when a session is active.
     * 
     * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
     * **Validates: Requirements 9.1**
     * 
     * @dataProvider activeStateProvider
     */
    public function testSystemPromptPreventsGreetingDuringActiveSession(string $state): void
    {
        $mockDb = $this->createMockedPdo();
        $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($mockDb, null);
        
        // Set the triage state to active
        $adapter->setTriageState($state);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($adapter);
        $method = $reflection->getMethod('buildPharmacySystemPrompt');
        $method->setAccessible(true);
        
        // Build system prompt with active session
        $systemPrompt = $method->invoke($adapter, null, null, [], true);
        
        // Verify the prompt contains instructions to prevent greeting
        $this->assertStringContainsString(
            'ห้ามทักทายใหม่',
            $systemPrompt,
            "System prompt should contain instruction to prevent greeting during active session"
        );
        
        $this->assertStringContainsString(
            'สถานะ Session',
            $systemPrompt,
            "System prompt should indicate active session status"
        );
    }
    
    /**
     * Property Test: System prompt should NOT prevent greeting for new sessions
     * 
     * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
     * **Validates: Requirements 9.1**
     */
    public function testSystemPromptAllowsGreetingForNewSession(): void
    {
        $mockDb = $this->createMockedPdo();
        $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($mockDb, null);
        
        // Don't set triage state (new session)
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($adapter);
        $method = $reflection->getMethod('buildPharmacySystemPrompt');
        $method->setAccessible(true);
        
        // Build system prompt without active session
        $systemPrompt = $method->invoke($adapter, null, null, [], false);
        
        // Verify the prompt does NOT contain the active session warning
        $this->assertStringNotContainsString(
            'สถานะ Session: กำลังซักประวัติอยู่',
            $systemPrompt,
            "System prompt should NOT contain active session warning for new sessions"
        );
    }
    
    /**
     * Helper function to check if text contains any greeting phrase
     */
    private function containsGreetingPhrase(string $text): bool
    {
        foreach (self::GREETING_PHRASES as $phrase) {
            if (mb_strpos($text, $phrase) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Property Test: Verify greeting detection helper works correctly
     * 
     * **Feature: liff-ai-assistant-integration, Property 7: Session Continuity - No Greeting During Active Session**
     * **Validates: Requirements 9.1**
     */
    public function testGreetingDetectionHelper(): void
    {
        // Test that greeting phrases are detected
        foreach (self::GREETING_PHRASES as $phrase) {
            $textWithGreeting = "ข้อความทดสอบ {$phrase} ข้อความต่อ";
            $this->assertTrue(
                $this->containsGreetingPhrase($textWithGreeting),
                "Should detect greeting phrase: {$phrase}"
            );
        }
        
        // Test that non-greeting text is not flagged
        $nonGreetingTexts = [
            'รับทราบค่ะ ความรุนแรงระดับ 9',
            'มีอาการอื่นร่วมด้วยไหมคะ',
            'แนะนำให้ทานยา Paracetamol',
            'เป็นมานานแค่ไหนคะ',
        ];
        
        foreach ($nonGreetingTexts as $text) {
            $this->assertFalse(
                $this->containsGreetingPhrase($text),
                "Should NOT detect greeting in: {$text}"
            );
        }
    }
}
