<?php
/**
 * User Order Detail - รายละเอียดคำสั่งซื้อ (AJAX Version)
 */
$pageTitle = 'รายละเอียดคำสั่งซื้อ';
require_once '../includes/user_header.php';

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Get order (use transactions table - unified with LIFF)
$stmt = $db->prepare("SELECT o.*, u.display_name, u.picture_url, u.line_user_id FROM transactions o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? AND (o.line_account_id = ? OR o.line_account_id IS NULL)");
$stmt->execute([$orderId, $currentBotId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items (use transaction_items table)
$stmt = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment slips (use transaction_id)
$stmt = $db->prepare("SELECT * FROM payment_slips WHERE transaction_id = ? ORDER BY created_at DESC");
$stmt->execute([$orderId]);
$slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-600',
    'confirmed' => 'bg-blue-100 text-blue-600',
    'paid' => 'bg-green-100 text-green-600',
    'shipping' => 'bg-indigo-100 text-indigo-600',
    'delivered' => 'bg-emerald-100 text-emerald-600',
    'cancelled' => 'bg-red-100 text-red-600'
];

$statusNames = [
    'pending' => 'รอดำเนินการ',
    'confirmed' => 'ยืนยันแล้ว',
    'paid' => 'ชำระแล้ว',
    'shipping' => 'กำลังจัดส่ง',
    'delivered' => 'ส่งแล้ว',
    'cancelled' => 'ยกเลิก'
];
?>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="toast-message">สำเร็จ</span>
    </div>
</div>

<div class="mb-4">
    <a href="orders.php" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left mr-2"></i>กลับไปรายการคำสั่งซื้อ
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold">คำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?></h2>
                    <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <span id="status-badge" class="px-3 py-1 rounded-lg text-sm font-medium <?= $statusColors[$order['status']] ?>">
                    <?= $statusNames[$order['status']] ?>
                </span>
            </div>
            
            <!-- Customer -->
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                    <?php if ($order['picture_url']): ?>
                    <img src="<?= htmlspecialchars($order['picture_url']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <i class="fas fa-user text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-4">
                    <div class="font-medium"><?= htmlspecialchars($order['display_name'] ?? 'Unknown') ?></div>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($order['shipping_phone'] ?? '-') ?></div>
                </div>
                <?php if ($order['line_user_id']): ?>
                <a href="messages.php?user=<?= $order['user_id'] ?>" class="ml-auto px-3 py-1 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600">
                    <i class="fas fa-comment mr-1"></i>แชท
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items -->
        <div class="bg-white rounded-xl shadow">
            <div class="p-4 border-b">
                <h3 class="font-semibold"><i class="fas fa-box text-orange-500 mr-2"></i>รายการสินค้า</h3>
            </div>
            <div class="p-4">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center py-3 border-b last:border-0">
                    <div class="flex-1">
                        <div class="font-medium"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="text-sm text-gray-500">฿<?= number_format($item['product_price']) ?> x <?= $item['quantity'] ?></div>
                    </div>
                    <div class="font-semibold">฿<?= number_format($item['subtotal']) ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="mt-4 pt-4 border-t space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">ยอดรวมสินค้า</span>
                        <span>฿<?= number_format($order['total_amount']) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">ค่าจัดส่ง</span>
                        <span>฿<?= number_format($order['shipping_fee']) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-sm text-green-600">
                        <span>ส่วนลด</span>
                        <span>-฿<?= number_format($order['discount_amount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>ยอดรวมทั้งหมด</span>
                        <span class="text-green-600">฿<?= number_format($order['grand_total']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Shipping -->
        <?php if ($order['shipping_address']): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3"><i class="fas fa-map-marker-alt text-red-500 mr-2"></i>ที่อยู่จัดส่ง</h3>
            <div class="text-gray-700">
                <p class="font-medium"><?= htmlspecialchars($order['shipping_name']) ?></p>
                <p class="text-sm"><?= htmlspecialchars($order['shipping_phone']) ?></p>
                <p class="text-sm mt-1"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Update Status -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-4"><i class="fas fa-edit text-blue-500 mr-2"></i>อัพเดทสถานะ</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">สถานะ</label>
                <select id="order-status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <?php foreach ($statusNames as $key => $name): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">เลขพัสดุ</label>
                <input type="text" id="tracking-number" value="<?= htmlspecialchars($order['shipping_tracking'] ?? '') ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="เลขติดตามพัสดุ">
            </div>
            <button onclick="updateOrderStatus()" id="btn-update-status" class="w-full py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
        </div>
        
        <!-- Payment Slips -->
        <div class="bg-white rounded-xl shadow">
            <div class="p-4 border-b">
                <h3 class="font-semibold"><i class="fas fa-receipt text-purple-500 mr-2"></i>หลักฐานการชำระเงิน</h3>
            </div>
            <div class="p-4" id="slips-container">
                <?php if (empty($slips)): ?>
                <p class="text-gray-400 text-center py-4">ยังไม่มีหลักฐานการชำระเงิน</p>
                <?php else: ?>
                <?php foreach ($slips as $slip): ?>
                <div class="mb-4 last:mb-0 slip-item" data-slip-id="<?= $slip['id'] ?>">
                    <a href="<?= htmlspecialchars($slip['image_url']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($slip['image_url']) ?>" class="w-full rounded-lg border">
                    </a>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($slip['created_at'])) ?></span>
                        <?php if ($slip['status'] === 'pending'): ?>
                        <div class="flex gap-2">
                            <button onclick="approveSlip(<?= $slip['id'] ?>)" class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">
                                <i class="fas fa-check mr-1"></i>ยืนยัน
                            </button>
                            <button onclick="rejectSlip(<?= $slip['id'] ?>)" class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">
                                <i class="fas fa-times mr-1"></i>ปฏิเสธ
                            </button>
                        </div>
                        <?php else: ?>
                        <span class="slip-status px-2 py-1 text-xs rounded <?= $slip['status'] === 'approved' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                            <?= $slip['status'] === 'approved' ? 'ยืนยันแล้ว' : 'ปฏิเสธ' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Note -->
        <?php if ($order['note']): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-2"><i class="fas fa-sticky-note text-yellow-500 mr-2"></i>หมายเหตุ</h3>
            <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>


<script>
const orderId = <?= $orderId ?>;
const statusColors = <?= json_encode($statusColors) ?>;
const statusNames = <?= json_encode($statusNames) ?>;

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-message');
    toastMsg.textContent = message;
    
    const toastDiv = toast.querySelector('div');
    toastDiv.className = isError 
        ? 'bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center'
        : 'bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center';
    
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function updateOrderStatus() {
    const btn = document.getElementById('btn-update-status');
    const status = document.getElementById('order-status').value;
    const tracking = document.getElementById('tracking-number').value;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';
    
    const formData = new FormData();
    formData.append('action', 'update_order_status');
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('tracking', tracking);
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Update status badge
            const badge = document.getElementById('status-badge');
            badge.className = 'px-3 py-1 rounded-lg text-sm font-medium ' + statusColors[status];
            badge.textContent = statusNames[status];
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>บันทึก';
    });
}

function approveSlip(slipId) {
    if (!confirm('ยืนยันการชำระเงินนี้?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve_slip');
    formData.append('order_id', orderId);
    formData.append('slip_id', slipId);
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Update UI
            const slipItem = document.querySelector(`.slip-item[data-slip-id="${slipId}"]`);
            const buttons = slipItem.querySelector('.flex.gap-2');
            if (buttons) {
                buttons.outerHTML = '<span class="slip-status px-2 py-1 text-xs rounded bg-green-100 text-green-600">ยืนยันแล้ว</span>';
            }
            // Update order status
            document.getElementById('order-status').value = 'paid';
            const badge = document.getElementById('status-badge');
            badge.className = 'px-3 py-1 rounded-lg text-sm font-medium ' + statusColors['paid'];
            badge.textContent = statusNames['paid'];
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    });
}

function rejectSlip(slipId) {
    if (!confirm('ปฏิเสธหลักฐานการชำระเงินนี้?')) return;
    
    const formData = new FormData();
    formData.append('action', 'reject_slip');
    formData.append('order_id', orderId);
    formData.append('slip_id', slipId);
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Update UI
            const slipItem = document.querySelector(`.slip-item[data-slip-id="${slipId}"]`);
            const buttons = slipItem.querySelector('.flex.gap-2');
            if (buttons) {
                buttons.outerHTML = '<span class="slip-status px-2 py-1 text-xs rounded bg-red-100 text-red-600">ปฏิเสธ</span>';
            }
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    });
}
</script>

<?php require_once '../includes/user_footer.php'; ?>
