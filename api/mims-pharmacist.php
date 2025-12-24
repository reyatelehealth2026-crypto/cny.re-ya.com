<?php
/**
 * MIMS Pharmacist AI API
 * 
 * API endpoint สำหรับ AI เภสัชกรออนไลน์ที่ใช้ข้อมูลจาก MIMS Pharmacy Thailand 2023
 * 
 * Endpoints:
 * - POST /api/mims-pharmacist.php?action=chat - ส่งข้อความถาม AI
 * - GET /api/mims-pharmacist.php?action=search&symptom=xxx - ค้นหาข้อมูลโรคจาก MIMS
 * - GET /api/mims-pharmacist.php?action=disease&category=xxx&key=xxx - ดึงข้อมูลโรคเฉพาะ
 * - GET /api/mims-pharmacist.php?action=red-flags&symptoms=xxx - ตรวจสอบ Red Flags
 * - POST /api/mims-pharmacist.php?action=reset - รีเซ็ตการสนทนา
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/AIChat/Autoloader.php';
loadAIChatModule();

use Modules\AIChat\Adapters\MIMSPharmacistAI;
use Modules\AIChat\Services\MIMSKnowledgeBase;

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'chat';
$lineAccountId = intval($_GET['line_account_id'] ?? $_POST['line_account_id'] ?? 1);
$userId = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

// Get JSON body for POST requests
$jsonBody = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    if (!empty($rawBody)) {
        $jsonBody = json_decode($rawBody, true) ?? [];
    }
    // Merge with POST data
    $jsonBody = array_merge($_POST, $jsonBody);
}

switch ($action) {
    case 'chat':
        handleChat($db, $lineAccountId, $userId, $jsonBody);
        break;
        
    case 'search':
        handleSearch($jsonBody);
        break;
        
    case 'disease':
        handleGetDisease($jsonBody);
        break;
        
    case 'red-flags':
        handleCheckRedFlags($jsonBody);
        break;
        
    case 'reset':
        handleReset($db, $lineAccountId, $userId);
        break;
        
    case 'categories':
        handleGetCategories();
        break;
        
    case 'corticosteroid':
        handleCorticosteroidRecommendation($jsonBody);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Handle chat request
 */
function handleChat($db, $lineAccountId, $userId, $data): void
{
    $message = $data['message'] ?? '';
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        return;
    }
    
    $ai = new MIMSPharmacistAI($db, $lineAccountId);
    
    if ($userId > 0) {
        $ai->setUserId($userId);
    }
    
    if (!$ai->isEnabled()) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'AI service is not available']);
        return;
    }
    
    $result = $ai->processMessage($message);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Handle search request
 */
function handleSearch($data): void
{
    $symptom = $data['symptom'] ?? $_GET['symptom'] ?? '';
    
    if (empty($symptom)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Symptom is required']);
        return;
    }
    
    $kb = new MIMSKnowledgeBase();
    $results = $kb->searchBySymptom($symptom);
    
    echo json_encode([
        'success' => true,
        'symptom' => $symptom,
        'count' => count($results),
        'results' => $results
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Handle get disease request
 */
function handleGetDisease($data): void
{
    $category = $data['category'] ?? $_GET['category'] ?? '';
    $key = $data['key'] ?? $_GET['key'] ?? '';
    
    if (empty($category) || empty($key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Category and key are required']);
        return;
    }
    
    $kb = new MIMSKnowledgeBase();
    $disease = $kb->getDisease($category, $key);
    
    if (!$disease) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Disease not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'disease' => $disease,
        'formatted' => $kb->formatDiseaseForAI($disease)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Handle check red flags request
 */
function handleCheckRedFlags($data): void
{
    $symptoms = $data['symptoms'] ?? $_GET['symptoms'] ?? '';
    
    if (empty($symptoms)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Symptoms are required']);
        return;
    }
    
    $kb = new MIMSKnowledgeBase();
    $flags = $kb->checkRedFlags($symptoms);
    
    $hasEmergency = false;
    foreach ($flags as $flag) {
        if ($flag['flag']['urgency'] === 'emergency') {
            $hasEmergency = true;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'symptoms' => $symptoms,
        'has_red_flags' => !empty($flags),
        'has_emergency' => $hasEmergency,
        'flags' => $flags
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Handle reset conversation
 */
function handleReset($db, $lineAccountId, $userId): void
{
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        return;
    }
    
    $ai = new MIMSPharmacistAI($db, $lineAccountId);
    $ai->setUserId($userId);
    $ai->resetConversation();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversation reset successfully'
    ]);
}

/**
 * Handle get categories
 */
function handleGetCategories(): void
{
    $kb = new MIMSKnowledgeBase();
    $categories = $kb->getCategories();
    
    $result = [];
    foreach ($categories as $cat) {
        $diseases = $kb->getCategory($cat);
        $result[$cat] = [
            'count' => count($diseases),
            'diseases' => array_keys($diseases)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Handle corticosteroid recommendation
 */
function handleCorticosteroidRecommendation($data): void
{
    $bodyArea = $data['body_area'] ?? $_GET['body_area'] ?? '';
    
    if (empty($bodyArea)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Body area is required']);
        return;
    }
    
    $kb = new MIMSKnowledgeBase();
    $recommendation = $kb->recommendCorticosteroid($bodyArea);
    
    // Get all potency levels for reference
    $allPotency = [];
    for ($i = 1; $i <= 7; $i++) {
        $allPotency[$i] = $kb->getCorticosteroidByPotency($i);
    }
    
    echo json_encode([
        'success' => true,
        'body_area' => $bodyArea,
        'recommendation' => $recommendation,
        'potency_chart' => $allPotency
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
