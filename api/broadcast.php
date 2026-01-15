<?php
/**
 * Broadcast API
 * ส่ง Flex Message Broadcast
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
    
    $currentBotId = $_SESSION['current_bot_id'] ?? null;
    
    if (!$currentBotId) {
        $lineManager = new LineAccountManager($db);
        $defaultAccount = $lineManager->getDefaultAccount();
        if ($defaultAccount) {
            $currentBotId = $defaultAccount['id'];
        }
    }
    
    if (!$currentBotId) {
        throw new Exception('ไม่พบ LINE Bot - กรุณาตั้งค่าก่อน');
    }
    
    $lineManager = new LineAccountManager($db);
    $line = $lineManager->getLineAPI($currentBotId);
    
    if (!$line) {
        throw new Exception('ไม่สามารถเชื่อมต่อ LINE API');
    }
    
    switch ($action) {
        case 'send_flex':
            $flex = $input['flex'] ?? null;
            $altText = $input['altText'] ?? 'Broadcast Message';
            
            if (!$flex) {
                throw new Exception('ไม่มีข้อมูล Flex Message');
            }
            
            // Validate flex structure
            if (!isset($flex['type'])) {
                throw new Exception('Flex Message ต้องมี type');
            }
            
            // Ensure all image URLs are valid
            $flexJson = json_encode($flex);
            if (strpos($flexJson, 'https://via.placeholder.com') !== false) {
                // Replace placeholder with actual image
                $flexJson = str_replace('https://via.placeholder.com/100', 'https://scdn.line-apps.com/n/channel_devcenter/img/fx/01_1_cafe.png', $flexJson);
                $flex = json_decode($flexJson, true);
            }
            
            $message = [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flex
            ];
            
            // Log the message for debugging
            error_log("Sending flex message: " . json_encode($message, JSON_UNESCAPED_UNICODE));
            
            $result = $line->broadcastMessage([$message]);
            
            if ($result['code'] === 200) {
                // Log broadcast
                try {
                    $stmt = $db->prepare("INSERT INTO broadcasts (line_account_id, title, message_type, content, target_type, sent_count, status, sent_at) VALUES (?, ?, 'flex', ?, 'all', 0, 'sent', NOW())");
                    $stmt->execute([$currentBotId, $altText, json_encode($flex, JSON_UNESCAPED_UNICODE)]);
                } catch (Exception $e) {
                    error_log("Failed to log broadcast: " . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Broadcast sent']);
            } else {
                $errorMsg = $result['body']['message'] ?? json_encode($result['body']);
                error_log("LINE API Error: " . json_encode($result));
                throw new Exception("LINE API Error: $errorMsg");
            }
            break;
            
        case 'send_targeted':
            $flex = $input['flex'] ?? null;
            $altText = $input['altText'] ?? 'Broadcast Message';
            $targetType = $input['targetType'] ?? '';
            $targetId = $input['targetId'] ?? null;
            
            if (!$flex) {
                throw new Exception('ไม่มีข้อมูล Flex Message');
            }
            
            $userIds = [];
            
            // Get users based on target type
            if ($targetType === 'segment' && $targetId) {
                // Get users from segment
                $stmt = $db->prepare("
                    SELECT DISTINCT u.line_user_id 
                    FROM users u
                    JOIN segment_members sm ON u.id = sm.user_id
                    WHERE sm.segment_id = ? 
                    AND u.line_user_id IS NOT NULL 
                    AND u.line_user_id != ''
                    AND (u.is_blocked = 0 OR u.is_blocked IS NULL)
                ");
                $stmt->execute([$targetId]);
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            } elseif ($targetType === 'tag' && $targetId) {
                // Get users with tag
                $stmt = $db->prepare("
                    SELECT DISTINCT u.line_user_id 
                    FROM users u
                    JOIN user_tag_assignments uta ON u.id = uta.user_id
                    WHERE uta.tag_id = ? 
                    AND u.line_user_id IS NOT NULL 
                    AND u.line_user_id != ''
                    AND (u.is_blocked = 0 OR u.is_blocked IS NULL)
                ");
                $stmt->execute([$targetId]);
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            if (empty($userIds)) {
                throw new Exception('ไม่พบผู้ใช้ในกลุ่มเป้าหมาย');
            }
            
            $message = [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flex
            ];
            
            // Send in chunks of 500
            $chunks = array_chunk($userIds, 500);
            $totalSent = 0;
            
            foreach ($chunks as $chunk) {
                $result = $line->multicastMessage($chunk, [$message]);
                if ($result['code'] === 200) {
                    $totalSent += count($chunk);
                }
            }
            
            // Log broadcast
            try {
                $stmt = $db->prepare("INSERT INTO broadcasts (account_id, type, content, target_type, target_id, sent_count, status, created_at) VALUES (?, 'flex', ?, ?, ?, ?, 'sent', NOW())");
                $stmt->execute([$currentBotId, json_encode($flex, JSON_UNESCAPED_UNICODE), $targetType, $targetId, $totalSent]);
            } catch (Exception $e) {}
            
            echo json_encode(['success' => true, 'sent' => $totalSent]);
            break;
            
        case 'send_multicast':
            $flex = $input['flex'] ?? null;
            $altText = $input['altText'] ?? 'Broadcast Message';
            $userIds = $input['userIds'] ?? [];
            
            if (!$flex || empty($userIds)) {
                throw new Exception('ข้อมูลไม่ครบ');
            }
            
            $message = [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flex
            ];
            
            // Send in chunks of 500
            $chunks = array_chunk($userIds, 500);
            $totalSent = 0;
            
            foreach ($chunks as $chunk) {
                $result = $line->multicastMessage($chunk, [$message]);
                if ($result['code'] === 200) {
                    $totalSent += count($chunk);
                }
            }
            
            echo json_encode(['success' => true, 'sent' => $totalSent]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
