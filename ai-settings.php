<?php
/**
 * AI Settings - ตั้งค่า Gemini API
 * ใช้โครงสร้างตาราง: id, line_account_id, is_enabled, system_prompt, model, max_tokens, temperature, gemini_api_key
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ตั้งค่า AI (Gemini)';

// Ensure gemini_api_key column exists
try {
    $stmt = $db->query("SHOW COLUMNS FROM ai_settings LIKE 'gemini_api_key'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE ai_settings ADD COLUMN gemini_api_key VARCHAR(255) DEFAULT NULL AFTER temperature");
    }
} catch (Exception $e) {}

$currentBotId = $_SESSION['current_bot_id'] ?? null;

// Get current settings
function getAISettings($db, $botId = null) {
    try {
        if ($botId) {
            $stmt = $db->prepare("SELECT * FROM ai_settings WHERE line_account_id = ?");
            $stmt->execute([$botId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM ai_settings WHERE line_account_id IS NULL LIMIT 1");
            $stmt->execute();
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function saveAISettings($db, $data, $botId = null) {
    try {
        // Check if record exists
        if ($botId) {
            $stmt = $db->prepare("SELECT id FROM ai_settings WHERE line_account_id = ?");
            $stmt->execute([$botId]);
        } else {
            $stmt = $db->prepare("SELECT id FROM ai_settings WHERE line_account_id IS NULL");
            $stmt->execute();
        }
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update
            $stmt = $db->prepare("UPDATE ai_settings SET 
                is_enabled = ?, system_prompt = ?, model = ?, gemini_api_key = ?
                WHERE id = ?");
            $stmt->execute([
                $data['is_enabled'] ?? 0,
                $data['system_prompt'] ?? '',
                $data['model'] ?? 'gemini-2.0-flash',
                $data['gemini_api_key'] ?? '',
                $existing['id']
            ]);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO ai_settings (line_account_id, is_enabled, system_prompt, model, gemini_api_key) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $botId,
                $data['is_enabled'] ?? 0,
                $data['system_prompt'] ?? '',
                $data['model'] ?? 'gemini-2.0-flash',
                $data['gemini_api_key'] ?? ''
            ]);
        }
        return true;
    } catch (Exception $e) {
        error_log("saveAISettings error: " . $e->getMessage());
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
        $data = [
            'gemini_api_key' => trim($_POST['gemini_api_key'] ?? ''),
            'model' => $_POST['default_model'] ?? 'gemini-2.0-flash',
            'is_enabled' => isset($_POST['ai_enabled']) ? 1 : 0,
            'system_prompt' => trim($_POST['system_prompt'] ?? '')
        ];
        
        if (saveAISettings($db, $data, $currentBotId)) {
            $success = 'บันทึกการตั้งค่าสำเร็จ!';
        } else {
            $error = 'เกิดข้อผิดพลาดในการบันทึก';
        }
    }
    
    if ($action === 'test_api') {
        $apiKey = trim($_POST['test_api_key'] ?? '');
        
        if (empty($apiKey)) {
            $error = 'กรุณากรอก API Key';
        } else {
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
$settings = getAISettings($db, $currentBotId);
$geminiApiKey = $settings['gemini_api_key'] ?? '';
$defaultModel = $settings['model'] ?? 'gemini-2.0-flash';
$aiEnabled = ($settings['is_enabled'] ?? 0) == 1;
$systemPrompt = $settings['system_prompt'] ?? '';

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
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold flex items-center">
                        <i class="fas fa-key text-yellow-500 mr-2"></i>ตั้งค่า Gemini API
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
                            <option value="gemini-2.0-flash" <?= $defaultModel === 'gemini-2.0-flash' ? 'selected' : '' ?>>Gemini 2.0 Flash (แนะนำ)</option>
                            <option value="gemini-1.5-flash" <?= $defaultModel === 'gemini-1.5-flash' ? 'selected' : '' ?>>Gemini 1.5 Flash</option>
                            <option value="gemini-1.5-pro" <?= $defaultModel === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro</option>
                            <option value="gemini-pro" <?= $defaultModel === 'gemini-pro' ? 'selected' : '' ?>>Gemini Pro</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2">System Prompt</label>
                        <textarea name="system_prompt" rows="4" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none"
                                  placeholder="กำหนดบุคลิกของ AI..."><?= htmlspecialchars($systemPrompt) ?></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                </form>
            </div>
            
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold flex items-center">
                        <i class="fas fa-flask text-purple-500 mr-2"></i>ทดสอบ API
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="test_api">
                        <div class="flex gap-2">
                            <input type="text" name="test_api_key" value="<?= htmlspecialchars($geminiApiKey) ?>" 
                                   class="flex-1 px-4 py-2 border rounded-lg" placeholder="วาง API Key">
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
                        <?php if ($testResult['success'] && isset($testResult['sample'])): ?>
                        <div class="mt-3 p-3 bg-white rounded border text-sm"><?= nl2br(htmlspecialchars($testResult['sample'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h4 class="font-semibold mb-4"><i class="fas fa-book text-blue-500 mr-2"></i>วิธีรับ API Key</h4>
                <ol class="text-sm text-gray-600 space-y-2">
                    <li>1. ไปที่ <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-500">Google AI Studio</a></li>
                    <li>2. Login ด้วย Google Account</li>
                    <li>3. คลิก "Create API Key"</li>
                    <li>4. Copy มาวางที่นี่</li>
                </ol>
                <div class="mt-4 p-3 bg-yellow-50 rounded-lg text-xs text-yellow-700">
                    <i class="fas fa-info-circle mr-1"></i>Gemini API ฟรี! 60 requests/นาที
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <h4 class="font-semibold mb-4"><i class="fas fa-info-circle text-gray-500 mr-2"></i>สถานะ</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">API Key:</span>
                        <span class="<?= $geminiApiKey ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $geminiApiKey ? '✅ ตั้งค่าแล้ว' : '❌ ยังไม่ได้ตั้งค่า' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">สถานะ:</span>
                        <span><?= $aiEnabled ? '🟢 เปิด' : '⚪ ปิด' ?></span>
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
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
