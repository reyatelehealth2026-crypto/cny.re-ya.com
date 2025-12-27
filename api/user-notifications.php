<?php
/**
 * User Notification Preferences API
 * Requirements: 14.1, 14.2, 14.3
 * - Display categorized notification toggles
 * - Save preferences immediately via API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../config/database.php';

$db = Database::getInstance()->getConnection();

// Ensure table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS user_notification_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        line_user_id VARCHAR(50),
        line_account_id INT,
        order_updates TINYINT(1) DEFAULT 1 COMMENT 'Order confirmation, shipping, delivery',
        promotions TINYINT(1) DEFAULT 1 COMMENT 'Sales and promotions',
        appointment_reminders TINYINT(1) DEFAULT 1 COMMENT '24hr and 30min before',
        drug_reminders TINYINT(1) DEFAULT 1 COMMENT 'Medication reminders',
        health_tips TINYINT(1) DEFAULT 0 COMMENT 'Health tips and articles',
        price_alerts TINYINT(1) DEFAULT 1 COMMENT 'Wishlist price drops',
        restock_alerts TINYINT(1) DEFAULT 1 COMMENT 'Back in stock alerts',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id),
        INDEX idx_line_user (line_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;

$action = $input['action'] ?? $_GET['action'] ?? 'get';
$lineUserId = $input['line_user_id'] ?? $_GET['line_user_id'] ?? '';
$lineAccountId = $input['line_account_id'] ?? $_GET['line_account_id'] ?? null;

// Get user_id from line_user_id
$userId = null;
if ($lineUserId) {
    $stmt = $db->prepare("SELECT id FROM users WHERE line_user_id = ?");
    $stmt->execute([$lineUserId]);
    $userId = $stmt->fetchColumn();
}

try {
    switch ($action) {
        case 'get':
            // Get notification preferences
            if (!$userId && !$lineUserId) {
                echo json_encode([
                    'success' => true,
                    'preferences' => getDefaultPreferences()
                ]);
                exit;
            }
            
            $sql = "SELECT * FROM user_notification_preferences WHERE ";
            if ($userId) {
                $sql .= "user_id = ?";
                $params = [$userId];
            } else {
                $sql .= "line_user_id = ?";
                $params = [$lineUserId];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prefs) {
                // Return defaults if no preferences saved
                echo json_encode([
                    'success' => true,
                    'preferences' => getDefaultPreferences()
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'preferences' => [
                        'order_updates' => (bool)$prefs['order_updates'],
                        'promotions' => (bool)$prefs['promotions'],
                        'appointment_reminders' => (bool)$prefs['appointment_reminders'],
                        'drug_reminders' => (bool)$prefs['drug_reminders'],
                        'health_tips' => (bool)$prefs['health_tips'],
                        'price_alerts' => (bool)$prefs['price_alerts'],
                        'restock_alerts' => (bool)$prefs['restock_alerts']
                    ]
                ]);
            }
            break;
            
        case 'update':
            // Update notification preferences
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            // Get current preferences or create new
            $stmt = $db->prepare("SELECT id FROM user_notification_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetchColumn();
            
            // Build update data
            $allowedFields = ['order_updates', 'promotions', 'appointment_reminders', 
                             'drug_reminders', 'health_tips', 'price_alerts', 'restock_alerts'];
            
            if ($exists) {
                // Update existing
                $updates = [];
                $params = [];
                
                foreach ($allowedFields as $field) {
                    if (isset($input[$field])) {
                        $updates[] = "$field = ?";
                        $params[] = $input[$field] ? 1 : 0;
                    }
                }
                
                if (!empty($updates)) {
                    $params[] = $userId;
                    $sql = "UPDATE user_notification_preferences SET " . implode(', ', $updates) . " WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                }
            } else {
                // Insert new
                $values = [
                    'user_id' => $userId,
                    'line_user_id' => $lineUserId,
                    'line_account_id' => $lineAccountId
                ];
                
                foreach ($allowedFields as $field) {
                    $values[$field] = isset($input[$field]) ? ($input[$field] ? 1 : 0) : 1;
                }
                
                $columns = implode(', ', array_keys($values));
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                
                $sql = "INSERT INTO user_notification_preferences ($columns) VALUES ($placeholders)";
                $stmt = $db->prepare($sql);
                $stmt->execute(array_values($values));
            }
            
            echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าแล้ว']);
            break;
            
        case 'toggle':
            // Toggle single preference
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            $category = $input['category'] ?? '';
            $enabled = isset($input['enabled']) ? ($input['enabled'] ? 1 : 0) : null;
            
            $allowedCategories = ['order_updates', 'promotions', 'appointment_reminders', 
                                 'drug_reminders', 'health_tips', 'price_alerts', 'restock_alerts'];
            
            if (!in_array($category, $allowedCategories)) {
                echo json_encode(['success' => false, 'error' => 'Invalid category']);
                exit;
            }
            
            // Check if exists
            $stmt = $db->prepare("SELECT id, $category FROM user_notification_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Toggle or set value
                $newValue = ($enabled !== null) ? $enabled : ($row[$category] ? 0 : 1);
                $stmt = $db->prepare("UPDATE user_notification_preferences SET $category = ? WHERE user_id = ?");
                $stmt->execute([$newValue, $userId]);
            } else {
                // Create with default values and set this one
                $newValue = ($enabled !== null) ? $enabled : 1;
                $stmt = $db->prepare("INSERT INTO user_notification_preferences 
                    (user_id, line_user_id, line_account_id, $category) 
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $lineUserId, $lineAccountId, $newValue]);
            }
            
            echo json_encode([
                'success' => true, 
                'category' => $category,
                'enabled' => (bool)$newValue,
                'message' => $newValue ? 'เปิดการแจ้งเตือนแล้ว' : 'ปิดการแจ้งเตือนแล้ว'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getDefaultPreferences() {
    return [
        'order_updates' => true,
        'promotions' => true,
        'appointment_reminders' => true,
        'drug_reminders' => true,
        'health_tips' => false,
        'price_alerts' => true,
        'restock_alerts' => true
    ];
}
