<?php
/**
 * User Customers - จัดการลูกค้าสำหรับผู้ใช้ทั่วไป
 */
$pageTitle = 'ลูกค้า';
require_once '../includes/user_header.php';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search
$search = trim($_GET['search'] ?? '');

// Build query
$where = "WHERE line_account_id = ?";
$params = [$currentBotId];

if ($search) {
    $where .= " AND (display_name LIKE ? OR line_user_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM users $where");
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-xl shadow">
    <!-- Header -->
    <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-gray-600">ลูกค้าทั้งหมด <?= number_format($totalUsers) ?> คน</span>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="ค้นหาลูกค้า..." 
                   class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลูกค้า</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่เพิ่ม</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400">ไม่พบลูกค้า</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                <?php if ($user['picture_url']): ?>
                                <img src="<?= htmlspecialchars($user['picture_url']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                <i class="fas fa-user text-gray-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <div class="font-medium"><?= htmlspecialchars($user['display_name'] ?? 'Unknown') ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars(substr($user['line_user_id'], 0, 20)) ?>...</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($user['is_blocked']): ?>
                        <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded">Blocked</span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-600 rounded">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="messages.php?user=<?= $user['id'] ?>" class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-comment"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="p-4 border-t flex justify-center gap-2">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
           class="px-3 py-1 border rounded <?= $i == $page ? 'bg-green-500 text-white' : 'hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/user_footer.php'; ?>
