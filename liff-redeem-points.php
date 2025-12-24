<?php
/**
 * LIFF Redeem Points - แลกแต้มสะสม
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
    <title>แลกแต้มสะสม - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
        .points-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .reward-card { transition: transform 0.15s, box-shadow 0.15s; }
        .reward-card:active { transform: scale(0.98); }
    </style>
</head>
<body class="min-h-screen pb-6">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">แลกแต้มสะสม</h1>
            <div class="w-10"></div>
        </div>
    </div>

    <!-- Points Badge -->
    <div class="p-4">
        <div class="points-badge rounded-2xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-sm">แต้มของคุณ</p>
                    <p class="text-3xl font-bold" id="currentPoints">-</p>
                </div>
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-coins text-2xl text-yellow-300"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Rewards List -->
    <div class="px-4">
        <h2 class="font-bold text-gray-800 mb-3">ของรางวัลที่แลกได้</h2>
        
        <div id="rewardsList" class="space-y-3">
            <!-- Loading -->
            <div id="loadingSkeleton" class="space-y-3">
                <div class="bg-white rounded-xl p-4 animate-pulse">
                    <div class="flex gap-4">
                        <div class="w-20 h-20 bg-gray-200 rounded-xl"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
                            <div class="h-8 bg-gray-200 rounded w-24"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <i class="fas fa-gift text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">ยังไม่มีของรางวัล</p>
            </div>
        </div>
    </div>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    let userId = null;
    let currentPoints = 0;

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
                await Promise.all([loadPoints(), loadRewards()]);
            } else {
                // Support external browser - redirect to LINE Login
                liff.login();
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาด');
        }
    }

    async function loadPoints() {
        try {
            const response = await fetch(`${BASE_URL}/api/points.php?action=history&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}&limit=1`);
            const data = await response.json();
            if (data.success) {
                currentPoints = data.current_points || 0;
                document.getElementById('currentPoints').textContent = numberFormat(currentPoints);
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function loadRewards() {
        try {
            const response = await fetch(`${BASE_URL}/api/points.php?action=rewards&line_account_id=${ACCOUNT_ID}`);
            const data = await response.json();
            
            document.getElementById('loadingSkeleton').classList.add('hidden');
            
            if (data.success && data.rewards.length > 0) {
                renderRewards(data.rewards);
            } else {
                document.getElementById('emptyState').classList.remove('hidden');
            }
        } catch (e) {
            console.error(e);
            showError('ไม่สามารถโหลดข้อมูลได้');
        }
    }

    function renderRewards(rewards) {
        const container = document.getElementById('rewardsList');
        let html = '';
        
        rewards.forEach(reward => {
            const canRedeem = currentPoints >= reward.points_required;
            const btnClass = canRedeem 
                ? 'bg-purple-600 text-white' 
                : 'bg-gray-200 text-gray-400 cursor-not-allowed';
            const iconBg = getRewardIcon(reward.type);
            
            html += `
                <div class="reward-card bg-white rounded-xl p-4 shadow-sm">
                    <div class="flex gap-4">
                        <div class="w-20 h-20 ${iconBg.bg} rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas ${iconBg.icon} text-3xl ${iconBg.color}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-800 truncate">${escapeHtml(reward.name)}</h3>
                            <p class="text-xs text-gray-500 mb-2 line-clamp-2">${escapeHtml(reward.description || '')}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-purple-600 font-bold text-sm">
                                    <i class="fas fa-coins text-yellow-500 mr-1"></i>${numberFormat(reward.points_required)} แต้ม
                                </span>
                                <button onclick="redeemReward(${reward.id}, '${escapeHtml(reward.name)}', ${reward.points_required})" 
                                    class="px-4 py-1.5 rounded-lg text-sm font-bold ${btnClass}" ${!canRedeem ? 'disabled' : ''}>
                                    แลก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function getRewardIcon(type) {
        const icons = {
            'discount': { bg: 'bg-green-50', icon: 'fa-percent', color: 'text-green-500' },
            'shipping': { bg: 'bg-blue-50', icon: 'fa-truck', color: 'text-blue-500' },
            'gift': { bg: 'bg-pink-50', icon: 'fa-gift', color: 'text-pink-500' },
            'product': { bg: 'bg-orange-50', icon: 'fa-box', color: 'text-orange-500' },
        };
        return icons[type] || icons['gift'];
    }

    async function redeemReward(rewardId, rewardName, pointsRequired) {
        if (currentPoints < pointsRequired) {
            Swal.fire({
                icon: 'warning',
                title: 'แต้มไม่เพียงพอ',
                text: `ต้องการ ${numberFormat(pointsRequired)} แต้ม คุณมี ${numberFormat(currentPoints)} แต้ม`,
                confirmButtonColor: '#7C3AED'
            });
            return;
        }
        
        const result = await Swal.fire({
            title: 'ยืนยันการแลก',
            html: `แลก <b>${rewardName}</b><br>ใช้ ${numberFormat(pointsRequired)} แต้ม`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        });
        
        if (!result.isConfirmed) return;
        
        try {
            Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            const response = await fetch(`${BASE_URL}/api/points.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'redeem',
                    line_user_id: userId,
                    line_account_id: ACCOUNT_ID,
                    reward_id: rewardId
                })
            });
            const data = await response.json();
            
            if (data.success) {
                currentPoints = data.new_balance;
                document.getElementById('currentPoints').textContent = numberFormat(currentPoints);
                
                await Swal.fire({
                    icon: 'success',
                    title: 'แลกสำเร็จ!',
                    html: `รหัสคูปอง: <b class="text-purple-600">${data.coupon_code}</b><br><small class="text-gray-500">กรุณาบันทึกรหัสนี้ไว้</small>`,
                    confirmButtonColor: '#7C3AED'
                });
                
                loadRewards(); // Refresh list
            } else {
                Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message, confirmButtonColor: '#7C3AED' });
            }
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', confirmButtonColor: '#7C3AED' });
        }
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
        document.getElementById('rewardsList').innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-circle text-5xl text-red-300 mb-4"></i>
                <p class="text-gray-500">${msg}</p>
            </div>
        `;
    }

    function numberFormat(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
