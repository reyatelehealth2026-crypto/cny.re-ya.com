<?php
/**
 * LIFF My Appointments - นัดหมายของฉัน
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
    <title>นัดหมายของฉัน - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-confirmed { background: #DBEAFE; color: #2563EB; }
        .status-in_progress { background: #E0E7FF; color: #4F46E5; }
        .status-completed { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
        .status-no_show { background: #F3F4F6; color: #6B7280; }
        .status-soon { background: #DCFCE7; color: #16A34A; animation: pulse 2s infinite; }
        .status-now { background: #06C755; color: white; animation: pulse 1s infinite; }
        .tab-active { border-bottom: 2px solid #7C3AED; color: #7C3AED; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .video-btn { background: linear-gradient(135deg, #06C755 0%, #00B341 100%); }
        .video-btn:hover { transform: scale(1.02); }
        .countdown-badge { background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%); }
    </style>
</head>
<body class="min-h-screen pb-20">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between p-4">
            <button onclick="goBack()" class="w-10 h-10 flex items-center justify-center text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </button>
            <h1 class="font-bold text-lg text-gray-800">นัดหมายของฉัน</h1>
            <div class="w-10"></div>
        </div>
        
        <!-- Tabs -->
        <div class="flex border-b">
            <button onclick="filterAppointments('upcoming')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500 tab-active" data-tab="upcoming">กำลังจะมาถึง</button>
            <button onclick="filterAppointments('past')" class="tab-btn flex-1 py-3 text-sm font-medium text-gray-500" data-tab="past">ที่ผ่านมา</button>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="p-4">
        <div id="appointmentsList" class="space-y-4">
            <!-- Loading -->
            <div id="loadingSkeleton" class="space-y-4">
                <div class="bg-white rounded-xl p-4 animate-pulse">
                    <div class="flex gap-4">
                        <div class="w-16 h-16 bg-gray-200 rounded-xl"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-16">
                <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">ยังไม่มีนัดหมาย</p>
                <button onclick="goToBook()" class="px-6 py-2 bg-purple-600 text-white rounded-xl font-bold">
                    <i class="fas fa-plus mr-2"></i>จองนัดหมาย
                </button>
            </div>
        </div>
    </div>

    <!-- FAB -->
    <button onclick="goToBook()" class="fixed bottom-6 right-6 w-14 h-14 bg-purple-600 text-white rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <script>
    const BASE_URL = '<?= $baseUrl ?>';
    const LIFF_ID = '<?= $liffId ?>';
    const ACCOUNT_ID = <?= (int)$lineAccountId ?>;
    
    let userId = null;
    let allData = { upcoming: [], past: [] };
    let currentTab = 'upcoming';

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
                await loadAppointments();
            } else {
                // Auto login - works in both LINE App and External Browser
                liff.login();
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาด');
        }
    }

    async function loadAppointments() {
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php?action=my_appointments&line_user_id=${userId}`);
            const data = await response.json();
            
            const skeleton = document.getElementById('loadingSkeleton');
            if (skeleton) skeleton.classList.add('hidden');
            
            if (data.success) {
                allData.upcoming = data.upcoming || [];
                allData.past = data.past || [];
                renderAppointments(allData[currentTab]);
            } else {
                showError(data.message || 'ไม่สามารถโหลดข้อมูลได้');
            }
        } catch (e) {
            console.error(e);
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    // Auto refresh every 30 seconds to update countdown
    setInterval(() => {
        if (currentTab === 'upcoming' && allData.upcoming.length > 0) {
            renderAppointments(allData.upcoming);
        }
    }, 30000);

    function renderAppointments(appointments) {
        const container = document.getElementById('appointmentsList');
        const skeleton = document.getElementById('loadingSkeleton');
        
        // Hide skeleton if exists
        if (skeleton) skeleton.classList.add('hidden');
        
        if (appointments.length === 0) {
            const emptyMsg = currentTab === 'upcoming' ? 'ยังไม่มีนัดหมายที่กำลังจะมาถึง' : 'ยังไม่มีนัดหมายที่ผ่านมา';
            container.innerHTML = `
                <div class="text-center py-16">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">${emptyMsg}</p>
                    <button onclick="goToBook()" class="px-6 py-2 bg-purple-600 text-white rounded-xl font-bold">
                        <i class="fas fa-plus mr-2"></i>จองนัดหมาย
                    </button>
                </div>
            `;
            return;
        }
        
        let html = '';
        const now = new Date();
        
        appointments.forEach(apt => {
            const aptDateTime = new Date(`${apt.appointment_date}T${apt.appointment_time}`);
            const diffMins = (aptDateTime - now) / 60000;
            
            // Determine appointment state
            const isPast = diffMins < -30; // ผ่านมาแล้ว 30 นาที
            const isNow = diffMins >= -15 && diffMins <= 5 && apt.status === 'confirmed';
            const isSoon = diffMins > 0 && diffMins <= 30 && apt.status === 'confirmed';
            const isCompleted = apt.status === 'completed';
            const isCancelled = apt.status === 'cancelled';
            const isNoShow = apt.status === 'no_show';
            const canVideoCall = (isSoon || isNow) && apt.status === 'confirmed';
            const canCancel = ['pending', 'confirmed'].includes(apt.status) && !isPast;
            const canEdit = ['pending', 'confirmed'].includes(apt.status) && diffMins > 60; // แก้ไขได้ถ้าเหลือเวลามากกว่า 1 ชม.
            const canRate = isCompleted && !apt.rating;
            
            // Status info with better visual
            let statusInfo = getStatusInfo(apt.status, apt.appointment_date, apt.appointment_time);
            
            // Override status for past appointments
            if (isPast && apt.status === 'confirmed') {
                statusInfo = { text: '⏰ เลยเวลาแล้ว', class: 'status-no_show' };
            }
            
            const dateObj = new Date(apt.appointment_date);
            const dateStr = dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
            const dayName = dateObj.toLocaleDateString('th-TH', { weekday: 'short' });
            
            // Countdown text
            let countdownHtml = '';
            if (isSoon && !isNow) {
                const mins = Math.ceil(diffMins);
                countdownHtml = `<div class="countdown-badge text-white text-xs px-3 py-1 rounded-full inline-flex items-center gap-1 mb-3">
                    <i class="fas fa-clock"></i> อีก ${mins} นาที
                </div>`;
            } else if (isNow) {
                countdownHtml = `<div class="bg-green-500 text-white text-xs px-3 py-1 rounded-full inline-flex items-center gap-1 mb-3 animate-pulse">
                    <i class="fas fa-video"></i> ถึงเวลานัดแล้ว!
                </div>`;
            } else if (isPast && !isCompleted && !isCancelled) {
                countdownHtml = `<div class="bg-gray-400 text-white text-xs px-3 py-1 rounded-full inline-flex items-center gap-1 mb-3">
                    <i class="fas fa-history"></i> เลยเวลานัดแล้ว
                </div>`;
            }
            
            // Card border color based on status
            let cardClass = 'bg-white';
            if (isNow) cardClass += ' ring-2 ring-green-500';
            else if (isSoon) cardClass += ' ring-2 ring-orange-400';
            else if (isCompleted) cardClass += ' border-l-4 border-green-500';
            else if (isCancelled) cardClass += ' border-l-4 border-red-400 opacity-70';
            else if (isPast) cardClass += ' border-l-4 border-gray-400 opacity-80';
            
            // Pharmacist details
            const pharmacistRating = apt.pharmacist_rating || '4.9';
            const consultationFee = apt.consultation_fee ? `฿${numberFormat(apt.consultation_fee)}` : 'ฟรี';
            const duration = apt.consultation_duration || 15;
            
            html += `
                <div class="${cardClass} rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <span class="text-xs text-gray-400">#${apt.appointment_id}</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500"><i class="far fa-calendar mr-1"></i>${dayName} ${dateStr}</span>
                                    <span class="text-xs font-bold text-purple-600"><i class="far fa-clock mr-1"></i>${apt.appointment_time.substring(0,5)} น.</span>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs font-bold ${statusInfo.class}">${statusInfo.text}</span>
                        </div>
                        
                        ${countdownHtml}
                        
                        <!-- Pharmacist Card -->
                        <div class="bg-gradient-to-r ${isCompleted ? 'from-green-50 to-emerald-50' : isCancelled ? 'from-gray-50 to-gray-100' : 'from-purple-50 to-indigo-50'} rounded-xl p-4 mb-3" onclick="showPharmacistDetail(${apt.pharmacist_id})">
                            <div class="flex gap-3">
                                <div class="relative">
                                    <img src="${apt.pharmacist_image || 'https://via.placeholder.com/80'}" 
                                        class="w-16 h-16 rounded-xl object-cover border-2 border-white shadow ${isCancelled ? 'grayscale' : ''}">
                                    <div class="absolute -bottom-1 -right-1 bg-yellow-400 text-white text-xs px-1.5 py-0.5 rounded-full flex items-center gap-0.5">
                                        <i class="fas fa-star text-[8px]"></i>
                                        <span class="font-bold">${pharmacistRating}</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800">${apt.pharmacist_title || ''}${escapeHtml(apt.pharmacist_name)}</h3>
                                    <p class="text-xs text-purple-600 font-medium">${escapeHtml(apt.specialty || 'เภสัชกรทั่วไป')}</p>
                                    ${apt.hospital ? `<p class="text-xs text-gray-500 mt-1"><i class="fas fa-hospital text-gray-400 mr-1"></i>${escapeHtml(apt.hospital)}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-green-600">${consultationFee}</p>
                                    <p class="text-xs text-gray-400">${duration} นาที</p>
                                </div>
                            </div>
                        </div>
                        
                        ${apt.symptoms ? `<p class="text-xs text-gray-500 bg-gray-50 rounded-lg p-2 mb-3"><i class="fas fa-notes-medical mr-1 text-purple-400"></i>${escapeHtml(apt.symptoms)}</p>` : ''}
                        
                        <!-- Completed/Cancelled Status Banner -->
                        ${isCompleted ? `
                        <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-3 flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500 text-lg"></i>
                            <div>
                                <p class="text-sm font-bold text-green-700">เสร็จสิ้นแล้ว</p>
                                <p class="text-xs text-green-600">ขอบคุณที่ใช้บริการ</p>
                            </div>
                        </div>` : ''}
                        
                        ${isCancelled ? `
                        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-3 flex items-center gap-2">
                            <i class="fas fa-times-circle text-red-500 text-lg"></i>
                            <div>
                                <p class="text-sm font-bold text-red-700">ยกเลิกแล้ว</p>
                                ${apt.cancel_reason ? `<p class="text-xs text-red-600">เหตุผล: ${escapeHtml(apt.cancel_reason)}</p>` : ''}
                            </div>
                        </div>` : ''}
                        
                        ${isNoShow ? `
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 mb-3 flex items-center gap-2">
                            <i class="fas fa-user-slash text-gray-500 text-lg"></i>
                            <div>
                                <p class="text-sm font-bold text-gray-700">ไม่มาตามนัด</p>
                            </div>
                        </div>` : ''}
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-2 flex-wrap">
                            ${canVideoCall ? `
                            <button onclick="event.stopPropagation(); startVideoCall('${apt.appointment_id}')" class="flex-1 py-3 video-btn text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-transform">
                                <i class="fas fa-video"></i> ${isNow ? 'เริ่ม Video Call เลย!' : 'Video Call'}
                            </button>` : ''}
                            
                            ${canEdit ? `
                            <button onclick="event.stopPropagation(); editAppointment('${apt.appointment_id}')" class="flex-1 py-2 border border-purple-300 text-purple-600 rounded-xl text-sm font-medium flex items-center justify-center gap-1">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>` : ''}
                            
                            ${canCancel ? `
                            <button onclick="event.stopPropagation(); cancelAppointment('${apt.appointment_id}')" class="flex-1 py-2 border border-red-200 text-red-500 rounded-xl text-sm font-medium flex items-center justify-center gap-1">
                                <i class="fas fa-times"></i> ยกเลิก
                            </button>` : ''}
                            
                            ${canRate ? `
                            <button onclick="event.stopPropagation(); rateAppointment('${apt.appointment_id}')" class="flex-1 py-2 bg-yellow-400 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-1">
                                <i class="fas fa-star"></i> ให้คะแนน
                            </button>` : ''}
                            
                            ${apt.rating ? `
                            <div class="flex-1 text-center py-2 bg-yellow-50 rounded-xl text-yellow-600 flex items-center justify-center gap-1">
                                <i class="fas fa-star"></i> ${apt.rating}/5 คะแนน
                            </div>` : ''}
                            
                            ${!canVideoCall && !canEdit && !canCancel && !canRate && !apt.rating && !isCompleted && !isCancelled ? `
                            <button onclick="event.stopPropagation(); goToBook()" class="flex-1 py-2 bg-purple-100 text-purple-600 rounded-xl text-sm font-medium flex items-center justify-center gap-1">
                                <i class="fas fa-plus"></i> จองใหม่
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function filterAppointments(tab) {
        currentTab = tab;
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('tab-active');
            if (btn.dataset.tab === tab) {
                btn.classList.add('tab-active');
            }
        });
        
        renderAppointments(allData[tab]);
    }

    function getStatusInfo(status, aptDate, aptTime) {
        // Check if appointment is soon or now
        if (status === 'confirmed' && aptDate && aptTime) {
            const aptDateTime = new Date(`${aptDate}T${aptTime}`);
            const now = new Date();
            const diffMins = (aptDateTime - now) / 60000;
            
            if (diffMins >= -15 && diffMins <= 5) {
                return { text: '🟢 ถึงเวลาแล้ว!', class: 'status-now' };
            }
            if (diffMins > 0 && diffMins <= 30) {
                return { text: '⏰ ใกล้ถึงเวลา', class: 'status-soon' };
            }
        }
        
        const statuses = {
            'pending': { text: 'รอยืนยัน', class: 'status-pending' },
            'confirmed': { text: 'ยืนยันแล้ว', class: 'status-confirmed' },
            'in_progress': { text: 'กำลังพบ', class: 'status-in_progress' },
            'completed': { text: 'เสร็จสิ้น', class: 'status-completed' },
            'cancelled': { text: 'ยกเลิก', class: 'status-cancelled' },
            'no_show': { text: 'ไม่มา', class: 'status-no_show' },
        };
        return statuses[status] || { text: status, class: 'status-pending' };
    }

    function startVideoCall(appointmentId) {
        // ใช้ LIFF URL สำหรับ Video Call (LIFF ID: 2008477880-FDhymfKU)
        const videoCallLiffId = '2008477880-FDhymfKU';
        window.location.href = `https://liff.line.me/${videoCallLiffId}?appointment=${appointmentId}&account=${ACCOUNT_ID}`;
    }

    function editAppointment(appointmentId) {
        // ไปหน้าแก้ไขนัดหมาย
        window.location.href = `${BASE_URL}/liff-appointment.php?account=${ACCOUNT_ID}&edit=${appointmentId}`;
    }

    async function showPharmacistDetail(pharmacistId) {
        try {
            Swal.fire({ title: 'กำลังโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            const response = await fetch(`${BASE_URL}/api/appointments.php?action=pharmacist_detail&pharmacist_id=${pharmacistId}`);
            const data = await response.json();
            
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'ไม่พบข้อมูล', confirmButtonColor: '#7C3AED' });
                return;
            }
            
            const p = data.pharmacist;
            const fee = p.consultation_fee > 0 ? `฿${numberFormat(p.consultation_fee)}` : 'ฟรี';
            const duration = p.consultation_duration || 15;
            
            Swal.fire({
                html: `
                    <div class="text-left">
                        <div class="flex items-center gap-4 mb-4">
                            <img src="${p.image_url || 'https://via.placeholder.com/80'}" class="w-20 h-20 rounded-xl object-cover">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800">${p.title || ''}${p.name}</h3>
                                <p class="text-purple-600 text-sm">${p.specialty || 'เภสัชกรทั่วไป'}</p>
                                <div class="flex items-center gap-1 mt-1">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="font-bold">${p.rating || '4.9'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            ${p.sub_specialty ? `<p><i class="fas fa-stethoscope text-purple-500 w-5"></i> ${p.sub_specialty}</p>` : ''}
                            ${p.license_no ? `<p><i class="fas fa-id-card text-purple-500 w-5"></i> ${p.license_no}</p>` : ''}
                            ${p.hospital ? `<p><i class="fas fa-hospital text-purple-500 w-5"></i> ${p.hospital}</p>` : ''}
                        </div>
                        
                        ${p.bio ? `<div class="mt-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">${p.bio}</div>` : ''}
                        
                        ${p.consulting_areas ? `
                        <div class="mt-4">
                            <p class="font-bold text-sm mb-2">ความเชี่ยวชาญ:</p>
                            <p class="text-sm text-gray-600">${p.consulting_areas}</p>
                        </div>` : ''}
                        
                        <div class="mt-4 flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-gray-600">ค่าบริการ</span>
                            <span class="font-bold text-purple-600 text-lg">${fee} <span class="text-sm font-normal text-gray-500">/ ${duration} นาที</span></span>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl' }
            });
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', confirmButtonColor: '#7C3AED' });
        }
    }

    function numberFormat(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    async function cancelAppointment(appointmentId) {
        const result = await Swal.fire({
            title: 'ยกเลิกนัดหมาย?',
            text: 'คุณต้องการยกเลิกนัดหมายนี้หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'ยกเลิกนัดหมาย',
            cancelButtonText: 'ไม่'
        });
        
        if (!result.isConfirmed) return;
        
        const { value: reason } = await Swal.fire({
            title: 'เหตุผลในการยกเลิก',
            input: 'text',
            inputPlaceholder: 'ระบุเหตุผล (ไม่บังคับ)',
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ข้าม'
        });
        
        try {
            Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            const response = await fetch(`${BASE_URL}/api/appointments.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cancel',
                    appointment_id: appointmentId,
                    line_user_id: userId,
                    reason: reason || ''
                })
            });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'ยกเลิกสำเร็จ', confirmButtonColor: '#7C3AED' });
                loadAppointments();
            } else {
                Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message, confirmButtonColor: '#7C3AED' });
            }
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', confirmButtonColor: '#7C3AED' });
        }
    }

    async function rateAppointment(appointmentId) {
        const { value: rating } = await Swal.fire({
            title: 'ให้คะแนนการบริการ',
            html: `
                <div class="flex justify-center gap-2 text-3xl my-4" id="starRating">
                    <i class="far fa-star cursor-pointer text-gray-300 hover:text-yellow-400" data-rating="1"></i>
                    <i class="far fa-star cursor-pointer text-gray-300 hover:text-yellow-400" data-rating="2"></i>
                    <i class="far fa-star cursor-pointer text-gray-300 hover:text-yellow-400" data-rating="3"></i>
                    <i class="far fa-star cursor-pointer text-gray-300 hover:text-yellow-400" data-rating="4"></i>
                    <i class="far fa-star cursor-pointer text-gray-300 hover:text-yellow-400" data-rating="5"></i>
                </div>
                <input type="hidden" id="ratingValue" value="0">
            `,
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            confirmButtonText: 'ส่งคะแนน',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                const stars = document.querySelectorAll('#starRating i');
                stars.forEach(star => {
                    star.addEventListener('click', () => {
                        const rating = parseInt(star.dataset.rating);
                        document.getElementById('ratingValue').value = rating;
                        stars.forEach((s, i) => {
                            if (i < rating) {
                                s.classList.remove('far', 'text-gray-300');
                                s.classList.add('fas', 'text-yellow-400');
                            } else {
                                s.classList.add('far', 'text-gray-300');
                                s.classList.remove('fas', 'text-yellow-400');
                            }
                        });
                    });
                });
            },
            preConfirm: () => {
                const rating = document.getElementById('ratingValue').value;
                if (rating < 1) {
                    Swal.showValidationMessage('กรุณาเลือกคะแนน');
                    return false;
                }
                return rating;
            }
        });
        
        if (!rating) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/appointments.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'rate',
                    appointment_id: appointmentId,
                    line_user_id: userId,
                    rating: parseInt(rating)
                })
            });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'ขอบคุณสำหรับคะแนน!', confirmButtonColor: '#7C3AED' });
                loadAppointments();
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

    function goToBook() {
        window.location.href = `${BASE_URL}/liff-appointment.php?account=${ACCOUNT_ID}`;
    }

    function showError(msg) {
        const skeleton = document.getElementById('loadingSkeleton');
        if (skeleton) skeleton.classList.add('hidden');
        
        const container = document.getElementById('appointmentsList');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-circle text-5xl text-red-300 mb-4"></i>
                    <p class="text-gray-500">${msg}</p>
                </div>
            `;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    </script>
    
    <?php include 'includes/liff-nav.php'; ?>
</body>
</html>
