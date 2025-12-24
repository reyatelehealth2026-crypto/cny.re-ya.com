<?php
/**
 * Video Call - ระบบ Video Call สำหรับแอดมิน
 * รับสายจากลูกค้าผ่าน LIFF
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Video Call';

require_once 'includes/header.php';

// Get pending calls - รวมทั้งที่มีและไม่มี line_account_id
$stmt = $db->prepare("SELECT vc.*, 
                      COALESCE(vc.display_name, u.display_name, 'ลูกค้า') as caller_name,
                      COALESCE(vc.picture_url, u.picture_url) as caller_picture
                      FROM video_calls vc 
                      LEFT JOIN users u ON vc.user_id = u.id 
                      WHERE (vc.line_account_id = ? OR vc.line_account_id IS NULL) 
                      AND vc.status IN ('pending', 'ringing', 'active')
                      ORDER BY vc.created_at DESC");
$stmt->execute([$currentBotId ?? 1]);
$pendingCalls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get call history
$stmt = $db->prepare("SELECT vc.*, 
                      COALESCE(vc.display_name, u.display_name, 'ลูกค้า') as caller_name,
                      COALESCE(vc.picture_url, u.picture_url) as caller_picture
                      FROM video_calls vc 
                      LEFT JOIN users u ON vc.user_id = u.id 
                      WHERE vc.line_account_id = ? OR vc.line_account_id IS NULL
                      ORDER BY vc.created_at DESC LIMIT 50");
$stmt->execute([$currentBotId ?? 1]);
$callHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Call Controls -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Video Area -->
        <div class="bg-black rounded-2xl overflow-hidden aspect-video relative" id="videoContainer">
            <!-- Remote Video (Customer) -->
            <video id="remoteVideo" class="w-full h-full object-cover" autoplay playsinline></video>
            
            <!-- Local Video (Admin) -->
            <div class="absolute bottom-4 right-4 w-48 aspect-video bg-gray-800 rounded-lg overflow-hidden shadow-lg">
                <video id="localVideo" class="w-full h-full object-cover" autoplay playsinline muted></video>
            </div>
            
            <!-- No Call Placeholder -->
            <div id="noCallPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-white bg-gradient-to-br from-gray-800 to-gray-900">
                <div class="text-6xl mb-4">📹</div>
                <h2 class="text-2xl font-bold mb-2">Video Call Center</h2>
                <p class="text-gray-400">รอรับสายจากลูกค้า...</p>
            </div>
            
            <!-- Incoming Call Overlay -->
            <div id="incomingCallOverlay" class="absolute inset-0 bg-black/80 hidden flex-col items-center justify-center text-white">
                <div class="animate-pulse mb-6">
                    <img id="callerAvatar" src="" class="w-24 h-24 rounded-full border-4 border-green-500">
                </div>
                <h3 class="text-xl font-bold mb-2" id="callerName">ลูกค้า</h3>
                <p class="text-gray-400 mb-6 animate-pulse">📞 กำลังโทรเข้า...</p>
                <div class="flex gap-4">
                    <button onclick="answerCall()" class="px-8 py-3 bg-green-500 rounded-full text-lg font-medium hover:bg-green-600 flex items-center gap-2">
                        <span class="text-2xl">📞</span> รับสาย
                    </button>
                    <button onclick="rejectCall()" class="px-8 py-3 bg-red-500 rounded-full text-lg font-medium hover:bg-red-600 flex items-center gap-2">
                        <span class="text-2xl">📵</span> ปฏิเสธ
                    </button>
                </div>
            </div>
            
            <!-- Call Timer -->
            <div id="callTimer" class="absolute top-4 left-4 bg-black/50 px-4 py-2 rounded-full text-white hidden">
                <span class="animate-pulse mr-2">🔴</span>
                <span id="timerDisplay">00:00</span>
            </div>
        </div>
        
        <!-- Call Controls -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-center gap-4">
                <button id="btnMute" onclick="toggleMute()" class="p-4 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="ปิด/เปิดไมค์">
                    <span class="text-2xl" id="muteIcon">🎤</span>
                </button>
                <button id="btnVideo" onclick="toggleVideo()" class="p-4 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="ปิด/เปิดกล้อง">
                    <span class="text-2xl" id="videoIcon">📹</span>
                </button>
                <button id="btnEndCall" onclick="endCall()" class="p-4 bg-red-500 text-white rounded-full hover:bg-red-600 transition hidden" title="วางสาย">
                    <span class="text-2xl">📵</span>
                </button>
                <button id="btnScreenShare" onclick="toggleScreenShare()" class="p-4 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="แชร์หน้าจอ">
                    <span class="text-2xl">🖥️</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Right: Call Queue & History -->
    <div class="space-y-6">
        <!-- Pending Calls -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white">
                <h3 class="font-bold flex items-center gap-2">
                    <span class="animate-pulse">📞</span> สายเรียกเข้า
                    <span class="bg-white/20 px-2 py-0.5 rounded-full text-sm" id="pendingCount"><?= count($pendingCalls) ?></span>
                </h3>
            </div>
            <div class="divide-y max-h-[300px] overflow-y-auto" id="pendingCallsList">
                <?php if (empty($pendingCalls)): ?>
                <div class="p-6 text-center text-gray-400">
                    <div class="text-3xl mb-2">📭</div>
                    <p>ไม่มีสายเรียกเข้า</p>
                </div>
                <?php else: ?>
                <?php foreach ($pendingCalls as $call): ?>
                <div class="p-4 hover:bg-gray-50 flex items-center gap-3" id="call-<?= $call['id'] ?>">
                    <img src="<?= $call['picture_url'] ?: 'https://via.placeholder.com/40' ?>" class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <div class="font-medium"><?= htmlspecialchars($call['display_name'] ?: 'ลูกค้า') ?></div>
                        <div class="text-xs text-gray-500"><?= $call['created_at'] ?></div>
                    </div>
                    <button onclick="pickupCall('<?= $call['room_id'] ?>')" class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                        รับสาย
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Settings -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-bold mb-4">⚙️ ตั้งค่า</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">กล้อง</label>
                    <select id="cameraSelect" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="changeCamera()">
                        <option>กำลังโหลด...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ไมโครโฟน</label>
                    <select id="micSelect" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="changeMic()">
                        <option>กำลังโหลด...</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="autoAnswer" class="rounded">
                    <span>รับสายอัตโนมัติ</span>
                </label>
            </div>
        </div>
        
        <!-- Call History -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="font-bold">📋 ประวัติการโทร</h3>
            </div>
            <div class="divide-y max-h-[250px] overflow-y-auto">
                <?php if (empty($callHistory)): ?>
                <div class="p-6 text-center text-gray-400">
                    <p>ยังไม่มีประวัติ</p>
                </div>
                <?php else: ?>
                <?php foreach ($callHistory as $call): ?>
                <div class="p-3 hover:bg-gray-50 flex items-center gap-3">
                    <div class="text-xl">
                        <?php 
                        echo match($call['status']) {
                            'completed' => '✅',
                            'missed' => '❌',
                            'rejected' => '🚫',
                            default => '📞'
                        };
                        ?>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium"><?= htmlspecialchars($call['display_name'] ?: 'ลูกค้า') ?></div>
                        <div class="text-xs text-gray-500">
                            <?= $call['duration'] ? gmdate('i:s', $call['duration']) : '-' ?> | 
                            <?= date('d/m H:i', strtotime($call['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- LIFF Link for Customers -->
<div class="mt-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-6 text-white">
    <h3 class="font-bold text-lg mb-2">📱 ลิงก์สำหรับลูกค้า</h3>
    <p class="text-sm opacity-80 mb-4">แชร์ลิงก์นี้ให้ลูกค้าเพื่อเริ่ม Video Call</p>
    <div class="flex gap-2">
        <input type="text" id="customerLink" value="<?= BASE_URL ?>liff-video-call.php" readonly 
               class="flex-1 px-4 py-2 rounded-lg bg-white/20 text-white placeholder-white/50">
        <button onclick="copyCustomerLink()" class="px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-gray-100">
            📋 คัดลอก
        </button>
    </div>
</div>

<script>
// WebRTC Configuration
const config = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

let localStream = null;
let remoteStream = null;
let peerConnection = null;
let currentCallId = null;
let callTimer = null;
let callSeconds = 0;
let isMuted = false;
let isVideoOff = false;

// Initialize
document.addEventListener('DOMContentLoaded', async function() {
    await initMedia();
    await loadDevices();
    startPolling();
});

// Initialize Media
async function initMedia() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        document.getElementById('localVideo').srcObject = localStream;
    } catch (err) {
        console.error('Error accessing media:', err);
        alert('ไม่สามารถเข้าถึงกล้องหรือไมค์ได้');
    }
}

// Load Available Devices
async function loadDevices() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        
        const cameraSelect = document.getElementById('cameraSelect');
        const micSelect = document.getElementById('micSelect');
        
        cameraSelect.innerHTML = '';
        micSelect.innerHTML = '';
        
        devices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `${device.kind} ${device.deviceId.slice(0, 5)}`;
            
            if (device.kind === 'videoinput') {
                cameraSelect.appendChild(option);
            } else if (device.kind === 'audioinput') {
                micSelect.appendChild(option);
            }
        });
    } catch (err) {
        console.error('Error loading devices:', err);
    }
}

// Poll for incoming calls
let lastCallCount = 0;
let pollingInterval = null;

function startPolling() {
    // Initial check
    checkForCalls();
    
    // Poll every 2 seconds
    pollingInterval = setInterval(checkForCalls, 2000);
}

async function checkForCalls() {
    try {
        const response = await fetch('api/video-call.php?action=check_calls&account_id=<?= $currentBotId ?? 1 ?>');
        const text = await response.text();
        console.log('Poll response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', text);
            return;
        }
        
        if (data.success && data.calls) {
            console.log('Found', data.calls.length, 'calls');
            updatePendingCalls(data.calls);
            
            // Show incoming call overlay for first ringing call
            const ringingCall = data.calls.find(c => c.status === 'ringing');
            if (ringingCall && !currentCallId) {
                showIncomingCall(ringingCall);
            }
            
            // Play sound if new call
            if (data.calls.length > lastCallCount) {
                playRingtone();
            }
            lastCallCount = data.calls.length;
        } else {
            console.log('API response:', data);
        }
    } catch (err) {
        console.error('Polling error:', err);
    }
}

function updatePendingCalls(calls) {
    document.getElementById('pendingCount').textContent = calls.length;
    
    // Update pending calls list
    const list = document.getElementById('pendingCallsList');
    if (calls.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-gray-400">
            <div class="text-3xl mb-2">📭</div>
            <p>ไม่มีสายเรียกเข้า</p>
        </div>`;
    } else {
        list.innerHTML = calls.map(call => `
            <div class="p-4 hover:bg-gray-50 flex items-center gap-3 ${call.status === 'ringing' ? 'bg-green-50 animate-pulse' : ''}" id="call-${call.id}">
                <img src="${call.picture_url || 'https://via.placeholder.com/40'}" class="w-10 h-10 rounded-full">
                <div class="flex-1">
                    <div class="font-medium">${call.display_name || 'ลูกค้า'}</div>
                    <div class="text-xs text-gray-500">${call.status === 'ringing' ? '📞 กำลังโทร...' : call.created_at}</div>
                </div>
                <button onclick="pickupCall('${call.id}')" class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                    รับสาย
                </button>
            </div>
        `).join('');
    }
}

function showIncomingCall(call) {
    document.getElementById('callerName').textContent = call.display_name || 'ลูกค้า';
    document.getElementById('callerAvatar').src = call.picture_url || 'https://via.placeholder.com/100';
    document.getElementById('incomingCallOverlay').classList.remove('hidden');
    document.getElementById('incomingCallOverlay').classList.add('flex');
    
    currentCallId = call.id;
    
    // Play ringtone
    playRingtone();
    
    // Auto answer if enabled
    if (document.getElementById('autoAnswer').checked) {
        setTimeout(() => answerCall(), 2000);
    }
}

function playRingtone() {
    // Simple beep sound
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 440;
    oscillator.type = 'sine';
    gainNode.gain.value = 0.3;
    
    oscillator.start();
    setTimeout(() => oscillator.stop(), 500);
}

// Answer Call
async function answerCall() {
    if (!currentCallId) return;
    
    document.getElementById('incomingCallOverlay').classList.add('hidden');
    document.getElementById('noCallPlaceholder').classList.add('hidden');
    document.getElementById('callTimer').classList.remove('hidden');
    document.getElementById('btnEndCall').classList.remove('hidden');
    
    // Start call timer
    startCallTimer();
    
    // Initialize WebRTC
    await initPeerConnection();
    
    // Update call status
    await fetch('api/video-call.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'answer',
            call_id: currentCallId
        })
    });
}

// Reject Call
async function rejectCall() {
    if (!currentCallId) return;
    
    document.getElementById('incomingCallOverlay').classList.add('hidden');
    
    await fetch('api/video-call.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'reject',
            call_id: currentCallId
        })
    });
    
    currentCallId = null;
}

// Pickup specific call
async function pickupCall(roomId) {
    currentCallId = roomId;
    await answerCall();
}

// Initialize Peer Connection
async function initPeerConnection() {
    peerConnection = new RTCPeerConnection(config);
    
    // Add local stream
    localStream.getTracks().forEach(track => {
        peerConnection.addTrack(track, localStream);
    });
    
    // Handle remote stream
    peerConnection.ontrack = (event) => {
        document.getElementById('remoteVideo').srcObject = event.streams[0];
    };
    
    // Handle ICE candidates
    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            sendSignal('ice-candidate', event.candidate);
        }
    };
}

// Send signaling data
async function sendSignal(type, data) {
    await fetch('api/video-call.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'signal',
            call_id: currentCallId,
            signal_type: type,
            signal_data: data
        })
    });
}

// End Call
async function endCall() {
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    stopCallTimer();
    
    document.getElementById('noCallPlaceholder').classList.remove('hidden');
    document.getElementById('callTimer').classList.add('hidden');
    document.getElementById('btnEndCall').classList.add('hidden');
    document.getElementById('remoteVideo').srcObject = null;
    
    if (currentCallId) {
        await fetch('api/video-call.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'end',
                call_id: currentCallId,
                duration: callSeconds
            })
        });
    }
    
    currentCallId = null;
    callSeconds = 0;
}

// Toggle Mute
function toggleMute() {
    isMuted = !isMuted;
    localStream.getAudioTracks().forEach(track => track.enabled = !isMuted);
    document.getElementById('muteIcon').textContent = isMuted ? '🔇' : '🎤';
    document.getElementById('btnMute').classList.toggle('bg-red-100', isMuted);
}

// Toggle Video
function toggleVideo() {
    isVideoOff = !isVideoOff;
    localStream.getVideoTracks().forEach(track => track.enabled = !isVideoOff);
    document.getElementById('videoIcon').textContent = isVideoOff ? '📷' : '📹';
    document.getElementById('btnVideo').classList.toggle('bg-red-100', isVideoOff);
}

// Toggle Screen Share
async function toggleScreenShare() {
    try {
        const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const screenTrack = screenStream.getVideoTracks()[0];
        
        if (peerConnection) {
            const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
            if (sender) {
                sender.replaceTrack(screenTrack);
            }
        }
        
        screenTrack.onended = () => {
            // Switch back to camera
            const cameraTrack = localStream.getVideoTracks()[0];
            if (peerConnection) {
                const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
                if (sender) {
                    sender.replaceTrack(cameraTrack);
                }
            }
        };
    } catch (err) {
        console.error('Screen share error:', err);
    }
}

// Call Timer
function startCallTimer() {
    callSeconds = 0;
    callTimer = setInterval(() => {
        callSeconds++;
        const mins = Math.floor(callSeconds / 60).toString().padStart(2, '0');
        const secs = (callSeconds % 60).toString().padStart(2, '0');
        document.getElementById('timerDisplay').textContent = `${mins}:${secs}`;
    }, 1000);
}

function stopCallTimer() {
    if (callTimer) {
        clearInterval(callTimer);
        callTimer = null;
    }
}

// Change Camera
async function changeCamera() {
    const deviceId = document.getElementById('cameraSelect').value;
    try {
        const newStream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: deviceId } },
            audio: true
        });
        
        const videoTrack = newStream.getVideoTracks()[0];
        const oldTrack = localStream.getVideoTracks()[0];
        
        localStream.removeTrack(oldTrack);
        localStream.addTrack(videoTrack);
        
        document.getElementById('localVideo').srcObject = localStream;
        
        if (peerConnection) {
            const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
            if (sender) sender.replaceTrack(videoTrack);
        }
    } catch (err) {
        console.error('Change camera error:', err);
    }
}

// Change Mic
async function changeMic() {
    const deviceId = document.getElementById('micSelect').value;
    try {
        const newStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: { deviceId: { exact: deviceId } }
        });
        
        const audioTrack = newStream.getAudioTracks()[0];
        const oldTrack = localStream.getAudioTracks()[0];
        
        localStream.removeTrack(oldTrack);
        localStream.addTrack(audioTrack);
        
        if (peerConnection) {
            const sender = peerConnection.getSenders().find(s => s.track.kind === 'audio');
            if (sender) sender.replaceTrack(audioTrack);
        }
    } catch (err) {
        console.error('Change mic error:', err);
    }
}

// Copy Customer Link
function copyCustomerLink() {
    const input = document.getElementById('customerLink');
    input.select();
    document.execCommand('copy');
    alert('คัดลอกลิงก์แล้ว!');
}
</script>

<?php require_once 'includes/footer.php'; ?>
