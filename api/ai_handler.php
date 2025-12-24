<?php
/**
 * AI Handler Endpoint
 * รับคำขอจาก Frontend ผ่าน AJAX
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Handle CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/GeminiAI.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

// Start session to get current bot
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    $action = $input['action'] ?? '';
    
    // โหลด API Key จากฐานข้อมูลตาม LINE Account ปัจจุบัน
    $db = Database::getInstance()->getConnection();
    $currentBotId = $_SESSION['current_bot_id'] ?? null;
    $apiKey = null;
    
    if ($currentBotId) {
        // ตรวจสอบว่ามี column gemini_api_key หรือไม่
        try {
            $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'gemini_api_key'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("SELECT gemini_api_key FROM line_accounts WHERE id = ?");
                $stmt->execute([$currentBotId]);
                $apiKey = $stmt->fetchColumn();
            }
        } catch (Exception $e) {}
    }
    
    // Fallback to config if no API key in database
    if (empty($apiKey) && defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
        $apiKey = GEMINI_API_KEY;
    }
    
    if (empty($apiKey)) {
        throw new Exception('กรุณาตั้งค่า Gemini API Key ในหน้า "ภาพรวมการตั้งค่า"');
    }
    
    $gemini = new GeminiAI($apiKey);
    
    $response = [];

    switch ($action) {
        case 'generate_broadcast':
            $topic = $input['topic'] ?? '';
            $tone = $input['tone'] ?? 'friendly';
            $target = $input['target'] ?? 'general';
            
            if (empty($topic)) throw new Exception("กรุณาระบุหัวข้อ");
            
            $response = $gemini->generateBroadcast($topic, $tone, $target);
            break;

        case 'generate_image_prompt':
            // ในระยะแรก ใช้ช่วยคิด Prompt ก่อน เพราะ Image API ตรงๆ อาจต้องตั้งค่า Google Cloud Project ซับซ้อน
            $description = $input['description'] ?? '';
            if (empty($description)) throw new Exception("กรุณาระบุรายละเอียดสินค้า");
            
            $response = $gemini->generateBroadcast("Prompt ภาษาอังกฤษสำหรับ AI วาดรูป: " . $description, "technical", "Artist");
            break;

        default:
            throw new Exception("Unknown Action");
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}