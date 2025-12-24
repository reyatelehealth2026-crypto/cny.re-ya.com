<?php
/**
 * CNY Pharmacy Sync API
 * Endpoints สำหรับ sync สินค้าจาก CNY Pharmacy
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

// Check authentication (optional - add your auth check here)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// if (!isset($_SESSION['admin_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
//     exit;
// }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$cnyApi = new CnyPharmacyAPI($db);

try {
    switch ($action) {
        case 'test':
            // Test API connection
            $result = $cnyApi->testConnection();
            echo json_encode($result);
            break;
            
        case 'sync_all':
            // Sync all products
            $options = [
                'update_existing' => ($_POST['update_existing'] ?? '1') === '1',
                'default_category_id' => $_POST['category_id'] ?? null
            ];
            $result = $cnyApi->syncAllProducts($options);
            echo json_encode($result);
            break;
            
        case 'sync_one':
            // Sync single product by SKU
            $sku = $_POST['sku'] ?? $_GET['sku'] ?? '';
            if (!$sku) {
                throw new Exception('SKU is required');
            }
            
            $apiResult = $cnyApi->getProductBySku($sku);
            if (!$apiResult['success']) {
                throw new Exception('Product not found in CNY API');
            }
            
            $syncResult = $cnyApi->syncProduct($apiResult['data']);
            echo json_encode(['success' => true, 'result' => $syncResult]);
            break;
            
        case 'get_products':
            // Get products from CNY API
            $result = $cnyApi->getAllProducts();
            echo json_encode($result);
            break;
            
        case 'get_product':
            // Get single product from CNY API
            $sku = $_GET['sku'] ?? '';
            if (!$sku) {
                throw new Exception('SKU is required');
            }
            $result = $cnyApi->getProductBySku($sku);
            echo json_encode($result);
            break;
            
        case 'search':
            // Search products from CNY API
            $keyword = $_GET['q'] ?? $_GET['keyword'] ?? '';
            if (!$keyword) {
                throw new Exception('Search keyword is required');
            }
            $result = $cnyApi->searchProducts($keyword);
            echo json_encode($result);
            break;
            
        case 'stats':
            // Get local sync stats
            $stats = $cnyApi->getSyncStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'find_local':
            // Find product in local DB by SKU or barcode
            $sku = $_GET['sku'] ?? '';
            $barcode = $_GET['barcode'] ?? '';
            
            $product = null;
            if ($sku) {
                $product = $cnyApi->findBySku($sku);
            } elseif ($barcode) {
                $product = $cnyApi->findByBarcode($barcode);
            }
            
            echo json_encode([
                'success' => true,
                'found' => $product !== null,
                'product' => $product
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'available_actions' => [
                    'test' => 'Test API connection',
                    'sync_all' => 'Sync all products (POST)',
                    'sync_one' => 'Sync single product by SKU (POST: sku)',
                    'get_products' => 'Get all products from CNY API',
                    'get_product' => 'Get product by SKU (GET: sku)',
                    'search' => 'Search products (GET: q or keyword)',
                    'stats' => 'Get local sync stats',
                    'find_local' => 'Find product in local DB (GET: sku or barcode)'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
