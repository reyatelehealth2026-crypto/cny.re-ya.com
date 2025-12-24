<?php
/**
 * LINE Content API
 * ดึงรูปภาพ/วิดีโอ/ไฟล์จาก LINE Message API
 */
header('Access-Control-Allow-Origin: *');

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/LineAPI.php';
require_once '../classes/LineAccountManager.php';

$messageId = $_GET['id'] ?? null;
$accountId = $_GET['account'] ?? null;

if (!$messageId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get LINE API instance
    $line = null;
    if ($accountId) {
        $manager = new LineAccountManager($db);
        $line = $manager->getLineAPI($accountId);
    } else {
        // Try to get default account
        $stmt = $db->query("SELECT id FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC LIMIT 1");
        $account = $stmt->fetch();
        if ($account) {
            $manager = new LineAccountManager($db);
            $line = $manager->getLineAPI($account['id']);
        } else {
            $line = new LineAPI();
        }
    }
    
    // Get content from LINE (returns binary data)
    $content = $line->getMessageContent($messageId);
    
    if ($content && strlen($content) > 100) {
        // Detect content type from binary data
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->buffer($content) ?: 'image/jpeg';
        
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
        echo $content;
    } else {
        // Return placeholder image
        http_response_code(404);
        header('Content-Type: image/svg+xml');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
            <rect fill="#f3f4f6" width="200" height="200"/>
            <text x="100" y="100" text-anchor="middle" fill="#9ca3af" font-size="14">รูปภาพไม่พร้อมใช้งาน</text>
        </svg>';
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
