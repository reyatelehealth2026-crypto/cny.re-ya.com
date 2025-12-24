<?php
/**
 * Telegram Notification - แจ้งเตือนผ่าน Telegram
 * ตั้งค่าได้ผ่านหน้าเว็บ
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Telegram Notification';

// Ensure table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS telegram_settings (
        id INT PRIMARY KEY DEFAULT 1,
        bot_token VARCHAR(100) DEFAULT NULL,
        chat_id VARCHAR(50) DEFAULT NULL,
        is_enabled TINYINT(1) DEFAULT 0,
        notify_new_follower TINYINT(1) DEFAULT 1,
        notify_new_message TINYINT(1) DEFAULT 1,
        notify_unfollow TINYINT(1) DEFAULT 1,
        notify_new_order TINYINT(1) DEFAULT 1,
        notify_payment TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default row if not exists
    $db->exec("INSERT IGNORE INTO telegram_settings (id) VALUES (1)");
} catch (Exception $e) {}

// Handle save settings
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_token') {
        $botToken = trim($_POST['bot_token'] ?? '');
        $chatId = trim($_POST['chat_id'] ?? '');
        
        $stmt = $db->prepare("UPDATE telegram_settings SET bot_token = ?, chat_id = ? WHERE id = 1");
        $stmt->execute([$botToken, $chatId]);
        
        $message = 'บันทึก Token และ Chat ID สำเร็จ!';
        $messageType = 'success';
        
    } elseif ($action === 'save_notifications') {
        $stmt = $db->prepare("UPDATE telegram_settings SET 
            is_enabled = ?, 
            notify_new_follower = ?, 
            notify_new_message = ?, 
            notify_unfollow = ?,
            notify_new_order = ?,
            notify_payment = ?
            WHERE id = 1");
        $stmt->execute([
            isset($_POST['is_enabled']) ? 1 : 0,
            isset($_POST['notify_new_follower']) ? 1 : 0,
            isset($_POST['notify_new_message']) ? 1 : 0,
            isset($_POST['notify_unfollow']) ? 1 : 0,
            isset($_POST['notify_new_order']) ? 1 : 0,
            isset($_POST['notify_payment']) ? 1 : 0
        ]);
        
        $message = 'บันทึกการตั้งค่าการแจ้งเตือนสำเร็จ!';
        $messageType = 'success';
        
    } elseif ($action === 'test') {
        // Get settings from DB
        $stmt = $db->query("SELECT bot_token, chat_id FROM telegram_settings WHERE id = 1");
        $tokenSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $botToken = $tokenSettings['bot_token'] ?? '';
        $chatId = $tokenSettings['chat_id'] ?? '';
        
        if (empty($botToken) || empty($chatId)) {
            $message = 'กรุณาตั้งค่า Bot Token และ Chat ID ก่อน';
            $messageType = 'error';
        } else {
            $testMessage = "🔔 <b>ทดสอบการแจ้งเตือน</b>\n\nระบบ LINE OA Manager ทำงานปกติ\n📅 " . date('Y-m-d H:i:s');
            $result = sendTelegramMessage($botToken, $chatId, $testMessage);
            
            if ($result['ok'] ?? false) {
                $message = 'ส่งข้อความทดสอบสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'ส่งข้อความไม่สำเร็จ: ' . ($result['description'] ?? 'Unknown error');
                $messageType = 'error';
            }
        }
        
    } elseif ($action === 'set_webhook') {
        $stmt = $db->query("SELECT bot_token FROM telegram_settings WHERE id = 1");
        $tokenSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        $botToken = $tokenSettings['bot_token'] ?? '';
        
        if (empty($botToken)) {
            $message = 'กรุณาตั้งค่า Bot Token ก่อน';
            $messageType = 'error';
        } else {
            $webhookUrl = rtrim(BASE_URL, '/') . '/telegram_webhook.php';
            $result = setTelegramWebhook($botToken, $webhookUrl);
            
            if ($result['ok'] ?? false) {
                $message = 'ตั้งค่า Webhook สำเร็จ! URL: ' . $webhookUrl;
                $messageType = 'success';
            } else {
                $message = 'ตั้งค่า Webhook ไม่สำเร็จ: ' . ($result['description'] ?? 'Unknown error');
                $messageType = 'error';
            }
        }
        
    } elseif ($action === 'get_chat_id') {
        $stmt = $db->query("SELECT bot_token FROM telegram_settings WHERE id = 1");
        $tokenSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        $botToken = $tokenSettings['bot_token'] ?? '';
        
        if (empty($botToken)) {
            $message = 'กรุณาตั้งค่า Bot Token ก่อน';
            $messageType = 'error';
        } else {
            $result = getTelegramUpdates($botToken);
            
            if (($result['ok'] ?? false) && !empty($result['result'])) {
                $chatIds = [];
                foreach ($result['result'] as $update) {
                    $chat = $update['message']['chat'] ?? $update['channel_post']['chat'] ?? null;
                    if ($chat) {
                        $chatIds[$chat['id']] = [
                            'id' => $chat['id'],
                            'type' => $chat['type'],
                            'name' => $chat['title'] ?? $chat['first_name'] ?? $chat['username'] ?? 'Unknown'
                        ];
                    }
                }
                
                if (!empty($chatIds)) {
                    $chatList = '';
                    foreach ($chatIds as $chat) {
                        $chatList .= "\n• {$chat['name']} ({$chat['type']}): <b>{$chat['id']}</b>";
                    }
                    $message = 'พบ Chat ID:' . $chatList;
                    $messageType = 'info';
                } else {
                    $message = 'ไม่พบ Chat ID กรุณาส่งข้อความหา Bot ก่อน';
                    $messageType = 'warning';
                }
            } else {
                $message = 'ไม่พบข้อมูล กรุณาส่งข้อความหา Bot ก่อน';
                $messageType = 'warning';
            }
        }
    }
}

// Get current settings
$stmt = $db->query("SELECT * FROM telegram_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Helper functions
function sendTelegramMessage($token, $chatId, $text) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? ['ok' => false];
}

function setTelegramWebhook($token, $webhookUrl) {
    $url = "https://api.telegram.org/bot{$token}/setWebhook";
    $data = ['url' => $webhookUrl];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? ['ok' => false];
}

function getTelegramUpdates($token) {
    $url = "https://api.telegram.org/bot{$token}/getUpdates";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? ['ok' => false];
}

require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="mb-4 p-4 rounded-lg <?php 
    echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 
        ($messageType === 'error' ? 'bg-red-100 text-red-700' : 
        ($messageType === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')); 
?>">
    <i class="fas <?php 
        echo $messageType === 'success' ? 'fa-check-circle' : 
            ($messageType === 'error' ? 'fa-times-circle' : 
            ($messageType === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle')); 
    ?> mr-2"></i>
    <?= nl2br(htmlspecialchars($message)) ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Bot Token & Chat ID Settings -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fab fa-telegram text-blue-500 mr-2"></i>ตั้งค่า Telegram Bot
        </h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_token">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bot Token</label>
                <input type="text" name="bot_token" value="<?= htmlspecialchars($settings['bot_token'] ?? '') ?>" 
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                <p class="text-xs text-gray-500 mt-1">รับ Token จาก @BotFather</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID</label>
                <div class="flex gap-2">
                    <input type="text" name="chat_id" value="<?= htmlspecialchars($settings['chat_id'] ?? '') ?>" 
                        class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="-1001234567890">
                    <button type="submit" name="action" value="get_chat_id" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Chat ID ของคุณหรือกลุ่ม (กดปุ่มค้นหาหลังส่งข้อความหา Bot)</p>
            </div>
            
            <button type="submit" class="w-full py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                <i class="fas fa-save mr-2"></i>บันทึก Token & Chat ID
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="flex gap-2">
            <form method="POST" class="flex-1">
                <input type="hidden" name="action" value="test">
                <button type="submit" class="w-full py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    <i class="fas fa-paper-plane mr-2"></i>ทดสอบส่งข้อความ
                </button>
            </form>
            <form method="POST" class="flex-1">
                <input type="hidden" name="action" value="set_webhook">
                <button type="submit" class="w-full py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 font-medium">
                    <i class="fas fa-link mr-2"></i>ตั้งค่า Webhook
                </button>
            </form>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-bell text-yellow-500 mr-2"></i>ตั้งค่าการแจ้งเตือน
        </h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_notifications">
            
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium">เปิดใช้งานการแจ้งเตือน</p>
                    <p class="text-sm text-gray-500">รับการแจ้งเตือนผ่าน Telegram Bot</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_enabled" class="sr-only peer" <?= ($settings['is_enabled'] ?? 0) ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                </label>
            </div>
            
            <div class="space-y-3">
                <p class="font-medium text-gray-700">เลือกประเภทการแจ้งเตือน:</p>
                
                <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="notify_new_follower" class="mr-3 w-4 h-4 text-blue-600" <?= ($settings['notify_new_follower'] ?? 0) ? 'checked' : '' ?>>
                    <div>
                        <p class="font-medium">🎉 ผู้ติดตามใหม่</p>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีคนเพิ่มเพื่อน LINE OA</p>
                    </div>
                </label>
                
                <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="notify_new_message" class="mr-3 w-4 h-4 text-blue-600" <?= ($settings['notify_new_message'] ?? 0) ? 'checked' : '' ?>>
                    <div>
                        <p class="font-medium">💬 ข้อความใหม่</p>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีข้อความเข้ามา</p>
                    </div>
                </label>
                
                <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="notify_unfollow" class="mr-3 w-4 h-4 text-blue-600" <?= ($settings['notify_unfollow'] ?? 0) ? 'checked' : '' ?>>
                    <div>
                        <p class="font-medium">😢 ยกเลิกติดตาม</p>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีคนบล็อก LINE OA</p>
                    </div>
                </label>
                
                <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="notify_new_order" class="mr-3 w-4 h-4 text-blue-600" <?= ($settings['notify_new_order'] ?? 0) ? 'checked' : '' ?>>
                    <div>
                        <p class="font-medium">🛒 ออเดอร์ใหม่</p>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีคำสั่งซื้อใหม่</p>
                    </div>
                </label>
                
                <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="notify_payment" class="mr-3 w-4 h-4 text-blue-600" <?= ($settings['notify_payment'] ?? 0) ? 'checked' : '' ?>>
                    <div>
                        <p class="font-medium">💳 การชำระเงิน</p>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีการแนบสลิป/ชำระเงิน</p>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </form>
    </div>
</div>

<!-- Instructions -->
<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-book text-purple-500 mr-2"></i>วิธีตั้งค่า Telegram Bot
        </h3>
        <div class="space-y-3 text-sm text-gray-600">
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">1</span>
                <p>เปิด Telegram แล้วค้นหา <a href="https://t.me/BotFather" target="_blank" class="text-blue-500 hover:underline font-medium">@BotFather</a></p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">2</span>
                <p>พิมพ์ <code class="bg-gray-100 px-2 py-0.5 rounded">/newbot</code> แล้วตั้งชื่อ Bot</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">3</span>
                <p>คัดลอก <b>Bot Token</b> ที่ได้รับมาใส่ในช่องด้านบน</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">4</span>
                <p>เริ่มแชทกับ Bot ของคุณ แล้วส่งข้อความอะไรก็ได้ 1 ข้อความ</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">5</span>
                <p>กดปุ่ม <i class="fas fa-search"></i> ข้าง Chat ID เพื่อค้นหา Chat ID อัตโนมัติ</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-xs font-bold">6</span>
                <p>กด "ทดสอบส่งข้อความ" เพื่อตรวจสอบว่าทำงานได้</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-terminal text-green-500 mr-2"></i>คำสั่งที่ใช้ได้ใน Telegram
        </h3>
        <div class="space-y-2 text-sm">
            <div class="p-3 bg-gray-50 rounded-lg">
                <code class="text-purple-600 font-mono">/reply [ID] ข้อความ</code>
                <p class="text-gray-500 mt-1">ตอบกลับข้อความไปยัง LINE User</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <code class="text-purple-600 font-mono">/r [ID] ข้อความ</code>
                <p class="text-gray-500 mt-1">ตอบกลับ (แบบสั้น)</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <code class="text-purple-600 font-mono">/broadcast ข้อความ</code>
                <p class="text-gray-500 mt-1">ส่งข้อความถึงผู้ติดตามทุกคน</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <code class="text-purple-600 font-mono">/users</code>
                <p class="text-gray-500 mt-1">ดูรายชื่อผู้ใช้ล่าสุด</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <code class="text-purple-600 font-mono">/stats</code>
                <p class="text-gray-500 mt-1">ดูสถิติระบบ</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
