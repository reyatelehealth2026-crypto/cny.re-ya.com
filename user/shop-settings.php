<?php
/**
 * User Shop Settings - ตั้งค่าร้านค้า
 */
$pageTitle = 'ตั้งค่าร้านค้า';
require_once '../includes/user_header.php';

$success = '';
$error = '';

// Get current settings (ใช้ line_account_id เป็น key)
$stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ?");
$stmt->execute([$currentBotId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// ถ้ายังไม่มี settings ให้สร้างใหม่
if (!$settings) {
    $stmt = $db->prepare("INSERT INTO shop_settings (line_account_id, shop_name, welcome_message, shipping_fee, free_shipping_min, is_open) VALUES (?, ?, ?, 50, 500, 1)");
    $stmt->execute([$currentBotId, $lineAccount['name'] ?? 'My Shop', 'ยินดีต้อนรับสู่ร้านค้าของเรา!']);
    
    $stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ?");
    $stmt->execute([$currentBotId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopName = trim($_POST['shop_name'] ?? '');
    $welcomeMessage = trim($_POST['welcome_message'] ?? '');
    $shippingFee = (float)($_POST['shipping_fee'] ?? 0);
    $freeShippingMin = (float)($_POST['free_shipping_min'] ?? 0);
    $promptpayNumber = trim($_POST['promptpay_number'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $isOpen = isset($_POST['is_open']) ? 1 : 0;
    
    // Bank accounts
    $bankAccounts = [];
    if (!empty($_POST['bank_name'])) {
        foreach ($_POST['bank_name'] as $i => $bankName) {
            if (!empty($bankName)) {
                $bankAccounts[] = [
                    'name' => $bankName,
                    'account' => $_POST['bank_account'][$i] ?? '',
                    'holder' => $_POST['bank_holder'][$i] ?? ''
                ];
            }
        }
    }
    
    $stmt = $db->prepare("UPDATE shop_settings SET shop_name = ?, welcome_message = ?, shipping_fee = ?, free_shipping_min = ?, promptpay_number = ?, contact_phone = ?, bank_accounts = ?, is_open = ? WHERE line_account_id = ?");
    $stmt->execute([
        $shopName, 
        $welcomeMessage, 
        $shippingFee, 
        $freeShippingMin, 
        $promptpayNumber, 
        $contactPhone, 
        json_encode(['banks' => $bankAccounts], JSON_UNESCAPED_UNICODE),
        $isOpen,
        $currentBotId
    ]);
    
    $success = 'บันทึกการตั้งค่าสำเร็จ';
    
    // Refresh settings
    $stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ?");
    $stmt->execute([$currentBotId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Parse bank accounts
$bankAccounts = [];
if (!empty($settings['bank_accounts'])) {
    $decoded = json_decode($settings['bank_accounts'], true);
    $bankAccounts = $decoded['banks'] ?? [];
}
?>

<div class="max-w-2xl mx-auto">
    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-600 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <!-- Shop Info -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-store text-green-500 mr-2"></i>ข้อมูลร้านค้า
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อร้านค้า</label>
                    <input type="text" name="shop_name" value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ข้อความต้อนรับร้านค้า</label>
                    <textarea name="welcome_message" rows="3" 
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($settings['welcome_message'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรติดต่อ</label>
                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="08x-xxx-xxxx">
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_open" <?= ($settings['is_open'] ?? 1) ? 'checked' : '' ?> 
                               class="w-5 h-5 text-green-500 rounded focus:ring-green-500">
                        <span class="ml-3 font-medium">เปิดร้านค้า</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1 ml-8">เมื่อปิด ลูกค้าจะไม่สามารถสั่งซื้อสินค้าได้</p>
                </div>
            </div>
        </div>
        
        <!-- Shipping -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-truck text-blue-500 mr-2"></i>ค่าจัดส่ง
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ค่าจัดส่ง (บาท)</label>
                        <input type="number" name="shipping_fee" value="<?= $settings['shipping_fee'] ?? 50 ?>" min="0" step="1"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ส่งฟรีเมื่อซื้อขั้นต่ำ (บาท)</label>
                        <input type="number" name="free_shipping_min" value="<?= $settings['free_shipping_min'] ?? 500 ?>" min="0" step="1"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">ใส่ 0 ถ้าไม่มีโปรโมชั่นส่งฟรี</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-credit-card text-purple-500 mr-2"></i>ช่องทางชำระเงิน
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">พร้อมเพย์</label>
                    <input type="text" name="promptpay_number" value="<?= htmlspecialchars($settings['promptpay_number'] ?? '') ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="เบอร์โทรหรือเลขบัตรประชาชน">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">บัญชีธนาคาร</label>
                    <div id="bankAccountsContainer">
                        <?php if (empty($bankAccounts)): ?>
                        <div class="bank-row grid grid-cols-12 gap-2 mb-2">
                            <input type="text" name="bank_name[]" placeholder="ชื่อธนาคาร" class="col-span-3 px-3 py-2 border rounded-lg text-sm">
                            <input type="text" name="bank_account[]" placeholder="เลขบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
                            <input type="text" name="bank_holder[]" placeholder="ชื่อบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
                            <button type="button" onclick="removeBank(this)" class="col-span-1 text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                        </div>
                        <?php else: ?>
                        <?php foreach ($bankAccounts as $bank): ?>
                        <div class="bank-row grid grid-cols-12 gap-2 mb-2">
                            <input type="text" name="bank_name[]" value="<?= htmlspecialchars($bank['name']) ?>" placeholder="ชื่อธนาคาร" class="col-span-3 px-3 py-2 border rounded-lg text-sm">
                            <input type="text" name="bank_account[]" value="<?= htmlspecialchars($bank['account']) ?>" placeholder="เลขบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
                            <input type="text" name="bank_holder[]" value="<?= htmlspecialchars($bank['holder']) ?>" placeholder="ชื่อบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
                            <button type="button" onclick="removeBank(this)" class="col-span-1 text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addBank()" class="mt-2 text-sm text-green-600 hover:text-green-700">
                        <i class="fas fa-plus mr-1"></i>เพิ่มบัญชีธนาคาร
                    </button>
                </div>
            </div>
        </div>
        
        <button type="submit" class="w-full py-3 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition">
            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
        </button>
    </form>
</div>

<script>
function addBank() {
    const container = document.getElementById('bankAccountsContainer');
    const row = document.createElement('div');
    row.className = 'bank-row grid grid-cols-12 gap-2 mb-2';
    row.innerHTML = `
        <input type="text" name="bank_name[]" placeholder="ชื่อธนาคาร" class="col-span-3 px-3 py-2 border rounded-lg text-sm">
        <input type="text" name="bank_account[]" placeholder="เลขบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
        <input type="text" name="bank_holder[]" placeholder="ชื่อบัญชี" class="col-span-4 px-3 py-2 border rounded-lg text-sm">
        <button type="button" onclick="removeBank(this)" class="col-span-1 text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}

function removeBank(btn) {
    btn.closest('.bank-row').remove();
}
</script>

<?php require_once '../includes/user_footer.php'; ?>
