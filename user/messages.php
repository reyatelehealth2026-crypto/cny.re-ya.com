<?php
/**
 * User Messages - จัดการข้อความสำหรับผู้ใช้ทั่วไป
 */
$pageTitle = 'ข้อความ';
require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../classes/LineAPI.php';

// Get users with messages
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT content FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
           (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND direction = 'incoming' AND is_read = 0) as unread_count
    FROM users u 
    WHERE u.line_account_id = ? AND u.is_blocked = 0
    ORDER BY last_message_time DESC
    LIMIT 50
");
$stmt->execute([$currentBotId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected user messages
$selectedUserId = $_GET['user'] ?? null;
$selectedUser = null;
$messages = [];

if ($selectedUserId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND line_account_id = ?");
    $stmt->execute([$selectedUserId, $currentBotId]);
    $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedUser) {
        // Mark as read
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND direction = 'incoming'");
        $stmt->execute([$selectedUserId]);
        
        // Get messages
        $stmt = $db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC LIMIT 100");
        $stmt->execute([$selectedUserId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $userId = $_POST['user_id'];
    $content = trim($_POST['content']);
    
    if ($content && $userId) {
        // Get user's LINE ID with reply token
        $stmt = $db->prepare("SELECT line_user_id, reply_token, reply_token_expires FROM users WHERE id = ? AND line_account_id = ?");
        $stmt->execute([$userId, $currentBotId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Get LINE account credentials
            $lineApi = new LineAPI($lineAccount['channel_access_token']);
            // ใช้ sendMessage เพื่อเช็ค replyToken ก่อน (ฟรี!) - with fallback
            if (method_exists($lineApi, 'sendMessage')) {
                $result = $lineApi->sendMessage($user['line_user_id'], ['type' => 'text', 'text' => $content], $user['reply_token'] ?? null, $user['reply_token_expires'] ?? null, $db, $userId);
            } else {
                $result = $lineApi->pushMessage($user['line_user_id'], [['type' => 'text', 'text' => $content]]);
            }
            
            if ($result && $result['code'] === 200) {
                // Save to database
                $stmt = $db->prepare("INSERT INTO messages (line_account_id, user_id, direction, message_type, content) VALUES (?, ?, 'outgoing', 'text', ?)");
                $stmt->execute([$currentBotId, $userId, $content]);
            }
        }
        
        header("Location: messages.php?user=$userId");
        exit;
    }
}
?>

<style>
.chat-container {
    display: flex;
    height: calc(100vh - 180px);
    background: white;
    border-radius: 12px;
    overflow: hidden;
}
.user-list {
    width: 320px;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
}
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.user-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s;
}
.user-item:hover, .user-item.active {
    background: #f8fafc;
}
.user-item.active {
    border-left: 3px solid #06C755;
}
.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8fafc;
}
.message {
    max-width: 70%;
    margin-bottom: 12px;
    clear: both;
}
.message.incoming {
    float: left;
}
.message.outgoing {
    float: right;
}
.message-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.4;
}
.message.incoming .message-bubble {
    background: white;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}
.message.outgoing .message-bubble {
    background: #06C755;
    color: white;
    border-bottom-right-radius: 4px;
}
.message-time {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}
.message.outgoing .message-time {
    text-align: right;
}
.chat-input {
    padding: 16px;
    border-top: 1px solid #e2e8f0;
    background: white;
}
.unread-badge {
    background: #ef4444;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}
@media (max-width: 768px) {
    .user-list {
        width: 100%;
        display: <?= $selectedUserId ? 'none' : 'block' ?>;
    }
    .chat-area {
        display: <?= $selectedUserId ? 'flex' : 'none' ?>;
    }
}
</style>

<div class="chat-container">
    <!-- User List -->
    <div class="user-list">
        <div class="p-4 border-b">
            <input type="text" placeholder="ค้นหาลูกค้า..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="searchUser">
        </div>
        <div id="userListContainer">
            <?php if (empty($users)): ?>
            <div class="p-8 text-center text-gray-400">ยังไม่มีข้อความ</div>
            <?php else: ?>
            <?php foreach ($users as $u): ?>
            <a href="?user=<?= $u['id'] ?>" class="user-item <?= $selectedUserId == $u['id'] ? 'active' : '' ?>">
                <div class="user-avatar">
                    <?php if ($u['picture_url']): ?>
                    <img src="<?= htmlspecialchars($u['picture_url']) ?>">
                    <?php else: ?>
                    <i class="fas fa-user text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm truncate"><?= htmlspecialchars($u['display_name'] ?? 'Unknown') ?></span>
                        <?php if ($u['unread_count'] > 0): ?>
                        <span class="unread-badge"><?= $u['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars(mb_substr($u['last_message'] ?? '', 0, 30)) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="chat-area">
        <?php if ($selectedUser): ?>
        <!-- Chat Header -->
        <div class="p-4 border-b flex items-center">
            <a href="messages.php" class="md:hidden mr-3 text-gray-500"><i class="fas fa-arrow-left"></i></a>
            <div class="user-avatar" style="width:40px;height:40px;">
                <?php if ($selectedUser['picture_url']): ?>
                <img src="<?= htmlspecialchars($selectedUser['picture_url']) ?>">
                <?php else: ?>
                <i class="fas fa-user text-gray-400"></i>
                <?php endif; ?>
            </div>
            <div class="ml-3">
                <div class="font-semibold"><?= htmlspecialchars($selectedUser['display_name'] ?? 'Unknown') ?></div>
                <div class="text-xs text-gray-500">LINE User</div>
            </div>
        </div>
        
        <!-- Messages -->
        <div class="messages-area" id="messagesArea">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['direction'] ?>">
                <div class="message-bubble"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                <div class="message-time"><?= date('d/m H:i', strtotime($msg['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
            <div style="clear:both"></div>
        </div>
        
        <!-- Input -->
        <form method="POST" class="chat-input">
            <input type="hidden" name="send_message" value="1">
            <input type="hidden" name="user_id" value="<?= $selectedUser['id'] ?>">
            <div class="flex gap-2">
                <input type="text" name="content" placeholder="พิมพ์ข้อความ..." 
                       class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="flex-1 flex items-center justify-center text-gray-400">
            <div class="text-center">
                <i class="fas fa-comments text-6xl mb-4"></i>
                <p>เลือกลูกค้าเพื่อดูข้อความ</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll to bottom
const messagesArea = document.getElementById('messagesArea');
if (messagesArea) {
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

// Search users
document.getElementById('searchUser')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(item => {
        const name = item.querySelector('.font-medium').textContent.toLowerCase();
        item.style.display = name.includes(search) ? 'flex' : 'none';
    });
});
</script>

<?php require_once '../includes/user_footer.php'; ?>
