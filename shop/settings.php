<?php
/**
 * Shop - ตั้งค่าร้านค้า
 * V2.5 - รองรับ Multi-bot (แยกการตั้งค่าตาม LINE Account)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ตั้งค่าร้านค้า';
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// ตรวจสอบและสร้างตาราง shop_settings ถ้ายังไม่มี
$tableExists = false;
try {
    $db->query("SELECT 1 FROM shop_settings LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {
    // สร้างตาราง
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS shop_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT DEFAULT NULL,
            shop_name VARCHAR(255) DEFAULT 'LINE Shop',
            shop_logo VARCHAR(500),
            welcome_message TEXT,
            shipping_fee DECIMAL(10,2) DEFAULT 50,
            free_shipping_min DECIMAL(10,2) DEFAULT 500,
            bank_accounts TEXT,
            promptpay_number VARCHAR(20),
            contact_phone VARCHAR(20),
            is_open TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $tableExists = true;
    } catch (Exception $e2) {
        $error = "ไม่สามารถสร้างตารางได้: " . $e2->getMessage();
    }
}

// ตรวจสอบว่ามี column line_account_id หรือไม่
$hasAccountCol = false;
if ($tableExists) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE 'line_account_id'");
        $hasAccountCol = $stmt->rowCount() > 0;
        
        // เพิ่ม column ถ้ายังไม่มี
        if (!$hasAccountCol) {
            $db->exec("ALTER TABLE shop_settings ADD COLUMN line_account_id INT DEFAULT NULL AFTER id");
            $hasAccountCol = true;
        }
    } catch (Exception $e) {}
}

// Handle POST BEFORE including header (to allow redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
    $currentBotId = $_SESSION['current_bot_id'] ?? 1;
    $bankAccounts = json_encode(['banks' => array_map(function($name, $account, $holder) {
        return ['name' => $name, 'account' => $account, 'holder' => $holder];
    }, $_POST['bank_name'] ?? [], $_POST['bank_account'] ?? [], $_POST['bank_holder'] ?? [])]);
    
    try {
        if ($hasAccountCol && $currentBotId) {
            // Check if settings exist for this bot
            $stmt = $db->prepare("SELECT id FROM shop_settings WHERE line_account_id = ?");
            $stmt->execute([$currentBotId]);
            $existingId = $stmt->fetchColumn();
            
            if ($existingId) {
                // Update existing
                $stmt = $db->prepare("UPDATE shop_settings SET 
                    shop_name=?, welcome_message=?, shipping_fee=?, free_shipping_min=?, 
                    bank_accounts=?, promptpay_number=?, contact_phone=?, is_open=? 
                    WHERE line_account_id = ?");
                $stmt->execute([
                    $_POST['shop_name'],
                    $_POST['welcome_message'],
                    (float)$_POST['shipping_fee'],
                    (float)$_POST['free_shipping_min'],
                    $bankAccounts,
                    $_POST['promptpay_number'],
                    $_POST['contact_phone'],
                    isset($_POST['is_open']) ? 1 : 0,
                    $currentBotId
                ]);
            } else {
                // Insert new for this bot
                $stmt = $db->prepare("INSERT INTO shop_settings 
                    (line_account_id, shop_name, welcome_message, shipping_fee, free_shipping_min, 
                     bank_accounts, promptpay_number, contact_phone, is_open) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $currentBotId,
                    $_POST['shop_name'],
                    $_POST['welcome_message'],
                    (float)$_POST['shipping_fee'],
                    (float)$_POST['free_shipping_min'],
                    $bankAccounts,
                    $_POST['promptpay_number'],
                    $_POST['contact_phone'],
                    isset($_POST['is_open']) ? 1 : 0
                ]);
            }
        } else {
            // Legacy mode - update id=1
            $stmt = $db->prepare("UPDATE shop_settings SET 
                shop_name=?, welcome_message=?, shipping_fee=?, free_shipping_min=?, 
                bank_accounts=?, promptpay_number=?, contact_phone=?, is_open=? 
                WHERE id = 1");
            $stmt->execute([
                $_POST['shop_name'],
                $_POST['welcome_message'],
                (float)$_POST['shipping_fee'],
                (float)$_POST['free_shipping_min'],
                $bankAccounts,
                $_POST['promptpay_number'],
                $_POST['contact_phone'],
                isset($_POST['is_open']) ? 1 : 0
            ]);
            
            if ($stmt->rowCount() == 0) {
                // Insert if not exists
                $stmt = $db->prepare("INSERT INTO shop_settings 
                    (shop_name, welcome_message, shipping_fee, free_shipping_min, 
                     bank_accounts, promptpay_number, contact_phone, is_open) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['shop_name'],
                    $_POST['welcome_message'],
                    (float)$_POST['shipping_fee'],
                    (float)$_POST['free_shipping_min'],
                    $bankAccounts,
                    $_POST['promptpay_number'],
                    $_POST['contact_phone'],
                    isset($_POST['is_open']) ? 1 : 0
                ]);
            }
        }
        header('Location: settings.php?saved=1');
        exit;
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Include header after POST handling
require_once '../includes/header.php';

// Get settings
$settings = [];
if ($tableExists) {
    try {
        if ($hasAccountCol && $currentBotId) {
            // Get settings for current bot
            $stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ?");
            $stmt->execute([$currentBotId]);
            $settings = $stmt->fetch();
        }
        
        // Fallback to legacy settings if not found
        if (!$settings) {
            $stmt = $db->query("SELECT * FROM shop_settings WHERE id = 1 OR line_account_id IS NULL LIMIT 1");
            $settings = $stmt->fetch();
        }
    } catch (Exception $e) {
        $settings = [];
    }
}

// Default values
if (!$settings) {
    $settings = [
        'shop_name' => 'LINE Shop',
        'welcome_message' => 'ยินดีต้อนรับ!',
        'shipping_fee' => 50,
        'free_shipping_min' => 500,
        'bank_accounts' => '{"banks":[]}',
        'promptpay_number' => '',
        'contact_phone' => '',
        'is_open' => 1
    ];
}

$bankAccounts = json_decode($settings['bank_accounts'] ?? '{"banks":[]}', true)['banks'] ?? [];
?>

<?php if (isset($_GET['saved'])): ?>
<div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>บันทึกการตั้งค่าสำเร็จ!
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">ข้อมูลร้านค้า</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อร้าน</label>
                    <input type="text" name="shop_name" value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ข้อความต้อนรับ</label>
                    <textarea name="welcome_message" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($settings['welcome_message'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เบอร์ติดต่อ</label>
                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium">สถานะร้านค้า</p>
                        <p class="text-sm text-gray-500">เปิด/ปิดรับออเดอร์</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_open" class="sr-only peer" <?= ($settings['is_open'] ?? 1) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Shipping Settings -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">ค่าจัดส่ง</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ค่าจัดส่ง (บาท)</label>
                    <input type="number" name="shipping_fee" value="<?= $settings['shipping_fee'] ?? 50 ?>" min="0" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ส่งฟรีเมื่อซื้อขั้นต่ำ (บาท)</label>
                    <input type="number" name="free_shipping_min" value="<?= $settings['free_shipping_min'] ?? 500 ?>" min="0" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-500 mt-1">ใส่ 0 เพื่อปิดส่งฟรี</p>
                </div>
            </div>
        </div>
        
        <!-- Payment Settings -->
        <div class="bg-white rounded-xl shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold mb-4">ช่องทางชำระเงิน</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">พร้อมเพย์</label>
                    <input type="text" name="promptpay_number" value="<?= htmlspecialchars($settings['promptpay_number'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="เบอร์โทรหรือเลขบัตรประชาชน">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">บัญชีธนาคาร</label>
                    <div id="bankAccounts" class="space-y-3">
                        <?php foreach ($bankAccounts as $i => $bank): ?>
                        <div class="flex space-x-2 bank-row">
                            <input type="text" name="bank_name[]" value="<?= htmlspecialchars($bank['name']) ?>" placeholder="ธนาคาร" class="flex-1 px-4 py-2 border rounded-lg">
                            <input type="text" name="bank_account[]" value="<?= htmlspecialchars($bank['account']) ?>" placeholder="เลขบัญชี" class="flex-1 px-4 py-2 border rounded-lg">
                            <input type="text" name="bank_holder[]" value="<?= htmlspecialchars($bank['holder']) ?>" placeholder="ชื่อบัญชี" class="flex-1 px-4 py-2 border rounded-lg">
                            <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-times"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addBankRow()" class="mt-2 px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">
                        <i class="fas fa-plus mr-2"></i>เพิ่มบัญชี
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-6">
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
        </button>
    </div>
</form>

<script>
function addBankRow() {
    const html = `
        <div class="flex space-x-2 bank-row">
            <input type="text" name="bank_name[]" placeholder="ธนาคาร" class="flex-1 px-4 py-2 border rounded-lg">
            <input type="text" name="bank_account[]" placeholder="เลขบัญชี" class="flex-1 px-4 py-2 border rounded-lg">
            <input type="text" name="bank_holder[]" placeholder="ชื่อบัญชี" class="flex-1 px-4 py-2 border rounded-lg">
            <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-times"></i></button>
        </div>
    `;
    document.getElementById('bankAccounts').insertAdjacentHTML('beforeend', html);
}
</script>

<?php require_once '../includes/footer.php'; ?>
