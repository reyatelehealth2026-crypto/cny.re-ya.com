<?php
/**
 * Messages V2 - Chat-focused with AI Assistant (Gemini)
 * - เน้นการแชทกับลูกค้า
 * - AI ช่วยเขียนคำตอบ, ตรวจไวยากรณ์, หาข้อมูล
 * - รายละเอียดลูกค้าแบบละเอียด
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();

// Get Gemini API Key from settings or config
$geminiApiKey = '';
try {
    $stmt = $db->query("SELECT value FROM settings WHERE `key` = 'gemini_api_key' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $geminiApiKey = $row['value'] ?? '';
} catch (Exception $e) {}
if (!$geminiApiKey && defined('GEMINI_API_KEY')) {
    $geminiApiKey = GEMINI_API_KEY;
}

// Auto-create tables - ใช้ user_tags และ user_tag_assignments ตาม user-tags.php
try {
    // ตาราง user_tags สำหรับเก็บ tags (ใช้ร่วมกับ user-tags.php)
    $stmt = $db->query("SHOW TABLES LIKE 'user_tags'");
    if ($stmt->rowCount() == 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS `user_tags` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `line_account_id` INT DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `color` varchar(7) DEFAULT '#3B82F6',
            `description` TEXT,
            `auto_assign_rules` JSON DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX idx_line_account (line_account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    // ตาราง user_tag_assignments สำหรับ assign tags ให้ users
    $stmt = $db->query("SHOW TABLES LIKE 'user_tag_assignments'");
    if ($stmt->rowCount() == 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `tag_id` int(11) NOT NULL,
            `assigned_by` VARCHAR(50) DEFAULT 'manual',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_tag` (`user_id`,`tag_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $db->exec("CREATE TABLE IF NOT EXISTS `user_notes` (`id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `note` text, `created_by` int(11), `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS `settings` (`id` int(11) NOT NULL AUTO_INCREMENT, `key` varchar(100) NOT NULL, `value` text, PRIMARY KEY (`id`), UNIQUE KEY `key` (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}


// AJAX Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'send_message':
                $userId = $_POST['user_id'];
                $message = trim($_POST['message'] ?? '');
                if (!$userId || !$message) throw new Exception("Invalid data");
                
                $stmt = $db->prepare("SELECT line_user_id, line_account_id, reply_token, reply_token_expires FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) throw new Exception("User not found");
                
                $lineManager = new LineAccountManager($db);
                $line = $lineManager->getLineAPI($user['line_account_id']);
                // ใช้ sendMessage เพื่อเช็ค replyToken ก่อน (ฟรี!) - with fallback
                if (method_exists($line, 'sendMessage')) {
                    $result = $line->sendMessage($user['line_user_id'], $message, $user['reply_token'] ?? null, $user['reply_token_expires'] ?? null, $db, $userId);
                } else {
                    $result = $line->pushMessage($user['line_user_id'], [['type' => 'text', 'text' => $message]]);
                    $result['method'] = 'push';
                }
                
                if ($result['code'] === 200) {
                    $stmt = $db->prepare("INSERT INTO messages (line_account_id, user_id, direction, message_type, content, created_at, is_read) VALUES (?, ?, 'outgoing', 'text', ?, NOW(), 1)");
                    $stmt->execute([$user['line_account_id'], $userId, $message]);
                    echo json_encode(['success' => true, 'content' => $message, 'time' => date('H:i'), 'method' => $result['method'] ?? 'push']);
                } else {
                    throw new Exception("LINE API Error: " . json_encode($result));
                }
                break;
                
            case 'ai_assist':
                $type = $_POST['type'] ?? 'reply'; // reply, grammar, search
                $text = trim($_POST['text'] ?? '');
                $context = trim($_POST['context'] ?? '');
                
                if (!$geminiApiKey) {
                    throw new Exception("กรุณาตั้งค่า Gemini API Key ที่ ai-settings.php");
                }
                
                // System context สำหรับร้านอายุรกรรม/สมุนไพร
                $systemContext = "คุณเป็นผู้เชี่ยวชาญด้านอายุรกรรมและสมุนไพรไทย ทำงานให้ร้านขายสินค้าสุขภาพออนไลน์ที่จำหน่าย:
- สมุนไพรไทย, ยาแผนโบราณ
- อาหารเสริม, วิตามิน
- ผลิตภัณฑ์บำรุงสุขภาพ
- น้ำมันนวด, ยาหม่อง, ลูกประคบ

หลักการตอบ:
- ใช้ภาษาสุภาพ อบอุ่น เป็นกันเอง
- ให้ข้อมูลที่ถูกต้องตามหลักอายุรกรรม
- แนะนำสินค้าที่เหมาะสมกับอาการ
- เตือนเรื่องข้อควรระวัง/ข้อห้ามใช้เมื่อจำเป็น
- ไม่อ้างว่ารักษาโรคได้ แต่ช่วยบรรเทาอาการ";

                $prompt = '';
                switch ($type) {
                    case 'reply':
                        $prompt = "$systemContext\n\nลูกค้าถามว่า: \"$text\"\n\nบริบทเพิ่มเติม: $context\n\nเขียนคำตอบสั้นๆ กระชับ เป็นภาษาไทย ที่ช่วยแนะนำและตอบคำถามลูกค้า:";
                        break;
                    case 'grammar':
                        $prompt = "ตรวจสอบและแก้ไขไวยากรณ์ภาษาไทยของข้อความนี้ ให้สุภาพและเป็นมืออาชีพสำหรับร้านสมุนไพร:\n\n\"$text\"\n\nข้อความที่แก้ไขแล้ว:";
                        break;
                    case 'search':
                        $prompt = "$systemContext\n\nค้นหาข้อมูลเกี่ยวกับ: \"$text\"\n\nให้ข้อมูลสรรพคุณ วิธีใช้ และข้อควรระวัง สั้นๆ:";
                        break;
                    case 'summarize':
                        $prompt = "สรุปบทสนทนากับลูกค้าร้านสมุนไพรนี้เป็นประเด็นสำคัญ:\n\n$text\n\nสรุป (อาการ, สินค้าที่สนใจ, ข้อกังวล):";
                        break;
                    case 'recommend':
                        $prompt = "$systemContext\n\nลูกค้ามีอาการ/ความต้องการ: \"$text\"\n\nแนะนำสมุนไพร/ผลิตภัณฑ์ที่เหมาะสม พร้อมเหตุผลสั้นๆ:";
                        break;
                    case 'caution':
                        $prompt = "$systemContext\n\nสมุนไพร/ผลิตภัณฑ์: \"$text\"\n\nบอกข้อควรระวัง ข้อห้ามใช้ และกลุ่มที่ไม่ควรใช้:";
                        break;
                }
                
                $response = callGeminiAPI($geminiApiKey, $prompt);
                echo json_encode(['success' => true, 'result' => $response]);
                break;
                
            case 'save_note':
                $userId = $_POST['user_id'];
                $note = trim($_POST['note'] ?? '');
                if (!$userId || !$note) throw new Exception("ข้อมูลไม่ครบ");
                
                $stmt = $db->prepare("INSERT INTO user_notes (user_id, note, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $note]);
                echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'note' => $note, 'created_at' => date('d/m/Y H:i')]);
                break;
                
            case 'delete_note':
                $noteId = $_POST['note_id'];
                $stmt = $db->prepare("DELETE FROM user_notes WHERE id = ?");
                $stmt->execute([$noteId]);
                echo json_encode(['success' => true]);
                break;
                
            case 'update_tags':
                $userId = $_POST['user_id'];
                $tagId = $_POST['tag_id'];
                $operation = $_POST['operation'];
                
                if ($operation === 'add') {
                    $stmt = $db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by) VALUES (?, ?, 'manual')");
                    $stmt->execute([$userId, $tagId]);
                } else {
                    $stmt = $db->prepare("DELETE FROM user_tag_assignments WHERE user_id = ? AND tag_id = ?");
                    $stmt->execute([$userId, $tagId]);
                }
                
                $stmt = $db->prepare("SELECT t.* FROM user_tags t JOIN user_tag_assignments uta ON t.id = uta.tag_id WHERE uta.user_id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true, 'tags' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                break;
                
            case 'save_gemini_key':
                $key = trim($_POST['api_key'] ?? '');
                $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('gemini_api_key', ?) ON DUPLICATE KEY UPDATE `value` = ?");
                $stmt->execute([$key, $key]);
                echo json_encode(['success' => true]);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function callGeminiAPI($apiKey, $prompt) {
    // Try different model names (updated for 2024-2025)
    $models = [
        'gemini-2.0-flash',
        'gemini-2.5-flash',
        'gemini-flash-latest',
        'gemini-2.0-flash-lite'
    ];
    
    $lastError = '';
    
    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
        
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $lastError = "cURL Error: $curlError";
            continue;
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }
            $lastError = "Invalid response format";
            continue;
        }
        
        // Parse error message
        $errorData = json_decode($response, true);
        $lastError = $errorData['error']['message'] ?? "HTTP $httpCode";
        
        // If 404, try next model
        if ($httpCode === 404) {
            continue;
        }
        
        // Other errors, stop trying
        break;
    }
    
    throw new Exception("Gemini API Error: $lastError");
}

// Alternative: Use OpenAI if available
function callOpenAI($apiKey, $prompt) {
    $url = "https://api.openai.com/v1/chat/completions";
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("OpenAI API Error: HTTP $httpCode");
    }
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? 'ไม่สามารถสร้างคำตอบได้';
}

$pageTitle = 'Messages';
require_once 'includes/header.php';
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// Get Users List with last message
$sql = "SELECT u.*, 
        (SELECT content FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_msg,
        (SELECT message_type FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_type,
        (SELECT created_at FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND direction = 'incoming' AND is_read = 0) as unread
        FROM users u WHERE u.line_account_id = ? ORDER BY last_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$currentBotId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Selected User
$selectedUser = null;
$messages = [];
$userTags = [];
$allTags = [];
$userNotes = [];
$userOrders = [];
$orderCount = 0;
$totalSpent = 0;
$loyaltyPoints = 0;

if (isset($_GET['user'])) {
    $uid = $_GET['user'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedUser) {
        // Mark as read
        $db->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND direction = 'incoming'")->execute([$uid]);
        
        // Get messages
        $stmt = $db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$uid]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tags - ใช้ user_tags และ user_tag_assignments
        try {
            $stmt = $db->prepare("SELECT t.* FROM user_tags t JOIN user_tag_assignments uta ON t.id = uta.tag_id WHERE uta.user_id = ?");
            $stmt->execute([$uid]);
            $userTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $db->prepare("SELECT * FROM user_tags WHERE line_account_id = ? OR line_account_id IS NULL ORDER BY name");
            $stmt->execute([$currentBotId]);
            $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        // Get notes
        try {
            $stmt = $db->prepare("SELECT * FROM user_notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$uid]);
            $userNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        // Get orders
        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$selectedUser['line_user_id']]);
            $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ?");
            $stmt->execute([$selectedUser['line_user_id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $orderCount = $stats['cnt'] ?? 0;
            $totalSpent = $stats['total'] ?? 0;
        } catch (Exception $e) {}
        
        // Get loyalty points
        $loyaltyPoints = $selectedUser['loyalty_points'] ?? 0;
    }
}

function getMessagePreview($content, $type) {
    if ($content === null) return '';
    if ($type === 'image') return '📷 รูปภาพ';
    if ($type === 'sticker') return '😊 สติกเกอร์';
    if ($type === 'flex') return '📋 Flex Message';
    return mb_strlen($content) > 25 ? mb_substr($content, 0, 25) . '...' : $content;
}

function getMembershipBadge($level) {
    $badges = [
        'bronze' => ['🥉', 'Bronze', 'bg-amber-100 text-amber-700'],
        'silver' => ['🥈', 'Silver', 'bg-gray-100 text-gray-700'],
        'gold' => ['🥇', 'Gold', 'bg-yellow-100 text-yellow-700'],
        'platinum' => ['💎', 'Platinum', 'bg-purple-100 text-purple-700']
    ];
    return $badges[$level] ?? $badges['bronze'];
}
?>


<style>
.chat-scroll::-webkit-scrollbar { width: 5px; }
.chat-scroll::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
.chat-bubble { white-space: pre-wrap; word-wrap: break-word; line-height: 1.5; }
.chat-incoming { background: #fff; color: #1E293B; border-radius: 0 16px 16px 16px; border: 1px solid #E2E8F0; }
.chat-outgoing { background: linear-gradient(135deg, #10B981, #059669); color: white; border-radius: 16px 0 16px 16px; }
.user-item.active { background: linear-gradient(90deg, #DCFCE7 0%, #F0FDF4 100%); border-left: 3px solid #10B981; }
.tag-badge { font-size: 0.6rem; padding: 2px 6px; border-radius: 9999px; font-weight: 500; }
.tag-blue { background: #DBEAFE; color: #1E40AF; }
.tag-red { background: #FEE2E2; color: #991B1B; }
.tag-green { background: #D1FAE5; color: #065F46; }
.tag-yellow { background: #FEF3C7; color: #92400E; }
.tag-gray { background: #F3F4F6; color: #374151; }
.ai-panel { background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%); }
.ai-btn { transition: all 0.2s; }
.ai-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
</style>

<div class="h-[calc(100vh-80px)] flex bg-white rounded-xl shadow-lg border overflow-hidden">

    <!-- LEFT: User List -->
    <div id="sidebar" class="w-72 bg-white border-r flex flex-col">
        <div class="p-3 border-b bg-gradient-to-r from-green-500 to-emerald-600">
            <h2 class="text-white font-bold flex items-center">
                <i class="fas fa-comments mr-2"></i>Messages
                <span class="ml-auto text-xs bg-white/20 px-2 py-0.5 rounded-full"><?= count($users) ?></span>
            </h2>
        </div>
        <div class="p-2 border-b">
            <input type="text" id="userSearch" placeholder="🔍 ค้นหาลูกค้า..." 
                   class="w-full px-3 py-2 bg-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none" 
                   onkeyup="filterUsers(this.value)">
        </div>
        <div class="flex-1 overflow-y-auto chat-scroll">
            <?php if (empty($users)): ?>
                <div class="p-6 text-center text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p class="text-sm">ยังไม่มีแชท</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <a href="?user=<?= $user['id'] ?>" 
                   class="user-item block p-3 hover:bg-green-50 border-b border-gray-50 <?= ($selectedUser && $selectedUser['id'] == $user['id']) ? 'active' : '' ?>" 
                   data-name="<?= strtolower($user['display_name']) ?>">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-shrink-0">
                            <img src="<?= $user['picture_url'] ?: 'https://via.placeholder.com/40' ?>" 
                                 class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
                            <?php if ($user['unread'] > 0): ?>
                            <div class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full font-bold">
                                <?= $user['unread'] > 9 ? '9+' : $user['unread'] ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline">
                                <h3 class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($user['display_name']) ?></h3>
                                <span class="text-[10px] text-gray-400"><?= $user['last_time'] ? date('H:i', strtotime($user['last_time'])) : '' ?></span>
                            </div>
                            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars(getMessagePreview($user['last_msg'], $user['last_type'])) ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>


    <!-- CENTER: Chat Area -->
    <div class="flex-1 flex flex-col bg-[#F8FAFC] min-w-0">
        <?php if ($selectedUser): ?>
        
        <!-- Chat Header -->
        <div class="h-14 bg-white border-b flex items-center justify-between px-4 flex-shrink-0 shadow-sm">
            <div class="flex items-center gap-3">
                <img src="<?= $selectedUser['picture_url'] ?: 'https://via.placeholder.com/40' ?>" class="w-10 h-10 rounded-full border-2 border-green-500">
                <div>
                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($selectedUser['display_name']) ?></h3>
                    <div class="flex gap-1 flex-wrap">
                        <?php foreach ($userTags as $tag): ?>
                        <span class="tag-badge" style="background-color: <?= htmlspecialchars($tag['color']) ?>20; color: <?= htmlspecialchars($tag['color']) ?>;"><?= htmlspecialchars($tag['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="toggleAIPanel()" class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-sm font-medium" title="AI Assistant">
                    <i class="fas fa-robot mr-1"></i>AI
                </button>
                <button onclick="toggleCustomerPanel()" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm" title="ข้อมูลลูกค้า">
                    <i class="fas fa-user"></i>
                </button>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3 chat-scroll">
            <?php foreach ($messages as $msg): 
                $isMe = ($msg['direction'] === 'outgoing');
                $content = $msg['content'];
                $type = $msg['message_type'];
                $json = (in_array($type, ['flex', 'sticker'])) ? json_decode($content, true) : null;
            ?>
            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?> group">
                <?php if (!$isMe): ?>
                <img src="<?= $selectedUser['picture_url'] ?>" class="w-7 h-7 rounded-full self-end mr-2">
                <?php endif; ?>
                <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?>" style="max-width:65%">
                    <?php if ($type === 'text'): ?>
                        <div class="chat-bubble px-4 py-2.5 text-sm shadow-sm <?= $isMe ? 'chat-outgoing' : 'chat-incoming' ?>">
                            <?= nl2br(htmlspecialchars($content ?? '')) ?>
                        </div>
                    <?php elseif ($type === 'image'): ?>
                        <img src="<?= htmlspecialchars($content ?? '') ?>" class="rounded-xl max-w-[200px] border shadow-sm cursor-pointer hover:opacity-90" onclick="window.open(this.src)">
                    <?php elseif ($type === 'sticker' && $json): ?>
                        <img src="https://stickershop.line-scdn.net/stickershop/v1/sticker/<?= $json['stickerId'] ?>/android/sticker.png" class="w-20">
                    <?php elseif ($json): ?>
                        <div class="bg-white rounded-lg border p-3 text-xs text-gray-500"><i class="fas fa-file-alt mr-1"></i>Flex Message</div>
                    <?php endif; ?>
                    <div class="text-[10px] text-gray-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <?= date('H:i', strtotime($msg['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- AI Assistant Panel (Collapsible) -->
        <div id="aiPanel" class="hidden border-t ai-panel">
            <div class="p-3">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-bold text-indigo-700"><i class="fas fa-robot mr-1"></i>AI Assistant (Gemini)</h4>
                    <button onclick="toggleAIPanel()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                
                <?php if (!$geminiApiKey): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-2">
                    <p class="text-xs text-yellow-700 mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>กรุณาตั้งค่า Gemini API Key</p>
                    <div class="flex gap-2">
                        <input type="text" id="geminiKeyInput" placeholder="AIza..." class="flex-1 px-2 py-1 border rounded text-xs">
                        <button onclick="saveGeminiKey()" class="px-3 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">บันทึก</button>
                    </div>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <button onclick="aiAssist('reply')" class="ai-btn px-3 py-2 bg-indigo-500 text-white rounded-lg text-xs font-medium hover:bg-indigo-600">
                        <i class="fas fa-reply mr-1"></i>ตอบลูกค้า
                    </button>
                    <button onclick="aiAssist('recommend')" class="ai-btn px-3 py-2 bg-green-500 text-white rounded-lg text-xs font-medium hover:bg-green-600">
                        <i class="fas fa-leaf mr-1"></i>แนะนำสมุนไพร
                    </button>
                    <button onclick="aiAssist('search')" class="ai-btn px-3 py-2 bg-blue-500 text-white rounded-lg text-xs font-medium hover:bg-blue-600">
                        <i class="fas fa-search mr-1"></i>หาสรรพคุณ
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <button onclick="aiAssist('caution')" class="ai-btn px-3 py-2 bg-orange-500 text-white rounded-lg text-xs font-medium hover:bg-orange-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>ข้อควรระวัง
                    </button>
                    <button onclick="aiAssist('grammar')" class="ai-btn px-3 py-2 bg-purple-500 text-white rounded-lg text-xs font-medium hover:bg-purple-600">
                        <i class="fas fa-spell-check mr-1"></i>ตรวจไวยากรณ์
                    </button>
                    <button onclick="aiAssist('summarize')" class="ai-btn px-3 py-2 bg-teal-500 text-white rounded-lg text-xs font-medium hover:bg-teal-600">
                        <i class="fas fa-compress-alt mr-1"></i>สรุปแชท
                    </button>
                </div>
                <div id="aiResult" class="hidden bg-white rounded-lg border p-3">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-medium text-gray-500">ผลลัพธ์ AI:</span>
                        <button onclick="useAIResult()" class="text-xs text-green-600 hover:text-green-700 font-medium">
                            <i class="fas fa-check mr-1"></i>ใช้ข้อความนี้
                        </button>
                    </div>
                    <p id="aiResultText" class="text-sm text-gray-700"></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white border-t flex-shrink-0">
            <form id="sendForm" class="flex gap-2 items-end" onsubmit="sendMessage(event)">
                <input type="hidden" name="user_id" value="<?= $selectedUser['id'] ?>">
                <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-2 focus-within:ring-2 focus-within:ring-green-500">
                    <textarea name="message" id="messageInput" rows="1" 
                              class="w-full bg-transparent border-0 outline-none text-sm resize-none max-h-24" 
                              placeholder="พิมพ์ข้อความ..." 
                              oninput="autoResize(this)"></textarea>
                </div>
                <button type="submit" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
        
        <?php else: ?>
        <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
            <i class="far fa-comments text-6xl mb-4 text-gray-300"></i>
            <p class="text-lg font-medium">เลือกแชทเพื่อเริ่มสนทนา</p>
            <p class="text-sm">เลือกลูกค้าจากรายการด้านซ้าย</p>
        </div>
        <?php endif; ?>
    </div>


    <!-- RIGHT: Customer Details Panel -->
    <?php if ($selectedUser): 
        $memberBadge = getMembershipBadge($selectedUser['membership_level'] ?? 'bronze');
    ?>
    <div id="customerPanel" class="w-80 bg-white border-l flex flex-col overflow-hidden">
        <div class="p-3 border-b bg-gradient-to-r from-blue-500 to-indigo-600 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-sm"><i class="fas fa-user mr-2"></i>ข้อมูลลูกค้า</h3>
                <button onclick="toggleCustomerPanel()" class="text-white/70 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto chat-scroll">
            <!-- Profile Card -->
            <div class="p-4 text-center border-b bg-gradient-to-b from-gray-50 to-white">
                <img src="<?= $selectedUser['picture_url'] ?: 'https://via.placeholder.com/80' ?>" 
                     class="w-20 h-20 rounded-full mx-auto border-4 border-white shadow-lg mb-3">
                <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($selectedUser['display_name']) ?></h4>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium <?= $memberBadge[2] ?> mt-1">
                    <?= $memberBadge[0] ?> <?= $memberBadge[1] ?>
                </span>
                <div class="flex justify-center gap-1 mt-2 flex-wrap">
                    <?php foreach ($userTags as $tag): ?>
                    <span class="tag-badge" style="background-color: <?= htmlspecialchars($tag['color']) ?>20; color: <?= htmlspecialchars($tag['color']) ?>;"><?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                    <button onclick="openTagModal()" class="tag-badge bg-gray-200 text-gray-600 hover:bg-gray-300">
                        <i class="fas fa-plus text-[8px]"></i>
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="p-4 border-b">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-green-50 rounded-lg p-2">
                        <div class="text-lg font-bold text-green-600"><?= $orderCount ?></div>
                        <div class="text-[10px] text-gray-500">ออเดอร์</div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2">
                        <div class="text-lg font-bold text-blue-600">฿<?= number_format($totalSpent) ?></div>
                        <div class="text-[10px] text-gray-500">ยอดซื้อ</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-2">
                        <div class="text-lg font-bold text-purple-600"><?= number_format($loyaltyPoints) ?></div>
                        <div class="text-[10px] text-gray-500">แต้ม</div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="p-4 border-b">
                <h5 class="text-xs font-bold text-gray-700 mb-2"><i class="fas fa-address-card text-blue-500 mr-1"></i>ข้อมูลติดต่อ</h5>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-phone text-gray-400 w-4"></i>
                        <span><?= $selectedUser['phone'] ?: 'ไม่ระบุ' ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-envelope text-gray-400 w-4"></i>
                        <span><?= $selectedUser['email'] ?: 'ไม่ระบุ' ?></span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-map-marker-alt text-gray-400 w-4 mt-0.5"></i>
                        <span class="flex-1"><?= $selectedUser['address'] ?: 'ไม่ระบุ' ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar text-gray-400 w-4"></i>
                        <span>สมาชิกตั้งแต่ <?= date('d/m/Y', strtotime($selectedUser['created_at'])) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="p-4 border-b">
                <h5 class="text-xs font-bold text-gray-700 mb-2"><i class="fas fa-sticky-note text-yellow-500 mr-1"></i>โน๊ต</h5>
                <form onsubmit="saveNote(event)" class="mb-2">
                    <textarea id="noteInput" rows="2" class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-green-500 outline-none resize-none" placeholder="เพิ่มโน๊ตเกี่ยวกับลูกค้า..."></textarea>
                    <button type="submit" class="w-full mt-1 bg-green-500 hover:bg-green-600 text-white text-xs py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i>บันทึกโน๊ต
                    </button>
                </form>
                <div id="notesList" class="space-y-2 max-h-32 overflow-y-auto">
                    <?php foreach ($userNotes as $note): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-xs relative group">
                        <p class="text-gray-700 pr-4"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                        <p class="text-[9px] text-gray-400 mt-1"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></p>
                        <button onclick="deleteNote(<?= $note['id'] ?>, this)" class="absolute top-1 right-1 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($userNotes)): ?>
                    <p class="text-gray-400 text-xs text-center py-2">ยังไม่มีโน๊ต</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <?php if (!empty($userOrders)): ?>
            <div class="p-4 border-b">
                <h5 class="text-xs font-bold text-gray-700 mb-2"><i class="fas fa-shopping-bag text-blue-500 mr-1"></i>ออเดอร์ล่าสุด</h5>
                <div class="space-y-2">
                    <?php foreach (array_slice($userOrders, 0, 3) as $order): ?>
                    <div class="bg-gray-50 rounded-lg p-2 text-xs hover:bg-gray-100 cursor-pointer">
                        <div class="flex justify-between">
                            <span class="font-medium">#<?= $order['order_number'] ?? $order['id'] ?></span>
                            <span class="text-green-600 font-bold">฿<?= number_format($order['total_amount'] ?? 0) ?></span>
                        </div>
                        <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                            <span><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                            <span class="px-1.5 py-0.5 rounded <?= $order['status'] == 'completed' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' ?>">
                                <?= $order['status'] ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="p-4">
                <a href="user-detail.php?id=<?= $selectedUser['id'] ?>" class="block w-full text-center bg-blue-500 hover:bg-blue-600 text-white text-xs py-2.5 rounded-lg font-medium mb-2">
                    <i class="fas fa-external-link-alt mr-1"></i>ดูโปรไฟล์เต็ม
                </a>
                <a href="shop/orders.php?user=<?= $selectedUser['line_user_id'] ?>" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs py-2.5 rounded-lg font-medium">
                    <i class="fas fa-list mr-1"></i>ดูออเดอร์ทั้งหมด
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- Tag Modal -->
<?php if ($selectedUser): ?>
<div id="tagModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xs overflow-hidden">
        <div class="p-3 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-sm text-gray-800"><i class="fas fa-tags mr-2 text-green-500"></i>จัดการแท็ก</h3>
            <button onclick="closeTagModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-3 space-y-3">
            <div class="flex gap-1">
                <input type="text" id="newTagName" placeholder="สร้างแท็กใหม่..." class="flex-1 border rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-green-500 outline-none">
                <select id="newTagColor" class="border rounded text-xs bg-white px-1">
                    <option value="gray">เทา</option>
                    <option value="blue">ฟ้า</option>
                    <option value="green">เขียว</option>
                    <option value="red">แดง</option>
                    <option value="yellow">เหลือง</option>
                </select>
                <button onclick="createNewTag()" class="bg-green-500 text-white px-2 rounded hover:bg-green-600 text-xs"><i class="fas fa-plus"></i></button>
            </div>
            <hr>
            <div class="space-y-1 max-h-48 overflow-y-auto" id="tagList">
                <?php foreach ($allTags as $t): 
                    $hasTag = false;
                    foreach($userTags as $ut) { if($ut['id'] == $t['id']) $hasTag = true; }
                ?>
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded cursor-pointer" onclick="toggleTag(<?= $t['id'] ?>, this)">
                    <span class="tag-badge" style="background-color: <?= htmlspecialchars($t['color']) ?>20; color: <?= htmlspecialchars($t['color']) ?>;"><?= htmlspecialchars($t['name']) ?></span>
                    <div class="check-indicator text-green-500 <?= $hasTag ? '' : 'hidden' ?>"><i class="fas fa-check-circle"></i></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const userId = "<?= $selectedUser['id'] ?? '' ?>";
const chatBox = document.getElementById('chatBox');
if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 96) + 'px';
}

function filterUsers(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.user-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function toggleAIPanel() {
    document.getElementById('aiPanel')?.classList.toggle('hidden');
}

function toggleCustomerPanel() {
    document.getElementById('customerPanel')?.classList.toggle('hidden');
}

async function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    appendMessage(msg, 'outgoing');
    input.value = '';
    input.style.height = 'auto';
    
    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('user_id', userId);
        formData.append('message', msg);
        await fetch('messages-v2.php', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'} });
    } catch (err) {
        console.error(err);
    }
}

function appendMessage(content, direction) {
    const div = document.createElement('div');
    div.className = `flex ${direction === 'outgoing' ? 'justify-end' : 'justify-start'}`;
    div.innerHTML = `
        <div class="flex flex-col ${direction === 'outgoing' ? 'items-end' : 'items-start'}" style="max-width:65%">
            <div class="chat-bubble px-4 py-2.5 text-sm shadow-sm ${direction === 'outgoing' ? 'chat-outgoing' : 'chat-incoming'}">${escapeHtml(content)}</div>
        </div>`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

// AI Assistant Functions
async function aiAssist(type) {
    const input = document.getElementById('messageInput');
    let text = input.value.trim();
    
    // For reply, get last incoming message
    if (type === 'reply' && !text) {
        const lastIncoming = document.querySelector('.chat-incoming:last-of-type');
        if (lastIncoming) {
            text = lastIncoming.textContent.trim();
        }
    }
    
    // For summarize, get all messages
    if (type === 'summarize') {
        const messages = [];
        document.querySelectorAll('.chat-bubble').forEach(el => {
            messages.push(el.textContent.trim());
        });
        text = messages.join('\n');
    }
    
    if (!text && type !== 'summarize') {
        alert('กรุณาพิมพ์ข้อความหรือเลือกข้อความก่อน');
        return;
    }
    
    const resultDiv = document.getElementById('aiResult');
    const resultText = document.getElementById('aiResultText');
    resultDiv.classList.remove('hidden');
    resultText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังประมวลผล...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'ai_assist');
        formData.append('type', type);
        formData.append('text', text);
        
        const res = await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        const data = await res.json();
        
        if (data.success) {
            resultText.textContent = data.result;
        } else {
            resultText.textContent = 'Error: ' + (data.error || 'Unknown error');
        }
    } catch (err) {
        resultText.textContent = 'Error: ' + err.message;
    }
}

function useAIResult() {
    const result = document.getElementById('aiResultText').textContent;
    if (result && !result.startsWith('Error:')) {
        document.getElementById('messageInput').value = result;
        document.getElementById('aiResult').classList.add('hidden');
    }
}

async function saveGeminiKey() {
    const key = document.getElementById('geminiKeyInput').value.trim();
    if (!key) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'save_gemini_key');
        formData.append('api_key', key);
        
        const res = await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        const data = await res.json();
        if (data.success) {
            alert('บันทึก API Key สำเร็จ! กรุณารีเฟรชหน้า');
            location.reload();
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// Notes Functions
async function saveNote(e) {
    e.preventDefault();
    const input = document.getElementById('noteInput');
    const note = input.value.trim();
    if (!note || !userId) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'save_note');
        formData.append('user_id', userId);
        formData.append('note', note);
        
        const res = await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        const data = await res.json();
        
        if (data.success) {
            const list = document.getElementById('notesList');
            const emptyMsg = list.querySelector('p.text-center');
            if (emptyMsg) emptyMsg.remove();
            
            const div = document.createElement('div');
            div.className = 'bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-xs relative group';
            div.innerHTML = `
                <p class="text-gray-700 pr-4">${escapeHtml(note)}</p>
                <p class="text-[9px] text-gray-400 mt-1">${data.created_at}</p>
                <button onclick="deleteNote(${data.id}, this)" class="absolute top-1 right-1 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100">
                    <i class="fas fa-times text-[10px]"></i>
                </button>`;
            list.insertBefore(div, list.firstChild);
            input.value = '';
        }
    } catch (err) {
        console.error(err);
    }
}

async function deleteNote(noteId, btn) {
    if (!confirm('ลบโน๊ตนี้?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_note');
        formData.append('note_id', noteId);
        
        await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        btn.closest('.bg-yellow-50').remove();
    } catch (err) {
        console.error(err);
    }
}

// Tag Functions
function openTagModal() {
    document.getElementById('tagModal').classList.remove('hidden');
}

function closeTagModal() {
    document.getElementById('tagModal').classList.add('hidden');
}

async function toggleTag(tagId, el) {
    const check = el.querySelector('.check-indicator');
    const isAdding = check.classList.contains('hidden');
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_tags');
        formData.append('user_id', userId);
        formData.append('tag_id', tagId);
        formData.append('operation', isAdding ? 'add' : 'remove');
        
        await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        check.classList.toggle('hidden');
    } catch (err) {
        console.error(err);
    }
}

async function createNewTag() {
    const name = document.getElementById('newTagName').value.trim();
    const color = document.getElementById('newTagColor').value;
    if (!name) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_tag');
        formData.append('tag_name', name);
        formData.append('color', color);
        
        await fetch('messages-v2.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        
        location.reload();
    } catch (err) {
        console.error(err);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
