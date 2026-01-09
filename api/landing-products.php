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
            
            // Check which table exists: products or business_items
            $tableName = 'products';
            try {
                $db->query("SELECT 1 FROM products LIMIT 1");
            } catch (PDOException $e) {
                try {
                    $db->query("SELECT 1 FROM business_items LIMIT 1");
                    $tableName = 'business_items';
                } catch (PDOException $e2) {
                    echo json_encode(['products' => [], 'error' => 'No products table']);
                    exit;
                }
            }
            
            // Build query based on table
            if ($tableName === 'business_items') {
                $sql = "SELECT id, item_name as name, item_code as sku, price, image_url 
                        FROM business_items 
                        WHERE (item_name LIKE ? OR item_code LIKE ?)";
            } else {
                $sql = "SELECT id, name, sku, price, image_url 
                        FROM products 
                        WHERE (name LIKE ? OR sku LIKE ?)";
            }
            
            $params = ["%{$query}%", "%{$query}%"];
            
            $sql .= " ORDER BY 2 ASC LIMIT 20";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['products' => $products, 'table' => $tableName, 'query' => $query]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action', 'products' => []]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'products' => []]);
}
