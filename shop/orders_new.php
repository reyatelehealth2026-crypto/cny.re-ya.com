<?php
/**
 * Shop - จัดการคำสั่งซื้อ/รายการ
 * V2.6 - Fixed version
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'รายการ/คำสั่งซื้อ';

// Always use orders table (simpler approach)
$ordersTable = 'orders';
$itemsTable = 'order_items';
$itemsFk = 'order_id';
$tablesExist = false;

try {
    $db->query("SELECT 1 FROM orders LIMIT 1");
    $tablesExist = true;
} catch (Exception $e) {
    // Create tables if not exist
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT DEFAULT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            shipping_fee DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            grand_total DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'paid', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            shipping_name VARCHAR(255),
            shipping_phone VARCHAR(20),
            shipping_address TEXT,
            shipping_tracking VARCHAR(100),
            note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL
        )");
        
        $tablesExist = true;
    } catch (Exception $e3) {
        $error = "ไม่สามารถสร้างตารางได้: " . $e3->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';

if (isset($error)): ?>
<div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
</div>
<?php endif;

if (!$tablesExist): ?>
<div class="bg-yellow-100 text-yellow-700 p-4 rounded-lg">
    <i class="fas fa-exclamation-triangle mr-2"></i>ระบบคำสั่งซื้อยังไม่พร้อมใช้งาน
</div>
<?php 
require_once __DIR__ . '/../includes/footer.php';
exit;
endif;

// Get LineAPI for current bot
$lineManager = new LineAccountManager($db);
$line = $lineManager->getLineAPI($currentBotId);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';
    
    if ($action === 'update_status' && $orderId) {
        $newStatus = $_POST['status'];
        $stmt = $db->prepare("UPDATE {$ordersTable} SET status = ? WHERE id = ? AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$newStatus, $orderId, $currentBotId]);
        
        // Notify customer via LINE - ดึง reply_token ด้วย
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.display_name, u.reply_token, u.reply_token_expires FROM {$ordersTable} o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['line_user_id']) {
            $statusText = [
                'confirmed' => '✅ ยืนยันแล้ว',
                'paid' => '💰 ชำระเงินแล้ว',
                'shipping' => '🚚 กำลังจัดส่ง',
                'delivered' => '📦 จัดส่งแล้ว',
                'cancelled' => '❌ ยกเลิก'
            ];
            $msg = "📋 อัพเดทรายการ #{$order['order_number']}\n\nสถานะ: " . ($statusText[$newStatus] ?? $newStatus);
            if ($newStatus === 'shipping' && !empty($_POST['tracking'])) {
                $stmt = $db->prepare("UPDATE {$ordersTable} SET shipping_tracking = ? WHERE id = ?");
                $stmt->execute([$_POST['tracking'], $orderId]);
                $msg .= "\n🚚 เลขพัสดุ: " . $_POST['tracking'];
            }
            // ใช้ sendMessage ถ้ามี หรือ fallback ไป pushMessage
            if (method_exists($line, 'sendMessage')) {
                $line->sendMessage($order['line_user_id'], $msg, $order['reply_token'] ?? null, $order['reply_token_expires'] ?? null, $db);
            } else {
                $line->pushMessage($order['line_user_id'], $msg);
            }
        }
    } elseif ($action === 'approve_payment' && $orderId) {
        $stmt = $db->prepare("UPDATE {$ordersTable} SET payment_status = 'paid', status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.reply_token, u.reply_token_expires FROM {$ordersTable} o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if ($order && $order['line_user_id']) {
            // ใช้ sendMessage ถ้ามี หรือ fallback ไป pushMessage
            $msg = "✅ ยืนยันการชำระเงินแล้ว!\n\nรายการ #{$order['order_number']}\nกำลังเตรียมดำเนินการ";
            if (method_exists($line, 'sendMessage')) {
                $line->sendMessage($order['line_user_id'], $msg, $order['reply_token'] ?? null, $order['reply_token_expires'] ?? null, $db);
            } else {
                $line->pushMessage($order['line_user_id'], $msg);
            }
        }
    }
    header('Location: orders_new.php');
    exit;
}

// Get orders filtered by current bot
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.display_name, u.picture_url,
        (SELECT COUNT(*) FROM {$itemsTable} WHERE {$itemsFk} = o.id) as item_count
        FROM {$ordersTable} o 
        JOIN users u ON o.user_id = u.id
        WHERE (o.line_account_id = ? OR o.line_account_id IS NULL)";
$params = [$currentBotId];

if ($statusFilter) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Count by status
$statusCounts = [];
try {
    $stmt = $db->prepare("SELECT status, COUNT(*) as c FROM {$ordersTable} WHERE (line_account_id = ? OR line_account_id IS NULL) GROUP BY status");
    $stmt->execute([$currentBotId]);
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['c'];
    }
} catch (Exception $e) {}

$statuses = [
    'pending' => ['label' => 'รอยืนยัน', 'color' => 'yellow'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'color' => 'blue'],
    'paid' => ['label' => 'ชำระแล้ว', 'color' => 'green'],
    'shipping' => ['label' => 'กำลังส่ง', 'color' => 'purple'],
    'delivered' => ['label' => 'ส่งแล้ว', 'color' => 'gray'],
    'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red']
];
?>

<!-- Status Tabs -->
<div class="mb-6 flex flex-wrap gap-2">
    <a href="?" class="px-4 py-2 rounded-lg <?= !$statusFilter ? 'bg-green-500 text-white' : 'bg-white hover:bg-gray-50' ?>">
        ทั้งหมด <span class="ml-1 text-sm">(<?= array_sum($statusCounts) ?>)</span>
    </a>
    <?php foreach ($statuses as $key => $status): ?>
    <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-lg <?= $statusFilter === $key ? 'bg-'.$status['color'].'-500 text-white' : 'bg-white hover:bg-gray-50' ?>">
        <?= $status['label'] ?> <span class="ml-1 text-sm">(<?= $statusCounts[$key] ?? 0 ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Orders List -->
<div class="space-y-4">
    <?php foreach ($orders as $order): ?>
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <div class="flex items-center">
                <img src="<?= $order['picture_url'] ?: 'https://via.placeholder.com/40' ?>" class="w-10 h-10 rounded-full mr-3">
                <div>
                    <p class="font-semibold">#<?= $order['order_number'] ?></p>
                    <p class="text-sm text-gray-500"><?= $order['display_name'] ?> • <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
            <div class="text-right">
                <?php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-600',
                    'confirmed' => 'bg-blue-100 text-blue-600',
                    'paid' => 'bg-green-100 text-green-600',
                    'shipping' => 'bg-purple-100 text-purple-600',
                    'delivered' => 'bg-gray-100 text-gray-600',
                    'cancelled' => 'bg-red-100 text-red-600'
                ];
                ?>
                <span class="px-3 py-1 rounded-full text-sm <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                    <?= $statuses[$order['status']]['label'] ?? $order['status'] ?>
                </span>
                <p class="text-lg font-bold text-green-600 mt-1">฿<?= number_format($order['grand_total'], 2) ?></p>
            </div>
        </div>
        <div class="p-4 bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <span><i class="fas fa-box mr-1"></i><?= $order['item_count'] ?> รายการ</span>
                <?php if (!empty($order['shipping_tracking'])): ?>
                <span class="ml-4"><i class="fas fa-truck mr-1"></i><?= $order['shipping_tracking'] ?></span>
                <?php endif; ?>
            </div>
            <div class="flex space-x-2">
                <a href="order-detail.php?id=<?= $order['id'] ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 text-sm">
                    <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                </a>
                <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm">
                        <i class="fas fa-check mr-1"></i>ยืนยัน
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($orders)): ?>
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
        <i class="fas fa-shopping-bag text-6xl mb-4"></i>
        <p>ยังไม่มีคำสั่งซื้อ</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
