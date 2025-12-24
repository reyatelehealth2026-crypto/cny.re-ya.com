<?php
/**
 * QR Code Generator - สร้าง QR Code สำหรับเพิ่มเพื่อน
 */
require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'QR Code Generator';

// LINE OA Basic ID (extract from config or set manually)
$lineBasicId = '@your-line-id'; // ต้องตั้งค่าเอง

require_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-center">สร้าง QR Code เพิ่มเพื่อน LINE OA</h3>
        
        <div class="mb-6">
            <label class="block text-sm font-medium mb-1">LINE Basic ID</label>
            <div class="flex space-x-2">
                <input type="text" id="lineId" value="<?= htmlspecialchars($lineBasicId) ?>" class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="@your-line-id">
                <button onclick="generateQR()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">สร้าง QR</button>
            </div>
            <p class="text-xs text-gray-500 mt-1">ใส่ Basic ID ของ LINE OA (เช่น @example)</p>
        </div>
        
        <div class="text-center">
            <div id="qrContainer" class="inline-block p-6 bg-white border-4 border-green-500 rounded-2xl">
                <img id="qrImage" src="" alt="QR Code" class="w-64 h-64 hidden">
                <div id="qrPlaceholder" class="w-64 h-64 flex items-center justify-center bg-gray-100 rounded-lg">
                    <span class="text-gray-400">กดปุ่ม "สร้าง QR" เพื่อสร้าง QR Code</span>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-center space-x-4">
            <button onclick="downloadQR()" id="downloadBtn" disabled class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-download mr-2"></i>ดาวน์โหลด
            </button>
            <button onclick="copyLink()" class="px-6 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-link mr-2"></i>คัดลอกลิงก์
            </button>
        </div>
        
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium mb-2">ลิงก์เพิ่มเพื่อน:</p>
            <input type="text" id="friendLink" readonly class="w-full px-4 py-2 bg-white border rounded-lg text-sm" value="">
        </div>
    </div>
    
    <div class="mt-6 bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">วิธีใช้งาน</h3>
        <div class="space-y-3 text-sm text-gray-600">
            <div class="flex items-start">
                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0">1</span>
                <p>ใส่ Basic ID ของ LINE Official Account (ดูได้จาก LINE Official Account Manager)</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0">2</span>
                <p>กดปุ่ม "สร้าง QR" เพื่อสร้าง QR Code</p>
            </div>
            <div class="flex items-start">
                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0">3</span>
                <p>ดาวน์โหลด QR Code หรือคัดลอกลิงก์ไปใช้งาน</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentLineId = '';

function generateQR() {
    const lineId = document.getElementById('lineId').value.trim();
    if (!lineId) {
        alert('กรุณาใส่ LINE Basic ID');
        return;
    }
    
    currentLineId = lineId.startsWith('@') ? lineId.substring(1) : lineId;
    const friendUrl = `https://line.me/R/ti/p/@${currentLineId}`;
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=${encodeURIComponent(friendUrl)}`;
    
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('qrImage').classList.remove('hidden');
    document.getElementById('qrPlaceholder').classList.add('hidden');
    document.getElementById('friendLink').value = friendUrl;
    document.getElementById('downloadBtn').disabled = false;
}

function downloadQR() {
    const img = document.getElementById('qrImage');
    const link = document.createElement('a');
    link.href = img.src;
    link.download = `line-qr-${currentLineId}.png`;
    link.click();
}

function copyLink() {
    const link = document.getElementById('friendLink').value;
    if (link) {
        navigator.clipboard.writeText(link).then(() => showToast('คัดลอกลิงก์แล้ว!'));
    }
}

// Auto generate if ID is set
if (document.getElementById('lineId').value && document.getElementById('lineId').value !== '@your-line-id') {
    generateQR();
}
</script>

<?php require_once 'includes/footer.php'; ?>
