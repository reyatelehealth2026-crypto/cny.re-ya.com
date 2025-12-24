<?php
/**
 * Shop - จัดการคำสั่งซื้อ/รายการ
 * V2.5 - รองรับทั้ง orders และ transactions
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'รายการ/คำสั่งซื้อ';

// Use transactions table (unified with LIFF checkout)
$_useTransactions = true;
$_ordersTable = 'transactions';
$_itemsTable = 'transaction_items';
$_itemsFk = 'transaction_id';
$tablesExist = true;

// Check if transactions table exists
try {
    $db->query("SELECT 1 FROM transactions LIMIT 1");
} catch (Exception $e) {
    $tablesExist = false;
    $error = "ตาราง transactions ยังไม่ถูกสร้าง กรุณารัน migration ก่อน";
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

$lineManager = new LineAccountManager($db);
$line = $lineManager->getLineAPI($currentBotId);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';
    
    if ($action === 'update_status' && $orderId) {
        $newStatus = $_POST['status'];
        $stmt = $db->prepare("UPDATE {$_ordersTable} SET status = ? WHERE id = ? AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$newStatus, $orderId, $currentBotId]);
        
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.display_name, u.reply_token, u.reply_token_expires FROM {$_ordersTable} o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['line_user_id']) {
            $statusText = ['confirmed' => '✅ ยืนยันแล้ว', 'paid' => '💰 ชำระเงินแล้ว', 'shipping' => '🚚 กำลังจัดส่ง', 'delivered' => '📦 จัดส่งแล้ว', 'cancelled' => '❌ ยกเลิก'];
            $msg = "📋 อัพเดทรายการ #{$order['order_number']}\n\nสถานะ: " . ($statusText[$newStatus] ?? $newStatus);
            if ($newStatus === 'shipping' && !empty($_POST['tracking'])) {
                $stmt = $db->prepare("UPDATE {$_ordersTable} SET shipping_tracking = ? WHERE id = ?");
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
        $stmt = $db->prepare("UPDATE {$_ordersTable} SET payment_status = 'paid', status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.reply_token, u.reply_token_expires FROM {$_ordersTable} o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
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
    header('Location: orders.php');
    exit;
}

// Get orders
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$botIdForQuery = $currentBotId ?? $_SESSION['current_bot_id'] ?? null;

$sql = "SELECT o.*, u.display_name, u.picture_url,
        (SELECT COUNT(*) FROM {$_itemsTable} WHERE {$_itemsFk} = o.id) as item_count
        FROM {$_ordersTable} o 
        JOIN users u ON o.user_id = u.id";

if ($botIdForQuery) {
    $sql .= " WHERE (o.line_account_id = ? OR o.line_account_id IS NULL)";
    $params = [$botIdForQuery];
} else {
    $sql .= " WHERE 1=1";
    $params = [];
}

if ($statusFilter) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}
if ($typeFilter && $_useTransactions) {
    $sql .= " AND o.transaction_type = ?";
    $params[] = $typeFilter;
}

// Filter by pending slips
if (isset($_GET['pending_slip']) && $_GET['pending_slip'] == '1') {
    $sql .= " AND o.id IN (SELECT DISTINCT transaction_id FROM payment_slips WHERE status = 'pending')";
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Count by status
$statusCounts = [];
try {
    if ($botIdForQuery) {
        $stmt = $db->prepare("SELECT status, COUNT(*) as c FROM {$_ordersTable} WHERE (line_account_id = ? OR line_account_id IS NULL) GROUP BY status");
        $stmt->execute([$botIdForQuery]);
    } else {
        $stmt = $db->query("SELECT status, COUNT(*) as c FROM {$_ordersTable} GROUP BY status");
    }
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['c'];
    }
} catch (Exception $e) {}

// Count pending slips (uploaded but not approved)
$pendingSlipsCount = 0;
$ordersWithPendingSlips = [];
try {
    $sql = "SELECT DISTINCT t.id, t.order_number 
            FROM transactions t 
            INNER JOIN payment_slips ps ON ps.transaction_id = t.id 
            WHERE ps.status = 'pending'";
    if ($botIdForQuery) {
        $sql .= " AND (t.line_account_id = ? OR t.line_account_id IS NULL)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$botIdForQuery]);
    } else {
        $stmt = $db->query($sql);
    }
    $ordersWithPendingSlips = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $pendingSlipsCount = count($ordersWithPendingSlips);
} catch (Exception $e) {}

$transactionTypes = [
    'purchase' => ['icon' => '🛒', 'label' => 'ซื้อสินค้า'],
    'booking' => ['icon' => '📅', 'label' => 'จองคิว'],
    'subscription' => ['icon' => '🔄', 'label' => 'สมัครสมาชิก'],
    'redemption' => ['icon' => '🎁', 'label' => 'แลกของรางวัล']
];

// Count dispensing records
$dispenseCount = 0;
try {
    if ($botIdForQuery) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM dispensing_records WHERE line_account_id = ?");
        $stmt->execute([$botIdForQuery]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM dispensing_records");
    }
    $dispenseCount = $stmt->fetchColumn();
} catch (Exception $e) {}

// Check if viewing dispense tab
$viewDispense = isset($_GET['view']) && $_GET['view'] === 'dispense';

// Get dispensing records if viewing dispense tab
$dispenseRecords = [];
if ($viewDispense) {
    try {
        $sql = "SELECT d.*, u.display_name, u.picture_url 
                FROM dispensing_records d 
                JOIN users u ON d.user_id = u.id";
        if ($botIdForQuery) {
            $sql .= " WHERE d.line_account_id = ?";
            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$botIdForQuery]);
        } else {
            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $db->query($sql);
        }
        $dispenseRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>

<?php if ($_useTransactions): ?>
<div class="mb-4 flex flex-wrap gap-2">
    <a href="?" class="px-3 py-1.5 rounded-lg text-sm <?= !$typeFilter && !$viewDispense ? 'bg-purple-500 text-white' : 'bg-white hover:bg-gray-50' ?>">ทุกประเภท</a>
    <?php foreach ($transactionTypes as $key => $type): ?>
    <a href="?type=<?= $key ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $typeFilter === $key ? 'bg-purple-500 text-white' : 'bg-white hover:bg-gray-50' ?>"><?= $type['icon'] ?> <?= $type['label'] ?></a>
    <?php endforeach; ?>
    <a href="?view=dispense" class="px-3 py-1.5 rounded-lg text-sm <?= $viewDispense ? 'bg-green-500 text-white' : 'bg-white hover:bg-gray-50' ?>">💊 จ่ายยา <?php if ($dispenseCount > 0): ?><span class="ml-1 px-1.5 py-0.5 bg-green-600 text-white rounded-full text-xs"><?= $dispenseCount ?></span><?php endif; ?></a>
</div>
<?php endif; ?>

<?php if (!$viewDispense): ?>
<?php if ($pendingSlipsCount > 0): ?>
<div class="mb-4 p-4 bg-orange-100 border border-orange-300 rounded-lg flex items-center justify-between">
    <div class="flex items-center">
        <i class="fas fa-receipt text-orange-500 text-xl mr-3"></i>
        <div>
            <p class="font-semibold text-orange-700">มีสลิปรอตรวจสอบ <?= $pendingSlipsCount ?> รายการ</p>
            <p class="text-sm text-orange-600">กรุณาตรวจสอบและอนุมัติสลิปการชำระเงิน</p>
        </div>
    </div>
    <a href="?pending_slip=1<?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
        <i class="fas fa-eye mr-1"></i>ดูรายการ
    </a>
</div>
<?php endif; ?>

<div class="mb-6 flex flex-wrap gap-2">
    <a href="?<?= $typeFilter ? 'type='.$typeFilter : '' ?>" class="px-4 py-2 rounded-lg <?= !$statusFilter && !isset($_GET['pending_slip']) ? 'bg-green-500 text-white' : 'bg-white hover:bg-gray-50' ?>">ทั้งหมด <span class="ml-1 text-sm">(<?= array_sum($statusCounts) ?>)</span></a>
    <?php if ($pendingSlipsCount > 0): ?>
    <a href="?pending_slip=1<?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="px-4 py-2 rounded-lg flex items-center gap-2 <?= isset($_GET['pending_slip']) ? 'bg-orange-500 text-white' : 'bg-white hover:bg-gray-50' ?>">
        <i class="fas fa-receipt"></i>รอตรวจสลิป 
        <span class="px-2 py-0.5 bg-orange-600 text-white rounded-full text-xs"><?= $pendingSlipsCount ?></span>
    </a>
    <?php endif; ?>
    <?php
    $statuses = [
        'pending' => ['label' => 'รอยืนยัน', 'color' => 'yellow'],
        'confirmed' => ['label' => 'ยืนยันแล้ว', 'color' => 'blue'],
        'paid' => ['label' => 'ชำระแล้ว', 'color' => 'green'],
        'shipping' => ['label' => 'กำลังส่ง', 'color' => 'purple'],
        'delivered' => ['label' => 'ส่งแล้ว', 'color' => 'gray'],
        'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red']
    ];
    foreach ($statuses as $key => $status):
    ?>
    <a href="?status=<?= $key ?><?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="px-4 py-2 rounded-lg <?= $statusFilter === $key ? 'bg-'.$status['color'].'-500 text-white' : 'bg-white hover:bg-gray-50' ?>"><?= $status['label'] ?> <span class="ml-1 text-sm">(<?= $statusCounts[$key] ?? 0 ?>)</span></a>
    <?php endforeach; ?>
</div>

<div class="space-y-4">
    <?php foreach ($orders as $order): ?>
    <?php 
    $transType = $order['transaction_type'] ?? 'purchase'; 
    $typeInfo = $transactionTypes[$transType] ?? $transactionTypes['purchase'];
    $hasPendingSlip = in_array($order['id'], $ordersWithPendingSlips);
    $deliveryInfo = json_decode($order['delivery_info'] ?? '{}', true);
    ?>
    <div class="bg-white rounded-xl shadow overflow-hidden <?= $hasPendingSlip ? 'ring-2 ring-orange-400' : '' ?>">
        <?php if ($hasPendingSlip): ?>
        <div class="bg-orange-500 text-white px-4 py-2 text-sm flex items-center">
            <i class="fas fa-receipt mr-2"></i>
            <span class="font-semibold">มีสลิปรอตรวจสอบ</span>
            <a href="order-detail.php?id=<?= $order['id'] ?>" class="ml-auto bg-white text-orange-500 px-3 py-1 rounded text-xs font-semibold hover:bg-orange-50">ตรวจสอบเลย</a>
        </div>
        <?php endif; ?>
        <div class="p-4 border-b flex justify-between items-center">
            <div class="flex items-center">
                <img src="<?= $order['picture_url'] ?: 'https://via.placeholder.com/40' ?>" class="w-10 h-10 rounded-full mr-3">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold">#<?= $order['order_number'] ?></p>
                        <?php if ($_useTransactions && $transType !== 'purchase'): ?>
                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs"><?= $typeInfo['icon'] ?> <?= $typeInfo['label'] ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-500"><?= $order['display_name'] ?> • <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
            <div class="text-right">
                <?php $statusColors = ['pending' => 'bg-yellow-100 text-yellow-600', 'confirmed' => 'bg-blue-100 text-blue-600', 'paid' => 'bg-green-100 text-green-600', 'shipping' => 'bg-purple-100 text-purple-600', 'delivered' => 'bg-gray-100 text-gray-600', 'cancelled' => 'bg-red-100 text-red-600']; ?>
                <span class="px-3 py-1 rounded-full text-sm <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= $statuses[$order['status']]['label'] ?? $order['status'] ?></span>
                <p class="text-lg font-bold text-green-600 mt-1">฿<?= number_format($order['grand_total'], 2) ?></p>
            </div>
        </div>
        <?php if (!empty($deliveryInfo['name']) || !empty($deliveryInfo['phone']) || !empty($deliveryInfo['address'])): ?>
        <div class="px-4 py-3 bg-blue-50 border-b text-sm">
            <div class="flex items-start gap-4">
                <div class="text-blue-600"><i class="fas fa-truck"></i></div>
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
                    <?php if (!empty($deliveryInfo['name'])): ?>
                    <div><span class="text-gray-500">ผู้รับ:</span> <span class="font-medium"><?= htmlspecialchars($deliveryInfo['name']) ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($deliveryInfo['phone'])): ?>
                    <div><span class="text-gray-500">โทร:</span> <span class="font-medium"><?= htmlspecialchars($deliveryInfo['phone']) ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($deliveryInfo['address'])): ?>
                    <div class="md:col-span-3"><span class="text-gray-500">ที่อยู่:</span> <span class="font-medium"><?= htmlspecialchars($deliveryInfo['address']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="p-4 bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <span><i class="fas fa-box mr-1"></i><?= $order['item_count'] ?> รายการ</span>
                <?php if ($order['shipping_tracking']): ?>
                <span class="ml-4"><i class="fas fa-truck mr-1"></i><?= $order['shipping_tracking'] ?></span>
                <?php endif; ?>
            </div>
            <div class="flex space-x-2">
                <a href="order-detail.php?id=<?= $order['id'] ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 text-sm"><i class="fas fa-eye mr-1"></i>ดูรายละเอียด</a>
                <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"><i class="fas fa-check mr-1"></i>ยืนยัน</button>
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
<?php endif; ?>

<?php if ($viewDispense): ?>
<!-- Dispensing Records Section -->
<div class="mb-6 flex flex-wrap gap-2">
    <span class="px-4 py-2 bg-green-500 text-white rounded-lg">💊 รายการจ่ายยา (<?= count($dispenseRecords) ?>)</span>
</div>

<div class="space-y-4">
    <?php foreach ($dispenseRecords as $record): ?>
    <?php $items = json_decode($record['items'], true) ?: []; ?>
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center bg-green-50">
            <div class="flex items-center">
                <img src="<?= $record['picture_url'] ?: 'https://via.placeholder.com/40' ?>" class="w-10 h-10 rounded-full mr-3">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-green-700">#<?= $record['order_number'] ?></p>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">💊 จ่ายยา</span>
                    </div>
                    <p class="text-sm text-gray-500"><?= $record['display_name'] ?> • <?= date('d/m/Y H:i', strtotime($record['created_at'])) ?></p>
                </div>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-600"><?= $record['payment_status'] === 'paid' ? '✅ ชำระแล้ว' : '⏳ รอชำระ' ?></span>
                <p class="text-lg font-bold text-green-600 mt-1">฿<?= number_format($record['total_amount'], 2) ?></p>
            </div>
        </div>
        
        <!-- Items List -->
        <div class="p-4 space-y-3">
            <?php foreach ($items as $item): ?>
            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0">
                    <?php if (!empty($item['isMedicine']) && $item['isMedicine'] !== false): ?>
                    <span class="text-2xl"><?= ($item['usageType'] ?? 'internal') === 'external' ? '🧴' : '💊' ?></span>
                    <?php else: ?>
                    <span class="text-2xl">📦</span>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="text-sm text-gray-500">จำนวน: <?= $item['qty'] ?> <?= $item['unit'] ?? 'ชิ้น' ?></p>
                    
                    <?php if (!empty($item['isMedicine']) && $item['isMedicine'] !== false): ?>
                    <div class="mt-2 text-xs space-y-1">
                        <?php if (!empty($item['indication'])): ?>
                        <p class="text-blue-600">📋 ข้อบ่งใช้: <?= htmlspecialchars($item['indication']) ?></p>
                        <?php endif; ?>
                        <p class="text-purple-600">
                            💊 รับประทานครั้งละ <?= $item['dosage'] ?? 1 ?> <?= $item['dosageUnit'] ?? 'เม็ด' ?> 
                            <?php 
                            $freq = $item['frequency'] ?? '3';
                            echo $freq === 'prn' ? 'เมื่อมีอาการ' : $freq . ' ครั้ง/วัน';
                            ?>
                        </p>
                        <?php 
                        $mealText = ['before' => 'ก่อนอาหาร', 'after' => 'หลังอาหาร', 'with' => 'พร้อมอาหาร'];
                        $timeIcons = ['morning' => '🌅', 'noon' => '☀️', 'evening' => '🌆', 'bedtime' => '🌙'];
                        ?>
                        <p class="text-yellow-600">
                            ⏰ <?= $mealText[$item['mealTiming'] ?? 'after'] ?? 'หลังอาหาร' ?>
                            <?php if (!empty($item['timeOfDay'])): ?>
                            | <?= implode(' ', array_map(fn($t) => $timeIcons[$t] ?? '', $item['timeOfDay'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['notes'])): ?>
                    <p class="mt-1 text-xs text-gray-500">📝 <?= htmlspecialchars($item['notes']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <p class="font-bold text-green-600">฿<?= number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-4 bg-gray-50 flex justify-between items-center border-t">
            <div class="text-sm text-gray-600">
                <span><i class="fas fa-box mr-1"></i><?= count($items) ?> รายการ</span>
                <?php 
                $paymentText = ['cash' => '💵 เงินสด', 'transfer' => '📱 โอนเงิน', 'credit' => '💳 บัตรเครดิต', 'later' => '⏰ จ่ายทีหลัง'];
                ?>
                <span class="ml-4"><?= $paymentText[$record['payment_method']] ?? $record['payment_method'] ?></span>
            </div>
            <a href="../chat.php?user=<?= $record['user_id'] ?>" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm">
                <i class="fas fa-comments mr-1"></i>แชท
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($dispenseRecords)): ?>
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
        <i class="fas fa-pills text-6xl mb-4"></i>
        <p>ยังไม่มีรายการจ่ายยา</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
