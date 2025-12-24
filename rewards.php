<?php
/**
 * Rewards Management - จัดการของรางวัลแลกแต้ม
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'จัดการของรางวัล';

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS point_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    points_required INT NOT NULL DEFAULT 100,
    type ENUM('discount','shipping','gift','product','coupon') DEFAULT 'discount',
    value DECIMAL(10,2) DEFAULT 0 COMMENT 'มูลค่า เช่น ส่วนลด 50 บาท',
    image_url VARCHAR(500),
    stock INT DEFAULT NULL COMMENT 'NULL = ไม่จำกัด',
    redeemed_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reward_active (is_active),
    INDEX idx_reward_points (points_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $pointsRequired = (int)$_POST['points_required'];
        $type = $_POST['type'];
        $value = (float)($_POST['value'] ?? 0);
        $stock = $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id) {
            $stmt = $db->prepare("UPDATE point_rewards SET name=?, description=?, points_required=?, type=?, value=?, stock=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $description, $pointsRequired, $type, $value, $stock, $isActive, $id]);
            $message = 'อัพเดทของรางวัลสำเร็จ!';
        } else {
            $stmt = $db->prepare("INSERT INTO point_rewards (name, description, points_required, type, value, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $pointsRequired, $type, $value, $stock, $isActive]);
            $message = 'เพิ่มของรางวัลสำเร็จ!';
        }
        $messageType = 'success';
        
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM point_rewards WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'ลบของรางวัลสำเร็จ!';
        $messageType = 'success';
        
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE point_rewards SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Get rewards
$rewards = $db->query("SELECT * FROM point_rewards ORDER BY points_required ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get redemption stats
$stats = $db->query("SELECT 
    COUNT(*) as total_rewards,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_rewards,
    SUM(redeemed_count) as total_redeemed
    FROM point_rewards")->fetch();

require_once 'includes/header.php';
?>

<?php if ($message): ?>
<div class="mb-4 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
    <i class="fas fa-check-circle mr-2"></i><?= $message ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-gray-500 text-sm">ของรางวัลทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-800"><?= $stats['total_rewards'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-gray-500 text-sm">เปิดใช้งาน</p>
        <p class="text-2xl font-bold text-green-600"><?= $stats['active_rewards'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-gray-500 text-sm">แลกไปแล้ว</p>
        <p class="text-2xl font-bold text-purple-600"><?= number_format($stats['total_redeemed'] ?? 0) ?> ครั้ง</p>
    </div>
</div>

<!-- Add Button -->
<div class="mb-4">
    <button onclick="openModal()" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
        <i class="fas fa-plus mr-2"></i>เพิ่มของรางวัล
    </button>
</div>

<!-- Rewards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($rewards as $reward): ?>
    <div class="bg-white rounded-xl shadow overflow-hidden <?= !$reward['is_active'] ? 'opacity-60' : '' ?>">
        <div class="p-4">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($reward['name']) ?></h3>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($reward['description'] ?: '-') ?></p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $reward['is_active'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $reward['is_active'] ? 'เปิด' : 'ปิด' ?>
                </span>
            </div>
            
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-coins text-yellow-500"></i>
                    <span class="font-bold text-purple-600"><?= number_format($reward['points_required']) ?> แต้ม</span>
                </div>
                <?php if ($reward['value'] > 0): ?>
                <span class="text-sm text-gray-500">มูลค่า ฿<?= number_format($reward['value']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                <span>
                    <?php
                    $typeLabels = ['discount' => '🏷️ ส่วนลด', 'shipping' => '🚚 จัดส่งฟรี', 'gift' => '🎁 ของขวัญ', 'product' => '📦 สินค้า', 'coupon' => '🎟️ คูปอง'];
                    echo $typeLabels[$reward['type']] ?? $reward['type'];
                    ?>
                </span>
                <span>
                    <?= $reward['stock'] !== null ? "คงเหลือ {$reward['stock']}" : 'ไม่จำกัด' ?>
                </span>
            </div>
            
            <div class="flex gap-2">
                <button onclick="editReward(<?= htmlspecialchars(json_encode($reward)) ?>)" class="flex-1 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>แก้ไข
                </button>
                <form method="POST" class="flex-1" onsubmit="return confirm('ยืนยันการลบ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $reward['id'] ?>">
                    <button type="submit" class="w-full py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 text-sm">
                        <i class="fas fa-trash mr-1"></i>ลบ
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($rewards)): ?>
    <div class="col-span-full text-center py-12 text-gray-500">
        <i class="fas fa-gift text-5xl mb-4 text-gray-300"></i>
        <p>ยังไม่มีของรางวัล</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="rewardModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold mb-4" id="modalTitle"><i class="fas fa-gift text-purple-500 mr-2"></i>เพิ่มของรางวัล</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="reward_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อของรางวัล *</label>
                    <input type="text" name="name" id="reward_name" required class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">รายละเอียด</label>
                    <textarea name="description" id="reward_description" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">แต้มที่ต้องใช้ *</label>
                        <input type="number" name="points_required" id="reward_points" required min="1" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ประเภท</label>
                        <select name="type" id="reward_type" class="w-full px-4 py-2 border rounded-lg">
                            <option value="discount">🏷️ ส่วนลด</option>
                            <option value="shipping">🚚 จัดส่งฟรี</option>
                            <option value="gift">🎁 ของขวัญ</option>
                            <option value="product">📦 สินค้า</option>
                            <option value="coupon">🎟️ คูปอง</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">มูลค่า (บาท)</label>
                        <input type="number" name="value" id="reward_value" min="0" step="0.01" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">จำนวน (เว้นว่าง = ไม่จำกัด)</label>
                        <input type="number" name="stock" id="reward_stock" min="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="reward_active" checked class="w-4 h-4">
                    <span>เปิดใช้งาน</span>
                </label>
            </div>
            
            <div class="flex gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="flex-1 py-2 border rounded-lg hover:bg-gray-50">ยกเลิก</button>
                <button type="submit" class="flex-1 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-gift text-purple-500 mr-2"></i>เพิ่มของรางวัล';
    document.getElementById('reward_id').value = '';
    document.getElementById('reward_name').value = '';
    document.getElementById('reward_description').value = '';
    document.getElementById('reward_points').value = '100';
    document.getElementById('reward_type').value = 'discount';
    document.getElementById('reward_value').value = '';
    document.getElementById('reward_stock').value = '';
    document.getElementById('reward_active').checked = true;
    document.getElementById('rewardModal').classList.remove('hidden');
    document.getElementById('rewardModal').classList.add('flex');
}

function editReward(reward) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-blue-500 mr-2"></i>แก้ไขของรางวัล';
    document.getElementById('reward_id').value = reward.id;
    document.getElementById('reward_name').value = reward.name;
    document.getElementById('reward_description').value = reward.description || '';
    document.getElementById('reward_points').value = reward.points_required;
    document.getElementById('reward_type').value = reward.type;
    document.getElementById('reward_value').value = reward.value || '';
    document.getElementById('reward_stock').value = reward.stock || '';
    document.getElementById('reward_active').checked = reward.is_active == 1;
    document.getElementById('rewardModal').classList.remove('hidden');
    document.getElementById('rewardModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('rewardModal').classList.add('hidden');
    document.getElementById('rewardModal').classList.remove('flex');
}
</script>

<?php require_once 'includes/footer.php'; ?>
