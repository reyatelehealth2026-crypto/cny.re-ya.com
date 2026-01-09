<?php
/**
 * Landing Products API
 * API สำหรับค้นหาสินค้าเพื่อเลือกแสดงบน Landing Page
 */

header('Content-Type: application/json');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Simple auth check for AJAX
if (empty($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'products' => []]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? '';
    $lineAccountId = $_SESSION['current_bot_id'] ?? null;

    switch ($action) {
        case 'search':
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                echo json_encode(['products' => []]);
                exit;
            }
            
            $sql = "SELECT id, name, sku, price, image_url 
                    FROM products 
                    WHERE is_active = 1 
                    AND (name LIKE ? OR sku LIKE ?)";
            $params = ["%{$query}%", "%{$query}%"];
            
            if ($lineAccountId !== null) {
                $sql .= " AND (line_account_id = ? OR line_account_id IS NULL)";
                $params[] = $lineAccountId;
            }
            
            $sql .= " ORDER BY name ASC LIMIT 20";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['products' => $products]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action', 'products' => []]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'products' => []]);
}
