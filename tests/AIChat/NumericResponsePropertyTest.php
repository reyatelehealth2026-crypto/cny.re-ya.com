<?php
/**
 * Property-Based Test: Numeric Response Interpretation
 * 
 * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
 * **Validates: Requirements 9.3**
 * 
 * Property: For any numeric response (1-10) sent when triage state is 'severity', 
 * the system SHALL interpret it as severity value and transition to next state.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/AIChat/Autoloader.php';

// Load AIChat modules
if (function_exists('loadAIChatModule')) {
    loadAIChatModule();
}

class NumericResponsePropertyTest extends TestCase
{
    /**
     * Generate random numeric severity values (1-10) for property testing
     * 
     * @return array Array of test data sets
     */
    public function numericSeverityProvider(): array
    {
        $testCases = [];
        
        // Generate 100 test cases with random severity values 1-10
        for ($i = 0; $i < 100; $i++) {
            $severity = rand(1, 10);
            $testCases["severity_{$i}"] = [(string)$severity, $severity];
        }
        
        return $testCases;
    }
    
    /**
     * Generate various numeric response formats
     * 
     * @return array Array of test data sets
     */
    public function numericResponseFormatProvider(): array
    {
        $testCases = [];
        
        // Direct numbers
        for ($i = 1; $i <= 10; $i++) {
            $testCases["direct_{$i}"] = [(string)$i, $i];
        }
        
        // Numbers with text
        $formats = [
            ['ประมาณ 5', 5],
            ['ระดับ 7', 7],
            ['สัก 3', 3],
            ['น่าจะ 8', 8],
            ['ราวๆ 6', 6],
        ];
        
        foreach ($formats as $idx => $format) {
            $testCases["format_{$idx}"] = $format;
        }
        
        return $testCases;
    }
    
    /**
     * Generate word-based severity responses
     * 
     * @return array Array of test data sets
     */
    public function wordBasedSeverityProvider(): array
    {
        return [
            ['เล็กน้อย', 2],
            ['นิดหน่อย', 2],
            ['ไม่มาก', 3],
            ['ปานกลาง', 5],
            ['พอทน', 5],
            ['มาก', 7],
            ['รุนแรง', 8],
            ['มากๆ', 8],
            ['ทนไม่ไหว', 9],
            ['รุนแรงมาก', 10],
        ];
    }
    
    /**
     * Property Test: isNumericSeverityResponse returns true for valid numeric inputs (1-10)
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     * 
     * @dataProvider numericSeverityProvider
     */
    public function testIsNumericSeverityResponseReturnsTrueForValidNumbers(string $message, int $expectedSeverity): void
    {
        // Create TriageEngine without database (for unit testing)
        $mockPdo = $this->createMock(\PDO::class);
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        // Verify the message is recognized as a numeric severity response
        $this->assertTrue(
            $engine->isNumericSeverityResponse($message),
            "Message '{$message}' should be recognized as a numeric severity response"
        );
    }
    
    /**
     * Property Test: isNumericSeverityResponse returns true for various numeric formats
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     * 
     * @dataProvider numericResponseFormatProvider
     */
    public function testIsNumericSeverityResponseHandlesVariousFormats(string $message, int $expectedSeverity): void
    {
        $mockPdo = $this->createMock(\PDO::class);
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        $this->assertTrue(
            $engine->isNumericSeverityResponse($message),
            "Message '{$message}' should be recognized as a numeric severity response"
        );
    }
    
    /**
     * Property Test: isNumericSeverityResponse returns true for word-based severity
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     * 
     * @dataProvider wordBasedSeverityProvider
     */
    public function testIsNumericSeverityResponseHandlesWordBasedSeverity(string $message, int $expectedSeverity): void
    {
        $mockPdo = $this->createMock(\PDO::class);
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        $this->assertTrue(
            $engine->isNumericSeverityResponse($message),
            "Message '{$message}' should be recognized as a severity response"
        );
    }
    
    /**
     * Property Test: isNumericSeverityResponse returns false for out-of-range numbers
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     */
    public function testIsNumericSeverityResponseReturnsFalseForOutOfRangeNumbers(): void
    {
        $mockPdo = $this->createMock(\PDO::class);
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        // Numbers outside 1-10 range should not be recognized as severity
        $outOfRangeNumbers = ['0', '11', '15', '100', '-1', '-5'];
        
        foreach ($outOfRangeNumbers as $number) {
            $this->assertFalse(
                $engine->isNumericSeverityResponse($number),
                "Number '{$number}' should NOT be recognized as a valid severity response"
            );
        }
    }
    
    /**
     * Property Test: isNumericSeverityResponse returns false for non-severity text
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     */
    public function testIsNumericSeverityResponseReturnsFalseForNonSeverityText(): void
    {
        $mockPdo = $this->createMock(\PDO::class);
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        // Non-severity text should not be recognized
        $nonSeverityTexts = [
            'ปวดหัว',
            'ไข้',
            'ไอ',
            '2 วัน',
            '1 สัปดาห์',
            'ไม่แพ้',
            'ไม่มี',
            'สวัสดี',
        ];
        
        foreach ($nonSeverityTexts as $text) {
            $this->assertFalse(
                $engine->isNumericSeverityResponse($text),
                "Text '{$text}' should NOT be recognized as a severity response"
            );
        }
    }
    
    /**
     * Property Test: For any numeric response (1-10) in severity state, 
     * the system transitions to next state (associated)
     * 
     * **Feature: liff-ai-assistant-integration, Property 8: Numeric Response Interpretation**
     * **Validates: Requirements 9.3**
     * 
     * @dataProvider numericSeverityProvider
     */
    public function testNumericResponseInSeverityStateTransitionsToNextState(string $message, int $expectedSeverity): void
    {
        // Create a mock PDO that returns no active session
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn(false);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $engine = new \Modules\AIChat\Services\TriageEngine(null, null, $mockPdo);
        
        // Use reflection to set the state to 'severity'
        $reflection = new \ReflectionClass($engine);
        $stateProperty = $reflection->getProperty('currentState');
        $stateProperty->setAccessible(true);
        $stateProperty->setValue($engine, [
            'state' => 'severity',
            'data' => [
                'symptoms' => ['ปวดหัว'],
                'duration' => '2 วัน',
                'severity' => null,
                'red_flags' => [],
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Process the numeric message
        $result = $engine->process($message);
        
        // Verify the state transitioned to 'associated'
        $this->assertEquals(
            'associated',
            $result['state'],
            "After processing severity '{$message}', state should transition to 'associated'"
        );
        
        // Verify severity was saved
        $triageData = $engine->getTriageData();
        $this->assertNotNull(
            $triageData['severity'],
            "Severity value should be saved after processing numeric response"
        );
        
        // Verify severity is within valid range
        $this->assertGreaterThanOrEqual(1, $triageData['severity']);
        $this->assertLessThanOrEqual(10, $triageData['severity']);
    }
}
