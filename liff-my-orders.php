<?php
/**
 * LIFF My Orders - ออเดอร์ของฉัน
 * Upgraded with Teal Theme (MorDee Style)
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

// Get shop name
$shopSettings = getShopSettings($db, $lineAccountId);
if (!empty($shopSettings['shop_name'])) $companyName = $shopSettings['shop_name'];

$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ออเดอร์ของฉัน - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-confirmed { background: #DBEAFE; color: #2563EB; }
        .status-shipping { background: #E0E7FF; color: #4F46E5; }
        .status-completed { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
        .tab-active { border-bottom: 2px solid #7C3AED; color: #7C3AED; }
    </style>
</head>
<body class="min-h-screen pb-6">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">ออเดอร์ของฉัน</h1>
            <div class="w-10"></div>
        </div>
        
        <!-- Tabs -->
        <div class="flex border-b">
            <button onclick="filterOrders('all')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500 tab-active" data-tab="all">ทั้งหมด</button>
            <button onclick="filterOrders('pending')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500" data-tab="pending">รอดำเนินการ</button>
            <button onclick="filterOrders('shipping')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500" data-tab="shipping">กำลังจัดส่ง</button>
            <button onclick="filterOrders('completed')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500" data-tab="completed">สำเร็จ</button>
        </div>
    </div>

    <!-- Orders List -->
    <div class="p-4">
        <div id="ordersList" class="space-y-4">
            <!-- Loading -->
            <div id="loadingSkeleton" class="space-y-4">
                <div class="bg-white rounded-xl p-4 animate-pulse">
                    <div class="flex justify-between mb-3">
                        <div class="h-4 bg-gray-200 rounded w-24"></div>
                        <div class="h-4 bg-gray-200 rounded w-16"></div>
                    </div>
                    <div class="flex gap-3 mb-3">
                        <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div class="h-4 bg-gray-200 rounded w-20 ml-auto"></div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-16">
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">ยังไม่มีออเดอร์</p>
                <button onclick="goToShop()" class="px-6 py-2 bg-purple-600 text-white rounded-xl font-bold">
                    <i class="fas fa-shopping-bag mr-2"></i>ไปช้อปปิ้ง
                </button>
            </div>
        </div>
    </div>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    let userId = null;
    let allOrders = [];
    let currentTab = 'all';

    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        console.log('Init LIFF, LIFF_ID:', LIFF_ID);
        
        if (!LIFF_ID) {
            showError('ไม่พบการตั้งค่า LIFF');
            return;
        }
        
        try {
            await liff.init({ liffId: LIFF_ID });
            console.log('LIFF initialized, isLoggedIn:', liff.isLoggedIn(), 'isInClient:', liff.isInClient());
            
            if (liff.isLoggedIn()) {
                const profile = await liff.getProfile();
                userId = profile.userId;
                console.log('Got profile, userId:', userId);
                await loadOrders();
            } else {
                // Auto login - works in both LINE App and External Browser
                console.log('Not logged in, calling liff.login()');
                liff.login();
            }
        } catch (e) {
            console.error('LIFF init error:', e);
            showError('เกิดข้อผิดพลาด: ' + e.message);
        }
    }

    async function loadOrders() {
        try {
            const apiUrl = `${BASE_URL}/api/orders.php?action=my_orders&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}`;
            console.log('Loading orders from:', apiUrl);
            console.log('User ID:', userId);
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            console.log('Orders API response:', data);
            
            document.getElementById('loadingSkeleton').classList.add('hidden');
            
            if (data.success) {
                allOrders = data.orders || [];
                console.log('Orders loaded:', allOrders.length);
                renderOrders(allOrders);
            } else {
                console.error('API error:', data.message);
                showError(data.message || 'ไม่สามารถโหลดข้อมูลได้');
            }
        } catch (e) {
            console.error('Load orders error:', e);
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    function renderOrders(orders) {
        const container = document.getElementById('ordersList');
        const empty = document.getElementById('emptyState');
        const skeleton = document.getElementById('loadingSkeleton');
        
        // Hide skeleton if exists
        if (skeleton) skeleton.classList.add('hidden');
        
        if (orders.length === 0) {
            // Show empty state
            container.innerHTML = `
                <div class="text-center py-16">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">ยังไม่มีออเดอร์</p>
                    <button onclick="goToShop()" class="px-6 py-2 bg-purple-600 text-white rounded-xl font-bold">
                        <i class="fas fa-shopping-bag mr-2"></i>ไปช้อปปิ้ง
                    </button>
                </div>
            `;
            return;
        }
        
        let html = '';
        orders.forEach(order => {
            const statusInfo = getStatusInfo(order.status);
            const items = order.items || [];
            const firstItem = items[0] || {};
            const moreCount = items.length - 1;
            
            html += `
                <div class="bg-white rounded-xl shadow-sm overflow-hidden" onclick="viewOrder('${order.order_id}')">
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-gray-500">#${order.order_id}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-bold ${statusInfo.class}">${statusInfo.text}</span>
                        </div>
                        
                        <div class="flex gap-3 mb-3">
                            <img src="${firstItem.image || 'https://via.placeholder.com/100'}" 
                                class="w-16 h-16 rounded-lg object-cover bg-gray-100" 
                                onerror="this.src='https://via.placeholder.com/100'">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 truncate">${escapeHtml(firstItem.name || 'สินค้า')}</p>
                                <p class="text-xs text-gray-500">x${firstItem.quantity || 1}</p>
                                ${moreCount > 0 ? `<p class="text-xs text-purple-600">+${moreCount} รายการ</p>` : ''}
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center pt-3 border-t">
                            <span class="text-xs text-gray-400">${formatDate(order.created_at)}</span>
                            <span class="font-bold text-gray-800">฿${numberFormat(order.total_amount)}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function filterOrders(tab) {
        currentTab = tab;
        
        // Update tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('tab-active');
            if (btn.dataset.tab === tab) {
                btn.classList.add('tab-active');
            }
        });
        
        // Filter orders
        let filtered = allOrders;
        if (tab === 'pending') {
            filtered = allOrders.filter(o => ['pending', 'confirmed', 'processing'].includes(o.status));
        } else if (tab === 'shipping') {
            filtered = allOrders.filter(o => ['shipping', 'shipped'].includes(o.status));
        } else if (tab === 'completed') {
            filtered = allOrders.filter(o => ['completed', 'delivered'].includes(o.status));
        }
        
        renderOrders(filtered);
    }

    function getStatusInfo(status) {
        const statuses = {
            'pending': { text: 'รอชำระเงิน', class: 'status-pending' },
            'confirmed': { text: 'ยืนยันแล้ว', class: 'status-confirmed' },
            'processing': { text: 'กำลังเตรียม', class: 'status-confirmed' },
            'shipping': { text: 'กำลังจัดส่ง', class: 'status-shipping' },
            'shipped': { text: 'จัดส่งแล้ว', class: 'status-shipping' },
            'completed': { text: 'สำเร็จ', class: 'status-completed' },
            'delivered': { text: 'ได้รับแล้ว', class: 'status-completed' },
            'cancelled': { text: 'ยกเลิก', class: 'status-cancelled' },
        };
        return statuses[status] || { text: status, class: 'status-pending' };
    }

    function viewOrder(orderId) {
        window.location.href = `${BASE_URL}/liff-order-detail.php?order=${orderId}&account=${ACCOUNT_ID}`;
    }

    function goToShop() {
        window.location.href = `${BASE_URL}/liff-shop.php?account=${ACCOUNT_ID}`;
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
        document.getElementById('ordersList').innerHTML = `
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
        return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
