<?php
/**
 * Debug Checkout API
 * ตรวจสอบปัญหา user not found
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/config.php';
require_once '../config/database.php';

$db = Database::getInstance()->getConnection();

// Get all input
$getParams = $_GET;
$postParams = $_POST;
$jsonInput = json_decode(file_get_contents('php://input'), true);

$lineUserId = $jsonInput['line_user_id'] ?? $_GET['line_user_id'] ?? $_POST['line_user_id'] ?? null;

$result = [
    'debug' => true,
    'get_params' => $getParams,
    'post_params' => $postParams,
    'json_input' => $jsonInput,
    'line_user_id_received' => $lineUserId,
];

// Check if user exists
if ($lineUserId) {
    $stmt = $db->prepare("SELECT id, line_user_id, display_name, line_account_id FROM users WHERE line_user_id = ?");
    $stmt->execute([$lineUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result['user_found'] = $user ? true : false;
    $result['user_data'] = $user;
    
    // Also check with LIKE
    $stmt = $db->prepare("SELECT id, line_user_id, display_name FROM users WHERE line_user_id LIKE ?");
    $stmt->execute(['%' . substr($lineUserId, -10) . '%']);
    $similarUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['similar_users'] = $similarUsers;
}

// Show some sample users
$stmt = $db->query("SELECT id, line_user_id, display_name, line_account_id FROM users ORDER BY id DESC LIMIT 5");
$result['sample_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check cart_items
if ($lineUserId) {
    $stmt = $db->prepare("
        SELECT c.*, u.line_user_id 
        FROM cart_items c 
        JOIN users u ON c.user_id = u.id 
        WHERE u.line_user_id = ?
    ");
    $stmt->execute([$lineUserId]);
    $result['cart_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
