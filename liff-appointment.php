<?php
/**
 * LIFF Appointment - นัดหมายพบเภสัชกร
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
    <title>นัดหมายพบเภสัชกร - <?= htmlspecialchars($companyName) ?></title>
    <meta name="description" content="จองนัดหมายพบเภสัชกรออนไลน์ที่ <?= htmlspecialchars($companyName) ?> ปรึกษาเรื่องยา สุขภาพ Video Call กับเภสัชกร">
    <meta name="keywords" content="นัดหมายเภสัชกร, จองคิวเภสัชกร, ปรึกษาเภสัชกร, <?= htmlspecialchars($companyName) ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="นัดหมายพบเภสัชกร - <?= htmlspecialchars($companyName) ?>">
    <meta property="og:description" content="จองนัดหมายพบเภสัชกรออนไลน์ ปรึกษาเรื่องยาและสุขภาพ">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="th_TH">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #FFF5F5; }
        .pharmacist-card { transition: transform 0.15s, box-shadow 0.15s; }
        .pharmacist-card:active { transform: scale(0.98); }
        .slot-btn { transition: all 0.15s; }
        .slot-btn.selected { background: #10B981; color: white; }
        .slot-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .date-btn.selected { background: #10B981; color: white; }
        .tab-btn { transition: all 0.2s; }
        .tab-btn.active { color: #EF4444; border-bottom: 2px solid #EF4444; }
        .insurance-logo { width: 32px; height: 32px; object-fit: contain; border-radius: 4px; }
        .rating-badge { 
            position: absolute; bottom: -5px; left: -5px; 
            background: linear-gradient(135deg, #10B981, #059669);
            color: white; font-size: 11px; font-weight: bold;
            padding: 2px 6px; border-radius: 8px;
        }
        .search-box { 
            background: white; border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .doctor-detail-modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: white; z-index: 100; overflow-y: auto;
            transform: translateX(100%); transition: transform 0.3s ease;
        }
        .doctor-detail-modal.show { transform: translateX(0); }
        /* Fix bottom nav overlap */
        .main-content { padding-bottom: 100px !important; }
        
        /* Glass Navigation */
        .glass-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            border-top: 1px solid rgba(255, 255, 255, 0.5);
        }
        .text-primary { color: #14B8A6; }
        .from-primary { --tw-gradient-from: #14B8A6; }
    </style>
</head>
<body class="min-h-screen main-content">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200">
                <i class="fas fa-chevron-left text-gray-600"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">นัดหมาย</h1>
            <button onclick="goToMyAppointments()" class="w-10 h-10 flex items-center justify-center text-red-500">
                <i class="fas fa-calendar-check text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Search Box -->
    <div class="px-4 py-3 bg-gradient-to-b from-white to-transparent">
        <div class="search-box flex items-center px-4 py-3">
            <i class="fas fa-search text-gray-400 mr-3"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาอาการ, แผนก, ชื่อหมอ" 
                   class="flex-1 outline-none text-sm" onkeyup="filterPharmacists()">
            <button onclick="doSearch()" class="bg-red-500 text-white px-4 py-1.5 rounded-lg text-sm font-medium ml-2">
                ค้นหา
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="px-4 mb-3">
        <div class="flex gap-6">
            <button class="tab-btn active pb-2 font-bold" data-tab="instant" onclick="switchTab('instant')">
                พบแพทย์ทันที
            </button>
            <button class="tab-btn pb-2 font-medium text-gray-400" data-tab="schedule" onclick="switchTab('schedule')">
                นัดล่วงหน้า
            </button>
        </div>
    </div>

    <!-- Step Indicator (hidden by default, show in schedule mode) -->
    <div id="stepIndicator" class="hidden px-4 py-3 bg-white border-b">
        <div class="flex items-center justify-center gap-2">
            <div class="step-dot w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold" data-step="1">1</div>
            <div class="w-8 h-0.5 bg-gray-200 step-line" data-step="1"></div>
            <div class="step-dot w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-bold" data-step="2">2</div>
            <div class="w-8 h-0.5 bg-gray-200 step-line" data-step="2"></div>
            <div class="step-dot w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-bold" data-step="3">3</div>
        </div>
        <div class="text-center mt-2 text-sm text-gray-500" id="stepLabel">เลือกเภสัชกร</div>
    </div>

    <!-- Step 1: Select Pharmacist -->
    <div id="step1" class="p-4">
        <div id="pharmacistList" class="space-y-4">
            <!-- Loading -->
            <div id="loadingSkeleton" class="space-y-4">
                <div class="bg-white rounded-xl p-4 animate-pulse">
                    <div class="flex gap-4">
                        <div class="w-20 h-20 bg-gray-200 rounded-xl"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Select Date & Time -->
    <div id="step2" class="hidden p-4">
        <div id="selectedPharmacistInfo" class="bg-white rounded-xl p-4 mb-4 shadow-sm"></div>
        
        <!-- Date Selection -->
        <div class="mb-4">
            <h3 class="font-bold text-gray-800 mb-3">เลือกวันที่</h3>
            <div id="dateList" class="flex gap-2 overflow-x-auto pb-2"></div>
        </div>
        
        <!-- Time Slots -->
        <div class="mb-4">
            <h3 class="font-bold text-gray-800 mb-3">เลือกเวลา</h3>
            <div id="timeSlots" class="grid grid-cols-4 gap-2"></div>
            <p id="noSlotsMsg" class="hidden text-center text-gray-500 py-8">ไม่มีช่วงเวลาว่างในวันนี้</p>
        </div>
        
        <!-- Next Button for Step 2 -->
        <button id="step2NextBtn" onclick="nextStep()" class="w-full py-3 bg-emerald-500 text-white rounded-xl font-bold text-lg disabled:opacity-50" disabled>
            ถัดไป
        </button>
    </div>

    <!-- Step 3: Confirm -->
    <div id="step3" class="hidden p-4">
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <h3 class="font-bold text-gray-800 mb-4">ยืนยันการนัดหมาย</h3>
            <div id="confirmDetails" class="space-y-3"></div>
        </div>
        
        <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
            <h3 class="font-bold text-gray-800 mb-3">อาการ/เหตุผลที่มา (ถ้ามี)</h3>
            <textarea id="symptoms" rows="3" class="w-full border rounded-xl p-3 text-sm" placeholder="เช่น ปวดหัว มีไข้ ต้องการปรึกษาเรื่องยา..."></textarea>
        </div>
        
        <!-- Confirm Button for Step 3 -->
        <button id="step3ConfirmBtn" onclick="bookAppointment()" class="w-full py-3 bg-emerald-500 text-white rounded-xl font-bold text-lg">
            ยืนยันนัดหมาย
        </button>
    </div>

    <!-- Doctor Detail Modal -->
    <div id="doctorDetailModal" class="doctor-detail-modal">
        <div class="bg-gradient-to-b from-teal-50 to-white min-h-screen pb-40">
            <!-- Header -->
            <div class="sticky top-0 bg-white/90 backdrop-blur-sm z-10">
                <div class="flex items-center p-4">
                    <button onclick="closeDoctorDetail()" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm">
                        <i class="fas fa-chevron-left text-gray-500"></i>
                    </button>
                    <h1 class="flex-1 text-center font-bold text-lg text-gray-800">ประวัติแพทย์</h1>
                    <div class="w-10"></div>
                </div>
            </div>
            <div id="doctorDetailContent" class="px-4 pt-2"></div>
        </div>
        <!-- Bottom Action Buttons -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-20">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <span class="text-sm text-gray-600">เลือกช่องทางการปรึกษา</span>
                <div class="flex items-center gap-1">
                    <i class="far fa-clock text-gray-400 text-sm"></i>
                    <span class="font-bold text-gray-800" id="modalDuration">15 นาที</span>
                </div>
            </div>
            <div class="flex gap-3 p-4">
                <button onclick="selectFromDetailVideo()" class="flex-1 py-3 bg-teal-500 text-white rounded-xl font-bold flex items-center justify-center gap-2">
                    <i class="fas fa-video"></i>
                    <span>ปรึกษาวิดีโอ</span>
                </button>
                <button onclick="selectFromDetail()" class="flex-1 py-3 bg-pink-500 text-white rounded-xl font-bold flex items-center justify-center gap-2">
                    <i class="fas fa-calendar-alt"></i>
                    <span>จองนัดหมาย</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    let userId = null;
    let currentStep = 1;
    let currentTab = 'instant';
    let selectedPharmacist = null;
    let selectedDate = null;
    let selectedTime = null;
    let allPharmacists = [];
    let viewingPharmacist = null;

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
                await loadPharmacists();
            } else {
                // Auto login - works in both LINE App and External Browser
                liff.login();
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาด');
        }
    }

    async function loadPharmacists() {
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php?action=pharmacists&line_account_id=${ACCOUNT_ID}`);
            const data = await response.json();
            
            document.getElementById('loadingSkeleton').classList.add('hidden');
            
            if (data.success && data.pharmacists.length > 0) {
                allPharmacists = data.pharmacists;
                renderPharmacists(data.pharmacists);
            } else {
                document.getElementById('pharmacistList').innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-user-md text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">ยังไม่มีเภสัชกรให้บริการ</p>
                    </div>
                `;
            }
        } catch (e) {
            console.error(e);
            showError('ไม่สามารถโหลดข้อมูลได้');
        }
    }

    function filterPharmacists() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();
        if (!query) {
            renderPharmacists(allPharmacists);
            return;
        }
        
        const filtered = allPharmacists.filter(p => {
            return p.name.toLowerCase().includes(query) ||
                   (p.specialty && p.specialty.toLowerCase().includes(query)) ||
                   (p.bio && p.bio.toLowerCase().includes(query)) ||
                   (p.consulting_areas && p.consulting_areas.toLowerCase().includes(query));
        });
        renderPharmacists(filtered);
    }

    function doSearch() {
        filterPharmacists();
    }

    function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
            btn.classList.toggle('text-gray-400', btn.dataset.tab !== tab);
        });
        
        // Show/hide step indicator based on tab
        document.getElementById('stepIndicator').classList.toggle('hidden', tab === 'instant');
    }

    function renderPharmacists(pharmacists) {
        const container = document.getElementById('pharmacistList');
        
        if (pharmacists.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">ไม่พบผลลัพธ์ที่ค้นหา</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        pharmacists.forEach(p => {
            const fee = p.consultation_fee > 0 ? `฿${numberFormat(p.consultation_fee)}` : 'ฟรี';
            const caseCount = p.case_count || 0;
            const duration = p.consultation_duration || 15;
            
            // Insurance logos from API
            const insurances = p.insurances || [];
            
            const insuranceHtml = insurances.slice(0, 4).map(ins => 
                `<img src="${ins.logo_url || ins.logo}" alt="${ins.name}" class="insurance-logo" onerror="this.src='https://via.placeholder.com/32?text=${ins.name.charAt(0)}'">`
            ).join('');
            
            const moreInsurance = insurances.length > 4 ? `<span class="text-xs text-gray-500 ml-1">+${insurances.length - 4}</span>` : '';
            
            html += `
                <div class="pharmacist-card bg-white rounded-2xl p-4 shadow-sm mb-4">
                    <div class="flex gap-4">
                        <div class="relative cursor-pointer" onclick="showDoctorDetail(${p.id})">
                            <img src="${p.image_url || 'https://via.placeholder.com/100'}" 
                                class="w-24 h-24 rounded-2xl object-cover bg-gray-100">
                            <div class="rating-badge">
                                <i class="fas fa-star text-yellow-300 mr-0.5"></i>${p.rating || '4.9'}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div class="cursor-pointer" onclick="showDoctorDetail(${p.id})">
                                    <h3 class="font-bold text-gray-800 text-lg">${escapeHtml(p.name)}</h3>
                                    <span class="inline-block px-2 py-0.5 bg-gray-100 rounded-full text-xs text-gray-600 mt-1">
                                        ${escapeHtml(p.specialty || 'เภสัชกรทั่วไป')}
                                    </span>
                                </div>
                                <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">TH</span>
                            </div>
                            <div class="mt-2 space-y-1 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-stethoscope text-emerald-500 w-4"></i>
                                    <span>${escapeHtml(p.sub_specialty || p.specialty || 'ความเชี่ยวชาญ')}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-id-card text-emerald-500 w-4"></i>
                                    <span>${escapeHtml(p.license_no || 'ใบอนุญาต')}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-hospital text-emerald-500 w-4"></i>
                                    <span>${escapeHtml(p.hospital || 'สถานที่ทำงาน')}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <span class="font-bold text-red-500 text-xl">${fee}</span>
                                <span class="text-sm text-gray-500">${duration} นาที</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Insurance Section -->
                    <div class="mt-3 pt-3 border-t flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">ประกันที่รองรับ</span>
                            <div class="flex items-center gap-1">
                                ${insuranceHtml || '<span class="text-xs text-gray-400">-</span>'}
                                ${moreInsurance}
                            </div>
                        </div>
                        <button onclick="selectPharmacistById(${p.id})" 
                                class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-medium ${!p.is_available ? 'opacity-50' : ''}"
                                ${!p.is_available ? 'disabled' : ''}>
                            ${p.is_available ? 'นัดหมาย' : 'ไม่ว่าง'}
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    async function showDoctorDetail(pharmacistId) {
        // Fetch full detail from API
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php?action=pharmacist_detail&pharmacist_id=${pharmacistId}`);
            const data = await response.json();
            
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'ไม่พบข้อมูล', text: data.message });
                return;
            }
            
            const pharmacist = data.pharmacist;
            viewingPharmacist = pharmacist;
            
            const modal = document.getElementById('doctorDetailModal');
            const content = document.getElementById('doctorDetailContent');
            
            const fee = pharmacist.consultation_fee > 0 ? `฿${numberFormat(pharmacist.consultation_fee)}` : 'ฟรี';
            const caseCount = pharmacist.case_count || 0;
            const duration = pharmacist.consultation_duration || 15;
            
            // Consulting areas from API
            const consultingAreas = pharmacist.consulting_areas || 'ไม่มีข้อมูล';
            
            // Work experience from API
            const workExperience = pharmacist.work_experience || pharmacist.bio || 'ไม่มีข้อมูล';
            
            // Insurances from API
            const insurances = pharmacist.insurances || [];
            
            const insuranceHtml = insurances.slice(0, 4).map(ins => 
                `<img src="${ins.logo_url || ins.logo}" alt="${ins.name}" class="w-8 h-8 object-contain rounded" onerror="this.src='https://via.placeholder.com/32?text=${ins.name.charAt(0)}'">`
            ).join('') || '<span class="text-gray-400 text-sm">ไม่มีข้อมูล</span>';
            
            content.innerHTML = `
                <!-- Doctor Info Card -->
                <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-gray-100">
                    <div class="flex gap-4">
                        <div class="relative flex-shrink-0">
                            <img src="${pharmacist.image_url || 'https://via.placeholder.com/100'}" 
                                class="w-24 h-24 rounded-2xl object-cover bg-gray-50">
                            <div class="absolute -bottom-2 -left-2 bg-teal-500 text-white text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class="fas fa-star text-yellow-300 text-[10px]"></i>
                                <span>${pharmacist.rating || '4.9'}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-gray-900 text-base leading-tight truncate">${escapeHtml(pharmacist.name)}</h3>
                                <span class="flex-shrink-0 text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">TH</span>
                            </div>
                            <span class="inline-block px-3 py-1 bg-gray-100 rounded-full text-xs text-gray-600 mt-2 font-medium">
                                ${escapeHtml(pharmacist.specialty || 'เภสัชกรทั่วไป')}
                            </span>
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-stethoscope text-teal-500 w-4 text-xs"></i>
                                    <span class="truncate">${escapeHtml(pharmacist.sub_specialty || pharmacist.specialty || 'ความเชี่ยวชาญ')}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-id-card text-teal-500 w-4 text-xs"></i>
                                    <span class="truncate">${escapeHtml(pharmacist.license_no || 'ใบอนุญาต')}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-hospital text-teal-500 w-4 text-xs"></i>
                                    <span class="truncate">${escapeHtml(pharmacist.hospital || 'สถานที่ทำงาน')}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-3 gap-2">
                                <span class="font-bold text-teal-600 text-xl">${fee}</span>
                                <span class="text-sm text-gray-400">${duration} นาที</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ความเชี่ยวชาญเฉพาะทาง -->
                <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-gray-100">
                    <h4 class="font-bold text-gray-900 text-base mb-2">ความเชี่ยวชาญเฉพาะทาง</h4>
                    <p class="text-gray-600 text-sm">${escapeHtml(pharmacist.sub_specialty || pharmacist.specialty || 'เภสัชกรทั่วไป')}</p>
                </div>
                
                <!-- ประวัติการทำงาน -->
                <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-gray-100">
                    <h4 class="font-bold text-gray-900 text-base mb-2">ประวัติการทำงาน</h4>
                    <p class="text-gray-600 text-sm whitespace-pre-line leading-relaxed">${escapeHtml(workExperience)}</p>
                </div>
                
                <!-- Consulting area -->
                <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-gray-100">
                    <h4 class="font-bold text-gray-900 text-base mb-2">Consulting area:</h4>
                    <p class="text-gray-600 text-sm whitespace-pre-line leading-relaxed">${escapeHtml(consultingAreas)}</p>
                </div>
                
                <!-- ประกันที่รองรับ -->
                <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-600 font-medium">ประกันที่รองรับ</span>
                            <div class="flex items-center gap-1.5">
                                ${insuranceHtml}
                            </div>
                        </div>
                        ${insurances.length > 4 ? `<button class="text-teal-500 text-sm font-medium">เพิ่มเติม</button>` : ''}
                    </div>
                </div>
                
                <!-- เลือกช่องทางการปรึกษา -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 text-sm">เลือกช่องทางการปรึกษา</span>
                        <div class="flex items-center gap-1.5">
                            <i class="far fa-clock text-gray-400 text-sm"></i>
                            <span class="font-bold text-gray-800">${duration} นาที</span>
                        </div>
                    </div>
                </div>
            `;
            
            modal.classList.add('show');
            
            // Update duration in modal footer
            document.getElementById('modalDuration').textContent = duration + ' นาที';
            
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด' });
        }
    }

    function selectPharmacistById(pharmacistId) {
        const pharmacist = allPharmacists.find(p => p.id == pharmacistId);
        if (pharmacist) {
            selectPharmacist(pharmacist);
        }
    }

    function closeDoctorDetail() {
        document.getElementById('doctorDetailModal').classList.remove('show');
        viewingPharmacist = null;
    }

    function selectFromDetail() {
        if (viewingPharmacist) {
            selectPharmacist(viewingPharmacist);
            closeDoctorDetail();
        }
    }

    function selectFromDetailVideo() {
        if (viewingPharmacist) {
            // Open video call directly
            window.location.href = `${BASE_URL}/liff-video-call-pro.php?pharmacist=${viewingPharmacist.id}&account=${ACCOUNT_ID}`;
        }
    }

    function selectPharmacist(pharmacist) {
        selectedPharmacist = pharmacist;
        
        // Go directly to step 2 (select date/time)
        showStep(2);
        renderSelectedPharmacist();
        renderDates();
    }

    async function nextStep() {
        if (currentStep === 2 && selectedDate && selectedTime) {
            showStep(3);
            renderConfirmation();
        } else if (currentStep === 3) {
            await bookAppointment();
        }
    }

    function showStep(step) {
        currentStep = step;
        
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.add('hidden');
        document.getElementById(`step${step}`).classList.remove('hidden');
        
        // Show step indicator when not on step 1
        document.getElementById('stepIndicator').classList.toggle('hidden', step === 1 && currentTab === 'instant');
        
        // Update step indicators
        document.querySelectorAll('.step-dot').forEach(dot => {
            const s = parseInt(dot.dataset.step);
            if (s <= step) {
                dot.classList.remove('bg-gray-200', 'text-gray-500');
                dot.classList.add('bg-emerald-500', 'text-white');
            } else {
                dot.classList.add('bg-gray-200', 'text-gray-500');
                dot.classList.remove('bg-emerald-500', 'text-white');
            }
        });
        
        document.querySelectorAll('.step-line').forEach(line => {
            const s = parseInt(line.dataset.step);
            line.classList.toggle('bg-emerald-500', s < step);
            line.classList.toggle('bg-gray-200', s >= step);
        });
        
        const labels = ['', 'เลือกเภสัชกร', 'เลือกวันและเวลา', 'ยืนยันการนัดหมาย'];
        document.getElementById('stepLabel').textContent = labels[step];
    }

    function renderSelectedPharmacist() {
        const p = selectedPharmacist;
        document.getElementById('selectedPharmacistInfo').innerHTML = `
            <div class="flex items-center gap-3">
                <img src="${p.image_url || 'https://via.placeholder.com/60'}" class="w-14 h-14 rounded-xl object-cover">
                <div>
                    <h3 class="font-bold text-gray-800">${escapeHtml(p.name)}</h3>
                    <p class="text-xs text-emerald-600">${escapeHtml(p.specialty)}</p>
                </div>
                <button onclick="showStep(1)" class="ml-auto text-gray-400 hover:text-gray-600">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;
    }

    function renderDates() {
        const container = document.getElementById('dateList');
        const today = new Date();
        let html = '';
        
        for (let i = 0; i < 14; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            const dateStr = date.toISOString().split('T')[0];
            const dayName = date.toLocaleDateString('th-TH', { weekday: 'short' });
            const dayNum = date.getDate();
            const monthName = date.toLocaleDateString('th-TH', { month: 'short' });
            
            html += `
                <button onclick="selectDate('${dateStr}')" 
                    class="date-btn flex-shrink-0 w-16 py-3 rounded-xl border text-center ${selectedDate === dateStr ? 'selected' : 'bg-white'}"
                    data-date="${dateStr}">
                    <div class="text-xs text-gray-500">${dayName}</div>
                    <div class="text-lg font-bold">${dayNum}</div>
                    <div class="text-xs">${monthName}</div>
                </button>
            `;
        }
        
        container.innerHTML = html;
        
        // Auto select first date
        if (!selectedDate) {
            selectDate(today.toISOString().split('T')[0]);
        }
    }

    async function selectDate(date) {
        selectedDate = date;
        selectedTime = null;
        
        // Update UI
        document.querySelectorAll('.date-btn').forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.date === date);
        });
        
        document.getElementById('step2NextBtn').disabled = true;
        
        // Load time slots
        await loadTimeSlots();
    }

    async function loadTimeSlots() {
        const container = document.getElementById('timeSlots');
        const noSlotsMsg = document.getElementById('noSlotsMsg');
        
        container.innerHTML = '<div class="col-span-4 text-center py-4"><i class="fas fa-spinner fa-spin text-emerald-500"></i></div>';
        noSlotsMsg.classList.add('hidden');
        
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php?action=available_slots&pharmacist_id=${selectedPharmacist.id}&date=${selectedDate}`);
            const data = await response.json();
            
            if (data.success && data.slots.length > 0) {
                let html = '';
                data.slots.forEach(slot => {
                    const selected = selectedTime === slot.time ? 'selected' : '';
                    html += `
                        <button onclick="selectTime('${slot.time}')" 
                            class="slot-btn py-2 rounded-lg border text-sm font-medium ${selected} ${slot.available ? 'bg-white' : 'bg-gray-100'}"
                            data-time="${slot.time}" ${!slot.available ? 'disabled' : ''}>
                            ${slot.time}
                        </button>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '';
                noSlotsMsg.classList.remove('hidden');
                noSlotsMsg.textContent = data.message || 'ไม่มีช่วงเวลาว่างในวันนี้';
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="col-span-4 text-center text-red-500 py-4">เกิดข้อผิดพลาด</div>';
        }
    }

    function selectTime(time) {
        selectedTime = time;
        
        document.querySelectorAll('.slot-btn').forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.time === time);
        });
        
        document.getElementById('step2NextBtn').disabled = false;
    }

    function renderConfirmation() {
        const p = selectedPharmacist;
        const dateObj = new Date(selectedDate);
        const dateStr = dateObj.toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const fee = p.consultation_fee > 0 ? `฿${numberFormat(p.consultation_fee)}` : 'ฟรี';
        
        document.getElementById('confirmDetails').innerHTML = `
            <div class="flex items-center gap-3 pb-3 border-b">
                <img src="${p.image_url || 'https://via.placeholder.com/50'}" class="w-12 h-12 rounded-xl object-cover">
                <div>
                    <h4 class="font-bold text-gray-800">${escapeHtml(p.name)}</h4>
                    <p class="text-xs text-emerald-600">${escapeHtml(p.specialty)}</p>
                </div>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500"><i class="far fa-calendar mr-2"></i>วันที่</span>
                <span class="font-medium">${dateStr}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500"><i class="far fa-clock mr-2"></i>เวลา</span>
                <span class="font-medium">${selectedTime} น.</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500"><i class="fas fa-hourglass-half mr-2"></i>ระยะเวลา</span>
                <span class="font-medium">${p.consultation_duration} นาที</span>
            </div>
            <div class="flex justify-between py-2 pt-3 border-t">
                <span class="text-gray-800 font-bold">ค่าบริการ</span>
                <span class="font-bold text-red-500 text-lg">${fee}</span>
            </div>
        `;
    }

    async function bookAppointment() {
        const btn = document.getElementById('step3ConfirmBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังจอง...';
        
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'book',
                    line_user_id: userId,
                    line_account_id: ACCOUNT_ID,
                    pharmacist_id: selectedPharmacist.id,
                    date: selectedDate,
                    time: selectedTime,
                    symptoms: document.getElementById('symptoms').value
                })
            });
            const data = await response.json();
            
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'จองสำเร็จ!',
                    html: `รหัสนัดหมาย: <b class="text-emerald-600">${data.appointment_id}</b><br>
                           <small class="text-gray-500">${selectedDate} เวลา ${selectedTime} น.</small>`,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'ดูนัดหมายของฉัน'
                });
                goToMyAppointments();
            } else {
                Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message, confirmButtonColor: '#10B981' });
                btn.disabled = false;
                btn.textContent = 'ยืนยันนัดหมาย';
            }
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', confirmButtonColor: '#10B981' });
            btn.disabled = false;
            btn.textContent = 'ยืนยันนัดหมาย';
        }
    }

    function goBack() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        } else if (liff.isInClient()) {
            window.location.href = `${BASE_URL}/liff-member-card.php?account=${ACCOUNT_ID}`;
        } else {
            window.history.back();
        }
    }

    function goToMyAppointments() {
        window.location.href = `${BASE_URL}/liff-my-appointments.php?account=${ACCOUNT_ID}`;
    }

    function showError(msg) {
        document.getElementById('loadingSkeleton').classList.add('hidden');
        document.getElementById('pharmacistList').innerHTML = `
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
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
