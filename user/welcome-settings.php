<?php
/**
 * User Welcome Settings - ตั้งค่าข้อความต้อนรับ
 */
$pageTitle = 'ข้อความต้อนรับ';
require_once '../includes/user_header.php';

$success = '';
$error = '';

// Get current settings
$stmt = $db->prepare("SELECT * FROM welcome_settings WHERE line_account_id = ?");
$stmt->execute([$currentBotId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
    $textContent = trim($_POST['text_content'] ?? '');
    
    if ($settings) {
        $stmt = $db->prepare("UPDATE welcome_settings SET is_enabled = ?, text_content = ? WHERE line_account_id = ?");
        $stmt->execute([$isEnabled, $textContent, $currentBotId]);
    } else {
        $stmt = $db->prepare("INSERT INTO welcome_settings (line_account_id, is_enabled, message_type, text_content) VALUES (?, ?, 'text', ?)");
        $stmt->execute([$currentBotId, $isEnabled, $textContent]);
    }
    
    $success = 'บันทึกการตั้งค่าสำเร็จ';
    
    // Refresh settings
    $stmt = $db->prepare("SELECT * FROM welcome_settings WHERE line_account_id = ?");
    $stmt->execute([$currentBotId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-2xl mx-auto">
    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-600 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">
                <i class="fas fa-hand-wave text-yellow-500 mr-2"></i>ข้อความต้อนรับ
            </h2>
            <p class="text-sm text-gray-500 mt-1">ข้อความที่จะส่งอัตโนมัติเมื่อมีคนเพิ่มเพื่อน LINE OA ของคุณ</p>
        </div>
        
        <form method="POST" class="p-6">
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_enabled" <?= ($settings['is_enabled'] ?? 1) ? 'checked' : '' ?> 
                           class="w-5 h-5 text-green-500 rounded focus:ring-green-500">
                    <span class="ml-3 font-medium">เปิดใช้งานข้อความต้อนรับ</span>
                </label>
                <p class="text-sm text-gray-500 mt-1 ml-8">เมื่อเปิด ระบบจะส่งข้อความต้อนรับอัตโนมัติเมื่อมีคนเพิ่มเพื่อน</p>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">ข้อความต้อนรับ</label>
                <textarea name="text_content" rows="6" 
                          class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                          placeholder="พิมพ์ข้อความต้อนรับที่ต้องการ..."><?= htmlspecialchars($settings['text_content'] ?? '') ?></textarea>
                <p class="text-sm text-gray-500 mt-1">สามารถใช้ emoji และขึ้นบรรทัดใหม่ได้</p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm font-medium text-gray-700 mb-2"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>ตัวอย่างข้อความ</p>
                <div class="bg-white rounded-lg p-3 border text-sm whitespace-pre-line"><?= htmlspecialchars($settings['text_content'] ?? 'ยังไม่ได้ตั้งค่าข้อความ') ?></div>
            </div>
            
            <button type="submit" class="w-full py-3 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </form>
    </div>
    
    <!-- Tips -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
        <h3 class="font-medium text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>เคล็ดลับ</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• ใช้ emoji เพื่อทำให้ข้อความดูน่าสนใจ 🎉</li>
            <li>• แนะนำสิ่งที่ลูกค้าสามารถทำได้ เช่น พิมพ์คำสั่งต่างๆ</li>
            <li>• ข้อความไม่ควรยาวเกินไป ควรกระชับและเข้าใจง่าย</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
