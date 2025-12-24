<?php
/**
 * อัพเดท LIFF ID สำหรับ LINE Account
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = intval($_POST['account_id'] ?? 0);
    $liffId = trim($_POST['liff_id'] ?? '');
    
    if ($accountId > 0) {
        try {
            $stmt = $db->prepare("UPDATE line_accounts SET liff_id = ? WHERE id = ?");
            $stmt->execute([$liffId ?: null, $accountId]);
            $message = "✅ อัพเดท LIFF ID สำเร็จ!";
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

// Get all accounts
$accounts = $db->query("SELECT id, name, liff_id, is_active, is_default FROM line_accounts ORDER BY is_default DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัพเดท LIFF ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">🔧 อัพเดท LIFF ID</h1>
        
        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-lg <?= strpos($message, '✅') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="font-bold mb-4">LINE Accounts</h2>
            
            <?php foreach ($accounts as $a): ?>
            <form method="POST" class="border-b pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                <input type="hidden" name="account_id" value="<?= $a['id'] ?>">
                
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-medium"><?= htmlspecialchars($a['name']) ?></span>
                    <?php if ($a['is_default']): ?><span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">Default</span><?php endif; ?>
                    <?php if ($a['is_active']): ?><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">Active</span><?php endif; ?>
                </div>
                
                <div class="flex gap-2">
                    <input type="text" name="liff_id" value="<?= htmlspecialchars($a['liff_id'] ?? '') ?>" 
                           placeholder="เช่น 2001234567-aBcDeFgH"
                           class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        บันทึก
                    </button>
                </div>
                
                <?php if ($a['liff_id']): ?>
                <div class="mt-2 text-sm">
                    <span class="text-gray-500">LIFF URL:</span>
                    <a href="https://liff.line.me/<?= $a['liff_id'] ?>" target="_blank" class="text-blue-500 hover:underline">
                        https://liff.line.me/<?= $a['liff_id'] ?>
                    </a>
                </div>
                <?php endif; ?>
            </form>
            <?php endforeach; ?>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-6">
            <h2 class="font-bold mb-3">📝 วิธีสร้าง LIFF App</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm">
                <li>ไปที่ <a href="https://developers.line.biz/" target="_blank" class="text-blue-500 hover:underline">LINE Developers Console</a></li>
                <li>เลือก Provider → Channel (Messaging API)</li>
                <li>ไปที่แท็บ <strong>LIFF</strong></li>
                <li>กด <strong>Add</strong> เพื่อสร้าง LIFF App ใหม่</li>
                <li>ตั้งค่า:
                    <ul class="list-disc list-inside ml-4 mt-1">
                        <li>Size: <strong>Full</strong></li>
                        <li>Endpoint URL: <code class="bg-gray-200 px-1 rounded"><?= BASE_URL ?>liff-shop.php</code></li>
                    </ul>
                </li>
                <li>Copy <strong>LIFF ID</strong> มาใส่ในช่องด้านบน</li>
            </ol>
        </div>
        
        <div class="mt-6 text-center">
            <a href="check_all_liff.php" class="text-blue-500 hover:underline">🔍 ตรวจสอบ LIFF ID ทั้งหมด</a>
        </div>
    </div>
</body>
</html>
