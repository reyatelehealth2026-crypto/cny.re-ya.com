<?php
/**
 * Dynamic Rich Menu API
 * API สำหรับจัดการ Dynamic Rich Menu
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';
require_once __DIR__ . '/../classes/DynamicRichMenu.php';

$db = Database::getInstance()->getConnection();

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$lineAccountId = $_POST['line_account_id'] ?? $_GET['line_account_id'] ?? 1;

try {
    $lineManager = new LineAccountManager($db);
    $line = $lineManager->getLineAPI($lineAccountId);
    $dynamicMenu = new DynamicRichMenu($db, $line, $lineAccountId);
    
    switch ($action) {
        // Trigger: เมื่อผู้ใช้ follow
        case 'on_follow':
            $lineUserId = $_POST['line_user_id'] ?? '';
            if (!$lineUserId) throw new Exception('Missing line_user_id');
            
            // หา user_id จาก line_user_id
            $stmt = $db->prepare("SELECT id FROM users WHERE line_user_id = ? AND line_account_id = ?");
            $stmt->execute([$lineUserId, $lineAccountId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $result = $dynamicMenu->assignRichMenuByRules($user['id'], $lineUserId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'User not found']);
            }
            break;

        // Trigger: เมื่อผู้ใช้ลงทะเบียน
        case 'on_register':
            $userId = $_POST['user_id'] ?? '';
            $lineUserId = $_POST['line_user_id'] ?? '';
            if (!$userId || !$lineUserId) throw new Exception('Missing user_id or line_user_id');
            
            $result = $dynamicMenu->assignRichMenuByRules($userId, $lineUserId);
            echo json_encode($result);
            break;
            
        // Trigger: เมื่อ tag เปลี่ยน
        case 'on_tag_change':
            $userId = $_POST['user_id'] ?? '';
            $lineUserId = $_POST['line_user_id'] ?? '';
            if (!$userId || !$lineUserId) throw new Exception('Missing user_id or line_user_id');
            
            $result = $dynamicMenu->assignRichMenuByRules($userId, $lineUserId);
            echo json_encode($result);
            break;
            
        // Trigger: เมื่อ tier เปลี่ยน
        case 'on_tier_change':
            $userId = $_POST['user_id'] ?? '';
            $lineUserId = $_POST['line_user_id'] ?? '';
            if (!$userId || !$lineUserId) throw new Exception('Missing user_id or line_user_id');
            
            $result = $dynamicMenu->assignRichMenuByRules($userId, $lineUserId);
            echo json_encode($result);
            break;
            
        // Manual: กำหนด Rich Menu โดยตรง
        case 'assign':
            $userId = $_POST['user_id'] ?? '';
            $lineUserId = $_POST['line_user_id'] ?? '';
            $richMenuId = $_POST['rich_menu_id'] ?? '';
            if (!$userId || !$lineUserId || !$richMenuId) throw new Exception('Missing required fields');
            
            $result = $dynamicMenu->assignRichMenu($userId, $lineUserId, $richMenuId, 'api');
            echo json_encode($result);
            break;
            
        // Manual: ประเมินตามกฎ
        case 'evaluate':
            $userId = $_POST['user_id'] ?? '';
            $lineUserId = $_POST['line_user_id'] ?? '';
            if (!$userId || !$lineUserId) throw new Exception('Missing user_id or line_user_id');
            
            $result = $dynamicMenu->assignRichMenuByRules($userId, $lineUserId);
            echo json_encode($result);
            break;
            
        // Bulk: ประเมินทั้งหมด
        case 'bulk_evaluate':
            $limit = (int)($_POST['limit'] ?? 100);
            $result = $dynamicMenu->reEvaluateAllUsers($limit);
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        // Get: ดึง Rich Menu ปัจจุบันของผู้ใช้
        case 'get_user_menu':
            $userId = $_GET['user_id'] ?? '';
            if (!$userId) throw new Exception('Missing user_id');
            
            $menu = $dynamicMenu->getUserCurrentRichMenu($userId);
            echo json_encode(['success' => true, 'menu' => $menu]);
            break;
            
        // Get: ดึงกฎทั้งหมด
        case 'get_rules':
            $rules = $dynamicMenu->getRules();
            echo json_encode(['success' => true, 'rules' => $rules]);
            break;
            
        // Get: ดึงสถิติ
        case 'get_stats':
            $stats = $dynamicMenu->getStatistics();
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
