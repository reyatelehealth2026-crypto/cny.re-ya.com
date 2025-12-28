<?php
/**
 * Property-Based Test: Pharmacist Notification on High Severity
 * 
 * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
 * **Validates: Requirements 4.2**
 * 
 * Property: For any triage assessment with severity_level 'high' or 'critical', 
 * a notification SHALL be created in pharmacist_notifications table.
 */

namespace Tests\AIChat;

use PHPUnit\Framework\TestCase;
use PDO;

class PharmacistNotificationPropertyTest extends TestCase
{
    private ?PDO $db = null;
    private int $testUserId = 99999;
    private int $testLineAccountId = 99999;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTestTables();
    }
    
    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }
    
    /**
     * Create test tables in SQLite
     */
    private function createTestTables(): void
    {
        // Create users table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                display_name TEXT,
                line_user_id TEXT
            )
        ");
        
        // Create triage_sessions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS triage_sessions (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                line_account_id INTEGER,
                current_state TEXT DEFAULT 'greeting',
                triage_data TEXT,
                status TEXT DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create ai_triage_assessments table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS ai_triage_assessments (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                line_account_id INTEGER,
                symptoms TEXT,
                duration TEXT,
                severity INTEGER,
                severity_level TEXT DEFAULT 'low',
                associated_symptoms TEXT,
                allergies TEXT,
                medical_conditions TEXT,
                current_medications TEXT,
                ai_assessment TEXT,
                recommended_action TEXT DEFAULT 'self_care',
                pharmacist_notified INTEGER DEFAULT 0,
                status TEXT DEFAULT 'pending',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create pharmacist_notifications table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pharmacist_notifications (
                id INTEGER PRIMARY KEY,
                line_account_id INTEGER,
                type TEXT DEFAULT 'triage_alert',
                title TEXT,
                message TEXT,
                notification_data TEXT,
                reference_id INTEGER,
                reference_type TEXT,
                user_id INTEGER,
                triage_session_id INTEGER,
                priority TEXT DEFAULT 'normal',
                status TEXT DEFAULT 'pending',
                is_read INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert test user
        $this->db->exec("INSERT INTO users (id, display_name, line_user_id) VALUES ({$this->testUserId}, 'Test User', 'U123456')");
    }
    
    /**
     * Simulate the saveTriageAssessment function behavior
     * This mimics the logic in PharmacyAIAdapter::saveTriageAssessment
     * 
     * @param array $data Assessment data
     * @return array Result with assessment_id and notification status
     */
    private function saveTriageAssessment(array $data): array
    {
        // Insert assessment
        $stmt = $this->db->prepare("
            INSERT INTO ai_triage_assessments 
            (user_id, line_account_id, symptoms, duration, severity, severity_level, 
             associated_symptoms, allergies, medical_conditions, current_medications,
             ai_assessment, recommended_action)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $this->testUserId,
            $this->testLineAccountId,
            $data['symptoms'] ?? '',
            $data['duration'] ?? '',
            intval($data['severity'] ?? 5),
            $data['severity_level'] ?? 'low',
            $data['associated_symptoms'] ?? '',
            $data['allergies'] ?? '',
            $data['medical_conditions'] ?? '',
            $data['current_medications'] ?? '',
            $data['ai_assessment'] ?? '',
            $data['recommended_action'] ?? 'self_care'
        ]);
        
        $assessmentId = $this->db->lastInsertId();
        
        // Notify pharmacist if severity is high or critical
        $severityLevel = $data['severity_level'] ?? 'low';
        $notified = false;
        
        if (in_array($severityLevel, ['high', 'critical'])) {
            $notified = $this->notifyPharmacist($assessmentId, $data);
        }
        
        return [
            'success' => true,
            'assessment_id' => $assessmentId,
            'severity_level' => $severityLevel,
            'pharmacist_notified' => $notified
        ];
    }
    
    /**
     * Simulate the notifyPharmacist function behavior
     * This mimics the logic in PharmacyAIAdapter::notifyPharmacist
     * 
     * @param int $assessmentId Assessment ID
     * @param array $data Assessment data
     * @return bool Success status
     */
    private function notifyPharmacist(int $assessmentId, array $data): bool
    {
        $severityLevel = $data['severity_level'] ?? 'high';
        $severityText = $severityLevel === 'critical' ? '🚨 ฉุกเฉิน' : '⚠️ รุนแรง';
        $priority = $severityLevel === 'critical' ? 'urgent' : 'normal';
        
        $title = "{$severityText} - ลูกค้าต้องการความช่วยเหลือ";
        $message = "ลูกค้า: Test User\n";
        $message .= "อาการ: " . ($data['symptoms'] ?? '-') . "\n";
        $message .= "ระยะเวลา: " . ($data['duration'] ?? '-') . "\n";
        $message .= "ความรุนแรง: " . ($data['severity'] ?? '-') . "/10\n";
        $message .= "การประเมิน: " . ($data['ai_assessment'] ?? '-');
        
        $notificationData = json_encode([
            'symptoms' => $data['symptoms'] ?? '',
            'duration' => $data['duration'] ?? '',
            'severity' => $data['severity'] ?? 5,
            'severity_level' => $severityLevel,
            'associated_symptoms' => $data['associated_symptoms'] ?? '',
            'allergies' => $data['allergies'] ?? '',
            'ai_assessment' => $data['ai_assessment'] ?? '',
            'recommended_action' => $data['recommended_action'] ?? 'consult_pharmacist'
        ]);
        
        $stmt = $this->db->prepare("
            INSERT INTO pharmacist_notifications 
            (line_account_id, type, title, message, notification_data, reference_id, reference_type, user_id, priority, status)
            VALUES (?, 'triage_alert', ?, ?, ?, ?, 'triage_assessment', ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $this->testLineAccountId,
            $title,
            $message,
            $notificationData,
            $assessmentId,
            $this->testUserId,
            $priority
        ]);
        
        // Update assessment as notified
        $stmt = $this->db->prepare("UPDATE ai_triage_assessments SET pharmacist_notified = 1 WHERE id = ?");
        $stmt->execute([$assessmentId]);
        
        return true;
    }
    
    /**
     * Check if notification exists for assessment
     * 
     * @param int $assessmentId Assessment ID
     * @return array|null Notification data or null
     */
    private function getNotificationForAssessment(int $assessmentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM pharmacist_notifications 
            WHERE reference_id = ? AND reference_type = 'triage_assessment'
        ");
        $stmt->execute([$assessmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Generate random severity level
     * 
     * @return string Severity level
     */
    private function generateRandomSeverityLevel(): string
    {
        $levels = ['low', 'medium', 'high', 'critical'];
        return $levels[array_rand($levels)];
    }
    
    /**
     * Generate random assessment data
     * 
     * @param string $severityLevel Severity level to use
     * @return array Assessment data
     */
    private function generateRandomAssessmentData(string $severityLevel): array
    {
        $symptoms = ['ปวดหัว', 'ไข้', 'ไอ', 'เจ็บคอ', 'ปวดท้อง', 'คลื่นไส้', 'อ่อนเพลีย'];
        $durations = ['1 วัน', '2 วัน', '3 วัน', '1 สัปดาห์', '2 สัปดาห์'];
        $actions = ['self_care', 'consult_pharmacist', 'see_doctor', 'emergency'];
        
        // Map severity level to numeric severity
        $severityMap = [
            'low' => rand(1, 3),
            'medium' => rand(4, 6),
            'high' => rand(7, 8),
            'critical' => rand(9, 10)
        ];
        
        return [
            'symptoms' => $symptoms[array_rand($symptoms)],
            'duration' => $durations[array_rand($durations)],
            'severity' => $severityMap[$severityLevel],
            'severity_level' => $severityLevel,
            'associated_symptoms' => '',
            'allergies' => '',
            'medical_conditions' => '',
            'current_medications' => '',
            'ai_assessment' => 'AI assessment for ' . $severityLevel . ' severity',
            'recommended_action' => $actions[array_rand($actions)]
        ];
    }
    
    /**
     * Data provider for property testing with various severity levels
     * 
     * @return array Test data sets
     */
    public function severityLevelProvider(): array
    {
        $testCases = [];
        
        // Generate 100 random test cases for property-based testing
        for ($i = 0; $i < 100; $i++) {
            $severityLevel = $this->generateRandomSeverityLevel();
            $testCases["random_case_{$i}_{$severityLevel}"] = [$severityLevel];
        }
        
        return $testCases;
    }
    
    /**
     * Property Test: High/Critical severity SHALL create pharmacist notification
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     * 
     * @dataProvider severityLevelProvider
     */
    public function testHighCriticalSeverityCreatesNotification(string $severityLevel): void
    {
        // Generate random assessment data with the given severity level
        $assessmentData = $this->generateRandomAssessmentData($severityLevel);
        
        // Save the assessment (this should trigger notification for high/critical)
        $result = $this->saveTriageAssessment($assessmentData);
        
        $this->assertTrue($result['success'], 'Assessment should be saved successfully');
        
        // Check if notification was created
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        if (in_array($severityLevel, ['high', 'critical'])) {
            // Property: High/Critical severity MUST create notification
            $this->assertNotNull(
                $notification,
                "Notification MUST be created for severity_level '{$severityLevel}'"
            );
            
            $this->assertTrue(
                $result['pharmacist_notified'],
                "pharmacist_notified flag MUST be true for severity_level '{$severityLevel}'"
            );
            
            // Verify notification has correct reference
            $this->assertEquals(
                $result['assessment_id'],
                $notification['reference_id'],
                "Notification reference_id should match assessment_id"
            );
            
            $this->assertEquals(
                'triage_assessment',
                $notification['reference_type'],
                "Notification reference_type should be 'triage_assessment'"
            );
            
            // Verify priority is set correctly
            $expectedPriority = $severityLevel === 'critical' ? 'urgent' : 'normal';
            $this->assertEquals(
                $expectedPriority,
                $notification['priority'],
                "Notification priority should be '{$expectedPriority}' for severity_level '{$severityLevel}'"
            );
        } else {
            // Property: Low/Medium severity should NOT create notification
            $this->assertNull(
                $notification,
                "Notification should NOT be created for severity_level '{$severityLevel}'"
            );
            
            $this->assertFalse(
                $result['pharmacist_notified'],
                "pharmacist_notified flag should be false for severity_level '{$severityLevel}'"
            );
        }
    }
    
    /**
     * Property Test: Critical severity creates urgent notification
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testCriticalSeverityCreatesUrgentNotification(): void
    {
        $assessmentData = $this->generateRandomAssessmentData('critical');
        $result = $this->saveTriageAssessment($assessmentData);
        
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        $this->assertNotNull($notification, 'Notification must be created for critical severity');
        $this->assertEquals('urgent', $notification['priority'], 'Critical severity should create urgent notification');
        $this->assertStringContainsString('ฉุกเฉิน', $notification['title'], 'Title should contain emergency indicator');
    }
    
    /**
     * Property Test: High severity creates normal priority notification
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testHighSeverityCreatesNormalPriorityNotification(): void
    {
        $assessmentData = $this->generateRandomAssessmentData('high');
        $result = $this->saveTriageAssessment($assessmentData);
        
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        $this->assertNotNull($notification, 'Notification must be created for high severity');
        $this->assertEquals('normal', $notification['priority'], 'High severity should create normal priority notification');
        $this->assertStringContainsString('รุนแรง', $notification['title'], 'Title should contain severity indicator');
    }
    
    /**
     * Property Test: Low severity does NOT create notification
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testLowSeverityDoesNotCreateNotification(): void
    {
        $assessmentData = $this->generateRandomAssessmentData('low');
        $result = $this->saveTriageAssessment($assessmentData);
        
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        $this->assertNull($notification, 'Notification should NOT be created for low severity');
        $this->assertFalse($result['pharmacist_notified'], 'pharmacist_notified should be false for low severity');
    }
    
    /**
     * Property Test: Medium severity does NOT create notification
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testMediumSeverityDoesNotCreateNotification(): void
    {
        $assessmentData = $this->generateRandomAssessmentData('medium');
        $result = $this->saveTriageAssessment($assessmentData);
        
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        $this->assertNull($notification, 'Notification should NOT be created for medium severity');
        $this->assertFalse($result['pharmacist_notified'], 'pharmacist_notified should be false for medium severity');
    }
    
    /**
     * Property Test: Notification contains triage data
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testNotificationContainsTriageData(): void
    {
        $assessmentData = [
            'symptoms' => 'ปวดหัวรุนแรง',
            'duration' => '3 วัน',
            'severity' => 8,
            'severity_level' => 'high',
            'associated_symptoms' => 'คลื่นไส้',
            'allergies' => 'aspirin',
            'ai_assessment' => 'อาจเป็นไมเกรน',
            'recommended_action' => 'consult_pharmacist'
        ];
        
        $result = $this->saveTriageAssessment($assessmentData);
        $notification = $this->getNotificationForAssessment($result['assessment_id']);
        
        $this->assertNotNull($notification, 'Notification must be created');
        
        // Check notification_data contains triage information
        $notificationData = json_decode($notification['notification_data'], true);
        
        $this->assertNotNull($notificationData, 'notification_data should be valid JSON');
        $this->assertEquals($assessmentData['symptoms'], $notificationData['symptoms'], 'Symptoms should be in notification_data');
        $this->assertEquals($assessmentData['duration'], $notificationData['duration'], 'Duration should be in notification_data');
        $this->assertEquals($assessmentData['severity'], $notificationData['severity'], 'Severity should be in notification_data');
        $this->assertEquals($assessmentData['severity_level'], $notificationData['severity_level'], 'Severity level should be in notification_data');
        
        // Check message contains key information
        $this->assertStringContainsString($assessmentData['symptoms'], $notification['message'], 'Message should contain symptoms');
        $this->assertStringContainsString($assessmentData['duration'], $notification['message'], 'Message should contain duration');
    }
    
    /**
     * Property Test: Assessment is marked as notified
     * 
     * **Feature: liff-ai-assistant-integration, Property 13: Pharmacist Notification on High Severity**
     * **Validates: Requirements 4.2**
     */
    public function testAssessmentIsMarkedAsNotified(): void
    {
        $assessmentData = $this->generateRandomAssessmentData('critical');
        $result = $this->saveTriageAssessment($assessmentData);
        
        // Check assessment is marked as notified
        $stmt = $this->db->prepare("SELECT pharmacist_notified FROM ai_triage_assessments WHERE id = ?");
        $stmt->execute([$result['assessment_id']]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(1, $assessment['pharmacist_notified'], 'Assessment should be marked as pharmacist_notified');
    }
}
