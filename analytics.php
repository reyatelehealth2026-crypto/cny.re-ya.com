<?php
/**
 * Analytics - สถิติและรายงานการใช้งาน
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Analytics';

// Date range filter
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

// Get statistics
$stats = [];

// Total followers
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_blocked = 0");
$stats['followers'] = $stmt->fetch()['total'];

// New followers in range
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$stats['new_followers'] = $stmt->fetch()['total'];

// Total messages in range
$stmt = $db->prepare("SELECT COUNT(*) as total FROM messages WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$stats['messages'] = $stmt->fetch()['total'];

// Broadcasts sent in range
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(sent_count) as recipients FROM broadcasts WHERE status = 'sent' AND DATE(sent_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$broadcastStats = $stmt->fetch();
$stats['broadcasts'] = $broadcastStats['total'] ?? 0;
$stats['broadcast_recipients'] = $broadcastStats['recipients'] ?? 0;

// Messages by day
$stmt = $db->prepare("SELECT DATE(created_at) as date, 
                      SUM(direction = 'incoming') as incoming,
                      SUM(direction = 'outgoing') as outgoing
                      FROM messages 
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$startDate, $endDate]);
$messagesByDay = $stmt->fetchAll();

// Followers by day
$stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count
                      FROM users 
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$startDate, $endDate]);
$followersByDay = $stmt->fetchAll();

// Top auto-reply keywords
$stmt = $db->query("SELECT keyword, 
                    (SELECT COUNT(*) FROM messages WHERE content LIKE CONCAT('%', auto_replies.keyword, '%')) as hit_count
                    FROM auto_replies WHERE is_active = 1 ORDER BY hit_count DESC LIMIT 5");
$topKeywords = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Date Filter -->
<div class="mb-6 bg-white rounded-xl shadow p-4">
    <form method="GET" class="flex items-center space-x-4">
        <div>
            <label class="block text-sm text-gray-500 mb-1">จากวันที่</label>
            <input type="date" name="start" value="<?= $startDate ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm text-gray-500 mb-1">ถึงวันที่</label>
            <input type="date" name="end" value="<?= $endDate ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div class="pt-6">
            <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">กรอง</button>
        </div>
    </form>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">ผู้ติดตามทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['followers']) ?></p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
        </div>
        <p class="text-sm text-green-600 mt-2">+<?= number_format($stats['new_followers']) ?> ในช่วงนี้</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">ข้อความทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['messages']) ?></p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-envelope text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Broadcast ที่ส่ง</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['broadcasts']) ?></p>
            </div>
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-bullhorn text-purple-600 text-xl"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mt-2"><?= number_format($stats['broadcast_recipients']) ?> ผู้รับ</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">เฉลี่ยข้อความ/วัน</p>
                <?php $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400); ?>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['messages'] / $days, 1) ?></p>
            </div>
            <div class="p-3 bg-orange-100 rounded-full">
                <i class="fas fa-chart-line text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Messages Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">ข้อความรายวัน</h3>
        <canvas id="messagesChart" height="200"></canvas>
    </div>

    <!-- Followers Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">ผู้ติดตามใหม่รายวัน</h3>
        <canvas id="followersChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Keywords -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Top Auto-Reply Keywords</h3>
        <div class="space-y-3">
            <?php foreach ($topKeywords as $i => $kw): ?>
            <div class="flex items-center">
                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3 text-sm"><?= $i + 1 ?></span>
                <span class="flex-1 font-medium"><?= htmlspecialchars($kw['keyword']) ?></span>
                <span class="text-gray-500"><?= number_format($kw['hit_count']) ?> hits</span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($topKeywords)): ?>
            <p class="text-gray-500 text-center">ยังไม่มีข้อมูล</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Export ข้อมูล</h3>
        <div class="space-y-3">
            <a href="export.php?type=messages&start=<?= $startDate ?>&end=<?= $endDate ?>" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                <i class="fas fa-file-csv text-green-500 text-2xl mr-4"></i>
                <div>
                    <p class="font-medium">Export ข้อความ</p>
                    <p class="text-sm text-gray-500">ดาวน์โหลดข้อมูลข้อความเป็น CSV</p>
                </div>
            </a>
            <a href="export.php?type=users&start=<?= $startDate ?>&end=<?= $endDate ?>" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                <i class="fas fa-file-csv text-blue-500 text-2xl mr-4"></i>
                <div>
                    <p class="font-medium">Export ผู้ติดตาม</p>
                    <p class="text-sm text-gray-500">ดาวน์โหลดข้อมูลผู้ติดตามเป็น CSV</p>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
// Messages Chart
new Chart(document.getElementById('messagesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($messagesByDay, 'date')) ?>,
        datasets: [
            { label: 'รับ', data: <?= json_encode(array_column($messagesByDay, 'incoming')) ?>, backgroundColor: '#3B82F6' },
            { label: 'ส่ง', data: <?= json_encode(array_column($messagesByDay, 'outgoing')) ?>, backgroundColor: '#00B900' }
        ]
    },
    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true } } }
});

// Followers Chart
new Chart(document.getElementById('followersChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($followersByDay, 'date')) ?>,
        datasets: [{
            label: 'ผู้ติดตามใหม่',
            data: <?= json_encode(array_column($followersByDay, 'count')) ?>,
            borderColor: '#00B900',
            backgroundColor: 'rgba(0, 185, 0, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true }
});
</script>

<?php require_once 'includes/footer.php'; ?>
