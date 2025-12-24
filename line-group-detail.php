<?php
/**
 * LINE Group Detail - รายละเอียดกลุ่ม
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$groupId = $_GET['id'] ?? 0;

// Get group info
$stmt = $db->prepare("
    SELECT g.*, la.name as bot_name 
    FROM line_groups g
    LEFT JOIN line_accounts la ON g.line_account_id = la.id
    WHERE g.id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header('Location: line-groups.php');
    exit;
}

$pageTitle = 'กลุ่ม: ' . ($group['group_name'] ?: 'Unknown');

// Get members
$members = [];
try {
    $stmt = $db->prepare("
        SELECT * FROM line_group_members 
        WHERE group_id = ? 
        ORDER BY is_active DESC, total_messages DESC
    ");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Get recent messages
$messages = [];
try {
    $stmt = $db->prepare("
        SELECT gm.*, lgm.display_name 
        FROM line_group_messages gm
        LEFT JOIN line_group_members lgm ON gm.group_id = lgm.group_id AND gm.line_user_id = lgm.line_user_id
        WHERE gm.group_id = ? 
        ORDER BY gm.created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$groupId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="mb-6">
        <a href="line-groups.php" class="text-green-600 hover:text-green-700">
            <i class="fas fa-arrow-left mr-1"></i> กลับไปรายการกลุ่ม
        </a>
    </div>
    
    <!-- Group Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start gap-4">
            <?php if ($group['picture_url']): ?>
            <img src="<?= htmlspecialchars($group['picture_url']) ?>" class="w-20 h-20 rounded-full">
            <?php else: ?>
            <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                <i class="fas fa-users text-3xl text-gray-400"></i>
            </div>
            <?php endif; ?>
            
            <div class="flex-1">
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($group['group_name'] ?: 'Unknown Group') ?></h1>
                <p class="text-gray-500"><?= $group['group_type'] === 'room' ? 'Room' : 'Group' ?> • บอท: <?= htmlspecialchars($group['bot_name'] ?? '-') ?></p>
                
                <div class="flex gap-4 mt-3">
                    <div>
                        <span class="text-2xl font-bold text-blue-500"><?= number_format($group['member_count']) ?></span>
                        <span class="text-gray-500 text-sm">สมาชิก</span>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-green-500"><?= number_format($group['total_messages']) ?></span>
                        <span class="text-gray-500 text-sm">ข้อความ</span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <?php if ($group['is_active']): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-sm">Active</span>
                    <?php else: ?>
                    <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-sm">Left</span>
                    <?php endif; ?>
                    <span class="text-sm text-gray-500 ml-2">
                        เข้าร่วมเมื่อ <?= date('d/m/Y H:i', strtotime($group['joined_at'])) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Members -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="font-bold">👥 สมาชิก (<?= count($members) ?>)</h3>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                <?php if (empty($members)): ?>
                <p class="text-gray-500 text-center py-4">ยังไม่มีข้อมูลสมาชิก</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($members as $member): ?>
                    <div class="flex items-center gap-3 p-2 rounded-lg <?= $member['is_active'] ? 'bg-gray-50' : 'bg-red-50' ?>">
                        <?php if ($member['picture_url']): ?>
                        <img src="<?= htmlspecialchars($member['picture_url']) ?>" class="w-10 h-10 rounded-full">
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <div class="font-medium"><?= htmlspecialchars($member['display_name'] ?: 'Unknown') ?></div>
                            <div class="text-xs text-gray-500">
                                ข้อความ: <?= number_format($member['total_messages']) ?>
                                <?php if ($member['last_message_at']): ?>
                                • ล่าสุด: <?= date('d/m H:i', strtotime($member['last_message_at'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$member['is_active']): ?>
                        <span class="text-xs text-red-500">ออกแล้ว</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Messages -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="font-bold">💬 ข้อความล่าสุด</h3>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                <?php if (empty($messages)): ?>
                <p class="text-gray-500 text-center py-4">ยังไม่มีข้อความ</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($messages as $msg): ?>
                    <div class="border-b pb-2">
                        <div class="flex justify-between items-start">
                            <span class="font-medium text-sm"><?= htmlspecialchars($msg['display_name'] ?: 'Unknown') ?></span>
                            <span class="text-xs text-gray-400"><?= date('d/m H:i', strtotime($msg['created_at'])) ?></span>
                        </div>
                        <p class="text-gray-600 text-sm mt-1">
                            <?php if ($msg['message_type'] !== 'text'): ?>
                            <span class="text-gray-400">[<?= $msg['message_type'] ?>]</span>
                            <?php endif; ?>
                            <?= htmlspecialchars(mb_substr($msg['content'], 0, 100)) ?>
                            <?= mb_strlen($msg['content']) > 100 ? '...' : '' ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
