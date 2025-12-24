<?php
/**
 * Telegram Bot Webhook - รับคำสั่งจาก Telegram และตอบกลับไป LINE
 */

// ต้อง return 200 OK ทันทีเพื่อป้องกัน Telegram ส่งซ้ำ
http_response_code(200);
header('Content-Type: application/json');

// Error handling - ไม่แสดง error
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    require_once 'classes/LineAPI.php';
    require_once 'classes/TelegramAPI.php';
} catch (Exception $e) {
    echo json_encode(['ok' => true]);
    exit;
}

$telegram = new TelegramAPI();
$line = new LineAPI();

// Handle webhook setup
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'set') {
        $webhookUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/telegram_webhook.php';
        $result = $telegram->setWebhook($webhookUrl);
        echo json_encode(['action' => 'setWebhook', 'url' => $webhookUrl, 'result' => $result], JSON_PRETTY_PRINT);
        exit;
    }
    
    if ($_GET['action'] === 'delete') {
        $result = $telegram->deleteWebhook();
        echo json_encode(['action' => 'deleteWebhook', 'result' => $result], JSON_PRETTY_PRINT);
        exit;
    }
    
    if ($_GET['action'] === 'info') {
        $result = $telegram->getWebhookInfo();
        echo json_encode(['action' => 'getWebhookInfo', 'result' => $result], JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['ok' => true]);
    exit;
}

// Get update from Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Always return OK to Telegram
if (!$update) {
    echo json_encode(['ok' => true]);
    exit;
}

// Connect to database
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo json_encode(['ok' => true]);
    exit;
}

// Process in background-like manner
try {
    // Handle callback query (inline button clicks)
    if (isset($update['callback_query'])) {
        handleCallbackQuery($update['callback_query'], $telegram, $db);
    }
    // Handle message
    elseif (isset($update['message'])) {
        handleMessage($update['message'], $telegram, $line, $db);
    }
} catch (Exception $e) {
    // Ignore errors, just return OK
}

echo json_encode(['ok' => true]);
exit;

/**
 * Handle callback query from inline buttons
 */
function handleCallbackQuery($callback, $telegram, $db) {
    $data = $callback['data'] ?? '';
    $chatId = $callback['message']['chat']['id'] ?? '';
    $callbackId = $callback['id'] ?? '';
    
    if (!$data || !$chatId) return;
    
    // Always answer callback first
    $telegram->answerCallbackQuery($callbackId);
    
    if (strpos($data, 'reply_') === 0) {
        $userId = substr($data, 6);
        
        $stmt = $db->prepare("SELECT display_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $telegram->sendMessage(
                "📝 <b>ตอบกลับถึง: {$user['display_name']}</b>\n\n" .
                "พิมพ์คำสั่ง:\n<code>/r {$userId} ข้อความ</code>",
                $chatId
            );
        }
    } elseif (strpos($data, 'profile_') === 0) {
        $userId = substr($data, 8);
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $text = "👤 <b>ข้อมูลผู้ใช้</b>\n\n";
            $text .= "📛 ชื่อ: {$user['display_name']}\n";
            $text .= "🆔 ID: {$user['id']}\n";
            $text .= "📅 เพิ่มเพื่อน: {$user['created_at']}\n";
            $text .= "🔒 สถานะ: " . ($user['is_blocked'] ? 'บล็อก' : 'ปกติ');
            
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM messages WHERE user_id = ?");
            $stmt->execute([$userId]);
            $msgCount = $stmt->fetch()['c'];
            $text .= "\n💬 ข้อความทั้งหมด: {$msgCount}";
            
            $telegram->sendMessage($text, $chatId);
        }
    }
}

/**
 * Handle text message commands
 */
function handleMessage($message, $telegram, $line, $db) {
    $chatId = $message['chat']['id'] ?? '';
    $text = $message['text'] ?? '';
    
    if (!$chatId || !$text) return;
    
    // Check if from authorized chat (allow private chat or specific group)
    $isAuthorized = ($chatId == TELEGRAM_CHAT_ID) || ($message['chat']['type'] === 'private');
    if (!$isAuthorized) return;
    
    // Command: /reply or /r
    if (preg_match('/^\/(reply|r)\s+(\d+)\s+(.+)$/s', $text, $matches)) {
        replyToLineUser($matches[2], trim($matches[3]), $telegram, $line, $db, $chatId);
        return;
    }
    
    // Command: /broadcast
    if (preg_match('/^\/broadcast\s+(.+)$/s', $text, $matches)) {
        broadcastMessage(trim($matches[1]), $telegram, $line, $db, $chatId);
        return;
    }
    
    // Command: /users
    if ($text === '/users' || $text === '/list') {
        listRecentUsers($telegram, $db, $chatId);
        return;
    }
    
    // Command: /stats
    if ($text === '/stats') {
        showStats($telegram, $db, $chatId);
        return;
    }
    
    // Command: /help or /start
    if ($text === '/help' || $text === '/start') {
        showHelp($telegram, $chatId);
        return;
    }
    
    // Quick reply - if message starts with user ID
    if (preg_match('/^(\d+)\s+(.+)$/s', $text, $matches)) {
        replyToLineUser($matches[1], trim($matches[2]), $telegram, $line, $db, $chatId);
        return;
    }
}

