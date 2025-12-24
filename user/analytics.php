<?php
/**
 * User Analytics - สถิติ
 */
$pageTitle = 'สถิติ';
require_once '../includes/user_header.php';

// Get date range
$days = (int)($_GET['days'] ?? 7);
$startDate = date('Y-m-d', strtotime("-$days days"));

// Users stats
$stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE line_account_id = ? AND created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$currentBotId, $startDate]);
$userStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Messages stats
$stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM messages WHERE line_account_id = ? AND created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$currentBotId, $startDate]);
$messageStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Orders stats
$orderStats = [];
$revenueStats = [];
try {
    $stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(grand_total) as revenue FROM orders WHERE line_account_id = ? AND created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
    $stmt->execute([$currentBotId, $startDate]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orderStats[$row['date']] = $row['count'];
        $revenueStats[$row['date']] = $row['revenue'];
    }
} catch (Exception $e) {}

// Prepare chart data
$labels = [];
$userData = [];
$messageData = [];
$orderData = [];
$revenueData = [];

for ($i = $days; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($date));
    $userData[] = $userStats[$date] ?? 0;
    $messageData[] = $messageStats[$date] ?? 0;
    $orderData[] = $orderStats[$date] ?? 0;
    $revenueData[] = $revenueStats[$date] ?? 0;
}

// Summary stats
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE line_account_id = ?");
$stmt->execute([$currentBotId]);
$totalUsers = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE line_account_id = ?");
$stmt->execute([$currentBotId]);
$totalMessages = $stmt->fetchColumn();

$totalOrders = 0;
$totalRevenue = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*), COALESCE(SUM(grand_total), 0) FROM orders WHERE line_account_id = ?");
    $stmt->execute([$currentBotId]);
    list($totalOrders, $totalRevenue) = $stmt->fetch(PDO::FETCH_NUM);
} catch (Exception $e) {}
?>

<!-- Date Range Selector -->
<div class="mb-6 flex justify-end">
    <select onchange="window.location='?days='+this.value" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 วันล่าสุด</option>
        <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 วันล่าสุด</option>
        <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 วันล่าสุด</option>
    </select>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">ลูกค้าทั้งหมด</div>
                <div class="text-2xl font-bold text-gray-800"><?= number_format($totalUsers) ?></div>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-blue-500"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">ข้อความทั้งหมด</div>
                <div class="text-2xl font-bold text-gray-800"><?= number_format($totalMessages) ?></div>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-comments text-green-500"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">คำสั่งซื้อทั้งหมด</div>
                <div class="text-2xl font-bold text-gray-800"><?= number_format($totalOrders) ?></div>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-orange-500"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">รายได้ทั้งหมด</div>
                <div class="text-2xl font-bold text-green-600">฿<?= number_format($totalRevenue) ?></div>
            </div>
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-baht-sign text-emerald-500"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl p-6">
        <h3 class="font-semibold mb-4"><i class="fas fa-user-plus text-blue-500 mr-2"></i>ลูกค้าใหม่</h3>
        <canvas id="usersChart" height="200"></canvas>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <h3 class="font-semibold mb-4"><i class="fas fa-comments text-green-500 mr-2"></i>ข้อความ</h3>
        <canvas id="messagesChart" height="200"></canvas>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <h3 class="font-semibold mb-4"><i class="fas fa-shopping-cart text-orange-500 mr-2"></i>คำสั่งซื้อ</h3>
        <canvas id="ordersChart" height="200"></canvas>
    </div>
    
    <div class="bg-white rounded-xl p-6">
        <h3 class="font-semibold mb-4"><i class="fas fa-baht-sign text-emerald-500 mr-2"></i>รายได้</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
</div>

<script>
const labels = <?= json_encode($labels) ?>;
const chartOptions = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true }
    }
};

new Chart(document.getElementById('usersChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($userData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: chartOptions
});

new Chart(document.getElementById('messagesChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($messageData) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: chartOptions
});

new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($orderData) ?>,
            backgroundColor: '#f97316'
        }]
    },
    options: chartOptions
});

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($revenueData) ?>,
            backgroundColor: '#10b981'
        }]
    },
    options: chartOptions
});
</script>

<?php require_once '../includes/user_footer.php'; ?>
