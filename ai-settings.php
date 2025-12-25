<?php
/**
 * AI Settings - ตั้งค่า Gemini API
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ตั้งค่า AI (Gemini)';

// Ensure ai_settings table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS ai_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_setting (line_account_id, setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

$currentBotId = $_SESSION['current_bot_id'] ?? null;

// Get current settings
function getAISetting($db, $key, $botId = null) {
    try {
        if ($botId) {
            $stmt = $db->prepare("SELECT setting_value FROM ai_settings WHERE setting_key = ? AND line_account_id = ?");
            $stmt->execute([$key, $botId]);
        } else {
            $stmt = $db->prepare("SELECT setting_value FROM ai_settings WHERE setting_key = ? AND line_account_id IS NULL");
            $stmt->execute([$key]);
        }
        return $stmt->fetchColumn() ?: '';
    } catch (Exception $e) {
        return '';
    }
}

function saveAISetting($db, $key, $value, $botId = null) {
    try {
        // Always include line_account_id in INSERT for proper UNIQUE KEY matching
        $stmt = $db->prepare("INSERT INTO ai_settings (line_account_id, setting_key, setting_value) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$botId, $key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("saveAISetting error: " . $e->getMessage());
        return false;
    }
}

$success = null;
$error = null;
$testResult = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        $apiKey = trim($_POST['gemini_api_key'] ?? '');
        $defaultModel = $_POST['default_model'] ?? 'gemini-2.0-flash';
        $enabled = isset($_POST['ai_enabled']) ? '1' : '0';
        $systemPrompt = trim($_POST['system_prompt'] ?? '');
        
        // Save settings
        saveAISetting($db, 'gemini_api_key', $apiKey, $currentBotId);
        saveAISetting($db, 'default_model', $defaultModel, $currentBotId);
        saveAISetting($db, 'ai_enabled', $enabled, $currentBotId);
        saveAISetting($db, 'system_prompt', $systemPrompt, $currentBotId);
        
        $success = 'บันทึกการตั้งค่าสำเร็จ!';
    }
    
    if ($action === 'test_api') {
        $apiKey = trim($_POST['test_api_key'] ?? '');
        
        if (empty($apiKey)) {
            $error = 'กรุณากรอก API Key';
        } else {
            // Test API
            try {
                require_once __DIR__ . '/classes/GeminiAI.php';
                $gemini = new GeminiAI($apiKey);
                $result = $gemini->generateBroadcast('ทดสอบการเชื่อมต่อ', 'friendly', 'ทั่วไป');
                
                if ($result['success']) {
                    $testResult = [
                        'success' => true,
                        'message' => '✅ เชื่อมต่อสำเร็จ!',
                        'model' => $result['model'] ?? 'unknown',
                        'sample' => $result['text']
                    ];
                } else {
                    $testResult = [
                        'success' => false,
                        'message' => '❌ ' . ($result['error'] ?? 'ไม่สามารถเชื่อมต่อได้')
                    ];
                }
            } catch (Exception $e) {
                $testResult = [
                    'success' => false,
                    'message' => '❌ Error: ' . $e->getMessage()
                ];
            }
        }
    }
}

// Get current values
$geminiApiKey = getAISetting($db, 'gemini_api_key', $currentBotId);
$defaultModel = getAISetting($db, 'default_model', $currentBotId) ?: 'gemini-2.0-flash';
$aiEnabled = getAISetting($db, 'ai_enabled', $currentBotId) !== '0';
$systemPrompt = getAISetting($db, 'system_prompt', $currentBotId);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Settings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- API Key Settings -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold flex items-center">
                        <i class="fas fa-key text-yellow-500 mr-2"></i>
                        ตั้งค่า Gemini API
                    </h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="ai_enabled" value="1" <?= $aiEnabled ? 'checked' : '' ?> class="w-5 h-5 text-green-500 rounded mr-3">
                            <div>
                                <span class="font-medium">เปิดใช้งาน AI</span>
                                <p class="text-sm text-gray-500">เปิดใช้งานฟีเจอร์ AI ในระบบ</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Gemini API Key *</label>
                        <div class="relative">
                            <input type="password" name="gemini_api_key" id="apiKeyInput" value="<?= htmlspecialchars($geminiApiKey) ?>" 
                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none pr-12"
                                   placeholder="AIzaSy...">
                            <button type="button" onclick="toggleApiKey()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-500 hover:underline">
                                <i class="fas fa-external-link-alt mr-1"></i>รับ API Key ฟรีที่ Google AI Studio
                            </a>
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Model เริ่มต้น</label>
                        <select name="default_model" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
                            <option value="gemini-2.0-flash" <?= $defaultModel === 'gemini-2.0-flash' ? 'selected' : '' ?>>Gemini 2.0 Flash (แนะนำ - เร็ว)</option>
                            <option value="gemini-1.5-flash" <?= $defaultModel === 'gemini-1.5-flash' ? 'selected' : '' ?>>Gemini 1.5 Flash (เร็ว)</option>
                            <option value="gemini-1.5-pro" <?= $defaultModel === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro (ฉลาดกว่า)</option>
                            <option value="gemini-pro" <?= $defaultModel === 'gemini-pro' ? 'selected' : '' ?>>Gemini Pro (เสถียร)</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2">System Prompt (ตัวตนของ AI)</label>
                        <textarea name="system_prompt" rows="4" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none"
                                  placeholder="เช่น: คุณเป็นผู้ช่วยขายของร้าน ABC ตอบคำถามลูกค้าอย่างเป็นมิตร..."><?= htmlspecialchars($systemPrompt) ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">กำหนดบุคลิกและวิธีการตอบของ AI</p>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                </form>
            </div>
            
            <!-- Test API -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold flex items-center">
                        <i class="fas fa-flask text-purple-500 mr-2"></i>
                        ทดสอบ API
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="test_api">
                        <div class="flex gap-2">
                            <input type="text" name="test_api_key" value="<?= htmlspecialchars($geminiApiKey) ?>" 
                                   class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                   placeholder="วาง API Key ที่ต้องการทดสอบ">
                            <button type="submit" class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                                <i class="fas fa-play mr-2"></i>ทดสอบ
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($testResult): ?>
                    <div class="p-4 rounded-lg <?= $testResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                        <p class="font-medium <?= $testResult['success'] ? 'text-green-700' : 'text-red-700' ?>">
                            <?= htmlspecialchars($testResult['message']) ?>
                        </p>
                        <?php if ($testResult['success'] && isset($testResult['model'])): ?>
                        <p class="text-sm text-green-600 mt-1">Model: <?= htmlspecialchars($testResult['model']) ?></p>
                        <?php endif; ?>
                        <?php if ($testResult['success'] && isset($testResult['sample'])): ?>
                        <div class="mt-3 p-3 bg-white rounded border">
                            <p class="text-xs text-gray-500 mb-1">ตัวอย่างข้อความที่สร้าง:</p>
                            <p class="text-sm"><?= nl2br(htmlspecialchars($testResult['sample'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Guide -->
            <div class="bg-white rounded-xl shadow p-6">
                <h4 class="font-semibold mb-4 flex items-center">
                    <i class="fas fa-book text-blue-500 mr-2"></i>
                    วิธีรับ API Key
                </h4>
                <ol class="text-sm text-gray-600 space-y-3">
                    <li class="flex">
                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">1</span>
                        <span>ไปที่ <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-500 hover:underline">Google AI Studio</a></span>
                    </li>
                    <li class="flex">
                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">2</span>
                        <span>Login ด้วย Google Account</span>
                    </li>
                    <li class="flex">
                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">3</span>
                        <span>คลิก "Create API Key"</span>
                    </li>
                    <li class="flex">
                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">4</span>
                        <span>Copy API Key มาวางที่นี่</span>
                    </li>
                </ol>
                
                <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                    <p class="text-xs text-yellow-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Gemini API ฟรี! มี quota 60 requests/นาที
                    </p>
                </div>
            </div>
            
            <!-- Features -->
            <div class="bg-white rounded-xl shadow p-6">
                <h4 class="font-semibold mb-4 flex items-center">
                    <i class="fas fa-magic text-pink-500 mr-2"></i>
                    ฟีเจอร์ AI
                </h4>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        สร้างข้อความ Broadcast
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        ตอบแชทอัตโนมัติ
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        สร้างคำอธิบายสินค้า
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        แนะนำสินค้าให้ลูกค้า
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        วิเคราะห์ความต้องการลูกค้า
                    </li>
                </ul>
            </div>
            
            <!-- Status -->
            <div class="bg-white rounded-xl shadow p-6">
                <h4 class="font-semibold mb-4 flex items-center">
                    <i class="fas fa-info-circle text-gray-500 mr-2"></i>
                    สถานะ
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">API Key:</span>
                        <span class="<?= $geminiApiKey ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $geminiApiKey ? '✅ ตั้งค่าแล้ว' : '❌ ยังไม่ได้ตั้งค่า' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">สถานะ:</span>
                        <span class="<?= $aiEnabled ? 'text-green-600' : 'text-gray-600' ?>">
                            <?= $aiEnabled ? '🟢 เปิดใช้งาน' : '⚪ ปิดใช้งาน' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Model:</span>
                        <span class="text-blue-600"><?= htmlspecialchars($defaultModel) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleApiKey() {
    const input = document.getElementById('apiKeyInput');
    const icon = document.getElementById('eyeIcon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
