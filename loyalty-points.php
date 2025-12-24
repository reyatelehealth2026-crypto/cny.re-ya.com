<?php
/**
 * Loyalty Points Management - ระบบสะสมแต้มแลกของรางวัล
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LoyaltyPoints.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ระบบสะสมแต้ม';
$currentBotId = $_SESSION['current_bot_id'] ?? null;

// Check if tables exist, if not redirect to migration
try {
    $db->query("SELECT 1 FROM points_settings LIMIT 1");
} catch (Exception $e) {
    header('Location: run_loyalty_migration.php');
    exit;
}

$loyalty = new LoyaltyPoints($db, $currentBotId);
$settings = $loyalty->getSettings();
$summary = $loyalty->getPointsSummary();
$rewards = $loyalty->getRewards(false);
$redemptions = $loyalty->getAllRedemptions(null, 20);

$error = null;
$success = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_settings':
                $loyalty->updateSettings([
                    'points_per_baht' => $_POST['points_per_baht'],
                    'min_order_for_points' => $_POST['min_order_for_points'],
                    'points_expiry_days' => $_POST['points_expiry_days'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);
                $success = 'บันทึกการตั้งค่าสำเร็จ';
                $settings = $loyalty->getSettings();
                break;
                
            case 'create_reward':
                $loyalty->createReward([
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'image_url' => $_POST['image_url'],
                    'points_required' => $_POST['points_required'],
                    'reward_type' => $_POST['reward_type'],
                    'stock' => $_POST['stock'] ?: -1,
                    'max_per_user' => $_POST['max_per_user'] ?: 0
                ]);
                $success = 'เพิ่มของรางวัลสำเร็จ';
                $rewards = $loyalty->getRewards(false);
                break;

            case 'update_reward':
                $loyalty->updateReward($_POST['reward_id'], [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'image_url' => $_POST['image_url'],
                    'points_required' => $_POST['points_required'],
                    'stock' => $_POST['stock'] ?: -1,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);
                $success = 'อัพเดทของรางวัลสำเร็จ';
                $rewards = $loyalty->getRewards(false);
                break;
                
            case 'delete_reward':
                $loyalty->deleteReward($_POST['reward_id']);
                $success = 'ลบของรางวัลสำเร็จ';
                $rewards = $loyalty->getRewards(false);
                break;
                
            case 'update_redemption':
                $loyalty->updateRedemptionStatus(
                    $_POST['redemption_id'],
                    $_POST['status'],
                    $_SESSION['admin_user']['id'] ?? null,
                    $_POST['notes'] ?? null
                );
                $success = 'อัพเดทสถานะสำเร็จ';
                $redemptions = $loyalty->getAllRedemptions(null, 20);
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <div class="text-2xl font-bold text-gray-800"><?= number_format($summary['total_issued']) ?></div>
                <div class="text-sm text-gray-500">แต้มที่แจกไป</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exchange-alt text-orange-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <div class="text-2xl font-bold text-gray-800"><?= number_format($summary['total_redeemed']) ?></div>
                <div class="text-sm text-gray-500">แต้มที่ใช้แลก</div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-gift text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <div class="text-2xl font-bold text-gray-800"><?= number_format($summary['active_rewards']) ?></div>
                <div class="text-sm text-gray-500">ของรางวัล</div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <div class="text-2xl font-bold text-gray-800"><?= number_format($summary['pending_redemptions']) ?></div>
                <div class="text-sm text-gray-500">รอดำเนินการ</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="bg-white rounded-xl shadow mb-6">
    <div class="border-b">
        <nav class="flex -mb-px">
            <button onclick="showTab('settings')" class="tab-btn active px-6 py-3 text-sm font-medium border-b-2" data-tab="settings">
                <i class="fas fa-cog mr-2"></i>ตั้งค่า
            </button>
            <button onclick="showTab('rewards')" class="tab-btn px-6 py-3 text-sm font-medium border-b-2" data-tab="rewards">
                <i class="fas fa-gift mr-2"></i>ของรางวัล
            </button>
            <button onclick="showTab('redemptions')" class="tab-btn px-6 py-3 text-sm font-medium border-b-2" data-tab="redemptions">
                <i class="fas fa-history mr-2"></i>การแลก
            </button>
        </nav>
    </div>

    <!-- Settings Tab -->
    <div id="tab-settings" class="tab-content p-6">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2">แต้มต่อบาท</label>
                    <input type="number" name="points_per_baht" step="0.01" value="<?= $settings['points_per_baht'] ?>" class="w-full px-4 py-2 border rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">เช่น 1 = ทุก 1 บาท ได้ 1 แต้ม</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">ยอดขั้นต่ำที่ได้แต้ม (บาท)</label>
                    <input type="number" name="min_order_for_points" value="<?= $settings['min_order_for_points'] ?>" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">แต้มหมดอายุ (วัน)</label>
                    <input type="number" name="points_expiry_days" value="<?= $settings['points_expiry_days'] ?>" class="w-full px-4 py-2 border rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">0 = ไม่หมดอายุ</p>
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" <?= $settings['is_active'] ? 'checked' : '' ?> class="w-5 h-5 text-green-600 rounded">
                        <span class="ml-2">เปิดใช้งานระบบแต้ม</span>
                    </label>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    <i class="fas fa-save mr-2"></i>บันทึก
                </button>
            </div>
        </form>
    </div>

    <!-- Rewards Tab -->
    <div id="tab-rewards" class="tab-content p-6 hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold">ของรางวัลทั้งหมด</h3>
            <button onclick="showRewardModal()" class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i>เพิ่มของรางวัล
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($rewards as $reward): ?>
            <div class="border rounded-lg overflow-hidden <?= $reward['is_active'] ? '' : 'opacity-50' ?>">
                <div class="h-32 bg-gray-100 flex items-center justify-center">
                    <?php if ($reward['image_url']): ?>
                    <img src="<?= htmlspecialchars($reward['image_url']) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                    <i class="fas fa-gift text-4xl text-gray-300"></i>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold"><?= htmlspecialchars($reward['name']) ?></h4>
                    <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($reward['description'] ?? '') ?></p>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-green-600 font-bold"><?= number_format($reward['points_required']) ?> แต้ม</span>
                        <span class="text-xs text-gray-400">
                            <?= $reward['stock'] < 0 ? 'ไม่จำกัด' : "เหลือ {$reward['stock']}" ?>
                        </span>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button onclick="editReward(<?= htmlspecialchars(json_encode($reward)) ?>)" class="flex-1 px-3 py-1 bg-blue-100 text-blue-600 rounded text-sm">แก้ไข</button>
                        <form method="POST" class="flex-1" onsubmit="return confirm('ลบของรางวัลนี้?')">
                            <input type="hidden" name="action" value="delete_reward">
                            <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                            <button type="submit" class="w-full px-3 py-1 bg-red-100 text-red-600 rounded text-sm">ลบ</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Redemptions Tab -->
    <div id="tab-redemptions" class="tab-content p-6 hidden">
        <h3 class="font-semibold mb-4">ประวัติการแลกของรางวัล</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">รหัส</th>
                        <th class="px-4 py-3 text-left">ผู้ใช้</th>
                        <th class="px-4 py-3 text-left">ของรางวัล</th>
                        <th class="px-4 py-3 text-right">แต้ม</th>
                        <th class="px-4 py-3 text-center">สถานะ</th>
                        <th class="px-4 py-3 text-left">วันที่</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($redemptions as $r): ?>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs"><?= $r['redemption_code'] ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <img src="<?= htmlspecialchars($r['picture_url'] ?? 'https://via.placeholder.com/32') ?>" class="w-8 h-8 rounded-full mr-2">
                                <?= htmlspecialchars($r['display_name']) ?>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?= htmlspecialchars($r['reward_name']) ?></td>
                        <td class="px-4 py-3 text-right"><?= number_format($r['points_used']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $statusColors = ['pending' => 'yellow', 'approved' => 'blue', 'delivered' => 'green', 'cancelled' => 'red'];
                            $statusLabels = ['pending' => 'รอดำเนินการ', 'approved' => 'อนุมัติ', 'delivered' => 'ส่งแล้ว', 'cancelled' => 'ยกเลิก'];
                            ?>
                            <span class="px-2 py-1 bg-<?= $statusColors[$r['status']] ?>-100 text-<?= $statusColors[$r['status']] ?>-600 rounded text-xs">
                                <?= $statusLabels[$r['status']] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="update_redemption">
                                <input type="hidden" name="redemption_id" value="<?= $r['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="text-xs border rounded px-2 py-1">
                                    <option value="">เปลี่ยนสถานะ</option>
                                    <option value="approved">อนุมัติ</option>
                                    <option value="delivered">ส่งแล้ว</option>
                                    <option value="cancelled">ยกเลิก</option>
                                </select>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reward Modal -->
<div id="rewardModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold" id="modalTitle">เพิ่มของรางวัล</h3>
            <button onclick="closeRewardModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-4 space-y-4">
            <input type="hidden" name="action" id="rewardAction" value="create_reward">
            <input type="hidden" name="reward_id" id="rewardId">
            
            <div>
                <label class="block text-sm font-medium mb-1">ชื่อของรางวัล *</label>
                <input type="text" name="name" id="rewardName" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">รายละเอียด</label>
                <textarea name="description" id="rewardDesc" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">รูปภาพ URL</label>
                <input type="url" name="image_url" id="rewardImage" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">แต้มที่ต้องใช้ *</label>
                    <input type="number" name="points_required" id="rewardPoints" required min="1" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">จำนวน</label>
                    <input type="number" name="stock" id="rewardStock" placeholder="-1 = ไม่จำกัด" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ประเภท</label>
                    <select name="reward_type" id="rewardType" class="w-full px-3 py-2 border rounded-lg">
                        <option value="gift">ของขวัญ</option>
                        <option value="discount">ส่วนลด</option>
                        <option value="coupon">คูปอง</option>
                        <option value="product">สินค้า</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">จำกัดต่อคน</label>
                    <input type="number" name="max_per_user" id="rewardMaxUser" placeholder="0 = ไม่จำกัด" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div id="editActiveDiv" class="hidden">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="rewardActive" class="w-5 h-5 text-green-600 rounded">
                    <span class="ml-2">เปิดใช้งาน</span>
                </label>
            </div>
            <div class="flex gap-2 pt-4">
                <button type="button" onclick="closeRewardModal()" class="flex-1 px-4 py-2 border rounded-lg">ยกเลิก</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<style>
.tab-btn { color: #9ca3af; border-color: transparent; }
.tab-btn.active { color: #06C755; border-color: #06C755; }
</style>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelector('[data-tab="' + tab + '"]').classList.add('active');
}

function showRewardModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มของรางวัล';
    document.getElementById('rewardAction').value = 'create_reward';
    document.getElementById('rewardId').value = '';
    document.getElementById('rewardName').value = '';
    document.getElementById('rewardDesc').value = '';
    document.getElementById('rewardImage').value = '';
    document.getElementById('rewardPoints').value = '';
    document.getElementById('rewardStock').value = '';
    document.getElementById('rewardType').value = 'gift';
    document.getElementById('rewardMaxUser').value = '';
    document.getElementById('editActiveDiv').classList.add('hidden');
    document.getElementById('rewardModal').classList.remove('hidden');
    document.getElementById('rewardModal').classList.add('flex');
}

function editReward(reward) {
    document.getElementById('modalTitle').textContent = 'แก้ไขของรางวัล';
    document.getElementById('rewardAction').value = 'update_reward';
    document.getElementById('rewardId').value = reward.id;
    document.getElementById('rewardName').value = reward.name;
    document.getElementById('rewardDesc').value = reward.description || '';
    document.getElementById('rewardImage').value = reward.image_url || '';
    document.getElementById('rewardPoints').value = reward.points_required;
    document.getElementById('rewardStock').value = reward.stock;
    document.getElementById('rewardType').value = reward.reward_type;
    document.getElementById('rewardMaxUser').value = reward.max_per_user;
    document.getElementById('rewardActive').checked = reward.is_active == 1;
    document.getElementById('editActiveDiv').classList.remove('hidden');
    document.getElementById('rewardModal').classList.remove('hidden');
    document.getElementById('rewardModal').classList.add('flex');
}

function closeRewardModal() {
    document.getElementById('rewardModal').classList.add('hidden');
    document.getElementById('rewardModal').classList.remove('flex');
}
</script>

<?php require_once 'includes/footer.php'; ?>
