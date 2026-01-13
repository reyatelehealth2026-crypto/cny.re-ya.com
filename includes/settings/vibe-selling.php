<?php
/**
 * Vibe Selling OS v2 Settings Tab
 * ตั้งค่าระบบ Vibe Selling OS v2 (Pharmacy Edition)
 * 
 * Features:
 * - Enable/Disable v2 toggle
 * - Graceful fallback to v1 when disabled
 * 
 * Requirements: 10.6
 */

// Get current v2 settings
$vibeV2Enabled = false;
$vibeSettings = [];

try {
    // Check if vibe_selling_settings table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'vibe_selling_settings'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the settings table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS `vibe_selling_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `line_account_id` INT NULL,
                `setting_key` VARCHAR(100) NOT NULL,
                `setting_value` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_vibe_setting` (`line_account_id`, `setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // Get current bot ID
    $currentBotId = $_SESSION['current_bot_id'] ?? null;
    
    // Get v2 enabled setting
    $stmt = $db->prepare("
        SELECT setting_value FROM vibe_selling_settings 
        WHERE setting_key = 'v2_enabled' 
        AND (line_account_id = ? OR (line_account_id IS NULL AND ? IS NULL))
        LIMIT 1
    ");
    $stmt->execute([$currentBotId, $currentBotId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $vibeV2Enabled = ($result && $result['setting_value'] === '1');
    
    // Get all vibe settings
    $stmt = $db->prepare("
        SELECT setting_key, setting_value FROM vibe_selling_settings 
        WHERE line_account_id = ? OR (line_account_id IS NULL AND ? IS NULL)
    ");
    $stmt->execute([$currentBotId, $currentBotId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vibeSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist yet, that's okay
    error_log("Vibe Selling Settings Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_vibe_settings') {
    try {
        $currentBotId = $_SESSION['current_bot_id'] ?? null;
        $v2Enabled = isset($_POST['v2_enabled']) ? '1' : '0';
        $autoSwitchOnError = isset($_POST['auto_switch_on_error']) ? '1' : '0';
        $showV2Badge = isset($_POST['show_v2_badge']) ? '1' : '0';
        
        // Upsert v2_enabled setting
        $stmt = $db->prepare("
            INSERT INTO vibe_selling_settings (line_account_id, setting_key, setting_value) 
            VALUES (?, 'v2_enabled', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmt->execute([$currentBotId, $v2Enabled]);
        
        // Upsert auto_switch_on_error setting
        $stmt = $db->prepare("
            INSERT INTO vibe_selling_settings (line_account_id, setting_key, setting_value) 
            VALUES (?, 'auto_switch_on_error', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmt->execute([$currentBotId, $autoSwitchOnError]);
        
        // Upsert show_v2_badge setting
        $stmt = $db->prepare("
            INSERT INTO vibe_selling_settings (line_account_id, setting_key, setting_value) 
            VALUES (?, 'show_v2_badge', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmt->execute([$currentBotId, $showV2Badge]);
        
        // Update local variables
        $vibeV2Enabled = ($v2Enabled === '1');
        $vibeSettings['v2_enabled'] = $v2Enabled;
        $vibeSettings['auto_switch_on_error'] = $autoSwitchOnError;
        $vibeSettings['show_v2_badge'] = $showV2Badge;
        
        $success = 'บันทึกการตั้งค่า Vibe Selling OS v2 สำเร็จ!';
        
        // Log activity
        if (isset($activityLogger)) {
            $activityLogger->logData(ActivityLogger::ACTION_UPDATE, 'อัพเดทการตั้งค่า Vibe Selling OS v2', [
                'entity_type' => 'vibe_selling_settings',
                'new_value' => [
                    'v2_enabled' => $v2Enabled,
                    'auto_switch_on_error' => $autoSwitchOnError,
                    'show_v2_badge' => $showV2Badge
                ]
            ]);
        }
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// Check if v2 services are available
$v2ServicesAvailable = true;
$missingServices = [];

$requiredServices = [
    'DrugPricingEngineService' => 'classes/DrugPricingEngineService.php',
    'CustomerHealthEngineService' => 'classes/CustomerHealthEngineService.php',
    'PharmacyImageAnalyzerService' => 'classes/PharmacyImageAnalyzerService.php',
    'PharmacyGhostDraftService' => 'classes/PharmacyGhostDraftService.php'
];

foreach ($requiredServices as $serviceName => $servicePath) {
    if (!file_exists(__DIR__ . '/../../' . $servicePath)) {
        $v2ServicesAvailable = false;
        $missingServices[] = $serviceName;
    }
}

// Check if v2 tables exist
$v2TablesExist = true;
$missingTables = [];
$requiredTables = [
    'customer_health_profiles',
    'symptom_analysis_cache',
    'drug_recognition_cache',
    'prescription_ocr_results',
    'pharmacy_ghost_learning',
    'consultation_stages'
];

try {
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '{$table}'")->rowCount();
        if ($result === 0) {
            $v2TablesExist = false;
            $missingTables[] = $table;
        }
    }
} catch (PDOException $e) {
    $v2TablesExist = false;
}
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-emerald-500 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-start gap-4">
            <div class="text-4xl">🧠</div>
            <div class="flex-1">
                <h2 class="text-xl font-bold mb-2">Vibe Selling OS v2 (Pharmacy Edition)</h2>
                <p class="text-purple-100 mb-3">ระบบ AI-Powered Pharmacy Assistant สำหรับร้านยา พร้อม Multi-Modal Analysis, Health Profiling, และ Ghost Drafting</p>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="bg-white/20 px-2 py-1 rounded">🔬 วิเคราะห์รูปอาการ</span>
                    <span class="bg-white/20 px-2 py-1 rounded">💊 ระบุยาจากรูป</span>
                    <span class="bg-white/20 px-2 py-1 rounded">📋 OCR ใบสั่งยา</span>
                    <span class="bg-white/20 px-2 py-1 rounded">⚠️ Drug Interaction</span>
                    <span class="bg-white/20 px-2 py-1 rounded">👤 Health Profile</span>
                    <span class="bg-white/20 px-2 py-1 rounded">✍️ Ghost Draft</span>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">📊</span>
            สถานะระบบ
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Services Status -->
            <div class="border rounded-lg p-4 <?= $v2ServicesAvailable ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' ?>">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl"><?= $v2ServicesAvailable ? '✅' : '❌' ?></span>
                    <h4 class="font-semibold">Services</h4>
                </div>
                <p class="text-sm <?= $v2ServicesAvailable ? 'text-green-600' : 'text-red-600' ?>">
                    <?= $v2ServicesAvailable ? 'พร้อมใช้งาน' : 'ไม่พร้อม - ขาด ' . count($missingServices) . ' services' ?>
                </p>
                <?php if (!$v2ServicesAvailable): ?>
                <ul class="text-xs text-red-500 mt-2 list-disc list-inside">
                    <?php foreach ($missingServices as $service): ?>
                    <li><?= htmlspecialchars($service) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <!-- Database Tables Status -->
            <div class="border rounded-lg p-4 <?= $v2TablesExist ? 'border-green-300 bg-green-50' : 'border-yellow-300 bg-yellow-50' ?>">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl"><?= $v2TablesExist ? '✅' : '⚠️' ?></span>
                    <h4 class="font-semibold">Database</h4>
                </div>
                <p class="text-sm <?= $v2TablesExist ? 'text-green-600' : 'text-yellow-600' ?>">
                    <?= $v2TablesExist ? 'ตารางพร้อมใช้งาน' : 'ขาด ' . count($missingTables) . ' ตาราง' ?>
                </p>
                <?php if (!$v2TablesExist): ?>
                <a href="install/run_vibe_selling_v2_migration.php" target="_blank" 
                   class="inline-block mt-2 text-xs text-blue-500 hover:underline">
                    <i class="fas fa-database mr-1"></i>รัน Migration
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Current Mode -->
            <div class="border rounded-lg p-4 <?= $vibeV2Enabled ? 'border-purple-300 bg-purple-50' : 'border-gray-300 bg-gray-50' ?>">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl"><?= $vibeV2Enabled ? '🚀' : '📦' ?></span>
                    <h4 class="font-semibold">โหมดปัจจุบัน</h4>
                </div>
                <p class="text-sm <?= $vibeV2Enabled ? 'text-purple-600 font-semibold' : 'text-gray-600' ?>">
                    <?= $vibeV2Enabled ? 'Vibe Selling OS v2' : 'Inbox v1 (Classic)' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <input type="hidden" name="action" value="save_vibe_settings">
        
        <div class="p-5 border-b bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
            <h2 class="font-bold flex items-center">
                <i class="fas fa-cog mr-2"></i>การตั้งค่า Vibe Selling OS v2
            </h2>
            <p class="text-purple-100 text-sm mt-1">เปิด/ปิดระบบ v2 และตั้งค่าการทำงาน</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Main Toggle -->
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border-2 <?= $vibeV2Enabled ? 'border-purple-400' : 'border-gray-300' ?>">
                <div class="flex items-center gap-4">
                    <div class="text-4xl"><?= $vibeV2Enabled ? '🧠' : '📦' ?></div>
                    <div>
                        <h3 class="font-bold text-lg">เปิดใช้งาน Vibe Selling OS v2</h3>
                        <p class="text-gray-600 text-sm">เมื่อเปิด จะใช้ระบบ Inbox v2 แทน v1 พร้อมฟีเจอร์ AI ทั้งหมด</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="v2_enabled" class="sr-only peer" 
                           <?= $vibeV2Enabled ? 'checked' : '' ?>
                           <?= (!$v2ServicesAvailable || !$v2TablesExist) ? 'disabled' : '' ?>>
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600 <?= (!$v2ServicesAvailable || !$v2TablesExist) ? 'opacity-50' : '' ?>"></div>
                </label>
            </div>
            
            <?php if (!$v2ServicesAvailable || !$v2TablesExist): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-700 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ไม่สามารถเปิดใช้งาน v2 ได้ เนื่องจากระบบยังไม่พร้อม กรุณาตรวจสอบ Services และ Database ด้านบน
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Additional Settings -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-sliders-h mr-2 text-purple-500"></i>
                    ตั้งค่าเพิ่มเติม
                </h4>
                
                <!-- Auto Switch on Error -->
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                    <div>
                        <span class="font-medium">สลับกลับ v1 อัตโนมัติเมื่อเกิดข้อผิดพลาด</span>
                        <p class="text-gray-500 text-sm">หาก v2 มีปัญหา ระบบจะสลับไปใช้ v1 โดยอัตโนมัติ</p>
                    </div>
                    <input type="checkbox" name="auto_switch_on_error" 
                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
                           <?= ($vibeSettings['auto_switch_on_error'] ?? '1') === '1' ? 'checked' : '' ?>>
                </label>
                
                <!-- Show V2 Badge -->
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                    <div>
                        <span class="font-medium">แสดง Badge "V2" ในหน้า Inbox</span>
                        <p class="text-gray-500 text-sm">แสดงป้ายกำกับว่ากำลังใช้งานระบบ v2</p>
                    </div>
                    <input type="checkbox" name="show_v2_badge" 
                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
                           <?= ($vibeSettings['show_v2_badge'] ?? '1') === '1' ? 'checked' : '' ?>>
                </label>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                การเปลี่ยนแปลงจะมีผลทันทีหลังบันทึก
            </div>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    <!-- Feature Comparison -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b">
            <h2 class="font-bold flex items-center">
                <i class="fas fa-balance-scale mr-2 text-indigo-500"></i>
                เปรียบเทียบ Inbox v1 vs v2
            </h2>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 px-4">ฟีเจอร์</th>
                            <th class="text-center py-3 px-4 bg-gray-50">Inbox v1</th>
                            <th class="text-center py-3 px-4 bg-purple-50">Vibe Selling OS v2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="py-3 px-4">แชทพื้นฐาน</td>
                            <td class="text-center py-3 px-4 bg-gray-50">✅</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">AI ช่วยตอบ</td>
                            <td class="text-center py-3 px-4 bg-gray-50">✅</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅ (ปรับปรุง)</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">วิเคราะห์รูปอาการ</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">ระบุยาจากรูป</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">OCR ใบสั่งยา</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">Drug Interaction Check</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">Customer Health Profile</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">Ghost Drafting</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">HUD Dashboard</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4">Pricing Engine</td>
                            <td class="text-center py-3 px-4 bg-gray-50">❌</td>
                            <td class="text-center py-3 px-4 bg-purple-50">✅</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-4 flex items-center">
            <i class="fas fa-external-link-alt mr-2 text-blue-500"></i>
            ลิงก์ด่วน
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="inbox.php" class="block p-4 bg-gray-50 rounded-xl hover:bg-gray-100 text-center border">
                <div class="text-2xl mb-2">📦</div>
                <div class="font-medium">Inbox v1</div>
                <div class="text-xs text-gray-500">ระบบเดิม</div>
            </a>
            <a href="inbox-v2.php" class="block p-4 bg-purple-50 rounded-xl hover:bg-purple-100 text-center border border-purple-200">
                <div class="text-2xl mb-2">🧠</div>
                <div class="font-medium text-purple-700">Inbox v2</div>
                <div class="text-xs text-purple-500">Vibe Selling OS</div>
            </a>
            <a href="install/run_vibe_selling_v2_migration.php" target="_blank" class="block p-4 bg-blue-50 rounded-xl hover:bg-blue-100 text-center border border-blue-200">
                <div class="text-2xl mb-2">🗄️</div>
                <div class="font-medium text-blue-700">Migration</div>
                <div class="text-xs text-blue-500">สร้างตาราง</div>
            </a>
            <a href="ai-settings.php" class="block p-4 bg-green-50 rounded-xl hover:bg-green-100 text-center border border-green-200">
                <div class="text-2xl mb-2">🤖</div>
                <div class="font-medium text-green-700">AI Settings</div>
                <div class="text-xs text-green-500">ตั้งค่า AI</div>
            </a>
        </div>
    </div>
</div>
