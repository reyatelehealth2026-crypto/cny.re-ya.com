<?php
/**
 * API: AI Chat
 * General AI Chat endpoint using Gemini API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['admin_user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$lineAccountId = $_SESSION['current_bot_id'] ?? null;
$db = Database::getInstance()->getConnection();

// Get Gemini API Key
$geminiApiKey = getGeminiApiKey($db, $lineAccountId);

// Get request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'chat';

switch ($action) {
    case 'status':
        echo json_encode([
            'success' => true,
            'ai_available' => !empty($geminiApiKey)
        ]);
        break;
        
    case 'chat':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';
        $history = $input['history'] ?? [];
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Message is required']);
            exit;
        }
        
        if (empty($geminiApiKey)) {
            echo json_encode([
                'success' => false, 
                'error' => 'กรุณาตั้งค่า Gemini API Key ที่หน้า AI Settings ก่อนใช้งาน'
            ]);
            exit;
        }
        
        $response = callGeminiAI($geminiApiKey, $message, $history);
        echo json_encode($response);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Get Gemini API Key from settings
 */
function getGeminiApiKey($db, $lineAccountId) {
    try {
        // Try line account specific key first
        if ($lineAccountId) {
            $stmt = $db->prepare("SELECT gemini_api_key FROM ai_settings WHERE line_account_id = ?");
            $stmt->execute([$lineAccountId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($result['gemini_api_key'])) {
                return $result['gemini_api_key'];
            }
        }
        
        // Try global config
        if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
            return GEMINI_API_KEY;
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    return null;
}

/**
 * Call Gemini AI API
 */
function callGeminiAI($apiKey, $message, $history = []) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
    
    // Build system prompt
    $systemPrompt = "คุณคือ AI Assistant ที่ช่วยเหลือผู้ใช้งานระบบ LINE CRM สำหรับร้านค้า/ร้านขายยา
คุณสามารถช่วย:
- เขียนข้อความต้อนรับลูกค้า
- คิดโปรโมชั่นและแคมเปญการตลาด
- เขียน caption สำหรับโพสต์ขายสินค้า
- ตอบคำถามเกี่ยวกับการใช้งานระบบ
- ให้คำแนะนำเรื่องการขายและการตลาด
- ช่วยเขียนข้อความตอบกลับลูกค้า

ตอบเป็นภาษาไทย กระชับ เข้าใจง่าย และเป็นมิตร";

    // Build conversation contents
    $contents = [];
    
    // Add history
    foreach ($history as $msg) {
        $role = $msg['role'] === 'user' ? 'user' : 'model';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $msg['content']]]
        ];
    }
    
    // Add current message with system prompt (if first message)
    if (empty($contents)) {
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\n---\n\nUser: " . $message]]
        ];
    } else {
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];
    }
    
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
            'topP' => 0.9
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH']
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Connection error: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'API Error';
        return ['success' => false, 'error' => $errorMsg];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => true,
            'message' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    // Check for blocked content
    if (isset($result['candidates'][0]['finishReason']) && $result['candidates'][0]['finishReason'] === 'SAFETY') {
        return ['success' => false, 'error' => 'ขออภัย ไม่สามารถตอบคำถามนี้ได้'];
    }
    
    return ['success' => false, 'error' => 'ไม่สามารถประมวลผลได้'];
}
