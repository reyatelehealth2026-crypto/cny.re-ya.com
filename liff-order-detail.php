<?php
/**
 * LIFF Order Detail - รายละเอียดออเดอร์
 */
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/liff-helper.php';

$db = Database::getInstance()->getConnection();
$orderId = $_GET['order'] ?? '';
$lineAccountId = $_GET['account'] ?? 1;

$liffData = getUnifiedLiffId($db, $lineAccountId);
$liffId = $liffData['liff_id'];
$lineAccountId = $liffData['line_account_id'];

$shopSettings = getShopSettings($db, $lineAccountId);
$companyName = $shopSettings['shop_name'] ?? 'ร้านค้า';
$baseUrl = rtrim(BASE_URL, '/');

// Get order data
$order = null;
$orderItems = [];
if ($orderId) {
    try {
        // Try transactions table first
        $stmt = $db->prepare("SELECT * FROM transactions WHERE order_number = ? OR id = ?");
        $stmt->execute([$orderId, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $stmt = $db->prepare("
                SELECT ti.*, COALESCE(p.name, ti.product_name) as name, p.image_url as image
                FROM transaction_items ti
                LEFT JOIN products p ON ti.product_id = p.id
                WHERE ti.transaction_id = ?
            ");
            $stmt->execute([$order['id']]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Order detail error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>รายละเอียดออเดอร์ - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-confirmed { background: #DBEAFE; color: #2563EB; }
        .status-paid { background: #D1FAE5; color: #059669; }
        .status-shipping { background: #E0E7FF; color: #4F46E5; }
        .status-completed { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
    </style>
</head>
<body class="min-h-screen pb-20">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">รายละเอียดออเดอร์</h1>
            <div class="w-10"></div>
        </div>
    </div>

    <?php if ($order): ?>
    <!-- Order Info -->
    <div class="p-4">
        <!-- Order Number & Status -->
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <p class="text-sm text-gray-500">หมายเลขออเดอร์</p>
                    <p class="font-bold text-lg">#<?= htmlspecialchars($order['order_number'] ?? $order['id']) ?></p>
                </div>
                <?php
                $statusClass = 'status-' . ($order['status'] ?? 'pending');
                $statusLabels = [
                    'pending' => 'รอชำระเงิน',
                    'confirmed' => 'ยืนยันแล้ว',
                    'paid' => 'ชำระแล้ว',
                    'processing' => 'กำลังเตรียม',
                    'shipping' => 'กำลังจัดส่ง',
                    'shipped' => 'จัดส่งแล้ว',
                    'completed' => 'สำเร็จ',
                    'cancelled' => 'ยกเลิก'
                ];
                $statusText = $statusLabels[$order['status']] ?? $order['status'];
                ?>
                <span class="px-3 py-1 rounded-full text-sm font-bold <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
            <p class="text-sm text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
            </p>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-box mr-2"></i>รายการสินค้า</h3>
            <div class="space-y-3">
                <?php foreach ($orderItems as $item): ?>
                <div class="flex gap-3 pb-3 border-b last:border-0 last:pb-0">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        <?php if (!empty($item['image'])): ?>
                        <img src="<?= htmlspecialchars($item['image']) ?>" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><i class=\'fas fa-image text-gray-300\'></i></div>'">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-gray-300"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 truncate"><?= htmlspecialchars($item['name'] ?? $item['product_name']) ?></p>
                        <p class="text-sm text-gray-500">฿<?= number_format($item['product_price'], 2) ?> x <?= $item['quantity'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">฿<?= number_format($item['subtotal'], 2) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Delivery Info -->
        <?php 
        $deliveryInfo = json_decode($order['delivery_info'] ?? '{}', true);
        if ($deliveryInfo): 
        ?>
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-truck mr-2"></i>ข้อมูลจัดส่ง</h3>
            <div class="text-sm text-gray-600 space-y-1">
                <p><strong>ชื่อ:</strong> <?= htmlspecialchars($deliveryInfo['name'] ?? '-') ?></p>
                <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($deliveryInfo['phone'] ?? '-') ?></p>
                <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($deliveryInfo['address'] ?? '-') ?></p>
            </div>
            <?php if (!empty($order['shipping_tracking'])): ?>
            <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800"><i class="fas fa-shipping-fast mr-1"></i> เลขพัสดุ: <strong><?= htmlspecialchars($order['shipping_tracking']) ?></strong></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Payment Summary -->
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-receipt mr-2"></i>สรุปการชำระเงิน</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">ยอดสินค้า</span>
                    <span>฿<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ค่าจัดส่ง</span>
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
            <div class="mt-3 text-sm text-gray-500">
                <p><i class="fas fa-credit-card mr-1"></i> <?= $order['payment_method'] === 'transfer' ? 'โอนเงิน' : ($order['payment_method'] === 'cod' ? 'เก็บเงินปลายทาง' : $order['payment_method']) ?></p>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($order['status'] === 'pending' && $order['payment_method'] === 'transfer'): ?>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <a href="liff-checkout.php?order=<?= $order['id'] ?>&action=slip&account=<?= $lineAccountId ?>" 
               class="block w-full py-3 bg-green-500 text-white text-center rounded-xl font-bold">
                <i class="fas fa-upload mr-2"></i>แจ้งชำระเงิน / อัพโหลดสลิป
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Not Found -->
    <div class="p-4">
        <div class="bg-white rounded-xl p-8 text-center">
            <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-4">ไม่พบออเดอร์นี้</p>
            <button onclick="goBack()" class="px-6 py-2 bg-gray-500 text-white rounded-lg">กลับ</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    function goBack() {
        window.location.href = `${BASE_URL}/liff-my-orders.php?account=${ACCOUNT_ID}`;
    }
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
