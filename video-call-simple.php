<?php
/**
 * Video Call Simple - หน้าแอดมินรับสายแบบง่าย
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📞 Video Call - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        .pulse-ring { animation: pulse-ring 1.5s infinite; }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-white">
    <div class="container mx-auto p-6 max-w-4xl">
        <h1 class="text-3xl font-bold mb-6 flex items-center gap-3">
            📞 Video Call Center
            <span id="statusBadge" class="text-sm bg-green-500 px-3 py-1 rounded-full">Online</span>
        </h1>
        
        <!-- Incoming Calls -->
        <div class="bg-gray-800 rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="relative">
                    📞
                    <span id="callIndicator" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full hidden"></span>
                </span>
                สายเรียกเข้า
                <span id="callCount" class="bg-gray-700 px-2 py-0.5 rounded-full text-sm">0</span>
            </h2>
            
            <div id="callsList" class="space-y-3">
                <div class="text-gray-400 text-center py-8">
                    <div class="text-4xl mb-2">📭</div>
                    <p>รอสายเรียกเข้า...</p>
                </div>
            </div>
        </div>
        
        <!-- Debug Info -->
        <div class="bg-gray-800 rounded-2xl p-6">
            <h2 class="text-xl font-bold mb-4">🔧 Debug</h2>
            <div id="debugLog" class="bg-black rounded-lg p-4 font-mono text-xs text-green-400 h-48 overflow-y-auto">
                <div>Starting...</div>
            </div>
            <div class="mt-4 flex gap-2 flex-wrap">
                <button onclick="checkCalls()" class="px-4 py-2 bg-blue-500 rounded-lg hover:bg-blue-600">
                    🔄 Check Now
                </button>
                <button onclick="testAPI()" class="px-4 py-2 bg-purple-500 rounded-lg hover:bg-purple-600">
                    🧪 Create Test Call
                </button>
                <button onclick="debugAPI()" class="px-4 py-2 bg-yellow-500 rounded-lg hover:bg-yellow-600 text-black">
                    🔍 Debug DB
                </button>
                <button onclick="clearLog()" class="px-4 py-2 bg-gray-500 rounded-lg hover:bg-gray-600">
                    🗑️ Clear Log
                </button>
            </div>
        </div>
    </div>

<script>
const API_URL = '<?= BASE_URL ?>api/video-call.php';
let pollInterval = null;

function log(msg) {
    const debugLog = document.getElementById('debugLog');
    const time = new Date().toLocaleTimeString();
    debugLog.innerHTML += `<div>[${time}] ${msg}</div>`;
    debugLog.scrollTop = debugLog.scrollHeight;
}

async function checkCalls() {
    log('Checking for calls...');
    
    try {
        // ใช้ relative URL แทน absolute URL
        const url = 'api/video-call.php?action=check_calls&account_id=1';
        log('Fetching: ' + url);
        
        const response = await fetch(url);
        log('HTTP Status: ' + response.status);
        
        const text = await response.text();
        log('Raw Response: ' + text.substring(0, 300));
        
        // Check if response is valid JSON
        if (!text.trim()) {
            log('ERROR: Empty response!');
            return;
        }
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            log('JSON Parse Error: ' + parseErr.message);
            log('Response was: ' + text.substring(0, 100));
            return;
        }
        
        if (data.success) {
            log('✅ Found ' + data.calls.length + ' calls');
            if (data.calls.length > 0) {
                log('First call: ID=' + data.calls[0].id + ', Status=' + data.calls[0].status);
            }
            updateCallsList(data.calls);
        } else {
            log('❌ Error: ' + (data.error || 'Unknown'));
        }
    } catch (err) {
        log('❌ Fetch Error: ' + err.message);
        console.error('Full error:', err);
    }
}

function updateCallsList(calls) {
    const container = document.getElementById('callsList');
    const countEl = document.getElementById('callCount');
    const indicator = document.getElementById('callIndicator');
    
    countEl.textContent = calls.length;
    
    if (calls.length > 0) {
        indicator.classList.remove('hidden');
        indicator.classList.add('pulse-ring');
        
        container.innerHTML = calls.map(call => `
            <div class="bg-gray-700 rounded-xl p-4 flex items-center gap-4 ${call.status === 'ringing' ? 'ring-2 ring-green-500 animate-pulse' : ''}">
                <img src="${call.picture_url || 'https://via.placeholder.com/50'}" class="w-12 h-12 rounded-full">
                <div class="flex-1">
                    <div class="font-bold">${call.display_name || 'ลูกค้า'}</div>
                    <div class="text-sm text-gray-400">
                        ${call.status === 'ringing' ? '📞 กำลังโทร...' : call.status}
                        | ID: ${call.id}
                    </div>
                </div>
                <button onclick="answerCall(${call.id})" class="px-6 py-3 bg-green-500 rounded-xl font-bold hover:bg-green-600">
                    📞 รับสาย
                </button>
                <button onclick="rejectCall(${call.id})" class="px-4 py-3 bg-red-500 rounded-xl hover:bg-red-600">
                    ❌
                </button>
            </div>
        `).join('');
        
        // Play sound
        playSound();
    } else {
        indicator.classList.add('hidden');
        container.innerHTML = `
            <div class="text-gray-400 text-center py-8">
                <div class="text-4xl mb-2">📭</div>
                <p>รอสายเรียกเข้า...</p>
            </div>
        `;
    }
}

async function answerCall(callId) {
    log('Answering call: ' + callId);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'answer', call_id: callId })
        });
        const data = await response.json();
        log('Answer response: ' + JSON.stringify(data));
        
        if (data.success) {
            alert('รับสายแล้ว! (ในระบบจริงจะเปิด Video Call)');
            checkCalls();
        }
    } catch (err) {
        log('Error: ' + err.message);
    }
}

async function rejectCall(callId) {
    log('Rejecting call: ' + callId);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject', call_id: callId })
        });
        const data = await response.json();
        log('Reject response: ' + JSON.stringify(data));
        checkCalls();
    } catch (err) {
        log('Error: ' + err.message);
    }
}

async function testAPI() {
    log('Testing API...');
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create',
                user_id: 'test',
                display_name: 'API Test',
                account_id: 1
            })
        });
        const data = await response.json();
        log('Create response: ' + JSON.stringify(data));
        
        if (data.success) {
            log('Test call created! Checking...');
            setTimeout(checkCalls, 1000);
        }
    } catch (err) {
        log('Error: ' + err.message);
    }
}

function playSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        gain.gain.value = 0.3;
        osc.start();
        setTimeout(() => osc.stop(), 200);
    } catch (e) {}
}

async function debugAPI() {
    log('🔍 Fetching debug info...');
    try {
        const response = await fetch('api/video-call.php?action=debug');
        const text = await response.text();
        log('Debug Response:');
        
        try {
            const data = JSON.parse(text);
            log('Total calls in DB: ' + data.total_calls);
            log('By status: ' + JSON.stringify(data.by_status));
            if (data.recent_calls && data.recent_calls.length > 0) {
                data.recent_calls.forEach(call => {
                    log(`  - ID:${call.id} Status:${call.status} Name:${call.display_name}`);
                });
            }
        } catch (e) {
            log('Raw: ' + text.substring(0, 500));
        }
    } catch (err) {
        log('Error: ' + err.message);
    }
}

function clearLog() {
    document.getElementById('debugLog').innerHTML = '<div>Log cleared</div>';
}

// Start polling
log('🚀 Starting polling...');
log('API URL: api/video-call.php');
debugAPI(); // First check DB status
setTimeout(checkCalls, 1000);
pollInterval = setInterval(checkCalls, 5000); // Poll every 5 seconds
</script>
</body>
</html>