/**
 * Reply to LINE user
 */
function replyToLineUser($userId, $message, $telegram, $line, $db, $chatId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $telegram->sendMessage("❌ ไม่พบผู้ใช้ ID: {$userId}", $chatId);
        return;
    }
    
    if ($user['is_blocked']) {
        $telegram->sendMessage("❌ ผู้ใช้นี้บล็อก LINE OA แล้ว", $chatId);
        return;
    }
    
    $result = $line->pushMessage($user['line_user_id'], $message);
    
    if ($result['code'] === 200) {
        $stmt = $db->prepare("INSERT INTO messages (user_id, direction, message_type, content) VALUES (?, 'outgoing', 'text', ?)");
        $stmt->execute([$userId, $message]);
        $telegram->sendReplyConfirmation($user['display_name'], $message);
    } else {
        $telegram->sendError("ส่งไม่สำเร็จ: " . json_encode($result['body']));
    }
}

/**
 * Broadcast message
 */
function broadcastMessage($message, $telegram, $line, $db, $chatId) {
    $result = $line->broadcastMessage($message);
    
    if ($result['code'] === 200) {
        $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE is_blocked = 0");
        $count = $stmt->fetch()['c'];
        
        $stmt = $db->prepare("INSERT INTO broadcasts (title, content, target_type, sent_count, status, sent_at) VALUES (?, ?, 'all', ?, 'sent', NOW())");
        $stmt->execute(['Telegram Broadcast', $message, $count]);
        
        $telegram->sendMessage("✅ <b>Broadcast สำเร็จ!</b>\nส่งถึง ~{$count} คน", $chatId);
    } else {
        $telegram->sendError("Broadcast ไม่สำเร็จ");
    }
}

/**
 * List recent users
 */
function listRecentUsers($telegram, $db, $chatId) {
    $stmt = $db->query("SELECT u.id, u.display_name FROM users u WHERE is_blocked = 0 ORDER BY u.id DESC LIMIT 10");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        $telegram->sendMessage("📭 ยังไม่มีผู้ใช้", $chatId);
        return;
    }
    
    $text = "👥 <b>ผู้ใช้ล่าสุด</b>\n\n";
    foreach ($users as $user) {
        $text .= "🆔 <code>{$user['id']}</code> - {$user['display_name']}\n";
    }
    $text .= "\n💡 ตอบกลับ: <code>/r [ID] ข้อความ</code>";
    
    $telegram->sendMessage($text, $chatId);
}

/**
 * Show statistics
 */
function showStats($telegram, $db, $chatId) {
    $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE is_blocked = 0");
    $users = $stmt->fetch()['c'];
    
    $stmt = $db->query("SELECT COUNT(*) as c FROM messages WHERE DATE(created_at) = CURDATE()");
    $today = $stmt->fetch()['c'];
    
    $stmt = $db->query("SELECT COUNT(*) as c FROM messages");
    $total = $stmt->fetch()['c'];
    
    $text = "📊 <b>สถิติ</b>\n\n";
    $text .= "👥 ผู้ติดตาม: {$users}\n";
    $text .= "💬 วันนี้: {$today}\n";
    $text .= "📨 ทั้งหมด: {$total}";
    
    $telegram->sendMessage($text, $chatId);
}

/**
 * Show help
 */
function showHelp($telegram, $chatId) {
    $text = "🤖 <b>LINE OA Bot</b>\n\n";
    $text .= "<code>/r [ID] ข้อความ</code> - ตอบกลับ\n";
    $text .= "<code>/broadcast ข้อความ</code> - ส่งทุกคน\n";
    $text .= "<code>/users</code> - รายชื่อ\n";
    $text .= "<code>/stats</code> - สถิติ";
    
    $telegram->sendMessage($text, $chatId);
}
