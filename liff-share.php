<?php
/**
 * LIFF Share Page - แชร์ Flex Message ให้เพื่อน
 * ใช้ LINE Share Target Picker
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get LIFF ID from config or database
$liffId = defined('LIFF_SHARE_ID') ? LIFF_SHARE_ID : '';

// Get share data from query params
$ruleId = $_GET['rule'] ?? '';
$flexData = $_GET['flex'] ?? '';

// If rule ID provided, get flex content from database
$flexContent = null;
$altText = 'ข้อความจาก LINE Bot';

if ($ruleId) {
    try {
        $stmt = $db->prepare("SELECT reply_content, alt_text, keyword FROM auto_replies WHERE id = ? AND reply_type = 'flex'");
        $stmt->execute([$ruleId]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rule) {
            $flexContent = $rule['reply_content'];
            $altText = $rule['alt_text'] ?: $rule['keyword'];
        }
    } catch (Exception $e) {}
} elseif ($flexData) {
    // Decode flex data from URL
    $flexContent = base64_decode($flexData);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แชร์ให้เพื่อน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <style>
        body { font-family: 'Noto Sans ', sans-serif; }
        .loading { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 text-center">
        <div id="loading" class="py-8">
            <div class="text-5xl mb-4 loading">📤</div>
            <p class="text-gray-600">กำลังเตรียมแชร์...</p>
        </div>
        
        <div id="content" class="hidden">
            <div class="text-5xl mb-4">📤</div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">แชร์ให้เพื่อน</h1>
            <p class="text-gray-600 mb-6">เลือกเพื่อนหรือกลุ่มที่ต้องการส่งต่อ</p>
            
            <button onclick="shareMessage()" class="w-full bg-green-500 text-white py-3 px-6 rounded-xl font-medium hover:bg-green-600 transition mb-3">
                <i class="fas fa-share-alt mr-2"></i>เลือกเพื่อนเพื่อแชร์
            </button>
            
            <button onclick="closeWindow()" class="w-full bg-gray-200 text-gray-700 py-3 px-6 rounded-xl font-medium hover:bg-gray-300 transition">
                ปิด
            </button>
        </div>
        
        <div id="success" class="hidden py-8">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-xl font-bold text-green-600 mb-2">แชร์สำเร็จ!</h2>
            <p class="text-gray-600 mb-6">ส่งข้อความให้เพื่อนเรียบร้อยแล้ว</p>
            <button onclick="closeWindow()" class="bg-green-500 text-white py-2 px-6 rounded-xl font-medium hover:bg-green-600 transition">
                ปิด
            </button>
        </div>
        
        <div id="error" class="hidden py-8">
            <div class="text-5xl mb-4">❌</div>
            <h2 class="text-xl font-bold text-red-600 mb-2">เกิดข้อผิดพลาด</h2>
            <p id="errorMessage" class="text-gray-600 mb-6"></p>
            <button onclick="closeWindow()" class="bg-gray-500 text-white py-2 px-6 rounded-xl font-medium hover:bg-gray-600 transition">
                ปิด
            </button>
        </div>
        
        <div id="noLiff" class="hidden py-8">
            <div class="text-5xl mb-4">⚠️</div>
            <h2 class="text-xl font-bold text-yellow-600 mb-2">ต้องเปิดใน LINE</h2>
            <p class="text-gray-600 mb-6">กรุณาเปิดลิงก์นี้ในแอป LINE เพื่อแชร์ให้เพื่อน</p>
        </div>
    </div>

    <script>
        const LIFF_ID = '<?= htmlspecialchars($liffId) ?>';
        const FLEX_CONTENT = <?= $flexContent ? $flexContent : 'null' ?>;
        const ALT_TEXT = '<?= htmlspecialchars($altText) ?>';
        
        async function initLiff() {
            if (!LIFF_ID) {
                showError('ยังไม่ได้ตั้งค่า LIFF ID');
                return;
            }
            
            try {
                await liff.init({ liffId: LIFF_ID });
                
                if (!liff.isInClient()) {
                    // Not in LINE app
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('noLiff').classList.remove('hidden');
                    return;
                }
                
                if (!liff.isLoggedIn()) {
                    liff.login();
                    return;
                }
                
                // Check if Share Target Picker is available
                if (!liff.isApiAvailable('shareTargetPicker')) {
                    showError('Share Target Picker ไม่พร้อมใช้งาน');
                    return;
                }
                
                // Show content
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('content').classList.remove('hidden');
                
            } catch (error) {
                showError(error.message);
            }
        }
        
        async function shareMessage() {
            if (!FLEX_CONTENT) {
                showError('ไม่พบข้อมูลที่จะแชร์');
                return;
            }
            
            try {
                const message = {
                    type: 'flex',
                    altText: ALT_TEXT,
                    contents: FLEX_CONTENT
                };
                
                const result = await liff.shareTargetPicker([message]);
                
                if (result) {
                    // Success
                    document.getElementById('content').classList.add('hidden');
                    document.getElementById('success').classList.remove('hidden');
                }
            } catch (error) {
                showError(error.message);
            }
        }
        
        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.add('hidden');
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('error').classList.remove('hidden');
        }
        
        function closeWindow() {
            if (liff.isInClient()) {
                liff.closeWindow();
            } else {
                window.close();
            }
        }
        
        // Initialize
        initLiff();
    </script>
</body>
</html>
