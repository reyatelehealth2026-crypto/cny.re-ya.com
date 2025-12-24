<?php
/**
 * Pharmacy AI API
 * API สำหรับ LIFF Pharmacy Consultation
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$userId = $input['user_id'] ?? null;
$state = $input['state'] ?? 'greeting';
$triageData = $input['triage_data'] ?? [];

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'No message']);
    exit;
}

try {
    // Get line_account_id
    $lineAccountId = null;
    if ($userId) {
        $stmt = $db->prepare("SELECT line_account_id FROM users WHERE line_user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $lineAccountId = $user['line_account_id'] ?? null;
    }
    
    // Try to use PharmacyAIAdapter
    $adapterFile = __DIR__ . '/../modules/AIChat/Adapters/PharmacyAIAdapter.php';
    
    if (file_exists($adapterFile)) {
        require_once $adapterFile;
        
        $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($db, $lineAccountId);
        
        if ($userId) {
            // Get internal user ID
            $stmt = $db->prepare("SELECT id FROM users WHERE line_user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userRecord) {
                $adapter->setUserId($userRecord['id']);
            }
        }
        
        if ($adapter->isEnabled()) {
            $result = $adapter->processMessage($message);
            
            echo json_encode([
                'success' => $result['success'],
                'response' => $result['response'] ?? $result['text'] ?? '',
                'state' => $result['state'] ?? $state,
                'data' => $result['data'] ?? $triageData,
                'quick_replies' => $result['message']['quickReply']['items'] ?? [],
                'is_critical' => $result['is_critical'] ?? false,
            ]);
            exit;
        }
    }
    
    // Fallback: Simple response
    echo json_encode([
        'success' => true,
        'response' => 'ขออภัยค่ะ ระบบ AI ยังไม่พร้อมใช้งาน กรุณาติดต่อเภสัชกรโดยตรง',
        'state' => $state,
        'data' => $triageData,
        'quick_replies' => [
            ['label' => '📞 ติดต่อเภสัชกร', 'text' => 'ติดต่อเภสัชกร'],
        ],
    ]);
    
} catch (Exception $e) {
    error_log("Pharmacy AI API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'response' => 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่',
    ]);
}
