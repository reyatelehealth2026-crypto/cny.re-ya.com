<!-- Gemini AI Helper Modal -->
<!-- แปะไฟล์นี้ไว้ใน footer หรือหน้าที่ต้องการใช้งาน -->

<div id="aiHelperModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                Gemini AI Assistant
            </h3>
            <button onclick="closeAIModal()" class="text-white hover:text-gray-200 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-4">
                <button onclick="switchTab('broadcast')" id="tab-broadcast" class="flex-1 py-2 px-4 text-blue-600 border-b-2 border-blue-600 font-medium focus:outline-none">📝 คิดแคปชั่น</button>
                <button onclick="switchTab('image')" id="tab-image" class="flex-1 py-2 px-4 text-gray-500 font-medium focus:outline-none hover:text-blue-500">🎨 ไอเดียรูปภาพ</button>
            </div>

            <!-- Broadcast Form -->
            <div id="panel-broadcast" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ต้องการขายอะไร / โปรโมชั่นอะไร?</label>
                    <input type="text" id="ai-topic" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border" placeholder="เช่น เสื้อยืดลด 50% ต้อนรับสงกรานต์">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">น้ำเสียง</label>
                        <select id="ai-tone" class="w-full border-gray-300 rounded-lg shadow-sm p-2 border">
                            <option value="friendly">เป็นกันเอง/สนุกสนาน</option>
                            <option value="professional">ทางการ/น่าเชื่อถือ</option>
                            <option value="urgent">เร่งด่วน (Flash Sale)</option>
                            <option value="luxury">หรูหรา/พรีเมียม</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ลูกค้าเป้าหมาย</label>
                        <select id="ai-target" class="w-full border-gray-300 rounded-lg shadow-sm p-2 border">
                            <option value="general">ลูกค้าทั่วไป</option>
                            <option value="new_customer">ลูกค้าใหม่</option>
                            <option value="vip">ลูกค้าประจำ/VIP</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Image Form (Hidden by default) -->
            <div id="panel-image" class="hidden space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดสินค้าที่ต้องการรูป</label>
                    <textarea id="ai-img-desc" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border" placeholder="เช่น รองเท้าผ้าใบสีขาว วางบนพื้นหญ้า แสงแดดธรรมชาติ"></textarea>
                    <p class="text-xs text-gray-500 mt-1">*AI จะช่วยคิด Prompt ภาษาอังกฤษสำหรับนำไปสร้างรูป</p>
                </div>
            </div>

            <!-- Result Area -->
            <div id="ai-result-area" class="hidden mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ผลลัพธ์จาก Gemini:</label>
                <div class="relative">
                    <textarea id="ai-result-text" rows="4" class="w-full bg-gray-50 border-gray-300 rounded-lg shadow-sm p-3 text-sm" readonly></textarea>
                    <button onclick="copyToClipboard()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 bg-white rounded p-1 shadow-sm border">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    </button>
                </div>
                <div class="mt-2 text-right">
                    <button onclick="useResult()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">✨ นำไปใช้ทันที</button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
            <button onclick="closeAIModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">ยกเลิก</button>
            <button onclick="generateAI()" id="btn-generate" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm flex items-center gap-2 transition-colors">
                <span>สร้างด้วย AI</span>
                <svg id="loading-icon" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
        </div>
    </div>
</div>

<script>
let currentMode = 'broadcast';

function openAIModal() {
    document.getElementById('aiHelperModal').classList.remove('hidden');
    document.getElementById('aiHelperModal').classList.add('flex');
}

function closeAIModal() {
    document.getElementById('aiHelperModal').classList.add('hidden');
    document.getElementById('aiHelperModal').classList.remove('flex');
    document.getElementById('ai-result-area').classList.add('hidden');
}

function switchTab(mode) {
    currentMode = mode;
    const tabBroad = document.getElementById('tab-broadcast');
    const tabImg = document.getElementById('tab-image');
    const panBroad = document.getElementById('panel-broadcast');
    const panImg = document.getElementById('panel-image');

    if (mode === 'broadcast') {
        tabBroad.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        tabBroad.classList.remove('text-gray-500');
        tabImg.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        tabImg.classList.add('text-gray-500');
        panBroad.classList.remove('hidden');
        panImg.classList.add('hidden');
    } else {
        tabImg.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        tabImg.classList.remove('text-gray-500');
        tabBroad.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        tabBroad.classList.add('text-gray-500');
        panImg.classList.remove('hidden');
        panBroad.classList.add('hidden');
    }
}

async function generateAI() {
    const btn = document.getElementById('btn-generate');
    const loader = document.getElementById('loading-icon');
    const resultArea = document.getElementById('ai-result-area');
    const resultText = document.getElementById('ai-result-text');

    // Prepare data
    let payload = {};
    if (currentMode === 'broadcast') {
        payload = {
            action: 'generate_broadcast',
            topic: document.getElementById('ai-topic').value,
            tone: document.getElementById('ai-tone').value,
            target: document.getElementById('ai-target').value
        };
    } else {
        payload = {
            action: 'generate_image_prompt',
            description: document.getElementById('ai-img-desc').value
        };
    }

    // UI Loading state
    btn.disabled = true;
    loader.classList.remove('hidden');
    resultArea.classList.add('hidden');

    try {
        // ใช้ path ที่ถูกต้องตาม location ปัจจุบัน
        const basePath = window.location.pathname.includes('/user/') ? '../api/ai_handler.php' : 'api/ai_handler.php';
        const response = await fetch(basePath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            resultText.value = data.text;
            resultArea.classList.remove('hidden');
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Connection Error');
    } finally {
        btn.disabled = false;
        loader.classList.add('hidden');
    }
}

function copyToClipboard() {
    const copyText = document.getElementById("ai-result-text");
    copyText.select();
    document.execCommand("copy");
    alert("คัดลอกแล้ว!");
}

function useResult() {
    const content = document.getElementById("ai-result-text").value;
    // หา Textarea หลักของหน้าเว็บ
    const targetInput = document.querySelector('textarea[name="content"], textarea#contentText, textarea[name="message"], textarea.main-editor');
    
    if (targetInput) {
        targetInput.value = content;
        closeAIModal();
    } else {
        alert("ไม่พบช่องข้อความหลัก กรุณากดปุ่มคัดลอกแทน");
    }
}
</script>