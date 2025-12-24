<?php
/**
 * LINE Groups Manager - จัดการกลุ่มที่บอทอยู่
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'จัดการกลุ่ม LINE';

// Get current bot
$currentBotId = $_SESSION['current_bot_id'] ?? null;

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'leave_group') {
        $groupDbId = $_POST['group_id'] ?? 0;
        
        // Get group info
        $stmt = $db->prepare("SELECT * FROM line_groups WHERE id = ?");
        $stmt->execute([$groupDbId]);
        $group = $stmt->fetch();
        
        if ($group) {
            try {
                $manager = new LineAccountManager($db);
                $line = $manager->getLineAPI($group['line_account_id']);
                
                // Leave group via LINE API
                if ($group['group_type'] === 'group') {
                    $result = $line->leaveGroup($group['group_id']);
                } else {
                    $result = $line->leaveRoom($group['group_id']);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE line_groups SET is_active = 0, left_at = NOW() WHERE id = ?");
                $stmt->execute([$groupDbId]);
                
                $message = "ออกจากกลุ่ม {$group['group_name']} แล้ว";
            } catch (Exception $e) {
                $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'send_message') {
        $groupDbId = $_POST['group_id'] ?? 0;
        $messageText = $_POST['message'] ?? '';
        
        if ($groupDbId && $messageText) {
            $stmt = $db->prepare("SELECT * FROM line_groups WHERE id = ?");
            $stmt->execute([$groupDbId]);
            $group = $stmt->fetch();
            
            if ($group) {
                try {
                    $manager = new LineAccountManager($db);
                    $line = $manager->getLineAPI($group['line_account_id']);
                    
                    $line->pushMessage($group['group_id'], $messageText);
                    $message = "ส่งข้อความไปยังกลุ่ม {$group['group_name']} แล้ว";
                } catch (Exception $e) {
                    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
                }
            }
        }
    }
}

// Get groups
$groups = [];
try {
    if ($currentBotId) {
        $stmt = $db->prepare("
            SELECT g.*, la.name as bot_name 
            FROM line_groups g
            LEFT JOIN line_accounts la ON g.line_account_id = la.id
            WHERE g.line_account_id = ?
            ORDER BY g.is_active DESC, g.joined_at DESC
        ");
        $stmt->execute([$currentBotId]);
    } else {
        $stmt = $db->query("
            SELECT g.*, la.name as bot_name 
            FROM line_groups g
            LEFT JOIN line_accounts la ON g.line_account_id = la.id
            ORDER BY g.is_active DESC, g.joined_at DESC
        ");
    }
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist
}

// Get stats
$stats = [
    'total' => 0,
    'active' => 0,
    'total_members' => 0,
    'total_messages' => 0
];

try {
    $whereClause = $currentBotId ? "WHERE line_account_id = {$currentBotId}" : "";
    
    $stmt = $db->query("SELECT COUNT(*) FROM line_groups {$whereClause}");
    $stats['total'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM line_groups {$whereClause}" . ($whereClause ? " AND" : " WHERE") . " is_active = 1");
    $stats['active'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT SUM(member_count) FROM line_groups {$whereClause}");
    $stats['total_members'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $db->query("SELECT SUM(total_messages) FROM line_groups {$whereClause}");
    $stats['total_messages'] = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Ignore
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">👥 จัดการกลุ่ม LINE</h1>
    </div>
    
    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-3xl font-bold text-blue-500"><?= number_format($stats['total']) ?></div>
            <div class="text-gray-500">กลุ่มทั้งหมด</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-3xl font-bold text-green-500"><?= number_format($stats['active']) ?></div>
            <div class="text-gray-500">กลุ่มที่ใช้งาน</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-3xl font-bold text-purple-500"><?= number_format($stats['total_members']) ?></div>
            <div class="text-gray-500">สมาชิกรวม</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-3xl font-bold text-orange-500"><?= number_format($stats['total_messages']) ?></div>
            <div class="text-gray-500">ข้อความในกลุ่ม</div>
        </div>
    </div>
    
    <!-- Groups List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold">รายการกลุ่ม</h3>
            <span class="text-sm text-gray-500"><?= count($groups) ?> กลุ่ม</span>
        </div>
        
        <?php if (empty($groups)): ?>
        <div class="p-8 text-center text-gray-500">
            <div class="text-4xl mb-2">👥</div>
            <p>ยังไม่มีกลุ่มที่บอทเข้าร่วม</p>
            <p class="text-sm mt-2">เมื่อมีคนเชิญบอทเข้ากลุ่ม จะแสดงที่นี่</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">กลุ่ม</th>
                        <th class="px-4 py-3 text-center">บอท</th>
                        <th class="px-4 py-3 text-center">สมาชิก</th>
                        <th class="px-4 py-3 text-center">ข้อความ</th>
                        <th class="px-4 py-3 text-center">สถานะ</th>
                        <th class="px-4 py-3 text-center">เข้าร่วมเมื่อ</th>
                        <th class="px-4 py-3 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <?php if ($group['picture_url']): ?>
                                <img src="<?= htmlspecialchars($group['picture_url']) ?>" class="w-10 h-10 rounded-full mr-3">
                                <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                    <i class="fas fa-users text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium"><?= htmlspecialchars($group['group_name'] ?: 'Unknown') ?></div>
                                    <div class="text-xs text-gray-400"><?= $group['group_type'] === 'room' ? 'Room' : 'Group' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($group['bot_name'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-medium"><?= number_format($group['member_count']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-gray-600"><?= number_format($group['total_messages']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($group['is_active']): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">Active</span>
                            <?php else: ?>
                            <span class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs">Left</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                            <?= date('d/m/Y H:i', strtotime($group['joined_at'])) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="line-group-detail.php?id=<?= $group['id'] ?>" 
                                   class="text-blue-500 hover:text-blue-700" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($group['is_active']): ?>
                                <button onclick="openSendModal(<?= $group['id'] ?>, '<?= htmlspecialchars($group['group_name'], ENT_QUOTES) ?>')" 
                                        class="text-green-500 hover:text-green-700" title="ส่งข้อความ">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <button onclick="confirmLeave(<?= $group['id'] ?>, '<?= htmlspecialchars($group['group_name'], ENT_QUOTES) ?>')" 
                                        class="text-red-500 hover:text-red-700" title="ออกจากกลุ่ม">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Send Message Modal -->
<div id="sendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold">ส่งข้อความไปยังกลุ่ม</h3>
            <button onclick="closeSendModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-4">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="group_id" id="sendGroupId">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">กลุ่ม</label>
                <div id="sendGroupName" class="text-gray-600"></div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ข้อความ</label>
                <textarea name="message" rows="4" required
                          class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."></textarea>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeSendModal()" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">ยกเลิก</button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    <i class="fas fa-paper-plane mr-1"></i> ส่งข้อความ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Leave Confirmation Form -->
<form id="leaveForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="leave_group">
    <input type="hidden" name="group_id" id="leaveGroupId">
</form>

<script>
function openSendModal(groupId, groupName) {
    document.getElementById('sendGroupId').value = groupId;
    document.getElementById('sendGroupName').textContent = groupName;
    document.getElementById('sendModal').classList.remove('hidden');
    document.getElementById('sendModal').classList.add('flex');
}

function closeSendModal() {
    document.getElementById('sendModal').classList.add('hidden');
    document.getElementById('sendModal').classList.remove('flex');
}

function confirmLeave(groupId, groupName) {
    if (confirm('ต้องการให้บอทออกจากกลุ่ม "' + groupName + '" หรือไม่?\n\nหมายเหตุ: บอทจะไม่สามารถกลับเข้ากลุ่มได้เอง ต้องให้สมาชิกเชิญใหม่')) {
        document.getElementById('leaveGroupId').value = groupId;
        document.getElementById('leaveForm').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
