<?php
/**
 * Shop - รายละเอียดคำสั่งซื้อ (Fixed Version)
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

$lineManager = new LineAccountManager($db);
$line = $lineManager->getLineAPI($currentBotId);

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders_new.php');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'];
        $stmt = $db->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        if (!empty($_POST['tracking'])) {
            $stmt = $db->prepare("UPDATE transactions SET shipping_tracking = ? WHERE id = ?");
            $stmt->execute([$_POST['tracking'], $orderId]);
        }
        
        // Notify customer - ดึง reply_token ด้วย
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.reply_token, u.reply_token_expires FROM transactions o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['line_user_id']) {
            $statusText = [
                'pending' => '⏳ รอยืนยัน',
                'confirmed' => '✅ ยืนยันแล้ว',
                'paid' => '💰 ชำระเงินแล้ว',
                'shipping' => '🚚 กำลังจัดส่ง',
                'delivered' => '📦 จัดส่งแล้ว',
                'cancelled' => '❌ ยกเลิก'
            ];
            $msg = "📋 อัพเดทรายการ #{$order['order_number']}\n\nสถานะ: " . ($statusText[$newStatus] ?? $newStatus);
            if ($newStatus === 'shipping' && !empty($_POST['tracking'])) {
                $msg .= "\n🚚 เลขพัสดุ: " . $_POST['tracking'];
            }
            // ใช้ sendMessage ถ้ามี หรือ fallback ไป pushMessage
            if (method_exists($line, 'sendMessage')) {
                $line->sendMessage($order['line_user_id'], $msg, $order['reply_token'] ?? null, $order['reply_token_expires'] ?? null, $db);
            } else {
                $line->pushMessage($order['line_user_id'], $msg);
            }
        }
        
        header("Location: order-detail-new.php?id={$orderId}&updated=1");
        exit;
    }
    
    if ($action === 'approve_payment') {
        $stmt = $db->prepare("UPDATE transactions SET payment_status = 'paid', status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        try {
            $stmt = $db->prepare("UPDATE payment_slips SET status = 'approved' WHERE transaction_id = ? AND status = 'pending'");
            $stmt->execute([$orderId]);
        } catch (Exception $e) {}
        
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.reply_token, u.reply_token_expires FROM transactions o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
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
        
        header("Location: order-detail-new.php?id={$orderId}&updated=1");
        exit;
    }
    
    if ($action === 'reject_payment') {
        try {
            $stmt = $db->prepare("UPDATE payment_slips SET status = 'rejected' WHERE transaction_id = ? AND status = 'pending'");
            $stmt->execute([$orderId]);
        } catch (Exception $e) {}
        
        $stmt = $db->prepare("SELECT o.*, u.line_user_id, u.reply_token, u.reply_token_expires FROM transactions o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['line_user_id']) {
            // ใช้ sendMessage ถ้ามี หรือ fallback ไป pushMessage
            $msg = "❌ หลักฐานการชำระเงินไม่ถูกต้อง\n\nรายการ #{$order['order_number']}\nกรุณาส่งหลักฐานใหม่อีกครั้ง";
            if (method_exists($line, 'sendMessage')) {
                $line->sendMessage($order['line_user_id'], $msg, $order['reply_token'] ?? null, $order['reply_token_expires'] ?? null, $db);
            } else {
                $line->pushMessage($order['line_user_id'], $msg);
            }
        }
        
        header("Location: order-detail-new.php?id={$orderId}&rejected=1");
        exit;
    }
    
    if ($action === 'update_shipping') {
        $stmt = $db->prepare("UPDATE transactions SET shipping_name=?, shipping_phone=?, shipping_address=? WHERE id=?");
        $stmt->execute([$_POST['shipping_name'], $_POST['shipping_phone'], $_POST['shipping_address'], $orderId]);
        
        header("Location: order-detail-new.php?id={$orderId}&updated=1");
        exit;
    }
}

// Get order (use transactions table - unified with LIFF)
$stmt = $db->prepare("SELECT o.*, u.display_name, u.picture_url, u.line_user_id 
                      FROM transactions o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders_new.php');
    exit;
}

// Get order items (use transaction_items table)
$stmt = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment slips (use transaction_id)
$slips = [];
try {
    $stmt = $db->prepare("SELECT * FROM payment_slips WHERE transaction_id = ? ORDER BY created_at DESC");
    $stmt->execute([$orderId]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$pageTitle = "รายการ #{$order['order_number']}";

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-600',
    'confirmed' => 'bg-blue-100 text-blue-600',
    'paid' => 'bg-green-100 text-green-600',
    'shipping' => 'bg-purple-100 text-purple-600',
    'delivered' => 'bg-gray-100 text-gray-600',
    'cancelled' => 'bg-red-100 text-red-600'
];
$statusLabels = [
    'pending' => 'รอยืนยัน',
    'confirmed' => 'ยืนยันแล้ว',
    'paid' => 'ชำระแล้ว',
    'shipping' => 'กำลังส่ง',
    'delivered' => 'ส่งแล้ว',
    'cancelled' => 'ยกเลิก'
];

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['updated'])): ?>
<div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>อัพเดทสำเร็จ!
</div>
<?php endif; ?>

<div class="mb-4">
    <a href="orders_new.php" class="text-green-600 hover:underline"><i class="fas fa-arrow-left mr-2"></i>กลับไปรายการคำสั่งซื้อ</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold">#<?= htmlspecialchars($order['order_number']) ?></h3>
                    <p class="text-gray-500"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-medium <?= $statusColors[$order['status']] ?>">
                    <?= $statusLabels[$order['status']] ?>
                </span>
            </div>
            
            <!-- Customer -->
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <img src="<?= $order['picture_url'] ?: 'https://via.placeholder.com/48' ?>" class="w-12 h-12 rounded-full mr-4">
                <div class="flex-1">
                    <p class="font-medium"><?= htmlspecialchars($order['display_name']) ?></p>
                    <p class="text-sm text-gray-500">LINE User</p>
                </div>
            </div>
        </div>
        
        <!-- Items -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-semibold mb-4">รายการสินค้า</h4>
            <div class="space-y-3">
                <?php foreach ($items as $item): ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($item['product_name']) ?></p>
                        <p class="text-sm text-gray-500">฿<?= number_format($item['product_price'], 2) ?> x <?= $item['quantity'] ?></p>
                    </div>
                    <p class="font-medium">฿<?= number_format($item['subtotal'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="border-t mt-4 pt-4 space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>ยอดสินค้า</span>
                    <span>฿<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>ค่าจัดส่ง</span>
                    <span><?= $order['shipping_fee'] > 0 ? '฿' . number_format($order['shipping_fee'], 2) : 'ฟรี' ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="flex justify-between text-green-600">
                    <span>ส่วนลด</span>
                    <span>-฿<?= number_format($order['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                    <span>รวมทั้งหมด</span>
                    <span class="text-green-600">฿<?= number_format($order['grand_total'], 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Shipping Info -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-semibold mb-4">ข้อมูลจัดส่ง</h4>
            <form method="POST">
                <input type="hidden" name="action" value="update_shipping">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">ชื่อผู้รับ</label>
                        <input type="text" name="shipping_name" value="<?= htmlspecialchars($order['shipping_name'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">เบอร์โทร</label>
                        <input type="text" name="shipping_phone" value="<?= htmlspecialchars($order['shipping_phone'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">ที่อยู่จัดส่ง</label>
                    <textarea name="shipping_address" rows="3" class="w-full px-4 py-2 border rounded-lg"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    <i class="fas fa-save mr-2"></i>บันทึกที่อยู่
                </button>
            </form>
            
            <?php if (!empty($order['shipping_tracking'])): ?>
            <div class="mt-4 p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-purple-600"><i class="fas fa-truck mr-2"></i>เลขพัสดุ: <strong><?= htmlspecialchars($order['shipping_tracking']) ?></strong></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status Update -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-semibold mb-4">🔧 เปลี่ยนสถานะ</h4>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="update_status">
                <select name="status" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="tracking" placeholder="เลขพัสดุ (ถ้ามี)" value="<?= htmlspecialchars($order['shipping_tracking'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg">
                <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-save mr-2"></i>อัพเดท
                </button>
            </form>
        </div>
        
        <!-- Payment Slips -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-semibold mb-4">💳 หลักฐานการชำระเงิน</h4>
            
            <!-- Payment Status -->
            <div class="mb-4 p-3 rounded-lg <?= ($order['payment_status'] ?? '') === 'paid' ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' ?>">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">สถานะการชำระ:</span>
                    <span class="px-3 py-1 rounded-full text-sm <?= ($order['payment_status'] ?? '') === 'paid' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white' ?>">
                        <?= ($order['payment_status'] ?? '') === 'paid' ? '✅ ชำระแล้ว' : '⏳ รอชำระ' ?>
                    </span>
                </div>
            </div>
            
            <?php if (empty($slips)): ?>
            <div class="text-center py-6 bg-gray-50 rounded-lg">
                <i class="fas fa-receipt text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-500">ยังไม่มีหลักฐานการชำระเงิน</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($slips as $slip): ?>
                <div class="border rounded-lg overflow-hidden">
                    <img src="<?= htmlspecialchars($slip['image_url']) ?>" class="w-full max-h-48 object-contain bg-gray-100">
                    <div class="p-2 text-sm flex justify-between items-center">
                        <span class="text-gray-500"><?= date('d/m/Y H:i', strtotime($slip['created_at'])) ?></span>
                        <span class="px-2 py-1 rounded text-xs <?= $slip['status'] === 'approved' ? 'bg-green-100 text-green-600' : ($slip['status'] === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600') ?>">
                            <?= $slip['status'] === 'approved' ? '✅ อนุมัติ' : ($slip['status'] === 'rejected' ? '❌ ปฏิเสธ' : '⏳ รอตรวจ') ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (($order['payment_status'] ?? '') !== 'paid'): ?>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <form method="POST">
                    <input type="hidden" name="action" value="approve_payment">
                    <button type="submit" onclick="return confirm('ยืนยันการชำระเงิน?')" class="w-full py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        <i class="fas fa-check mr-1"></i>อนุมัติ
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="reject_payment">
                    <button type="submit" onclick="return confirm('ปฏิเสธหลักฐานนี้?')" class="w-full py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                        <i class="fas fa-times mr-1"></i>ปฏิเสธ
                    </button>
                </form>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Note -->
        <?php if (!empty($order['note'])): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-semibold mb-2">หมายเหตุ</h4>
            <p class="text-gray-600"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
