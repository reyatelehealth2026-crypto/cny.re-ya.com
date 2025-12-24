<?php
/**
 * LIFF Points History - ประวัติแต้มสะสม
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_GET['account'] ?? 1;
$companyName = 'ร้านค้า';

// Include LIFF helper
require_once 'includes/liff-helper.php';

// Get Unified LIFF ID
$liffData = getUnifiedLiffId($db, $lineAccountId);
$liffId = $liffData['liff_id'];
$lineAccountId = $liffData['line_account_id'];
$companyName = $liffData['account_name'];

$shopSettings = getShopSettings($db, $lineAccountId);
if (!empty($shopSettings['shop_name'])) $companyName = $shopSettings['shop_name'];

$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ประวัติแต้มสะสม - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
        .points-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="min-h-screen pb-6">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">ประวัติแต้มสะสม</h1>
            <div class="w-10"></div>
        </div>
    </div>

    <!-- Points Summary Card -->
    <div class="p-4">
        <div class="points-card rounded-2xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-white/70 text-sm">แต้มสะสมปัจจุบัน</p>
                    <p class="text-3xl font-bold" id="currentPoints">-</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-coins text-3xl text-yellow-300"></i>
                </div>
            </div>
            <div class="flex gap-4 text-sm">
                <div class="flex-1 bg-white/10 rounded-xl p-3 text-center">
                    <p class="text-white/70">ได้รับทั้งหมด</p>
                    <p class="font-bold text-lg" id="totalEarned">0</p>
                </div>
                <div class="flex-1 bg-white/10 rounded-xl p-3 text-center">
                    <p class="text-white/70">ใช้ไปแล้ว</p>
                    <p class="font-bold text-lg" id="totalUsed">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="px-4 mb-4">
        <div class="flex bg-gray-100 rounded-xl p-1">
            <button onclick="filterHistory('all')" class="filter-btn flex-1 py-2 rounded-lg text-sm font-medium active" data-filter="all">ทั้งหมด</button>
            <button onclick="filterHistory('earn')" class="filter-btn flex-1 py-2 rounded-lg text-sm font-medium" data-filter="earn">ได้รับ</button>
            <button onclick="filterHistory('use')" class="filter-btn flex-1 py-2 rounded-lg text-sm font-medium" data-filter="use">ใช้แต้ม</button>
        </div>
    </div>

    <!-- History List -->
    <div class="px-4">
        <div id="historyList" class="space-y-3">
            <!-- Loading -->
            <div id="loadingSkeleton" class="space-y-3">
                <div class="bg-white rounded-xl p-4 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                        <div class="h-6 bg-gray-200 rounded w-16"></div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <i class="fas fa-history text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">ยังไม่มีประวัติแต้ม</p>
            </div>
        </div>
    </div>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    let userId = null;
    let allHistory = [];
    let currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        if (!LIFF_ID) {
            showError('ไม่พบการตั้งค่า LIFF');
            return;
        }
        
        try {
            await liff.init({ liffId: LIFF_ID });
            
            if (liff.isLoggedIn()) {
                const profile = await liff.getProfile();
                userId = profile.userId;
                await loadPointsHistory();
            } else {
                // Support external browser - redirect to LINE Login
                liff.login();
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาด');
        }
    }

    async function loadPointsHistory() {
        try {
            const response = await fetch(`${BASE_URL}/api/points.php?action=history&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}`);
            const data = await response.json();
            
            document.getElementById('loadingSkeleton').classList.add('hidden');
            
            if (data.success) {
                document.getElementById('currentPoints').textContent = numberFormat(data.current_points || 0);
                document.getElementById('totalEarned').textContent = numberFormat(data.total_earned || 0);
                document.getElementById('totalUsed').textContent = numberFormat(data.total_used || 0);
                
                allHistory = data.history || [];
                renderHistory(allHistory);
            } else {
                showError(data.message || 'ไม่สามารถโหลดข้อมูลได้');
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    function renderHistory(history) {
        const container = document.getElementById('historyList');
        const skeleton = document.getElementById('loadingSkeleton');
        const empty = document.getElementById('emptyState');
        
        skeleton.classList.add('hidden');
        
        if (history.length === 0) {
            empty.classList.remove('hidden');
            return;
        }
        
        empty.classList.add('hidden');
        
        let html = '';
        history.forEach(item => {
            const isEarn = item.points > 0;
            const iconClass = isEarn ? 'fa-plus-circle text-green-500' : 'fa-minus-circle text-red-500';
            const pointsClass = isEarn ? 'text-green-600' : 'text-red-600';
            const pointsPrefix = isEarn ? '+' : '';
            const bgColor = isEarn ? 'bg-green-50' : 'bg-red-50';
            
            html += `
                <div class="bg-white rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 ${bgColor} rounded-full flex items-center justify-center">
                            <i class="fas ${iconClass} text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 truncate">${escapeHtml(item.description || item.type)}</p>
                            <p class="text-xs text-gray-500">${formatDate(item.created_at)}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold ${pointsClass}">${pointsPrefix}${numberFormat(item.points)}</p>
                            <p class="text-xs text-gray-400">แต้ม</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html + skeleton.outerHTML + empty.outerHTML;
    }

    function filterHistory(filter) {
        currentFilter = filter;
        
        // Update active tab
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-white', 'shadow-sm', 'text-purple-600');
            btn.classList.add('text-gray-600');
        });
        const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
        activeBtn.classList.add('active', 'bg-white', 'shadow-sm', 'text-purple-600');
        activeBtn.classList.remove('text-gray-600');
        
        // Filter data
        let filtered = allHistory;
        if (filter === 'earn') {
            filtered = allHistory.filter(h => h.points > 0);
        } else if (filter === 'use') {
            filtered = allHistory.filter(h => h.points < 0);
        }
        
        renderHistory(filtered);
    }

    function goBack() {
        if (liff.isInClient()) {
            window.location.href = `${BASE_URL}/liff-member-card.php?account=${ACCOUNT_ID}`;
        } else {
            window.history.back();
        }
    }

    function showError(msg) {
        document.getElementById('loadingSkeleton').classList.add('hidden');
        document.getElementById('historyList').innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-circle text-5xl text-red-300 mb-4"></i>
                <p class="text-gray-500">${msg}</p>
            </div>
        `;
    }

    function numberFormat(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Initialize filter button style
    document.querySelector('.filter-btn.active').classList.add('bg-white', 'shadow-sm', 'text-purple-600');
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
