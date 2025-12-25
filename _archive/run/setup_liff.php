<?php
/**
 * LIFF Setup Guide
 * คู่มือการตั้งค่า LIFF สำหรับ LINE Shop
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get current LIFF settings
$liffId = '';
$lineAccountId = null;
try {
    $stmt = $db->query("SELECT id, name, liff_id, channel_id FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC LIMIT 1");
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($account) {
        $liffId = $account['liff_id'] ?? '';
        $lineAccountId = $account['id'];
    }
} catch (Exception $e) {}

// Base URL
$baseUrl = 'https://l.poppyedc.xyz';
$shopUrl = $baseUrl . '/liff-shop.php';
$checkoutUrl = $baseUrl . '/liff-checkout.php';

// Handle LIFF ID update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liff_id'])) {
    $newLiffId = trim($_POST['liff_id']);
    $accountId = intval($_POST['account_id']);
    
    try {
        $stmt = $db->prepare("UPDATE line_accounts SET liff_id = ? WHERE id = ?");
        $stmt->execute([$newLiffId, $accountId]);
        $liffId = $newLiffId;
        $message = "✅ บันทึก LIFF ID สำเร็จ!";
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIFF Setup Guide</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔧 LIFF Setup Guide</h1>
        
        <?php if (isset($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">📊 สถานะปัจจุบัน</h2>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 font-medium">LINE Account:</td>
                    <td class="py-2"><?= htmlspecialchars($account['name'] ?? 'ไม่พบ') ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">Channel ID:</td>
                    <td class="py-2"><?= htmlspecialchars($account['channel_id'] ?? 'ไม่ระบุ') ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">LIFF ID:</td>
                    <td class="py-2">
                        <?php if ($liffId): ?>
                        <span class="text-green-600 font-mono"><?= htmlspecialchars($liffId) ?></span>
                        <?php else: ?>
                        <span class="text-red-500">❌ ยังไม่ได้ตั้งค่า</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="py-2 font-medium">LIFF URL:</td>
                    <td class="py-2">
                        <?php if ($liffId): ?>
                        <a href="https://liff.line.me/<?= $liffId ?>" target="_blank" class="text-blue-500 hover:underline">
                            https://liff.line.me/<?= $liffId ?>
                        </a>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Update LIFF ID -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">💾 อัพเดท LIFF ID</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="account_id" value="<?= $lineAccountId ?>">
                <div>
                    <label class="block text-sm font-medium mb-1">LIFF ID:</label>
                    <input type="text" name="liff_id" value="<?= htmlspecialchars($liffId) ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="เช่น 2001234567-aBcDeFgH">
                </div>
                <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    💾 บันทึก
                </button>
            </form>
        </div>

        <!-- Setup Guide -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">📖 วิธีสร้าง LIFF App</h2>
            
            <div class="space-y-6">
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-lg">Step 1: เข้า LINE Developers Console</h3>
                    <p class="text-gray-600 mt-1">ไปที่ <a href="https://developers.line.biz/console/" target="_blank" class="text-blue-500 hover:underline">https://developers.line.biz/console/</a></p>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-lg">Step 2: เลือก Provider และ Channel</h3>
                    <p class="text-gray-600 mt-1">เลือก Provider ของคุณ → เลือก LINE Login Channel (หรือสร้างใหม่)</p>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-lg">Step 3: สร้าง LIFF App</h3>
                    <ol class="list-decimal list-inside text-gray-600 mt-1 space-y-1">
                        <li>ไปที่แท็บ <strong>LIFF</strong></li>
                        <li>คลิก <strong>Add</strong></li>
                        <li>กรอกข้อมูล:</li>
                    </ol>
                    <div class="bg-gray-50 p-4 rounded mt-2 font-mono text-sm">
                        <p><strong>LIFF app name:</strong> LINE Shop</p>
                        <p><strong>Size:</strong> Full</p>
                        <p><strong>Endpoint URL:</strong> <span class="text-green-600"><?= $shopUrl ?></span></p>
                        <p><strong>Scope:</strong> ✅ profile, ✅ openid</p>
                        <p><strong>Bot link feature:</strong> On (Aggressive)</p>
                    </div>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-lg">Step 4: คัดลอก LIFF ID</h3>
                    <p class="text-gray-600 mt-1">หลังสร้างเสร็จ จะได้ LIFF ID เช่น <code class="bg-gray-100 px-2 py-1 rounded">2001234567-aBcDeFgH</code></p>
                    <p class="text-gray-600 mt-1">นำมาใส่ในช่องด้านบน แล้วกด บันทึก</p>
                </div>
                
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-bold text-lg">Step 5: ตั้งค่า Linked OA (สำคัญ!)</h3>
                    <p class="text-gray-600 mt-1">ใน LIFF App settings:</p>
                    <ol class="list-decimal list-inside text-gray-600 mt-1 space-y-1">
                        <li>เลื่อนลงไปที่ <strong>Linked OA</strong></li>
                        <li>เลือก LINE Official Account ของคุณ</li>
                        <li>เปิด <strong>Bot link feature</strong> = On (Aggressive)</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- URLs Reference -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">🔗 URLs สำหรับตั้งค่า</h2>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 font-medium">LIFF Shop (Endpoint URL):</td>
                    <td class="py-2 font-mono text-sm">
                        <input type="text" value="<?= $shopUrl ?>" readonly 
                               class="w-full px-2 py-1 bg-gray-50 border rounded" onclick="this.select()">
                    </td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">LIFF Checkout:</td>
                    <td class="py-2 font-mono text-sm">
                        <input type="text" value="<?= $checkoutUrl ?>" readonly 
                               class="w-full px-2 py-1 bg-gray-50 border rounded" onclick="this.select()">
                    </td>
                </tr>
                <tr>
                    <td class="py-2 font-medium">Webhook URL:</td>
                    <td class="py-2 font-mono text-sm">
                        <input type="text" value="<?= $baseUrl ?>/webhook.php" readonly 
                               class="w-full px-2 py-1 bg-gray-50 border rounded" onclick="this.select()">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Test Links -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">🧪 ทดสอบ</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="liff-shop.php?debug=1" target="_blank" 
                   class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 text-center">
                    <div class="text-2xl mb-2">🛒</div>
                    <div class="font-medium">LIFF Shop (Debug)</div>
                </a>
                <?php if ($liffId): ?>
                <a href="https://liff.line.me/<?= $liffId ?>" target="_blank" 
                   class="block p-4 bg-green-50 rounded-lg hover:bg-green-100 text-center">
                    <div class="text-2xl mb-2">📱</div>
                    <div class="font-medium">เปิดใน LINE App</div>
                </a>
                <?php else: ?>
                <div class="block p-4 bg-gray-100 rounded-lg text-center opacity-50">
                    <div class="text-2xl mb-2">📱</div>
                    <div class="font-medium">ต้องตั้งค่า LIFF ID ก่อน</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
