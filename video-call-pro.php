<?php
/**
 * Video Call Pro - ระบบ Video Call ประสิทธิภาพสูง
 * - Optimized WebRTC signaling
 * - Better UI/UX
 * - Network quality indicator
 * - Screen sharing
 */
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$accountId = $_GET['account_id'] ?? $currentBotId ?? 1;
$pageTitle = 'Video Call';
require_once 'includes/header.php';
?>

<style>
@keyframes ring { 0%,100%{transform:rotate(-15deg)} 50%{transform:rotate(15deg)} }
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.5} }
.ringing { animation: ring 0.3s ease-in-out infinite; }
.pulse-dot { animation: pulse-dot 1s ease-in-out infinite; }
.video-container { position:relative; background:#000; border-radius:1rem; overflow:hidden; }
.video-container video { width:100%; height:100%; object-fit:cover; }
.pip-video { position:absolute; bottom:1rem; right:1rem; width:180px; aspect-ratio:16/9; 
             border-radius:0.5rem; overflow:hidden; border:2px solid rgba(255,255,255,0.3);
             background:#1f2937; transition: all 0.3s; cursor:move; }
.pip-video:hover { border-color: #10b981; }
.control-btn { padding:1rem; border-radius:9999px; transition:all 0.2s; }
.control-btn:hover { transform:scale(1.1); }
.quality-good { color:#10b981; }
.quality-medium { color:#f59e0b; }
.quality-poor { color:#ef4444; }
</style>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
    <!-- Main Video Area -->
    <div class="xl:col-span-3 space-y-4">
        <!-- Video Container -->
        <div class="video-container aspect-video" id="videoContainer">
            <video id="remoteVideo" autoplay playsinline></video>
            <div class="pip-video" id="pipVideo">
                <video id="localVideo" autoplay playsinline muted></video>
            </div>

            <!-- Overlays -->
            <div id="waitingOverlay" class="absolute inset-0 bg-gradient-to-br from-gray-900 to-gray-800 flex flex-col items-center justify-center text-white">
                <div class="text-7xl mb-6">📹</div>
                <h2 class="text-2xl font-bold mb-2">Video Call Center</h2>
                <p class="text-gray-400 mb-4">พร้อมรับสายจากลูกค้า</p>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></span>
                    <span id="statusText">Online</span>
                </div>
            </div>
            
            <div id="incomingOverlay" class="absolute inset-0 bg-gradient-to-br from-green-900/95 to-emerald-900/95 hidden flex-col items-center justify-center text-white">
                <div class="text-6xl mb-4 ringing">📞</div>
                <img id="callerPic" src="" class="w-24 h-24 rounded-full mb-4 border-4 border-white shadow-xl">
                <h2 class="text-2xl font-bold mb-1" id="callerName">ลูกค้า</h2>
                <p class="text-green-300 mb-2 animate-pulse" id="callerStatus">กำลังโทรเข้า...</p>
                <!-- Appointment Info (if any) -->
                <div id="appointmentInfo" class="hidden bg-white/10 backdrop-blur rounded-xl px-6 py-3 mb-6 text-center">
                    <p class="text-xs text-green-200 mb-1">📅 นัดหมาย</p>
                    <p class="font-bold" id="appointmentId">-</p>
                    <p class="text-sm text-green-200" id="appointmentTime">-</p>
                </div>
                <div class="flex gap-4">
                    <button onclick="answerCall()" class="px-10 py-4 bg-green-500 rounded-2xl text-xl font-bold hover:bg-green-400 shadow-lg transform hover:scale-105 transition flex items-center gap-3">
                        <span class="text-3xl">📞</span> รับสาย
                    </button>
                    <button onclick="rejectCall()" class="px-6 py-4 bg-red-500/80 rounded-2xl text-xl font-bold hover:bg-red-500 transition">
                        ❌
                    </button>
                </div>
            </div>
            
            <!-- Call Info Bar -->
            <div id="callInfoBar" class="absolute top-4 left-4 right-4 flex justify-between items-center hidden">
                <div class="bg-black/60 backdrop-blur px-4 py-2 rounded-full flex items-center gap-3">
                    <span class="w-3 h-3 bg-red-500 rounded-full pulse-dot"></span>
                    <span id="timerDisplay" class="font-mono text-white">00:00</span>
                </div>
                <div class="bg-black/60 backdrop-blur px-4 py-2 rounded-full flex items-center gap-2">
                    <span id="qualityIcon">📶</span>
                    <span id="qualityText" class="text-white text-sm">Good</span>
                </div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-center items-center gap-4">
                <button onclick="toggleMute()" id="btnMute" class="control-btn bg-gray-100 hover:bg-gray-200" title="ปิด/เปิดไมค์">
                    <span class="text-2xl" id="muteIcon">🎤</span>
                </button>
                <button onclick="toggleVideo()" id="btnVideo" class="control-btn bg-gray-100 hover:bg-gray-200" title="ปิด/เปิดกล้อง">
                    <span class="text-2xl" id="videoIcon">📹</span>
                </button>
                <button onclick="endCall()" id="btnEnd" class="control-btn bg-red-500 hover:bg-red-600 hidden" title="วางสาย">
                    <span class="text-2xl">📵</span>
                </button>
                <button onclick="toggleScreenShare()" id="btnScreen" class="control-btn bg-gray-100 hover:bg-gray-200 hidden" title="แชร์หน้าจอ">
                    <span class="text-2xl" id="screenIcon">🖥️</span>
                </button>
                <button onclick="toggleFullscreen()" class="control-btn bg-gray-100 hover:bg-gray-200" title="เต็มจอ">
                    <span class="text-2xl">⛶</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        <!-- Incoming Calls -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white">
                <h3 class="font-bold flex items-center gap-2">
                    📞 สายเรียกเข้า
                    <span id="pendingBadge" class="bg-white/20 px-2 py-0.5 rounded-full text-sm">0</span>
                </h3>
            </div>
            <div id="pendingList" class="divide-y max-h-64 overflow-y-auto">
                <div class="p-6 text-center text-gray-400">
                    <div class="text-4xl mb-2">📭</div>
                    <p>ไม่มีสายเรียกเข้า</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-bold mb-3">⚡ Quick Actions</h3>
            <div class="grid grid-cols-2 gap-2">
                <button onclick="sendQuickMessage('สวัสดีครับ')" class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm hover:bg-blue-100">
                    👋 ทักทาย
                </button>
                <button onclick="sendQuickMessage('รอสักครู่นะครับ')" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg text-sm hover:bg-yellow-100">
                    ⏳ รอสักครู่
                </button>
                <button onclick="takeSnapshot()" class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm hover:bg-purple-100">
                    📸 Screenshot
                </button>
                <button onclick="toggleRecording()" id="btnRecord" class="p-2 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">
                    ⏺️ บันทึก
                </button>
            </div>
        </div>
        
        <!-- Customer Link -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 text-white">
            <h3 class="font-bold mb-2">📱 ลิงก์สำหรับลูกค้า</h3>
            <?php $customerUrl = rtrim(BASE_URL, '/') . '/liff-video-call-pro.php'; ?>
            <input type="text" id="customerLink" value="<?= htmlspecialchars($customerUrl) ?>" readonly 
                   class="w-full px-3 py-2 bg-white/20 rounded-lg text-xs font-mono mb-2" onclick="this.select()">
            <div class="flex gap-2">
                <button onclick="copyLink()" class="flex-1 px-3 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 text-sm">
                    📋 คัดลอกลิงก์
                </button>
                <a href="<?= htmlspecialchars($customerUrl) ?>" target="_blank" class="px-3 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm">
                    🔗 เปิด
                </a>
            </div>
        </div>
        
        <!-- Settings -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-bold mb-3">⚙️ ตั้งค่า</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600">กล้อง</label>
                    <select id="cameraSelect" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">ไมโครโฟน</label>
                    <select id="micSelect" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="autoAnswer" class="rounded">
                    <span>รับสายอัตโนมัติ</span>
                </label>
            </div>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white">
                <h3 class="font-bold flex items-center gap-2">
                    📅 นัดหมายวันนี้
                    <span id="appointmentBadge" class="bg-white/20 px-2 py-0.5 rounded-full text-sm">0</span>
                </h3>
            </div>
            <div id="appointmentList" class="divide-y max-h-48 overflow-y-auto">
                <div class="p-4 text-center text-gray-400">
                    <div class="text-3xl mb-2">📭</div>
                    <p class="text-sm">ไม่มีนัดหมายวันนี้</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const API = 'api/video-call.php';
const ACCOUNT_ID = <?= $accountId ?>;

// WebRTC Config - Compatible
const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'turn:openrelay.metered.ca:80', username: 'openrelayproject', credential: 'openrelayproject' },
        { urls: 'turn:openrelay.metered.ca:443', username: 'openrelayproject', credential: 'openrelayproject' }
    ]
};

// State
let localStream = null, peerConnection = null, currentCall = null;
let callTimer = null, callSeconds = 0;
let isMuted = false, isVideoOff = false, isScreenSharing = false;
let pollInterval = null, signalPollInterval = null;
let mediaRecorder = null, recordedChunks = [];
let offerHandled = false; // Track if offer already handled (prevent duplicate answers)
let pendingIceCandidates = []; // Queue for ICE candidates before remote description is set

// Initialize
async function init() {
    console.log('🚀 Initializing Video Call Pro...');
    
    // Start polling FIRST - don't wait for media
    startPolling();
    console.log('✅ Polling started');
    
    // Load today's appointments
    loadTodayAppointments();
    
    // Then try to get media (non-blocking)
    try {
        await initMedia();
        console.log('✅ Media initialized');
    } catch (e) {
        console.warn('⚠️ Media init failed:', e);
    }
    
    try {
        await loadDevices();
        console.log('✅ Devices loaded');
    } catch (e) {
        console.warn('⚠️ Load devices failed:', e);
    }
    
    setupPIP();
    console.log('✅ Init complete');
}

// Load today's appointments
async function loadTodayAppointments() {
    try {
        const res = await fetch(`api/appointments.php?action=today_appointments&line_account_id=${ACCOUNT_ID}`);
        const data = await res.json();
        if (data.success && data.appointments) {
            updateAppointmentList(data.appointments);
        }
    } catch (e) {
        console.warn('Failed to load appointments:', e);
    }
}

function updateAppointmentList(appointments) {
    document.getElementById('appointmentBadge').textContent = appointments.length;
    const list = document.getElementById('appointmentList');
    
    if (!appointments.length) {
        list.innerHTML = '<div class="p-4 text-center text-gray-400"><div class="text-3xl mb-2">📭</div><p class="text-sm">ไม่มีนัดหมายวันนี้</p></div>';
        return;
    }
    
    list.innerHTML = appointments.map(apt => {
        const time = apt.appointment_time.substring(0, 5);
        const isNow = isAppointmentNow(apt.appointment_time);
        const isSoon = isAppointmentSoon(apt.appointment_time);
        
        return `
        <div class="p-3 hover:bg-gray-50 ${isNow ? 'bg-green-50 border-l-4 border-green-500' : isSoon ? 'bg-yellow-50 border-l-4 border-yellow-500' : ''}">
            <div class="flex items-center gap-3">
                <div class="text-center">
                    <div class="text-lg font-bold ${isNow ? 'text-green-600' : isSoon ? 'text-yellow-600' : 'text-gray-700'}">${time}</div>
                    <div class="text-xs text-gray-400">${apt.duration || 15} นาที</div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium truncate">${apt.user_name || 'ลูกค้า'}</div>
                    <div class="text-xs text-gray-500">#${apt.appointment_id}</div>
                    ${apt.symptoms ? `<div class="text-xs text-gray-400 truncate">💬 ${apt.symptoms}</div>` : ''}
                </div>
                <div class="text-right">
                    <span class="px-2 py-1 rounded-full text-xs ${getStatusClass(apt.status)}">${getStatusText(apt.status)}</span>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function isAppointmentNow(time) {
    const now = new Date();
    const [h, m] = time.split(':').map(Number);
    const aptTime = new Date();
    aptTime.setHours(h, m, 0, 0);
    const diff = (now - aptTime) / 60000; // minutes
    return diff >= -5 && diff <= 30; // 5 min before to 30 min after
}

function isAppointmentSoon(time) {
    const now = new Date();
    const [h, m] = time.split(':').map(Number);
    const aptTime = new Date();
    aptTime.setHours(h, m, 0, 0);
    const diff = (aptTime - now) / 60000; // minutes
    return diff > 0 && diff <= 30; // within 30 minutes
}

function getStatusClass(status) {
    const classes = {
        'confirmed': 'bg-blue-100 text-blue-700',
        'in_progress': 'bg-green-100 text-green-700',
        'completed': 'bg-gray-100 text-gray-600',
        'cancelled': 'bg-red-100 text-red-600'
    };
    return classes[status] || 'bg-gray-100 text-gray-600';
}

function getStatusText(status) {
    const texts = {
        'confirmed': 'รอพบ',
        'in_progress': 'กำลังพบ',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก'
    };
    return texts[status] || status;
}

async function initMedia() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
            audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
        });
        document.getElementById('localVideo').srcObject = localStream;
    } catch (err) {
        console.error('Media error:', err);
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
            isVideoOff = true;
            updateVideoButton();
        } catch (e) {
            alert('ไม่สามารถเข้าถึงกล้องหรือไมค์ได้');
        }
    }
}

async function loadDevices() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const camSelect = document.getElementById('cameraSelect');
    const micSelect = document.getElementById('micSelect');
    camSelect.innerHTML = ''; micSelect.innerHTML = '';
    
    devices.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.deviceId;
        opt.text = d.label || `${d.kind} ${d.deviceId.slice(0,5)}`;
        if (d.kind === 'videoinput') camSelect.appendChild(opt);
        else if (d.kind === 'audioinput') micSelect.appendChild(opt);
    });
}

// Polling
function startPolling() {
    checkCalls();
    pollInterval = setInterval(checkCalls, 2000);
}

async function checkCalls() {
    try {
        const url = `${API}?action=check_calls&account_id=${ACCOUNT_ID}`;
        console.log('📞 Checking calls:', url);
        const res = await fetch(url);
        const data = await res.json();
        console.log('📞 Calls response:', data);
        if (data.success) {
            updatePendingList(data.calls);
            // Debug: show all statuses
            data.calls.forEach(c => console.log('📊 Call', c.id, 'status:', c.status));
            
            const ringing = data.calls.find(c => c.status === 'ringing');
            console.log('🔍 Ringing call found:', ringing ? 'YES' : 'NO', 'currentCall:', currentCall ? currentCall.id : 'none');
            
            if (ringing && !currentCall) {
                console.log('🔔 Incoming call:', ringing);
                showIncomingCall(ringing);
            }
        }
    } catch (err) { console.error('❌ Poll error:', err); }
}

function updatePendingList(calls) {
    console.log('📋 Updating pending list:', calls.length, 'calls');
    document.getElementById('pendingBadge').textContent = calls.length;
    const list = document.getElementById('pendingList');
    
    if (!calls.length) {
        list.innerHTML = '<div class="p-6 text-center text-gray-400"><div class="text-4xl mb-2">📭</div><p>ไม่มีสายเรียกเข้า</p></div>';
        return;
    }
    
    console.log('📋 Calls to display:', calls);
    
    list.innerHTML = calls.map(c => `
        <div class="p-3 hover:bg-gray-50 flex items-center gap-3 ${c.status==='ringing'?'bg-green-50':''}">
            <img src="${c.picture_url||'https://ui-avatars.com/api/?name='+encodeURIComponent(c.display_name||'C')}" class="w-10 h-10 rounded-full">
            <div class="flex-1 min-w-0">
                <div class="font-medium truncate">${c.display_name||'ลูกค้า'}</div>
                <div class="text-xs text-gray-400">
                    ${c.status==='ringing'?'📞 กำลังโทร...':c.status}
                    ${c.phone ? ' • 📱'+c.phone : ''}
                </div>
                ${c.appointment_id ? `<span class="text-xs text-blue-600">📅 นัดหมาย #${c.appointment_id}</span>` : 
                  c.user_id ? '<span class="text-xs text-green-600">✓ ผู้ใช้ในระบบ</span>' : '<span class="text-xs text-gray-400">Guest</span>'}
            </div>
            <button onclick="pickupCall(${c.id})" class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm hover:bg-green-400">รับ</button>
        </div>
    `).join('');
}

function showIncomingCall(call) {
    currentCall = call;
    document.getElementById('callerName').textContent = call.display_name || 'ลูกค้า';
    document.getElementById('callerPic').src = call.picture_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(call.display_name || 'C');
    document.getElementById('incomingOverlay').classList.remove('hidden');
    document.getElementById('incomingOverlay').classList.add('flex');
    
    // Show appointment info if available
    const aptInfo = document.getElementById('appointmentInfo');
    if (call.appointment_id) {
        aptInfo.classList.remove('hidden');
        document.getElementById('appointmentId').textContent = '#' + call.appointment_id;
        document.getElementById('appointmentTime').textContent = call.appointment_time ? 
            `⏰ ${call.appointment_time.substring(0, 5)} น.` : '';
        document.getElementById('callerStatus').textContent = '📅 โทรตามนัดหมาย';
    } else {
        aptInfo.classList.add('hidden');
        document.getElementById('callerStatus').textContent = 'กำลังโทรเข้า...';
    }
    
    playRingtone();
    
    if (document.getElementById('autoAnswer').checked) setTimeout(answerCall, 1500);
}

function playRingtone() {
    try {
        const ctx = new AudioContext();
        [0, 200, 400].forEach(delay => {
            setTimeout(() => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = 440; gain.gain.value = 0.2;
                osc.start(); setTimeout(() => osc.stop(), 150);
            }, delay);
        });
    } catch(e) {}
}

// Call Management
async function answerCall() {
    if (!currentCall) return;
    
    document.getElementById('incomingOverlay').classList.add('hidden');
    document.getElementById('waitingOverlay').classList.add('hidden');
    document.getElementById('callInfoBar').classList.remove('hidden');
    document.getElementById('btnEnd').classList.remove('hidden');
    document.getElementById('btnScreen').classList.remove('hidden');
    
    // Try to get media, but continue even if it fails (receive-only mode)
    if (!localStream) {
        console.log('⚠️ No local stream, trying to get media...');
        try {
            await initMedia();
        } catch (e) {
            console.warn('⚠️ Could not get media, will use receive-only mode');
        }
    }
    
    // Continue even without local media - we can still receive video/audio
    if (!localStream) {
        console.warn('⚠️ No local media - using receive-only mode');
    }
    
    await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'answer', call_id: currentCall.id })
    });
    
    await setupPeerConnection();
    startTimer();
    startSignalPolling();
}

async function pickupCall(callId) {
    const res = await fetch(`${API}?action=check_calls&account_id=${ACCOUNT_ID}`);
    const data = await res.json();
    const call = data.calls.find(c => c.id == callId);
    if (call) { currentCall = call; await answerCall(); }
}

async function rejectCall() {
    if (!currentCall) return;
    await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reject', call_id: currentCall.id })
    });
    document.getElementById('incomingOverlay').classList.add('hidden');
    currentCall = null;
}

async function endCall() {
    if (signalPollInterval) { clearInterval(signalPollInterval); signalPollInterval = null; }
    if (peerConnection) { peerConnection.close(); peerConnection = null; }
    stopTimer();
    
    const duration = callSeconds;
    const callId = currentCall?.id;
    
    // Reset state immediately
    currentCall = null; callSeconds = 0;
    offerHandled = false;
    
    if (callId) {
        await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'end', call_id: callId, duration: duration })
        });
    }
    
    // Show ended message
    showCallEnded('สิ้นสุดการโทร');
}

// WebRTC
async function setupPeerConnection() {
    console.log('🔧 Setting up peer connection...');
    
    peerConnection = new RTCPeerConnection(rtcConfig);
    console.log('✅ RTCPeerConnection created');
    
    // Add tracks if we have local media
    if (localStream) {
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
            console.log('➕ Added track:', track.kind);
        });
    } else {
        // No local media - add transceiver for receive-only mode
        console.log('⚠️ No local stream - setting up receive-only mode');
        peerConnection.addTransceiver('video', { direction: 'recvonly' });
        peerConnection.addTransceiver('audio', { direction: 'recvonly' });
        console.log('✅ Added receive-only transceivers');
    }
    
    peerConnection.ontrack = (e) => {
        console.log('📹 Got remote track!');
        document.getElementById('remoteVideo').srcObject = e.streams[0];
    };
    
    peerConnection.onicecandidate = (e) => {
        console.log('🧊 Admin ICE candidate event:', e.candidate ? 'has candidate' : 'gathering complete');
        if (e.candidate) {
            console.log('🧊 Admin sending ICE candidate...');
            sendSignal('ice-candidate', e.candidate);
        }
    };
    
    peerConnection.onicegatheringstatechange = () => {
        console.log('🧊 ICE gathering state:', peerConnection.iceGatheringState);
    };
    
    peerConnection.oniceconnectionstatechange = () => {
        console.log('🔗 ICE connection state:', peerConnection.iceConnectionState);
    };
    
    peerConnection.onconnectionstatechange = () => {
        const state = peerConnection.connectionState;
        console.log('📡 Connection state:', state);
        updateConnectionStatus(state);
        if (state === 'failed') setTimeout(() => location.reload(), 2000);
    };
    
    // Monitor network quality
    setInterval(updateNetworkQuality, 3000);
}

async function sendSignal(type, data) {
    console.log('📤 Admin sending signal:', type);
    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'signal', call_id: currentCall.id, signal_type: type, signal_data: data, from: 'admin' })
    });
    const result = await res.json();
    console.log('📤 Signal sent result:', result);
}

function startSignalPolling() {
    console.log('🔄 Starting signal polling for admin...');
    signalPollInterval = setInterval(async () => {
        if (!currentCall) return;
        try {
            // Check if customer ended the call
            const statusRes = await fetch(`${API}?action=get_status&call_id=${currentCall.id}`);
            const statusData = await statusRes.json();
            if (statusData.status === 'completed' || statusData.status === 'ended') {
                console.log('📵 Customer ended the call');
                showCallEnded('ลูกค้าวางสายแล้ว');
                return;
            }
            
            const res = await fetch(`${API}?action=get_signals&call_id=${currentCall.id}&for=admin`);
            const data = await res.json();
            console.log('📡 Admin poll:', data.signals?.length || 0, 'signals');
            if (data.success && data.signals && data.signals.length > 0) {
                console.log('📥 Processing', data.signals.length, 'signals');
                for (const sig of data.signals) await handleSignal(sig);
            }
        } catch (err) { console.error('Signal error:', err); }
    }, 500);
}

// Show call ended notification
function showCallEnded(message) {
    // Stop polling and cleanup
    if (signalPollInterval) { clearInterval(signalPollInterval); signalPollInterval = null; }
    if (peerConnection) { peerConnection.close(); peerConnection = null; }
    stopTimer();
    
    // Show notification
    const overlay = document.getElementById('waitingOverlay');
    overlay.innerHTML = `
        <div class="text-7xl mb-6">📵</div>
        <h2 class="text-2xl font-bold mb-2 text-white">${message}</h2>
        <p class="text-gray-400 mb-4">ระยะเวลาสนทนา: ${document.getElementById('timerDisplay')?.textContent || '00:00'}</p>
        <button onclick="resetAfterEnd()" class="px-6 py-3 bg-green-500 text-white rounded-xl font-bold hover:bg-green-400">
            พร้อมรับสายใหม่
        </button>
    `;
    
    // Reset UI
    document.getElementById('waitingOverlay').classList.remove('hidden');
    document.getElementById('callInfoBar').classList.add('hidden');
    document.getElementById('btnEnd').classList.add('hidden');
    document.getElementById('btnScreen').classList.add('hidden');
    document.getElementById('remoteVideo').srcObject = null;
    
    currentCall = null; callSeconds = 0;
    offerHandled = false;
    pendingIceCandidates = [];
}

function resetAfterEnd() {
    // Reset overlay to original state
    document.getElementById('waitingOverlay').innerHTML = `
        <div class="text-7xl mb-6">📹</div>
        <h2 class="text-2xl font-bold mb-2">Video Call Center</h2>
        <p class="text-gray-400 mb-4">พร้อมรับสายจากลูกค้า</p>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></span>
            <span id="statusText">Online</span>
        </div>
    `;
    checkCalls();
}

async function handleSignal(signal) {
    console.log('📥 Admin received signal:', signal.signal_type);
    if (!peerConnection) {
        console.error('No peer connection!');
        return;
    }
    try {
        if (signal.signal_type === 'offer') {
            // Only handle offer once to prevent duplicate answers
            if (offerHandled) {
                console.log('⏭️ Offer already handled, skipping');
                return;
            }
            
            // Verify we have tracks before processing offer
            const senders = peerConnection.getSenders();
            console.log('📊 Current senders:', senders.length, senders.map(s => s.track?.kind));
            
            if (senders.length === 0 && localStream) {
                console.log('⚠️ No senders, adding tracks now...');
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                    console.log('➕ Added track:', track.kind);
                });
            }
            
            console.log('📥 Setting remote description (offer)...');
            await peerConnection.setRemoteDescription(new RTCSessionDescription(signal.signal_data));
            console.log('✅ Remote description set');
            
            // Process queued ICE candidates
            if (pendingIceCandidates.length > 0) {
                console.log('📥 Processing', pendingIceCandidates.length, 'queued ICE candidates');
                for (const candidate of pendingIceCandidates) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                }
                pendingIceCandidates = [];
            }
            
            console.log('📤 Creating answer...');
            const answer = await peerConnection.createAnswer();
            console.log('📊 Answer SDP preview:', answer.sdp.substring(0, 200));
            
            await peerConnection.setLocalDescription(answer);
            console.log('✅ Local description set');
            
            console.log('📤 Sending answer to customer...');
            await sendSignal('answer', answer);
            console.log('✅ Answer sent!');
            
            offerHandled = true; // Mark as handled
        } else if (signal.signal_type === 'ice-candidate' && signal.signal_data) {
            console.log('📥 Received ICE candidate from customer');
            // Queue if remote description not set yet
            if (!peerConnection.remoteDescription) {
                console.log('⏳ Queuing ICE candidate (no remote description yet)');
                pendingIceCandidates.push(signal.signal_data);
            } else {
                await peerConnection.addIceCandidate(new RTCIceCandidate(signal.signal_data));
                console.log('✅ ICE candidate added');
            }
        }
    } catch (err) { 
        console.error('❌ Handle signal error:', err); 
    }
}

function updateConnectionStatus(state) {
    const statusEl = document.getElementById('statusText');
    const states = {
        'connecting': '🔄 กำลังเชื่อมต่อ...',
        'connected': '🟢 เชื่อมต่อแล้ว',
        'disconnected': '🟡 ขาดการเชื่อมต่อ',
        'failed': '🔴 เชื่อมต่อล้มเหลว'
    };
    if (statusEl) statusEl.textContent = states[state] || state;
}

async function updateNetworkQuality() {
    if (!peerConnection) return;
    try {
        const stats = await peerConnection.getStats();
        let quality = 'good';
        stats.forEach(report => {
            if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                const rtt = report.currentRoundTripTime * 1000;
                if (rtt > 300) quality = 'poor';
                else if (rtt > 150) quality = 'medium';
            }
        });
        
        const icon = document.getElementById('qualityIcon');
        const text = document.getElementById('qualityText');
        const classes = { good: 'quality-good', medium: 'quality-medium', poor: 'quality-poor' };
        const labels = { good: 'ดี', medium: 'ปานกลาง', poor: 'ไม่ดี' };
        
        text.textContent = labels[quality];
        text.className = 'text-sm ' + classes[quality];
    } catch (e) {}
}

// Controls
function toggleMute() {
    isMuted = !isMuted;
    localStream?.getAudioTracks().forEach(t => t.enabled = !isMuted);
    document.getElementById('muteIcon').textContent = isMuted ? '🔇' : '🎤';
    document.getElementById('btnMute').classList.toggle('bg-red-100', isMuted);
}

function toggleVideo() {
    isVideoOff = !isVideoOff;
    localStream?.getVideoTracks().forEach(t => t.enabled = !isVideoOff);
    updateVideoButton();
}

function updateVideoButton() {
    document.getElementById('videoIcon').textContent = isVideoOff ? '📷' : '📹';
    document.getElementById('btnVideo').classList.toggle('bg-red-100', isVideoOff);
}

async function toggleScreenShare() {
    if (!isScreenSharing) {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            const screenTrack = screenStream.getVideoTracks()[0];
            const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'video');
            if (sender) await sender.replaceTrack(screenTrack);
            
            screenTrack.onended = () => stopScreenShare();
            isScreenSharing = true;
            document.getElementById('screenIcon').textContent = '🖥️✓';
            document.getElementById('btnScreen').classList.add('bg-blue-100');
        } catch (e) { console.error('Screen share error:', e); }
    } else {
        stopScreenShare();
    }
}

async function stopScreenShare() {
    const videoTrack = localStream?.getVideoTracks()[0];
    const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'video');
    if (sender && videoTrack) await sender.replaceTrack(videoTrack);
    isScreenSharing = false;
    document.getElementById('screenIcon').textContent = '🖥️';
    document.getElementById('btnScreen').classList.remove('bg-blue-100');
}

function toggleFullscreen() {
    const container = document.getElementById('videoContainer');
    if (!document.fullscreenElement) container.requestFullscreen();
    else document.exitFullscreen();
}

// Timer
function startTimer() {
    callSeconds = 0;
    callTimer = setInterval(() => {
        callSeconds++;
        const m = Math.floor(callSeconds/60).toString().padStart(2,'0');
        const s = (callSeconds%60).toString().padStart(2,'0');
        document.getElementById('timerDisplay').textContent = `${m}:${s}`;
    }, 1000);
}

function stopTimer() {
    if (callTimer) { clearInterval(callTimer); callTimer = null; }
}

// Utils
function copyLink() {
    navigator.clipboard.writeText(document.getElementById('customerLink').value);
    alert('คัดลอกลิงก์แล้ว!');
}

function setupPIP() {
    const pip = document.getElementById('pipVideo');
    let isDragging = false, startX, startY, startLeft, startTop;
    
    pip.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.clientX; startY = e.clientY;
        const rect = pip.getBoundingClientRect();
        startLeft = rect.left; startTop = rect.top;
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        pip.style.position = 'fixed';
        pip.style.left = (startLeft + e.clientX - startX) + 'px';
        pip.style.top = (startTop + e.clientY - startY) + 'px';
        pip.style.right = 'auto'; pip.style.bottom = 'auto';
    });
    
    document.addEventListener('mouseup', () => isDragging = false);
}

function takeSnapshot() {
    const video = document.getElementById('remoteVideo');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const link = document.createElement('a');
    link.download = `snapshot_${Date.now()}.png`;
    link.href = canvas.toDataURL();
    link.click();
}

function toggleRecording() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
        const stream = document.getElementById('remoteVideo').srcObject;
        if (!stream) return alert('ยังไม่มีวิดีโอให้บันทึก');
        
        mediaRecorder = new MediaRecorder(stream);
        recordedChunks = [];
        mediaRecorder.ondataavailable = (e) => recordedChunks.push(e.data);
        mediaRecorder.onstop = () => {
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            const link = document.createElement('a');
            link.download = `recording_${Date.now()}.webm`;
            link.href = URL.createObjectURL(blob);
            link.click();
        };
        mediaRecorder.start();
        document.getElementById('btnRecord').innerHTML = '⏹️ หยุด';
        document.getElementById('btnRecord').classList.add('bg-red-200');
    } else {
        mediaRecorder.stop();
        document.getElementById('btnRecord').innerHTML = '⏺️ บันทึก';
        document.getElementById('btnRecord').classList.remove('bg-red-200');
    }
}

function sendQuickMessage(msg) { alert('ส่งข้อความ: ' + msg); }

// Device change
document.getElementById('cameraSelect')?.addEventListener('change', async (e) => {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { deviceId: e.target.value } });
    const track = stream.getVideoTracks()[0];
    localStream?.getVideoTracks().forEach(t => { t.stop(); localStream.removeTrack(t); });
    localStream?.addTrack(track);
    document.getElementById('localVideo').srcObject = localStream;
    const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'video');
    if (sender) await sender.replaceTrack(track);
});

document.getElementById('micSelect')?.addEventListener('change', async (e) => {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: e.target.value } });
    const track = stream.getAudioTracks()[0];
    localStream?.getAudioTracks().forEach(t => { t.stop(); localStream.removeTrack(t); });
    localStream?.addTrack(track);
    const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'audio');
    if (sender) await sender.replaceTrack(track);
});

// Start
init();
</script>

<?php require_once 'includes/footer.php'; ?>
