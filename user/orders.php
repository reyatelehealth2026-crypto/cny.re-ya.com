<?php
/**
 * User Orders - จัดการคำสั่งซื้อ
 */
$pageTitle = 'คำสั่งซื้อ';
require_once '../includes/user_header.php';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter
$status = $_GET['status'] ?? '';

// Build query
$where = "WHERE o.line_account_id = ?";
$params = [$currentBotId];

if ($status) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$stmt = $db->prepare("SELECT o.*, u.display_name, u.picture_url FROM orders o LEFT JOIN users u ON o.user_id = u.id $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status counts
$statusCounts = [];
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM orders WHERE line_account_id = ? GROUP BY status");
$stmt->execute([$currentBotId]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<style>
.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}
.status-pending { background: #fef3c7; color: #d97706; }
.status-confirmed { background: #dbeafe; color: #2563eb; }
.status-paid { background: #dcfce7; color: #16a34a; }
.status-shipping { background: #e0e7ff; color: #4f46e5; }
.status-delivered { background: #d1fae5; color: #059669; }
.status-cancelled { background: #fee2e2; color: #dc2626; }
</style>

<!-- Status Tabs -->
<div class="mb-4 flex flex-wrap gap-2">
    <a href="orders.php" class="px-4 py-2 rounded-lg <?= !$status ? 'bg-green-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
        ทั้งหมด (<?= $totalOrders ?>)
    </a>
    <a href="?status=pending" class="px-4 py-2 rounded-lg <?= $status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
        รอดำเนินการ (<?= $statusCounts['pending'] ?? 0 ?>)
    </a>
    <a href="?status=paid" class="px-4 py-2 rounded-lg <?= $status === 'paid' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
        ชำระแล้ว (<?= $statusCounts['paid'] ?? 0 ?>)
    </a>
    <a href="?status=shipping" class="px-4 py-2 rounded-lg <?= $status === 'shipping' ? 'bg-indigo-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
        กำลังจัดส่ง (<?= $statusCounts['shipping'] ?? 0 ?>)
    </a>
    <a href="?status=delivered" class="px-4 py-2 rounded-lg <?= $status === 'delivered' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
        ส่งแล้ว (<?= $statusCounts['delivered'] ?? 0 ?>)
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เลขที่</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลูกค้า</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ยอดรวม</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">จัดการ</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">ไม่มีคำสั่งซื้อ</td>
            </tr>
            <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">#<?= htmlspecialchars($order['order_number'] ?? $order['id']) ?></td>
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                            <?php if ($order['picture_url']): ?>
                            <img src="<?= htmlspecialchars($order['picture_url']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                            <?php endif; ?>
                        </div>
                        <span class="ml-2 text-sm"><?= htmlspecialchars($order['display_name'] ?? 'Unknown') ?></span>
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-green-600">฿<?= number_format($order['grand_total']) ?></td>
                <td class="px-4 py-3 text-center">
                    <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                <td class="px-4 py-3 text-center">
                    <a href="order-detail.php?id=<?= $order['id'] ?>" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="p-4 border-t flex justify-center gap-2">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>" 
           class="px-3 py-1 border rounded <?= $i == $page ? 'bg-green-500 text-white' : 'hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/user_footer.php'; ?>
