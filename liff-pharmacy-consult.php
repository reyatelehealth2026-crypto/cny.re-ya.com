<?php
/**
 * LIFF Pharmacy Consultation - หน้าปรึกษาเภสัชกรออนไลน์
 * Version 2.0 - Professional Telecare Interface
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// Get LIFF ID
$liffId = '';
try {
    $stmt = $db->query("SELECT liff_id FROM line_accounts WHERE id = 1 LIMIT 1");
    $result = $stmt->fetch();
    $liffId = $result['liff_id'] ?? '';
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ปรึกษาเภสัชกร - บริการให้คำปรึกษาด้านยาและสุขภาพ</title>
    <meta name="description" content="ปรึกษาเภสัชกรออนไลน์ฟรี บริการให้คำปรึกษาด้านยา อาการเจ็บป่วย การใช้ยาอย่างถูกต้อง โดยเภสัชกรผู้เชี่ยวชาญ">
    <meta name="keywords" content="ปรึกษาเภสัชกร, ถามยา, ปรึกษายาออนไลน์, เภสัชกรออนไลน์, คำแนะนำการใช้ยา">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="ปรึกษาเภสัชกรออนไลน์">
    <meta property="og:description" content="บริการให้คำปรึกษาด้านยาและสุขภาพ โดยเภสัชกรผู้เชี่ยวชาญ">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="th_TH">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .symptom-btn { transition: all 0.2s; }
        .symptom-btn:active { transform: scale(0.95); }
        .symptom-btn.selected { background: #10B981; color: white; border-color: #10B981; }
        .chat-bubble { max-width: 85%; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .typing-indicator span { animation: typing 1.4s infinite; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%,60%,100% { opacity: 0.3; } 30% { opacity: 1; } }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white p-4 sticky top-0 z-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-md text-lg"></i>
                </div>
                <div>
                    <h1 class="font-bold">ปรึกษาเภสัชกร</h1>
                    <p class="text-xs text-white/80" id="statusText">🟢 พร้อมให้บริการ</p>
                </div>
            </div>
            <button onclick="showMenu()" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>

    <!-- Chat Container -->
    <div id="chatContainer" class="p-4 pb-32 space-y-4">
        <!-- Welcome Message -->
        <div class="chat-bubble bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm">
            <p class="text-gray-800">สวัสดีค่ะ 👋</p>
            <p class="text-gray-600 text-sm mt-2">ยินดีให้บริการค่ะ ดิฉันพร้อมช่วยประเมินอาการและแนะนำยาเบื้องต้น</p>
            <p class="text-gray-500 text-xs mt-3">เลือกอาการด้านล่าง หรือพิมพ์บอกอาการได้เลยค่ะ</p>
        </div>

        <!-- Quick Symptoms -->
        <div class="grid grid-cols-2 gap-2" id="quickSymptoms">
            <button onclick="selectSymptom('ปวดหัว')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                🤕 ปวดหัว
            </button>
            <button onclick="selectSymptom('ไข้ หวัด')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                🤒 ไข้ / หวัด
            </button>
            <button onclick="selectSymptom('ไอ เจ็บคอ')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                😷 ไอ / เจ็บคอ
            </button>
            <button onclick="selectSymptom('ท้องเสีย')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                🤢 ท้องเสีย
            </button>
            <button onclick="selectSymptom('ปวดกล้ามเนื้อ')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                💪 ปวดกล้ามเนื้อ
            </button>
            <button onclick="selectSymptom('แพ้ คัน')" class="symptom-btn p-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium">
                🤧 แพ้ / คัน
            </button>
        </div>
    </div>

    <!-- Input Area -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 safe-area-bottom">
        <div class="flex gap-2">
            <button onclick="showAttachMenu()" class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                <i class="fas fa-plus"></i>
            </button>
            <input type="text" id="messageInput" placeholder="พิมพ์อาการหรือคำถาม..." 
                   class="flex-1 px-4 py-3 bg-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-emerald-500"
                   onkeypress="if(event.key==='Enter')sendMessage()">
            <button onclick="sendMessage()" class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center text-white">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- Attach Menu Modal -->
    <div id="attachMenu" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" onclick="hideAttachMenu()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 safe-area-bottom">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-6"></div>
            <div class="grid grid-cols-4 gap-4">
                <button onclick="takePhoto()" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-camera text-blue-500 text-xl"></i>
                    </div>
                    <span class="text-xs text-gray-600">ถ่ายรูป</span>
                </button>
                <button onclick="selectImage()" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-image text-purple-500 text-xl"></i>
                    </div>
                    <span class="text-xs text-gray-600">รูปภาพ</span>
                </button>
                <button onclick="startVideoCall()" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-video text-green-500 text-xl"></i>
                    </div>
                    <span class="text-xs text-gray-600">Video Call</span>
                </button>
                <button onclick="showHistory()" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-history text-orange-500 text-xl"></i>
                    </div>
                    <span class="text-xs text-gray-600">ประวัติ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Modal -->
    <div id="menuModal" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" onclick="hideMenu()"></div>
        <div class="absolute top-16 right-4 bg-white rounded-2xl shadow-xl overflow-hidden w-56">
            <button onclick="showProfile()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3">
                <i class="fas fa-user text-gray-400"></i>
                <span>ข้อมูลสุขภาพ</span>
            </button>
            <button onclick="showHistory()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3">
                <i class="fas fa-clipboard-list text-gray-400"></i>
                <span>ประวัติการปรึกษา</span>
            </button>
            <button onclick="showMedications()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3">
                <i class="fas fa-pills text-gray-400"></i>
                <span>ยาที่ทานอยู่</span>
            </button>
            <div class="border-t"></div>
            <button onclick="closeLiff()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3 text-red-500">
                <i class="fas fa-times"></i>
                <span>ปิด</span>
            </button>
        </div>
    </div>

<script>
const LIFF_ID = '<?= $liffId ?>';
let userId = null;
let currentState = 'greeting';
let triageData = {};

// Initialize LIFF
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await liff.init({ liffId: LIFF_ID });
        
        if (liff.isLoggedIn()) {
            const profile = await liff.getProfile();
            userId = profile.userId;
        }
    } catch (e) {
        console.error('LIFF init error:', e);
    }
});

// Select symptom
function selectSymptom(symptom) {
    const btn = event.target.closest('.symptom-btn');
    btn.classList.toggle('selected');
    
    if (!triageData.symptoms) triageData.symptoms = [];
    
    if (btn.classList.contains('selected')) {
        triageData.symptoms.push(symptom);
    } else {
        triageData.symptoms = triageData.symptoms.filter(s => s !== symptom);
    }
    
    // Auto send after selection
    if (triageData.symptoms.length > 0) {
        setTimeout(() => {
            sendSymptoms();
        }, 500);
    }
}

// Send symptoms
function sendSymptoms() {
    if (!triageData.symptoms || triageData.symptoms.length === 0) return;
    
    const symptoms = triageData.symptoms.join(', ');
    addUserMessage(symptoms);
    
    // Hide quick symptoms
    document.getElementById('quickSymptoms').classList.add('hidden');
    
    // Process with AI
    processMessage(symptoms);
}

// Send message
function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    addUserMessage(message);
    input.value = '';
    
    processMessage(message);
}

// Add user message to chat
function addUserMessage(text) {
    const container = document.getElementById('chatContainer');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble bg-emerald-500 text-white rounded-2xl rounded-tr-sm p-4 ml-auto';
    bubble.textContent = text;
    container.appendChild(bubble);
    scrollToBottom();
}

// Add AI message to chat
function addAIMessage(text, quickReplies = []) {
    const container = document.getElementById('chatContainer');
    
    // Remove typing indicator
    const typing = container.querySelector('.typing-indicator');
    if (typing) typing.remove();
    
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm';
    bubble.innerHTML = text.replace(/\n/g, '<br>');
    container.appendChild(bubble);
    
    // Add quick replies
    if (quickReplies.length > 0) {
        const qrContainer = document.createElement('div');
        qrContainer.className = 'flex flex-wrap gap-2 mt-2';
        
        quickReplies.forEach(qr => {
            const btn = document.createElement('button');
            btn.className = 'px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium';
            btn.textContent = qr.label;
            btn.onclick = () => {
                addUserMessage(qr.text);
                processMessage(qr.text);
            };
            qrContainer.appendChild(btn);
        });
        
        container.appendChild(qrContainer);
    }
    
    scrollToBottom();
}

// Show typing indicator
function showTyping() {
    const container = document.getElementById('chatContainer');
    const typing = document.createElement('div');
    typing.className = 'chat-bubble bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm typing-indicator';
    typing.innerHTML = '<span class="inline-block w-2 h-2 bg-gray-400 rounded-full mx-0.5"></span><span class="inline-block w-2 h-2 bg-gray-400 rounded-full mx-0.5"></span><span class="inline-block w-2 h-2 bg-gray-400 rounded-full mx-0.5"></span>';
    container.appendChild(typing);
    scrollToBottom();
}

// Process message with AI
async function processMessage(message) {
    showTyping();
    
    try {
        const response = await fetch('api/pharmacy-ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                user_id: userId,
                state: currentState,
                triage_data: triageData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentState = data.state || currentState;
            triageData = data.data || triageData;
            
            addAIMessage(data.response, data.quick_replies || []);
            
            // Handle special states
            if (data.is_critical) {
                showEmergencyAlert();
            }
        } else {
            addAIMessage('ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    } catch (e) {
        console.error('Process error:', e);
        addAIMessage('ขออภัยค่ะ ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่');
    }
}

function scrollToBottom() {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

function showAttachMenu() { document.getElementById('attachMenu').classList.remove('hidden'); }
function hideAttachMenu() { document.getElementById('attachMenu').classList.add('hidden'); }
function showMenu() { document.getElementById('menuModal').classList.remove('hidden'); }
function hideMenu() { document.getElementById('menuModal').classList.add('hidden'); }

function takePhoto() {
    hideAttachMenu();
    if (liff.isApiAvailable('scanCode')) {
        // Use camera
    }
}

function selectImage() {
    hideAttachMenu();
    // Open image picker
}

function startVideoCall() {
    hideAttachMenu();
    window.location.href = 'liff-video-call-pro.php';
}

function showHistory() {
    hideAttachMenu();
    hideMenu();
    // Show history
}

function showProfile() {
    hideMenu();
    // Show profile
}

function showMedications() {
    hideMenu();
    // Show medications
}

function showEmergencyAlert() {
    const alert = document.createElement('div');
    alert.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    alert.innerHTML = `
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="relative bg-white rounded-2xl p-6 max-w-sm w-full text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl pulse"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">พบอาการที่ต้องระวัง</h3>
            <p class="text-gray-600 mb-6">กรุณาติดต่อแพทย์หรือเภสัชกรโดยด่วน</p>
            <div class="space-y-2">
                <a href="tel:1669" class="block w-full py-3 bg-red-500 text-white rounded-xl font-semibold">
                    <i class="fas fa-phone mr-2"></i>โทร 1669
                </a>
                <button onclick="this.closest('.fixed').remove()" class="block w-full py-3 bg-gray-100 text-gray-700 rounded-xl">
                    ปิด
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(alert);
}

function closeLiff() {
    if (liff.isInClient()) {
        liff.closeWindow();
    } else {
        window.close();
    }
}
</script>
</body>
</html>
