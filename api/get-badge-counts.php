<?php
/**
 * API: Get Badge Counts
 * ไฟล์นี้ให้บริการ AJAX สำหรับดึงจำนวน Badge แบบ Real-time
 * เพื่อให้ UX ลื่นไหลและไม่ต้องโหลดหน้าใหม่
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบ Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// รับ Bot ID จาก Query String
$botId = isset($_GET['bot_id']) ? (int)$_GET['bot_id'] : null;

if (!$botId) {
    http_response_code(400);
    echo json_encode(['error' => 'bot_id is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // นับข้อความที่ยังไม่ได้อ่าน
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM messages 
        WHERE is_read = 0 
        AND direction = 'incoming' 
        AND (line_account_id = ? OR line_account_id IS NULL)
    ");
    $stmt->execute([$botId]);
    $unreadMessages = (int)$stmt->fetchColumn();
    
    // นับออเดอร์ที่รอดำเนินการ
    $pendingOrders = 0;
    
    // ตรวจสอบว่ามีตาราง orders หรือ transactions
    $ordersTable = null;
    try {
        $db->query("SELECT 1 FROM orders LIMIT 1");
        $ordersTable = 'orders';
    } catch (Exception $e) {
        try {
            $db->query("SELECT 1 FROM transactions LIMIT 1");
            $ordersTable = 'transactions';
        } catch (Exception $e) {}
    }
    
    if ($ordersTable) {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM {$ordersTable} 
            WHERE status = 'pending' 
            AND (line_account_id = ? OR line_account_id IS NULL)
        ");
        $stmt->execute([$botId]);
        $pendingOrders = (int)$stmt->fetchColumn();
    }
    
    // นับสลิปที่รอตรวจสอบ
    $pendingSlips = 0;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT ps.transaction_id) 
            FROM payment_slips ps 
            INNER JOIN transactions t ON ps.transaction_id = t.id 
            WHERE ps.status = 'pending' 
            AND (t.line_account_id = ? OR t.line_account_id IS NULL)
        ");
        $stmt->execute([$botId]);
        $pendingSlips = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // ตาราง payment_slips อาจไม่มี
    }
    
    // ส่งข้อมูลกลับเป็น JSON
    echo json_encode([
        'success' => true,
        'messages' => $unreadMessages,
        'orders' => $pendingOrders,
        'slips' => $pendingSlips,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
