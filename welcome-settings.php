<?php
/**
 * Welcome Message Settings - ตั้งค่าข้อความต้อนรับ
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ข้อความต้อนรับ';

require_once 'includes/header.php';
?>
<script src="assets/js/flex-preview.js"></script>
<?php

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $messageType = $_POST['message_type'];
        $textContent = $_POST['text_content'] ?? '';
        $flexContent = $_POST['flex_content'] ?? '';
        
        // Check if settings exist for this bot
        $stmt = $db->prepare("SELECT id FROM welcome_settings WHERE line_account_id = ? OR (line_account_id IS NULL AND ? IS NULL)");
        $stmt->execute([$currentBotId, $currentBotId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $db->prepare("UPDATE welcome_settings SET is_enabled = ?, message_type = ?, text_content = ?, flex_content = ? WHERE id = ?");
            $stmt->execute([$isEnabled, $messageType, $textContent, $flexContent, $exists['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO welcome_settings (line_account_id, is_enabled, message_type, text_content, flex_content) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$currentBotId, $isEnabled, $messageType, $textContent, $flexContent]);
        }
        
        $success = true;
    }
}

// Get current settings
$stmt = $db->prepare("SELECT * FROM welcome_settings WHERE line_account_id = ? OR (line_account_id IS NULL AND ? IS NULL) LIMIT 1");
$stmt->execute([$currentBotId, $currentBotId]);
$settings = $stmt->fetch();

if (!$settings) {
    $settings = [
        'is_enabled' => 0,
        'message_type' => 'text',
        'text_content' => "สวัสดีค่ะ ยินดีต้อนรับ! 🎉\n\nขอบคุณที่เพิ่มเราเป็นเพื่อน\nหากต้องการความช่วยเหลือ สามารถพิมพ์ข้อความมาได้เลยค่ะ",
        'flex_content' => ''
    ];
}

// Default Flex template
$defaultFlex = json_encode([
    'type' => 'bubble',
    'hero' => [
        'type' => 'image',
        'url' => 'https://developers-resource.landpress.line.me/fx/img/01_1_cafe.png',
        'size' => 'full',
        'aspectRatio' => '20:13',
        'aspectMode' => 'cover'
    ],
    'body' => [
        'type' => 'box',
        'layout' => 'vertical',
        'contents' => [
            ['type' => 'text', 'text' => 'ยินดีต้อนรับ! 🎉', 'weight' => 'bold', 'size' => 'xl', 'color' => '#06C755'],
            ['type' => 'text', 'text' => 'ขอบคุณที่เพิ่มเราเป็นเพื่อน', 'size' => 'sm', 'color' => '#666666', 'margin' => 'md', 'wrap' => true],
            ['type' => 'separator', 'margin' => 'lg'],
            ['type' => 'text', 'text' => '📌 บริการของเรา', 'weight' => 'bold', 'size' => 'sm', 'margin' => 'lg'],
            [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'md',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '• สินค้าคุณภาพ', 'size' => 'sm', 'color' => '#666666'],
                    ['type' => 'text', 'text' => '• จัดส่งรวดเร็ว', 'size' => 'sm', 'color' => '#666666'],
                    ['type' => 'text', 'text' => '• บริการหลังการขาย', 'size' => 'sm', 'color' => '#666666']
                ]
            ]
        ]
    ],
    'footer' => [
        'type' => 'box',
        'layout' => 'vertical',
        'spacing' => 'sm',
        'contents' => [
            ['type' => 'button', 'action' => ['type' => 'message', 'label' => '🛒 ดูสินค้า', 'text' => 'shop'], 'style' => 'primary', 'color' => '#06C755'],
            ['type' => 'button', 'action' => ['type' => 'message', 'label' => '📞 ติดต่อเรา', 'text' => 'ติดต่อ'], 'style' => 'secondary']
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if (empty($settings['flex_content'])) {
    $settings['flex_content'] = $defaultFlex;
}
?>

<?php if (isset($success)): ?>
<div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>บันทึกการตั้งค่าเรียบร้อยแล้ว
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Settings Form -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-hand-wave text-green-500 mr-2"></i>
            ตั้งค่าข้อความต้อนรับ
        </h3>
        
        <form method="POST" id="welcomeForm">
            <input type="hidden" name="action" value="save">
            
            <!-- Enable/Disable -->
            <div class="mb-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_enabled" <?= $settings['is_enabled'] ? 'checked' : '' ?> class="w-5 h-5 text-green-500 rounded mr-3">
                    <span class="font-medium">เปิดใช้งานข้อความต้อนรับ</span>
                </label>
                <p class="text-sm text-gray-500 mt-1 ml-8">ส่งข้อความอัตโนมัติเมื่อมีคนเพิ่มเพื่อน</p>
            </div>
            
            <!-- Message Type -->
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">ประเภทข้อความ</label>
                <div class="flex space-x-4">
                    <label class="flex items-center px-4 py-3 border rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-green-50 has-[:checked]:border-green-500">
                        <input type="radio" name="message_type" value="text" <?= $settings['message_type'] === 'text' ? 'checked' : '' ?> class="mr-2" onchange="toggleMessageType()">
                        <i class="fas fa-font mr-2 text-gray-500"></i>
                        <span>ข้อความธรรมดา</span>
                    </label>
                    <label class="flex items-center px-4 py-3 border rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-green-50 has-[:checked]:border-green-500">
                        <input type="radio" name="message_type" value="flex" <?= $settings['message_type'] === 'flex' ? 'checked' : '' ?> class="mr-2" onchange="toggleMessageType()">
                        <i class="fas fa-puzzle-piece mr-2 text-gray-500"></i>
                        <span>Flex Message</span>
                    </label>
                </div>
            </div>
            
            <!-- Text Content -->
            <div id="textSection" class="mb-6 <?= $settings['message_type'] !== 'text' ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium mb-2">ข้อความต้อนรับ</label>
                <textarea name="text_content" id="textContent" rows="6" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="พิมพ์ข้อความต้อนรับ..."><?= htmlspecialchars($settings['text_content']) ?></textarea>
                <p class="text-xs text-gray-500 mt-1">รองรับ Emoji และขึ้นบรรทัดใหม่ได้</p>
            </div>
            
            <!-- Flex Content -->
            <div id="flexSection" class="mb-6 <?= $settings['message_type'] !== 'flex' ? 'hidden' : '' ?>">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium">Flex Message JSON</label>
                    <div class="space-x-2">
                        <button type="button" onclick="loadTemplate('welcome')" class="text-sm text-green-600 hover:underline">โหลด Template</button>
                        <a href="flex-builder.php" target="_blank" class="text-sm text-blue-600 hover:underline">Flex Builder →</a>
                    </div>
                </div>
                <textarea name="flex_content" id="flexContent" rows="15" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 font-mono text-sm"><?= htmlspecialchars($settings['flex_content']) ?></textarea>
                <p class="text-xs text-gray-500 mt-1">ใส่ JSON ของ Flex Message (bubble หรือ carousel)</p>
            </div>
            
            <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </form>
    </div>
    
    <!-- Preview -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-eye text-blue-500 mr-2"></i>
            ตัวอย่างข้อความ
        </h3>
        
        <div class="bg-gray-100 rounded-lg p-4 min-h-[400px]">
            <!-- Chat Preview -->
            <div class="max-w-sm mx-auto">
                <!-- System message -->
                <div class="text-center mb-4">
                    <span class="text-xs bg-gray-300 text-gray-600 px-3 py-1 rounded-full">ผู้ใช้เพิ่มเพื่อน</span>
                </div>
                
                <!-- Bot message -->
                <div id="previewArea" class="flex justify-start">
                    <div class="max-w-[280px]">
                        <div id="textPreview" class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow">
                            <p class="text-sm whitespace-pre-wrap"><?= nl2br(htmlspecialchars($settings['text_content'])) ?></p>
                        </div>
                        <div id="flexPreview" class="hidden">
                            <!-- Visual Flex Preview -->
                            <div id="flexPreviewContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Button -->
        <div class="mt-4">
            <p class="text-sm text-gray-500 mb-2">ทดสอบส่งข้อความต้อนรับไปยังตัวเอง</p>
            <button type="button" onclick="testWelcome()" class="w-full py-2 border border-green-500 text-green-500 rounded-lg hover:bg-green-50">
                <i class="fas fa-paper-plane mr-2"></i>ทดสอบส่ง
            </button>
        </div>
    </div>
</div>

<!-- Flex Templates -->
<div class="mt-6 bg-white rounded-xl shadow p-6">
    <h3 class="text-lg font-semibold mb-4">
        <i class="fas fa-palette text-purple-500 mr-2"></i>
        Template สำเร็จรูป
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button type="button" onclick="loadTemplate('simple')" class="p-4 border rounded-lg hover:bg-gray-50 text-left">
            <div class="font-medium mb-1">🎉 แบบเรียบง่าย</div>
            <p class="text-sm text-gray-500">ข้อความต้อนรับพื้นฐาน</p>
        </button>
        <button type="button" onclick="loadTemplate('shop')" class="p-4 border rounded-lg hover:bg-gray-50 text-left">
            <div class="font-medium mb-1">🛒 แบบร้านค้า</div>
            <p class="text-sm text-gray-500">มีปุ่มดูสินค้า</p>
        </button>
        <button type="button" onclick="loadTemplate('service')" class="p-4 border rounded-lg hover:bg-gray-50 text-left">
            <div class="font-medium mb-1">💼 แบบบริการ</div>
            <p class="text-sm text-gray-500">แนะนำบริการ</p>
        </button>
    </div>
</div>

<script>
const templates = {
    simple: {
        type: 'bubble',
        body: {
            type: 'box',
            layout: 'vertical',
            contents: [
                {type: 'text', text: 'ยินดีต้อนรับ! 🎉', weight: 'bold', size: 'xl', color: '#06C755'},
                {type: 'text', text: 'ขอบคุณที่เพิ่มเราเป็นเพื่อน', size: 'sm', color: '#666666', margin: 'md', wrap: true},
                {type: 'text', text: 'หากต้องการความช่วยเหลือ สามารถพิมพ์ข้อความมาได้เลยค่ะ', size: 'sm', color: '#666666', margin: 'md', wrap: true}
            ]
        }
    },
    shop: {
        type: 'bubble',
        hero: {
            type: 'image',
            url: 'https://developers-resource.landpress.line.me/fx/img/01_1_cafe.png',
            size: 'full',
            aspectRatio: '20:13',
            aspectMode: 'cover'
        },
        body: {
            type: 'box',
            layout: 'vertical',
            contents: [
                {type: 'text', text: 'ยินดีต้อนรับสู่ร้านของเรา! 🛒', weight: 'bold', size: 'lg', color: '#06C755'},
                {type: 'text', text: 'ขอบคุณที่เพิ่มเราเป็นเพื่อน', size: 'sm', color: '#666666', margin: 'md'},
                {type: 'separator', margin: 'lg'},
                {type: 'text', text: '🎁 สิทธิพิเศษสำหรับเพื่อนใหม่', weight: 'bold', size: 'sm', margin: 'lg'},
                {type: 'text', text: '• ส่วนลด 10% ออเดอร์แรก', size: 'sm', color: '#FF6B6B', margin: 'sm'},
                {type: 'text', text: '• ส่งฟรีเมื่อซื้อครบ 500 บาท', size: 'sm', color: '#666666', margin: 'sm'}
            ]
        },
        footer: {
            type: 'box',
            layout: 'vertical',
            spacing: 'sm',
            contents: [
                {type: 'button', action: {type: 'message', label: '🛒 ดูสินค้า', text: 'shop'}, style: 'primary', color: '#06C755'},
                {type: 'button', action: {type: 'message', label: '📞 ติดต่อเรา', text: 'ติดต่อ'}, style: 'secondary'}
            ]
        }
    },
    service: {
        type: 'bubble',
        body: {
            type: 'box',
            layout: 'vertical',
            contents: [
                {type: 'text', text: 'ยินดีต้อนรับ! 💼', weight: 'bold', size: 'xl', color: '#06C755'},
                {type: 'text', text: 'ขอบคุณที่สนใจบริการของเรา', size: 'sm', color: '#666666', margin: 'md'},
                {type: 'separator', margin: 'lg'},
                {type: 'text', text: '📌 บริการของเรา', weight: 'bold', size: 'sm', margin: 'lg'},
                {
                    type: 'box',
                    layout: 'vertical',
                    margin: 'md',
                    spacing: 'sm',
                    contents: [
                        {type: 'text', text: '✅ ให้คำปรึกษาฟรี', size: 'sm', color: '#666666'},
                        {type: 'text', text: '✅ บริการรวดเร็ว', size: 'sm', color: '#666666'},
                        {type: 'text', text: '✅ ราคาเป็นกันเอง', size: 'sm', color: '#666666'}
                    ]
                }
            ]
        },
        footer: {
            type: 'box',
            layout: 'vertical',
            spacing: 'sm',
            contents: [
                {type: 'button', action: {type: 'message', label: '📋 ดูบริการ', text: 'บริการ'}, style: 'primary', color: '#06C755'},
                {type: 'button', action: {type: 'uri', label: '📞 โทรหาเรา', uri: 'tel:0812345678'}, style: 'secondary'}
            ]
        }
    }
};

function toggleMessageType() {
    const type = document.querySelector('input[name="message_type"]:checked').value;
    document.getElementById('textSection').classList.toggle('hidden', type !== 'text');
    document.getElementById('flexSection').classList.toggle('hidden', type !== 'flex');
    document.getElementById('textPreview').classList.toggle('hidden', type !== 'text');
    document.getElementById('flexPreview').classList.toggle('hidden', type !== 'flex');
    
    // Update flex preview
    if (type === 'flex') {
        updateFlexPreview();
    }
}

function updateFlexPreview() {
    const jsonStr = document.getElementById('flexContent')?.value?.trim();
    if (!jsonStr) {
        document.getElementById('flexPreviewContainer').innerHTML = '<div class="text-center text-gray-400 py-4"><i class="fas fa-puzzle-piece text-2xl mb-2"></i><p class="text-sm">ใส่ JSON เพื่อดู Preview</p></div>';
        return;
    }
    try {
        const json = JSON.parse(jsonStr);
        FlexPreview.render('flexPreviewContainer', json);
    } catch (e) {
        document.getElementById('flexPreviewContainer').innerHTML = '<div class="text-center text-red-400 py-4"><i class="fas fa-exclamation-circle text-2xl mb-2"></i><p class="text-sm">JSON ไม่ถูกต้อง</p></div>';
    }
}

function loadTemplate(name) {
    if (templates[name]) {
        document.getElementById('flexContent').value = JSON.stringify(templates[name], null, 2);
        document.querySelector('input[name="message_type"][value="flex"]').checked = true;
        toggleMessageType();
    }
}

function testWelcome() {
    alert('ฟีเจอร์ทดสอบจะส่งข้อความไปยัง LINE ของคุณ\n\nกรุณาบันทึกการตั้งค่าก่อน แล้วเพิ่มบอทเป็นเพื่อนใหม่เพื่อทดสอบ');
}

// Update preview on text change
document.getElementById('textContent')?.addEventListener('input', function(e) {
    document.querySelector('#textPreview p').innerHTML = e.target.value.replace(/\n/g, '<br>');
});

// Update flex preview on change
document.getElementById('flexContent')?.addEventListener('input', updateFlexPreview);

// Initialize
toggleMessageType();
</script>

<?php require_once 'includes/footer.php'; ?>
