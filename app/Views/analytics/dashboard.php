<?php
/**
 * Advanced Analytics Dashboard View
 * MVC Pattern - View Layer
 */
?>
<!-- Period Selector -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Advanced Analytics</h2>
        <p class="text-gray-500 text-sm">ข้อมูลเชิงลึกและสถิติการใช้งาน</p>
    </div>
    <div class="flex items-center gap-3">
        <select id="periodSelector" onchange="changePeriod(this.value)" 
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
            <option value="24h" <?= $period === '24h' ? 'selected' : '' ?>>24 ชั่วโมง</option>
            <option value="7d" <?= $period === '7d' ? 'selected' : '' ?>>7 วัน</option>
            <option value="30d" <?= $period === '30d' ? 'selected' : '' ?>>30 วัน</option>
            <option value="90d" <?= $period === '90d' ? 'selected' : '' ?>>90 วัน</option>
        </select>
        <button onclick="refreshData()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
            <i class="fas fa-sync-alt mr-1"></i> รีเฟรช
        </button>
        <a href="?action=export&period=<?= $period ?>" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition">
            <i class="fas fa-download mr-1"></i> Export
        </a>
    </div>
</div>

<!-- Real-time Stats Bar -->
<div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 mb-6 text-white">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
            <span class="text-sm font-medium">Real-time</span>
        </div>
        <div class="flex items-center gap-8 text-sm">
            <div>
                <span class="opacity-80">Active Users:</span>
                <span id="realtimeActiveUsers" class="font-bold ml-1"><?= $realtime['active_users'] ?? 0 ?></span>
            </div>
            <div>
                <span class="opacity-80">Messages/hr:</span>
                <span id="realtimeMessages" class="font-bold ml-1"><?= $realtime['messages_per_hour'] ?? 0 ?></span>
            </div>
            <div>
                <span class="opacity-80">Orders Today:</span>
                <span id="realtimeOrders" class="font-bold ml-1"><?= $realtime['orders_today'] ?? 0 ?></span>
            </div>
            <div>
                <span class="opacity-80">Revenue Today:</span>
                <span id="realtimeRevenue" class="font-bold ml-1">฿<?= number_format($realtime['revenue_today'] ?? 0, 0) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Users Card -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-500"></i>
            </div>
            <span class="text-xs px-2 py-1 rounded-full <?= ($stats['users']['growth_rate'] ?? 0) >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                <?= ($stats['users']['growth_rate'] ?? 0) >= 0 ? '+' : '' ?><?= $stats['users']['growth_rate'] ?? 0 ?>%
            </span>
        </div>
        <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['users']['total'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">ผู้ใช้ทั้งหมด</div>
        <div class="mt-2 text-xs text-gray-400">
            ใหม่ <?= number_format($stats['users']['new'] ?? 0) ?> | Active <?= number_format($stats['users']['active'] ?? 0) ?>
        </div>
    </div>

    <!-- Messages Card -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-comments text-green-500"></i>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-600">
                <?= $stats['messages']['response_rate'] ?? 0 ?>% ตอบกลับ
            </span>
        </div>
        <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['messages']['total'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">ข้อความทั้งหมด</div>
        <div class="mt-2 text-xs text-gray-400">
            เข้า <?= number_format($stats['messages']['incoming'] ?? 0) ?> | ออก <?= number_format($stats['messages']['outgoing'] ?? 0) ?>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shopping-cart text-yellow-500"></i>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-600">
                <?= $stats['orders']['conversion_rate'] ?? 0 ?>% สำเร็จ
            </span>
        </div>
        <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['orders']['total'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">คำสั่งซื้อ</div>
        <div class="mt-2 text-xs text-gray-400">
            รอ <?= $stats['orders']['pending'] ?? 0 ?> | ชำระแล้ว <?= $stats['orders']['paid'] ?? 0 ?>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-baht-sign text-purple-500"></i>
            </div>
            <span class="text-xs px-2 py-1 rounded-full <?= ($stats['revenue']['growth'] ?? 0) >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                <?= ($stats['revenue']['growth'] ?? 0) >= 0 ? '+' : '' ?><?= $stats['revenue']['growth'] ?? 0 ?>%
            </span>
        </div>
        <div class="text-2xl font-bold text-gray-800">฿<?= number_format($stats['revenue']['total'] ?? 0, 0) ?></div>
        <div class="text-sm text-gray-500">รายได้</div>
        <div class="mt-2 text-xs text-gray-400">
            เฉลี่ย/ออเดอร์ ฿<?= number_format($stats['orders']['avg_order_value'] ?? 0, 0) ?>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Users Chart -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">ผู้ใช้ใหม่รายวัน</h3>
        <canvas id="usersChart" height="200"></canvas>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">รายได้รายวัน</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
</div>

<!-- Second Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Message Types -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">ประเภทข้อความ</h3>
        <canvas id="messageTypesChart" height="200"></canvas>
    </div>

    <!-- Hourly Distribution -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">ช่วงเวลาที่มีการสนทนา</h3>
        <canvas id="hourlyChart" height="200"></canvas>
    </div>

    <!-- Customer Funnel -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">Customer Funnel</h3>
        <div id="funnelContainer" class="space-y-3">
            <!-- Funnel will be rendered here -->
        </div>
    </div>
</div>

<!-- Top Products & Engagement -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Products -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">สินค้าขายดี</h3>
        <div class="space-y-3" id="topProductsList">
            <?php if (!empty($stats['revenue']['top_products'])): ?>
                <?php foreach ($stats['revenue']['top_products'] as $i => $product): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs font-bold"><?= $i + 1 ?></span>
                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($product['product_name']) ?></span>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-800">฿<?= number_format($product['revenue'], 0) ?></div>
                        <div class="text-xs text-gray-400"><?= number_format($product['qty']) ?> ชิ้น</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-8">ไม่มีข้อมูล</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Broadcast Performance -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">ประสิทธิภาพ Broadcast</h3>
        <div class="space-y-3" id="broadcastList">
            <?php if (!empty($stats['engagement']['broadcasts'])): ?>
                <?php foreach ($stats['engagement']['broadcasts'] as $broadcast): ?>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($broadcast['name']) ?></span>
                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-600 rounded-full"><?= $broadcast['ctr'] ?? 0 ?>% CTR</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-400">
                        <span><i class="fas fa-paper-plane mr-1"></i> <?= number_format($broadcast['sent_count']) ?> sent</span>
                        <span><i class="fas fa-mouse-pointer mr-1"></i> <?= number_format($broadcast['unique_clicks']) ?> clicks</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-8">ไม่มีข้อมูล</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<input type="hidden" id="csrfToken" value="<?= $csrf_token ?>">

<script>
// Chart.js Configuration
const chartColors = {
    primary: '#06C755',
    primaryLight: 'rgba(6, 199, 85, 0.1)',
    blue: '#3B82F6',
    yellow: '#F59E0B',
    purple: '#8B5CF6',
    red: '#EF4444',
    gray: '#6B7280'
};

// Initialize Charts
let usersChart, revenueChart, messageTypesChart, hourlyChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadFunnel();
    
    // Auto refresh every 60 seconds
    setInterval(refreshRealtime, 60000);
});

function initCharts() {
    // Users Chart
    const usersData = <?= json_encode($stats['users']['daily'] ?? []) ?>;
    usersChart = new Chart(document.getElementById('usersChart'), {
        type: 'line',
        data: {
            labels: usersData.map(d => d.date),
            datasets: [{
                label: 'ผู้ใช้ใหม่',
                data: usersData.map(d => d.count),
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primaryLight,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });

    // Revenue Chart
    const revenueData = <?= json_encode($stats['orders']['daily'] ?? []) ?>;
    revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.date),
            datasets: [{
                label: 'รายได้',
                data: revenueData.map(d => d.revenue),
                backgroundColor: chartColors.primary,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });

    // Message Types Chart
    const messageTypes = <?= json_encode($stats['messages']['by_type'] ?? []) ?>;
    messageTypesChart = new Chart(document.getElementById('messageTypesChart'), {
        type: 'doughnut',
        data: {
            labels: messageTypes.map(d => d.message_type || 'text'),
            datasets: [{
                data: messageTypes.map(d => d.count),
                backgroundColor: [chartColors.primary, chartColors.blue, chartColors.yellow, chartColors.purple, chartColors.red]
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Hourly Chart
    const hourlyData = <?= json_encode($stats['messages']['hourly'] ?? []) ?>;
    const hours = Array.from({length: 24}, (_, i) => i);
    const hourlyValues = hours.map(h => {
        const found = hourlyData.find(d => parseInt(d.hour) === h);
        return found ? found.count : 0;
    });
    
    hourlyChart = new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hours.map(h => h + ':00'),
            datasets: [{
                label: 'ข้อความ',
                data: hourlyValues,
                backgroundColor: chartColors.blue,
                borderRadius: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
}

function loadFunnel() {
    fetch('?action=api_funnel&period=' + document.getElementById('periodSelector').value)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderFunnel(data.data);
            }
        });
}

function renderFunnel(funnelData) {
    const container = document.getElementById('funnelContainer');
    container.innerHTML = funnelData.map((item, i) => {
        const width = Math.max(item.rate, 20);
        return `
            <div class="relative">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-600">${item.stage}</span>
                    <span class="text-sm font-semibold">${item.count.toLocaleString()}</span>
                </div>
                <div class="h-8 bg-gray-100 rounded-lg overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-green-500 to-green-400 rounded-lg flex items-center justify-end pr-2 transition-all duration-500"
                         style="width: ${width}%">
                        <span class="text-xs text-white font-medium">${item.rate}%</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function changePeriod(period) {
    window.location.href = '?period=' + period;
}

function refreshData() {
    location.reload();
}

function refreshRealtime() {
    fetch('?action=api_realtime')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('realtimeActiveUsers').textContent = data.data.active_users;
                document.getElementById('realtimeMessages').textContent = data.data.messages_per_hour;
                document.getElementById('realtimeOrders').textContent = data.data.orders_today;
                document.getElementById('realtimeRevenue').textContent = '฿' + data.data.revenue_today.toLocaleString();
            }
        });
}
</script>
