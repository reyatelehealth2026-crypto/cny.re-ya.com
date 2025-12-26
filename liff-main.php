<?php
/**
 * LIFF Member Card & Doctor Appointment - หน้าหลัก (UX/UI Upgrade)
 * ปรับปรุงดีไซน์เน้น Mobile First, Glassmorphism และ Touch Friendly
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_GET['account'] ?? 1;
$companyName = 'MedCare';
$companyLogo = '';

// Get LIFF IDs & Account Info
$liffId = '';
$liffShopId = '';
$liffCheckoutId = '';
$liffConsentId = '';
$liffRegisterId = '';

try {
    // Check columns
    $hasNewCols = false;
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_member_card_id'");
        $hasNewCols = $checkCol->rowCount() > 0;
    } catch (Exception $e) {}
    
    $selectCols = "id, name, liff_id";
    if ($hasNewCols) {
        $selectCols .= ", liff_member_card_id, liff_shop_id, liff_checkout_id, liff_consent_id, liff_register_id";
    }
    
    $stmt = $db->prepare("SELECT {$selectCols} FROM line_accounts WHERE id = ? OR is_default = 1 ORDER BY is_default DESC LIMIT 1");
    $stmt->execute([$lineAccountId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account) {
        $lineAccountId = $account['id'];
        $companyName = $account['name'];
        $liffId = $account['liff_member_card_id'] ?? $account['liff_id'] ?? '';
        $liffShopId = $account['liff_shop_id'] ?? $account['liff_id'] ?? '';
        $liffCheckoutId = $account['liff_checkout_id'] ?? $account['liff_id'] ?? '';
        $liffConsentId = $account['liff_consent_id'] ?? '';
        $liffRegisterId = $account['liff_register_id'] ?? '';
    }
    
    $stmt = $db->prepare("SELECT shop_name, logo_url FROM shop_settings WHERE line_account_id = ? LIMIT 1");
    $stmt->execute([$lineAccountId]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shop) {
        $companyName = $shop['shop_name'] ?: $companyName;
        $companyLogo = $shop['logo_url'] ?? '';
    }
} catch (Exception $e) {}

$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($companyName) ?> - ร้านขายยาออนไลน์</title>
    <meta name="description" content="<?= htmlspecialchars($companyName) ?> ร้านขายยาออนไลน์ บริการปรึกษาเภสัชกร สั่งยาออนไลน์ ส่งถึงบ้าน นัดหมาย Video Call">
    <meta name="keywords" content="<?= htmlspecialchars($companyName) ?>, ร้านขายยา, ยาออนไลน์, เภสัชกร, ปรึกษายา, ส่งยาถึงบ้าน">
    <meta name="author" content="<?= htmlspecialchars($companyName) ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?= htmlspecialchars($companyName) ?> - ร้านขายยาออนไลน์">
    <meta property="og:description" content="บริการปรึกษาเภสัชกร สั่งยาออนไลน์ ส่งถึงบ้าน">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="th_TH">
    <meta name="theme-color" content="#11B0A6">
    
    <!-- Tailwind Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#11B0A6',
                        'primary-dark': '#0E938B',
                        secondary: '#FF5A5F',
                        surface: '#F8FAFC',
                    },
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.05)',
                        'glow': '0 0 15px rgba(17, 176, 166, 0.3)',
                    }
                }
            }
        }
    </script>

    <!-- Libs -->
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Base Styles */
        body { 
            background-color: #F8FAFC;
            color: #334155;
            -webkit-tap-highlight-color: transparent;
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Glassmorphism Utilities */
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Card Gradients */
        .card-gradient-platinum { background: linear-gradient(135deg, #334155 0%, #1E293B 100%); }
        .card-gradient-gold { background: linear-gradient(135deg, #F59E0B 0%, #B45309 100%); }
        .card-gradient-silver { background: linear-gradient(135deg, #94A3B8 0%, #475569 100%); }
        
        .member-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        /* Shine Effect for Card */
        .member-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(125deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0) 70%);
            z-index: 1;
            pointer-events: none;
        }

        /* Animation */
        .skeleton { 
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer { 
            0% { background-position: 200% 0; } 
            100% { background-position: -200% 0; } 
        }

        .service-btn:active { transform: scale(0.95); }
        .doctor-card:active { transform: scale(0.98); }

        /* Scrollbar Hide */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen relative pb-24">

    <!-- Top Header Background -->
    <div class="fixed top-0 left-0 right-0 h-48 bg-gradient-to-b from-white to-transparent -z-10 pointer-events-none"></div>

    <!-- Header Section -->
    <header class="px-6 pt-8 pb-4 flex items-center justify-between sticky top-0 z-30 bg-white/80 backdrop-blur-md transition-all duration-300" id="mainHeader">
        <div class="flex items-center gap-3">
            <?php if ($companyLogo): ?>
            <img src="<?= htmlspecialchars($companyLogo) ?>" class="w-10 h-10 rounded-full object-cover shadow-sm border border-gray-100">
            <?php else: ?>
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white shadow-glow">
                <i class="fas fa-heartbeat"></i>
            </div>
            <?php endif; ?>
            <div>
                <h1 class="font-bold text-gray-800 text-lg leading-tight"><?= htmlspecialchars($companyName) ?></h1>
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Health & Wellness</p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="sendMessage('แจ้งเตือน')" class="w-9 h-9 rounded-full bg-gray-50 text-gray-400 hover:text-primary hover:bg-teal-50 flex items-center justify-center transition-colors">
                <i class="far fa-bell"></i>
            </button>
            <button onclick="sendMessage('แชท')" class="w-9 h-9 rounded-full bg-gray-50 text-gray-400 hover:text-primary hover:bg-teal-50 flex items-center justify-center transition-colors">
                <i class="far fa-comment-dots"></i>
            </button>
        </div>
    </header>

    <!-- Member Card Area -->
    <div class="px-6 mb-8">
        <div id="memberCard" class="member-card card-gradient-platinum rounded-[28px] p-6 text-white shadow-card w-full aspect-[1.8/1] flex flex-col justify-between">
            <!-- Loading State -->
            <div id="cardSkeleton" class="w-full h-full flex flex-col justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 skeleton rounded-full opacity-20"></div>
                    <div class="space-y-2">
                        <div class="h-4 w-32 skeleton rounded opacity-20"></div>
                        <div class="h-3 w-20 skeleton rounded opacity-20"></div>
                    </div>
                </div>
                <div class="h-2 w-full skeleton rounded-full opacity-20 mt-auto"></div>
            </div>

            <!-- Real Content -->
            <div id="cardContent" class="hidden w-full h-full flex flex-col justify-between relative z-10">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <img id="memberPicture" src="" class="w-12 h-12 rounded-full border-2 border-white/20 object-cover bg-white/10">
                            <div class="absolute -bottom-1 -right-1 bg-green-500 w-3 h-3 rounded-full border-2 border-[#334155]"></div>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg leading-none mb-1 tracking-wide" id="memberName">Guest</h3>
                            <span class="text-[10px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full font-medium" id="tierName">Member</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold leading-none tracking-tight" id="memberPoints">0</div>
                        <div class="text-[9px] text-white/60 mt-1 uppercase font-medium">Points</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] text-white/70 px-1">
                        <span id="nextTierLabel">สะสมแต้มเพื่อเลื่อนระดับ</span>
                    </div>
                    <div class="bg-black/20 rounded-full h-1.5 overflow-hidden backdrop-blur-sm">
                        <div id="progressBar" class="h-full bg-gradient-to-r from-teal-400 to-emerald-400 rounded-full shadow-[0_0_10px_rgba(45,212,191,0.5)] transition-all duration-1000" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between items-end">
                        <span id="memberId" class="font-mono text-[10px] text-white/40 tracking-wider">ID: -</span>
                    </div>
                </div>
            </div>

            <!-- Not Registered State -->
            <div id="notRegisteredCard" class="hidden w-full h-full flex flex-col items-center justify-center text-center z-10">
                <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center mb-3 backdrop-blur-sm">
                    <i class="fas fa-user-plus text-xl text-white/80"></i>
                </div>
                <p class="text-sm text-white/90 font-medium mb-4">สมัครสมาชิกเพื่อรับสิทธิพิเศษ</p>
                <button onclick="openRegister()" class="w-full max-w-[200px] py-2.5 bg-white text-gray-900 rounded-xl font-bold text-sm shadow-lg hover:bg-gray-50 transition-colors">
                    ลงทะเบียน
                </button>
            </div>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="px-6 mb-8">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="w-1 h-5 bg-primary rounded-full"></span>
            บริการของเรา
        </h2>
        <div class="grid grid-cols-4 gap-x-4 gap-y-6">
            <!-- 1. ร้านค้า -->
            <div onclick="window.location.href='liff-shop.php?account=<?= $lineAccountId ?>'" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-emerald-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-store"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-emerald-600">ร้านค้า</span>
            </div>
            
            <!-- 2. ตะกร้า -->
            <div onclick="window.location.href='liff-checkout.php?account=<?= $lineAccountId ?>'" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-orange-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-orange-600">ตะกร้า</span>
            </div>
            
            <!-- 3. ออเดอร์ -->
            <div onclick="window.location.href='liff-my-orders.php?account=<?= $lineAccountId ?>'" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-blue-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-box-open"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-blue-600">ออเดอร์</span>
            </div>
            
            <!-- 4. แชท -->
            <div onclick="sendMessage('คุยกับเจ้าหน้าที่')" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-indigo-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-comments"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-indigo-600">แชท</span>
            </div>

            <!-- 5. ประวัติแต้ม -->
            <div onclick="sendMessage('ประวัติแต้ม')" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-amber-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-history"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-amber-600">ประวัติแต้ม</span>
            </div>

            <!-- 6. แลกแต้ม -->
            <div onclick="sendMessage('แลกแต้ม')" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-purple-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-gift"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-purple-600">แลกรางวัล</span>
            </div>
            
             <!-- 7. นัดหมาย -->
             <div onclick="window.location.href='liff-appointment.php?account=<?= $lineAccountId ?>'" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-rose-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-rose-600">นัดหมาย</span>
            </div>
            
             <!-- 8. โปรไฟล์ -->
             <div onclick="editProfile()" class="service-btn flex flex-col items-center gap-2 cursor-pointer group">
                <div class="w-[58px] h-[58px] rounded-2xl bg-gray-50 text-gray-500 flex items-center justify-center text-xl shadow-sm group-hover:bg-gray-100 group-hover:shadow-md transition-all duration-300">
                    <i class="fas fa-user-cog"></i>
                </div>
                <span class="text-[11px] font-medium text-gray-600 group-hover:text-gray-800">ตั้งค่า</span>
            </div>
        </div>
    </div>

    <!-- Appointment Section -->
    <div class="px-6 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <span class="w-1 h-5 bg-secondary rounded-full"></span>
                นัดหมายแพทย์
            </h2>
            <div class="flex bg-gray-100 rounded-lg p-0.5">
                <button class="px-3 py-1 rounded-md text-[10px] font-bold bg-white text-primary shadow-sm transition-all" onclick="switchTab(this, 'now')">ทันที</button>
                <button class="px-3 py-1 rounded-md text-[10px] font-medium text-gray-500 hover:text-gray-700 transition-all" onclick="switchTab(this, 'book')">ล่วงหน้า</button>
            </div>
        </div>
        
        <div class="space-y-4">
            <!-- Doctor Card 1 -->
            <div class="doctor-card bg-white p-4 rounded-2xl shadow-soft border border-gray-50 relative overflow-hidden group cursor-pointer" onclick="bookDoctor('นพ. ไกรวิทย์')">
                <div class="absolute top-0 right-0 p-3">
                    <i class="far fa-heart text-gray-300 group-hover:text-secondary transition-colors"></i>
                </div>
                <div class="flex gap-4">
                    <div class="relative flex-shrink-0">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-[72px] h-[72px] rounded-2xl bg-teal-50 object-cover">
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-white shadow-md border border-gray-50 px-2 py-0.5 rounded-full flex items-center gap-1 min-w-max">
                            <i class="fas fa-star text-[8px] text-yellow-400"></i>
                            <span class="text-[9px] font-bold text-gray-700">4.9</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 pt-1">
                        <h4 class="font-bold text-gray-800 text-sm truncate">นพ. ไกรวิทย์ เชิญขวัญ...</h4>
                        <p class="text-[11px] text-primary font-medium mb-2">อายุรแพทย์ทั่วไป</p>
                        
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-bold">AIA</span>
                                <span class="px-1.5 py-0.5 bg-green-50 text-green-600 rounded text-[9px] font-bold">SCB</span>
                            </div>
                            <div class="text-right">
                                <span class="text-secondary font-bold text-sm">฿375</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Card 2 -->
            <div class="doctor-card bg-white p-4 rounded-2xl shadow-soft border border-gray-50 relative overflow-hidden group cursor-pointer" onclick="bookDoctor('พญ. ณิชา')">
                <div class="absolute top-0 right-0 p-3">
                    <i class="far fa-heart text-gray-300 group-hover:text-secondary transition-colors"></i>
                </div>
                <div class="flex gap-4">
                    <div class="relative flex-shrink-0">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Nicha" class="w-[72px] h-[72px] rounded-2xl bg-pink-50 object-cover">
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-white shadow-md border border-gray-50 px-2 py-0.5 rounded-full flex items-center gap-1 min-w-max">
                            <i class="fas fa-star text-[8px] text-yellow-400"></i>
                            <span class="text-[9px] font-bold text-gray-700">5.0</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 pt-1">
                        <h4 class="font-bold text-gray-800 text-sm truncate">พญ. ณิชา พิมลักษณ์</h4>
                        <p class="text-[11px] text-pink-500 font-medium mb-2">แพทย์ผิวหนัง</p>
                        
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold">จ่ายเอง</span>
                            </div>
                            <div class="text-right">
                                <span class="text-secondary font-bold text-sm">฿550</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 glass-nav z-50 pb-[env(safe-area-inset-bottom)]">
        <div class="flex justify-around items-center h-16 px-2">
            <a href="#" class="nav-item flex flex-col items-center justify-center w-full h-full text-primary space-y-1">
                <i class="fas fa-home text-lg"></i>
                <span class="text-[10px] font-medium">หน้าหลัก</span>
            </a>
            <a href="liff-appointment.php?account=<?= $lineAccountId ?>" class="nav-item flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary space-y-1 transition-colors">
                <i class="far fa-calendar-alt text-lg"></i>
                <span class="text-[10px] font-medium">นัดหมาย</span>
            </a>
            
            <!-- Center FAB -->
            <div class="relative -top-6">
                <a href="liff-shop.php?account=<?= $lineAccountId ?>" class="flex items-center justify-center w-14 h-14 bg-gradient-to-tr from-primary to-emerald-400 rounded-full text-white shadow-lg shadow-teal-500/30 transform hover:scale-105 transition-transform">
                    <i class="fas fa-shopping-basket text-xl"></i>
                </a>
            </div>

            <a href="liff-my-orders.php?account=<?= $lineAccountId ?>" class="nav-item flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary space-y-1 transition-colors">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-[10px] font-medium">ออเดอร์</span>
            </a>
            <a href="liff-member-card.php?account=<?= $lineAccountId ?>" class="nav-item flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary space-y-1 transition-colors">
                <i class="far fa-user text-lg"></i>
                <span class="text-[10px] font-medium">โปรไฟล์</span>
            </a>
        </div>
    </nav>

<script>
const BASE_URL = '<?= $baseUrl ?>';
const LIFF_ID = '<?= $liffId ?>';
const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
let userId = null;
let profile = null;

// Sticky Header Effect
window.addEventListener('scroll', () => {
    const header = document.getElementById('mainHeader');
    if (window.scrollY > 10) {
        header.classList.add('shadow-sm', 'bg-white/95');
        header.classList.remove('bg-white/80');
    } else {
        header.classList.remove('shadow-sm', 'bg-white/95');
        header.classList.add('bg-white/80');
    }
});

document.addEventListener('DOMContentLoaded', init);

async function init() {
    if (!LIFF_ID) {
        showGuestCard();
        return;
    }
    
    try {
        await liff.init({ liffId: LIFF_ID });
        
        if (liff.isLoggedIn()) {
            profile = await liff.getProfile();
            userId = profile.userId;
            await loadMemberData();
        } else {
            if (liff.isInClient()) {
                liff.login();
            } else {
                showGuestCard();
            }
        }
    } catch (e) {
        console.error('LIFF Init Error', e);
        showGuestCard();
    }
}

async function loadMemberData() {
    try {
        const response = await fetch(`${BASE_URL}/api/member.php?action=get_card&line_user_id=${userId}&line_account_id=${ACCOUNT_ID}`);
        const data = await response.json();
        
        if (data.success && data.member) {
            renderCard(data);
        } else {
            showNotRegisteredCard();
        }
    } catch (e) {
        showNotRegisteredCard();
    }
}

function renderCard(data) {
    const { member, tier, next_tier } = data;
    
    document.getElementById('cardSkeleton').classList.add('hidden');
    document.getElementById('cardContent').classList.remove('hidden');
    document.getElementById('notRegisteredCard').classList.add('hidden');
    
    const card = document.getElementById('memberCard');
    // Remove old classes
    card.className = card.className.replace(/card-gradient-\w+/g, '');
    card.classList.add('card-gradient-' + (tier?.tier_code || 'platinum'));
    
    // Set Profile Image
    const picUrl = member.picture_url || profile?.pictureUrl || 'https://via.placeholder.com/100';
    document.getElementById('memberPicture').src = picUrl;
    
    // Set Name
    const dispName = (member.first_name || profile?.displayName || '-') + (member.last_name ? ' ' + member.last_name : '');
    document.getElementById('memberName').textContent = dispName;
    
    // Set Tier
    document.getElementById('tierName').textContent = (tier?.tier_name || 'Platinum');
    
    // Set ID
    document.getElementById('memberId').textContent = 'ID: ' + (member.member_id || '000000');
    
    // Set Points
    const points = member.points || 0;
    animateValue('memberPoints', 0, points, 1000);
    
    // Progress Bar
    if (next_tier && tier) {
        const progress = ((points - tier.min_points) / (next_tier.min_points - tier.min_points)) * 100;
        setTimeout(() => {
            document.getElementById('progressBar').style.width = Math.min(100, progress) + '%';
        }, 100);
        document.getElementById('nextTierLabel').textContent = `อีก ${new Intl.NumberFormat('th-TH').format(next_tier.min_points - points)} แต้มถึง ${next_tier.tier_name}`;
    } else {
        document.getElementById('progressBar').style.width = '100%';
        document.getElementById('nextTierLabel').textContent = 'ระดับสูงสุด (Max Level)';
    }
}

function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = new Intl.NumberFormat('th-TH').format(Math.floor(progress * (end - start) + start));
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

function showNotRegisteredCard() {
    document.getElementById('cardSkeleton').classList.add('hidden');
    document.getElementById('cardContent').classList.add('hidden');
    document.getElementById('notRegisteredCard').classList.remove('hidden');
}

function showGuestCard() {
    showNotRegisteredCard();
}

function openRegister() {
    window.location.href = BASE_URL + '/liff-register.php?account=' + ACCOUNT_ID;
}

function editProfile() {
    window.location.href = BASE_URL + '/liff-register.php?account=' + ACCOUNT_ID + '&edit=1';
}

function switchTab(btn, type) {
    // Simple visual switch logic
    const parent = btn.parentElement;
    parent.querySelectorAll('button').forEach(el => {
        el.className = "px-3 py-1 rounded-md text-[10px] font-medium text-gray-500 hover:text-gray-700 transition-all";
    });
    btn.className = "px-3 py-1 rounded-md text-[10px] font-bold bg-white text-primary shadow-sm transition-all";
}

function bookDoctor(name) {
    Swal.fire({
        title: `<span class="text-gray-800 text-lg">นัดพบ ${name}</span>`,
        text: 'ยืนยันการนัดหมายแพทย์ทันที?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#11B0A6',
        cancelButtonColor: '#f3f4f6',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: '<span class="text-gray-600">ยกเลิก</span>',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl px-6',
            cancelButton: 'rounded-xl px-6'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            sendMessage(`นัดพบแพทย์: ${name}`);
        }
    });
}

async function sendMessage(text) {
    if (liff.isInClient()) {
        try {
            await liff.sendMessages([{ type: 'text', text: text }]);
            liff.closeWindow();
        } catch (e) { 
            console.error(e); 
        }
    } else {
        // Fallback for browser testing
        Swal.fire({ 
            icon: 'success', 
            title: 'ส่งข้อความแล้ว', 
            text: `(Simulation) ${text}`, 
            confirmButtonColor: '#11B0A6',
            timer: 1500,
            showConfirmButton: false
        });
    }
}
</script>
</body>
</html>