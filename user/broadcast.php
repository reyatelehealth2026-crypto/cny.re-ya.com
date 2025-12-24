<?php
/**
 * User Broadcast - ส่งข้อความหาลูกค้าทั้งหมด
 */
$pageTitle = 'Broadcast';
require_once '../includes/user_header.php';
require_once '../classes/LineAPI.php';

$success = '';
$error = '';

// Handle send broadcast
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title) || empty($content)) {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    } else {
        try {
            // Get all users for this LINE account
            $stmt = $db->prepare("SELECT line_user_id FROM users WHERE line_account_id = ? AND is_blocked = 0");
            $stmt->execute([$currentBotId]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($users)) {
                $error = 'ไม่มีลูกค้าในระบบ';
            } else {
                // Send via LINE API
                $lineApi = new LineAPI($lineAccount['channel_access_token']);
                $sentCount = 0;
                
                foreach ($users as $lineUserId) {
                    $result = $lineApi->pushMessage($lineUserId, ['type' => 'text', 'text' => $content]);
                    if ($result) $sentCount++;
                }
                
                // Save broadcast record
                $stmt = $db->prepare("INSERT INTO broadcasts (line_account_id, title, content, target_type, sent_count, status, sent_at) VALUES (?, ?, ?, 'all', ?, 'sent', NOW())");
                $stmt->execute([$currentBotId, $title, $content, $sentCount]);
                
                $success = "ส่งข้อความสำเร็จ $sentCount คน";
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// Get broadcast history
$stmt = $db->prepare("SELECT * FROM broadcasts WHERE line_account_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$currentBotId]);
$broadcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user count
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE line_account_id = ? AND is_blocked = 0");
$stmt->execute([$currentBotId]);
$userCount = $stmt->fetchColumn();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Send Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-bullhorn text-green-500 mr-2"></i>ส่งข้อความ Broadcast
            </h2>
            
            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-600 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <i class="fas fa-users text-blue-500 mr-2"></i>
                จะส่งถึงลูกค้า <strong><?= number_format($userCount) ?></strong> คน
            </div>
            
            <form method="POST">
                <input type="hidden" name="send_broadcast" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ (สำหรับบันทึก)</label>
                    <input type="text" name="title" required 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="เช่น โปรโมชั่นเดือนนี้">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ข้อความ</label>
                    <textarea name="content" rows="6" required 
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="พิมพ์ข้อความที่ต้องการส่ง..."></textarea>
                </div>
                
                <button type="submit" class="w-full py-3 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition"
                        onclick="return confirm('ยืนยันส่งข้อความถึงลูกค้า <?= number_format($userCount) ?> คน?')">
                    <i class="fas fa-paper-plane mr-2"></i>ส่ง Broadcast
                </button>
            </form>
        </div>
    </div>
    
    <!-- History -->
    <div>
        <div class="bg-white rounded-xl shadow">
            <div class="p-4 border-b">
                <h3 class="font-semibold">
                    <i class="fas fa-history text-gray-400 mr-2"></i>ประวัติการส่ง
                </h3>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <?php if (empty($broadcasts)): ?>
                <div class="p-8 text-center text-gray-400">ยังไม่มีประวัติ</div>
                <?php else: ?>
                <?php foreach ($broadcasts as $bc): ?>
                <div class="p-4 border-b hover:bg-gray-50">
                    <div class="font-medium text-sm"><?= htmlspecialchars($bc['title']) ?></div>
                    <div class="text-xs text-gray-500 mt-1 truncate"><?= htmlspecialchars(mb_substr($bc['content'], 0, 50)) ?></div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($bc['created_at'])) ?></span>
                        <span class="text-xs text-green-600"><i class="fas fa-check mr-1"></i><?= $bc['sent_count'] ?> คน</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
