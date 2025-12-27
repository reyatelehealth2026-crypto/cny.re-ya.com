<?php
/**
 * Test Notification API
 * API สำหรับทดสอบส่งการแจ้งเตือนผ่าน LINE
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

$input = json_decode(file_get_contents('php://input'), true);
$lineUserId = $input['line_user_id'] ?? null;
$notificationType = $input['type'] ?? 'test';
$message = $input['message'] ?? null;

if (!$lineUserId) {
    echo json_encode(['success' => false, 'error' => 'Missing line_user_id']);
    exit;
}

try {
    // Get user and LINE account info
    $stmt = $db->prepare("
        SELECT u.id, u.display_name, u.line_account_id, la.channel_access_token 
        FROM users u 
        JOIN line_accounts la ON u.line_account_id = la.id 
        WHERE u.line_user_id = ?
    ");
    $stmt->execute([$lineUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['channel_access_token']) {
        echo json_encode(['success' => false, 'error' => 'User or LINE account not found']);
        exit;
    }
    
    // Build message based on type
    $messages = [];
    
    switch ($notificationType) {
        case 'order_update':
            $messages[] = [
                'type' => 'flex',
                'altText' => '📦 อัพเดทออเดอร์',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '📦 อัพเดทออเดอร์', 'weight' => 'bold', 'size' => 'lg'],
                            ['type' => 'text', 'text' => 'ออเดอร์ #TEST001 กำลังจัดส่ง', 'margin' => 'md', 'color' => '#666666'],
                            ['type' => 'text', 'text' => 'คาดว่าจะถึงภายใน 1-2 วัน', 'margin' => 'sm', 'color' => '#999999', 'size' => 'sm']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#F0FDF4']]
                ]
            ];
            break;
            
        case 'promotion':
            $messages[] = [
                'type' => 'flex',
                'altText' => '🎉 โปรโมชั่นพิเศษ',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '🎉 โปรโมชั่นพิเศษ!', 'weight' => 'bold', 'size' => 'lg', 'color' => '#E65100'],
                            ['type' => 'text', 'text' => 'ลด 20% ทุกรายการ วันนี้เท่านั้น!', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'ใช้โค้ด: TEST20', 'margin' => 'sm', 'color' => '#11B0A6', 'weight' => 'bold']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#FFF8E1']]
                ]
            ];
            break;
            
        case 'appointment':
            $messages[] = [
                'type' => 'flex',
                'altText' => '📅 เตือนนัดหมาย',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 เตือนนัดหมาย', 'weight' => 'bold', 'size' => 'lg'],
                            ['type' => 'text', 'text' => 'คุณมีนัดปรึกษาเภสัชกรพรุ่งนี้', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'เวลา 10:00 น.', 'margin' => 'sm', 'color' => '#11B0A6', 'weight' => 'bold']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#E3F2FD']]
                ]
            ];
            break;
            
        case 'medication':
            $messages[] = [
                'type' => 'flex',
                'altText' => '💊 เตือนทานยา',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '💊 เตือนทานยา', 'weight' => 'bold', 'size' => 'lg', 'color' => '#E91E63'],
                            ['type' => 'text', 'text' => 'ถึงเวลาทานยาแล้วค่ะ', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'Paracetamol 500mg - 1 เม็ด', 'margin' => 'sm', 'color' => '#666666']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#FCE4EC']]
                ]
            ];
            break;
            
        case 'health_tip':
            $messages[] = [
                'type' => 'flex',
                'altText' => '💚 เคล็ดลับสุขภาพ',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '💚 เคล็ดลับสุขภาพ', 'weight' => 'bold', 'size' => 'lg', 'color' => '#4CAF50'],
                            ['type' => 'text', 'text' => 'ดื่มน้ำอย่างน้อย 8 แก้วต่อวัน', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'ช่วยให้ร่างกายทำงานได้ดีขึ้น', 'margin' => 'sm', 'color' => '#666666', 'size' => 'sm']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#E8F5E9']]
                ]
            ];
            break;
            
        case 'price_alert':
            $messages[] = [
                'type' => 'flex',
                'altText' => '🔔 แจ้งเตือนราคา',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '🔔 แจ้งเตือนราคา', 'weight' => 'bold', 'size' => 'lg'],
                            ['type' => 'text', 'text' => 'สินค้าที่คุณติดตามลดราคาแล้ว!', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'วิตามินซี ลด 30%', 'margin' => 'sm', 'color' => '#F44336', 'weight' => 'bold']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#FFEBEE']]
                ]
            ];
            break;
            
        case 'stock_alert':
            $messages[] = [
                'type' => 'flex',
                'altText' => '📦 แจ้งสินค้าเข้า',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '📦 สินค้าเข้าแล้ว!', 'weight' => 'bold', 'size' => 'lg', 'color' => '#2196F3'],
                            ['type' => 'text', 'text' => 'สินค้าที่คุณรอกลับมาแล้ว', 'margin' => 'md'],
                            ['type' => 'text', 'text' => 'รีบสั่งก่อนหมด!', 'margin' => 'sm', 'color' => '#666666']
                        ]
                    ],
                    'styles' => ['body' => ['backgroundColor' => '#E3F2FD']]
                ]
            ];
            break;
            
        default: // test
            $messages[] = [
                'type' => 'text',
                'text' => $message ?? "🔔 ทดสอบการแจ้งเตือน\n\nสวัสดีคุณ {$user['display_name']}!\nนี่คือข้อความทดสอบจากระบบแจ้งเตือน"
            ];
    }
    
    // Send via LINE Messaging API
    $result = sendLineMessage($user['channel_access_token'], $lineUserId, $messages);
    
    if ($result['success']) {
        // Log notification
        $stmt = $db->prepare("
            INSERT INTO user_notifications (user_id, line_account_id, type, title, message, is_sent, sent_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $user['id'],
            $user['line_account_id'],
            $notificationType,
            'Test Notification',
            json_encode($messages)
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to send']);
    }
    
} catch (Exception $e) {
    error_log("Test Notification API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Send LINE message
 */
function sendLineMessage($accessToken, $userId, $messages) {
    $url = 'https://api.line.me/v2/bot/message/push';
    
    $data = [
        'to' => $userId,
        'messages' => $messages
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    if ($httpCode === 200) {
        return ['success' => true];
    }
    
    $responseData = json_decode($response, true);
    return ['success' => false, 'error' => $responseData['message'] ?? 'HTTP ' . $httpCode];
}
