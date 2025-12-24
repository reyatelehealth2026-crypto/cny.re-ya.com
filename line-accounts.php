<?php
/**
 * LINE Accounts Management - Complete Settings
 * จัดการบัญชี LINE OA ครบทุกฟังก์ชัน
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();

// Auto-migrate columns
try {
    $cols = $db->query("SHOW COLUMNS FROM line_accounts")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('bot_mode', $cols)) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN bot_mode ENUM('shop', 'general', 'auto_reply_only') DEFAULT 'shop'");
    }
    if (!in_array('welcome_message', $cols)) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN welcome_message TEXT");
    }
    if (!in_array('auto_reply_enabled', $cols)) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN auto_reply_enabled TINYINT(1) DEFAULT 1");
    }
    if (!in_array('shop_enabled', $cols)) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN shop_enabled TINYINT(1) DEFAULT 1");
    }
    if (!in_array('rich_menu_id', $cols)) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN rich_menu_id VARCHAR(100)");
    }
} catch (Exception $e) {}

$manager = new LineAccountManager($db);
$pageTitle = 'ตั้งค่าบัญชี LINE';

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'test') {
            $result = $manager->testConnection($_POST['id']);
            echo json_encode($result);
            exit;
        }
        if ($action === 'get_rich_menus') {
            $account = $manager->getAccount($_POST['id']);
            $line = new LineAPI($account['channel_access_token'], $account['channel_secret']);
            $menus = $line->getRichMenuList();
            echo json_encode(['success' => true, 'menus' => $menus]);
            exit;
        }
        if ($action === 'set_rich_menu') {
            $account = $manager->getAccount($_POST['id']);
            $line = new LineAPI($account['channel_access_token'], $account['channel_secret']);
            $result = $line->setDefaultRichMenu($_POST['rich_menu_id']);
            if ($result) {
                $db->prepare("UPDATE line_accounts SET rich_menu_id = ? WHERE id = ?")->execute([$_POST['rich_menu_id'], $_POST['id']]);
            }
            echo json_encode(['success' => $result]);
            exit;
        }
        if ($action === 'get_bot_info') {
            $account = $manager->getAccount($_POST['id']);
            $line = new LineAPI($account['channel_access_token'], $account['channel_secret']);
            $info = $line->getBotInfo();
            echo json_encode(['success' => true, 'info' => $info]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $manager->createAccount([
            'name' => $_POST['name'],
            'channel_id' => $_POST['channel_id'],
            'channel_secret' => $_POST['channel_secret'],
            'channel_access_token' => $_POST['channel_access_token'],
            'basic_id' => $_POST['basic_id'] ?? '',
            'liff_id' => $_POST['liff_id'] ?? null,
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'bot_mode' => $_POST['bot_mode'] ?? 'shop',
            'welcome_message' => $_POST['welcome_message'] ?? '',
            'auto_reply_enabled' => isset($_POST['auto_reply_enabled']) ? 1 : 0,
            'shop_enabled' => isset($_POST['shop_enabled']) ? 1 : 0,
        ]);
        header('Location: line-accounts.php?success=created');
        exit;
    } elseif ($action === 'update') {
        $manager->updateAccount($_POST['id'], [
            'name' => $_POST['name'],
            'channel_id' => $_POST['channel_id'],
            'channel_secret' => $_POST['channel_secret'],
            'channel_access_token' => $_POST['channel_access_token'],
            'basic_id' => $_POST['basic_id'] ?? '',
            'liff_id' => $_POST['liff_id'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'bot_mode' => $_POST['bot_mode'] ?? 'shop',
            'welcome_message' => $_POST['welcome_message'] ?? '',
            'auto_reply_enabled' => isset($_POST['auto_reply_enabled']) ? 1 : 0,
            'shop_enabled' => isset($_POST['shop_enabled']) ? 1 : 0,
        ]);
        header('Location: line-accounts.php?success=updated');
        exit;
    } elseif ($action === 'delete') {
        $manager->deleteAccount($_POST['id']);
        header('Location: line-accounts.php?success=deleted');
        exit;
    } elseif ($action === 'set_default') {
        $manager->setDefault($_POST['id']);
        header('Location: line-accounts.php?success=default');
        exit;
    }
}

$accounts = $manager->getAllAccounts();
require_once 'includes/header.php';
?>

<style>
.account-card { transition: all 0.2s; }
.account-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.tab-btn.active { border-bottom: 2px solid #10B981; color: #10B981; }
</style>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <p class="text-gray-600">จัดการบัญชี LINE Official Account และตั้งค่าต่างๆ</p>
    </div>
    <button onclick="openModal()" class="px-5 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 shadow-lg hover:shadow-xl transition">
        <i class="fas fa-plus mr-2"></i>เพิ่มบัญชี LINE
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg flex items-center">
    <i class="fas fa-check-circle mr-2"></i>
    <?= ['created'=>'เพิ่มบัญชีสำเร็จ','updated'=>'อัพเดทสำเร็จ','deleted'=>'ลบสำเร็จ','default'=>'ตั้งเป็นบัญชีหลักสำเร็จ'][$_GET['success']] ?? 'สำเร็จ' ?>
</div>
<?php endif; ?>

<!-- Account Cards -->
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php foreach ($accounts as $account): 
        $botMode = $account['bot_mode'] ?? 'shop';
        $modeInfo = [
            'shop' => ['icon'=>'🛒','label'=>'ร้านค้า','color'=>'purple'],
            'general' => ['icon'=>'💬','label'=>'ทั่วไป','color'=>'blue'],
            'auto_reply_only' => ['icon'=>'🤖','label'=>'Auto Reply','color'=>'orange']
        ][$botMode] ?? ['icon'=>'❓','label'=>$botMode,'color'=>'gray'];
    ?>
    <div class="account-card bg-white rounded-2xl shadow-lg overflow-hidden <?= $account['is_default'] ? 'ring-2 ring-green-500' : '' ?>">
        <!-- Header -->
        <div class="p-5 bg-gradient-to-r from-green-500 to-emerald-600 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php if ($account['picture_url']): ?>
                    <img src="<?= htmlspecialchars($account['picture_url']) ?>" class="w-14 h-14 rounded-full border-2 border-white shadow">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fab fa-line text-3xl"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($account['name']) ?></h3>
                        <p class="text-green-100 text-sm"><?= htmlspecialchars($account['basic_id'] ?: 'ไม่มี Basic ID') ?></p>
                    </div>
                </div>
                <?php if ($account['is_default']): ?>
                <span class="px-3 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">⭐ หลัก</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Status Badges -->
        <div class="px-5 py-3 bg-gray-50 flex flex-wrap gap-2">
            <span class="px-2 py-1 text-xs rounded-full <?= $account['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $account['is_active'] ? '✓ Active' : '✗ Inactive' ?>
            </span>
            <span class="px-2 py-1 text-xs rounded-full bg-<?= $modeInfo['color'] ?>-100 text-<?= $modeInfo['color'] ?>-700">
                <?= $modeInfo['icon'] ?> <?= $modeInfo['label'] ?>
            </span>
            <?php if (!empty($account['liff_id'])): ?>
            <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">📱 LIFF</span>
            <?php endif; ?>
            <?php if ($account['auto_reply_enabled'] ?? 1): ?>
            <span class="px-2 py-1 text-xs rounded-full bg-cyan-100 text-cyan-700">🤖 Auto Reply</span>
            <?php endif; ?>
        </div>
        
        <!-- Info -->
        <div class="p-5 space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Channel ID</span>
                <span class="font-mono text-gray-700"><?= htmlspecialchars($account['channel_id'] ?? '-') ?></span>
            </div>
            <?php if (!empty($account['liff_id'])): ?>
            <div class="flex justify-between">
                <span class="text-gray-500">LIFF ID</span>
                <span class="font-mono text-green-600 text-xs"><?= htmlspecialchars($account['liff_id']) ?></span>
            </div>
            <div>
                <span class="text-gray-500 text-xs">LIFF Main URL (สำหรับ Rich Menu):</span>
                <div class="flex mt-1">
                    <input type="text" readonly value="https://liff.line.me/<?= htmlspecialchars($account['liff_id']) ?>" 
                           class="flex-1 text-xs bg-green-50 border-0 rounded-l px-2 py-1.5 font-mono text-green-700" id="liff_<?= $account['id'] ?>">
                    <button onclick="copyLiffMain(<?= $account['id'] ?>)" class="px-3 bg-green-100 hover:bg-green-200 rounded-r text-green-600">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <span class="text-gray-500 text-xs">Webhook URL:</span>
                <div class="flex mt-1">
                    <input type="text" readonly value="<?= BASE_URL ?>webhook.php?account=<?= $account['id'] ?>" 
                           class="flex-1 text-xs bg-gray-100 border-0 rounded-l px-2 py-1.5 font-mono" id="webhook_<?= $account['id'] ?>">
                    <button onclick="copyWebhook(<?= $account['id'] ?>)" class="px-3 bg-gray-200 hover:bg-gray-300 rounded-r text-gray-600">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="px-5 pb-5 grid grid-cols-4 gap-2">
            <button onclick="testConnection(<?= $account['id'] ?>)" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 text-center" title="ทดสอบ">
                <i class="fas fa-plug"></i>
            </button>
            <button onclick='editAccount(<?= json_encode($account) ?>)' class="p-2 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 text-center" title="แก้ไข">
                <i class="fas fa-cog"></i>
            </button>
            <button onclick="showStats(<?= $account['id'] ?>)" class="p-2 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 text-center" title="สถิติ">
                <i class="fas fa-chart-bar"></i>
            </button>
            <?php if (!$account['is_default']): ?>
            <form method="POST" class="contents">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="id" value="<?= $account['id'] ?>">
                <button type="submit" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100 text-center" title="ตั้งเป็นหลัก">
                    <i class="fas fa-star"></i>
                </button>
            </form>
            <?php else: ?>
            <div class="p-2 bg-yellow-100 text-yellow-600 rounded-lg text-center">
                <i class="fas fa-star"></i>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($accounts)): ?>
    <div class="col-span-full">
        <div class="text-center py-16 bg-white rounded-2xl shadow">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fab fa-line text-4xl text-green-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีบัญชี LINE</h3>
            <p class="text-gray-500 mb-6">เริ่มต้นเพิ่มบัญชี LINE Official Account แรกของคุณ</p>
            <button onclick="openModal()" class="px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 shadow-lg">
                <i class="fas fa-plus mr-2"></i>เพิ่มบัญชีแรก
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- Main Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <form method="POST" id="accountForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId">
            
            <!-- Header -->
            <div class="p-5 border-b flex justify-between items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white">
                <h3 class="text-lg font-bold" id="modalTitle"><i class="fab fa-line mr-2"></i>เพิ่มบัญชี LINE</h3>
                <button type="button" onclick="closeModal()" class="w-8 h-8 rounded-full hover:bg-white/20 flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="flex border-b bg-gray-50">
                <button type="button" onclick="showTab('basic')" class="tab-btn active flex-1 py-3 text-sm font-medium" data-tab="basic">
                    <i class="fas fa-info-circle mr-1"></i>ข้อมูลพื้นฐาน
                </button>
                <button type="button" onclick="showTab('settings')" class="tab-btn flex-1 py-3 text-sm font-medium" data-tab="settings">
                    <i class="fas fa-cog mr-1"></i>ตั้งค่า
                </button>
                <button type="button" onclick="showTab('advanced')" class="tab-btn flex-1 py-3 text-sm font-medium" data-tab="advanced">
                    <i class="fas fa-sliders-h mr-1"></i>ขั้นสูง
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-5">
                <!-- Tab: Basic -->
                <div id="tab-basic" class="tab-content space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1">ชื่อบัญชี <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" required class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="เช่น ร้านค้า A">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1">LINE Basic ID</label>
                            <input type="text" name="basic_id" id="basic_id" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="@yourshop">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Channel ID</label>
                        <input type="text" name="channel_id" id="channel_id" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 font-mono" placeholder="1234567890">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Channel Secret <span class="text-red-500">*</span></label>
                        <input type="text" name="channel_secret" id="channel_secret" required class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 font-mono text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Channel Access Token <span class="text-red-500">*</span></label>
                        <textarea name="channel_access_token" id="channel_access_token" required rows="3" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 font-mono text-xs"></textarea>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-xl">
                        <p class="font-medium text-blue-700 mb-2"><i class="fas fa-info-circle mr-1"></i>วิธีรับ Credentials</p>
                        <ol class="list-decimal list-inside text-blue-600 text-sm space-y-1">
                            <li>ไปที่ <a href="https://developers.line.biz/console/" target="_blank" class="underline font-medium">LINE Developers Console</a></li>
                            <li>เลือก Provider → Channel (Messaging API)</li>
                            <li>คัดลอก Channel ID, Channel Secret</li>
                            <li>ไปที่ Messaging API → Issue Channel Access Token</li>
                        </ol>
                    </div>
                </div>
                
                <!-- Tab: Settings -->
                <div id="tab-settings" class="tab-content space-y-4 hidden">
                    <div>
                        <label class="block text-sm font-medium mb-2">โหมดบอท <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer hover:border-green-300 transition has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                <input type="radio" name="bot_mode" value="shop" checked class="mt-1 mr-3 text-green-500">
                                <div>
                                    <span class="font-semibold text-gray-800">🛒 โหมดร้านค้า</span>
                                    <p class="text-xs text-gray-500 mt-1">ระบบร้านค้าเต็มรูปแบบ: สินค้า, ตะกร้า, สั่งซื้อ, Auto Reply, Broadcast, CRM</p>
                                </div>
                            </label>
                            <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="bot_mode" value="general" class="mt-1 mr-3 text-blue-500">
                                <div>
                                    <span class="font-semibold text-gray-800">💬 โหมดทั่วไป</span>
                                    <p class="text-xs text-gray-500 mt-1">ไม่มีระบบร้านค้า: Auto Reply, Broadcast, CRM เท่านั้น</p>
                                </div>
                            </label>
                            <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer hover:border-orange-300 transition has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                                <input type="radio" name="bot_mode" value="auto_reply_only" class="mt-1 mr-3 text-orange-500">
                                <div>
                                    <span class="font-semibold text-gray-800">🤖 Auto Reply เท่านั้น</span>
                                    <p class="text-xs text-gray-500 mt-1">ตอบกลับอัตโนมัติตาม keyword เท่านั้น</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">ข้อความต้อนรับ</label>
                        <textarea name="welcome_message" id="welcome_message" rows="3" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="ข้อความที่จะส่งเมื่อมีคนเพิ่มเพื่อน..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">ใช้ {name} แทนชื่อผู้ใช้</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="auto_reply_enabled" id="auto_reply_enabled" checked class="mr-3 w-5 h-5 text-green-500 rounded">
                            <div>
                                <span class="font-medium">🤖 Auto Reply</span>
                                <p class="text-xs text-gray-500">เปิดระบบตอบกลับอัตโนมัติ</p>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="shop_enabled" id="shop_enabled" checked class="mr-3 w-5 h-5 text-green-500 rounded">
                            <div>
                                <span class="font-medium">🛒 ร้านค้า</span>
                                <p class="text-xs text-gray-500">เปิดระบบร้านค้า</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" checked class="mr-2 w-5 h-5 text-green-500 rounded">
                            <span>เปิดใช้งาน</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" id="is_default" class="mr-2 w-5 h-5 text-green-500 rounded">
                            <span>ตั้งเป็นบัญชีหลัก</span>
                        </label>
                    </div>
                </div>
                
                <!-- Tab: Advanced -->
                <div id="tab-advanced" class="tab-content space-y-4 hidden">
                    <div class="bg-green-50 p-4 rounded-xl mb-4">
                        <p class="font-medium text-green-700 mb-2"><i class="fas fa-magic mr-1"></i>Unified LIFF (แนะนำ)</p>
                        <p class="text-green-600 text-sm">ใช้ LIFF ID เดียวสำหรับทุกฟังก์ชัน - สมัครสมาชิก, ซื้อสินค้า, แลกแต้ม, นัดหมาย ฯลฯ</p>
                    </div>
                    
                    <!-- Unified LIFF ID -->
                    <div class="p-5 border-2 border-green-300 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50">
                        <label class="block text-sm font-medium mb-2 text-green-700">
                            <i class="fas fa-mobile-alt mr-1"></i>LIFF ID (Unified)
                        </label>
                        <input type="text" name="liff_id" id="liff_id" class="w-full px-4 py-3 border-2 border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono text-lg" placeholder="2006xxxxxx-xxxxxxxx">
                        <p class="text-sm text-green-600 mt-2">
                            <i class="fas fa-link mr-1"></i>Endpoint URL: <code class="bg-white px-2 py-1 rounded"><?= BASE_URL ?>liff-app.php</code>
                        </p>
                    </div>
                    
                    <!-- LIFF URL for Rich Menu -->
                    <div id="liffUrlSection" class="bg-white border-2 border-green-200 rounded-xl p-4 hidden">
                        <p class="font-medium text-green-700 mb-3"><i class="fas fa-qrcode mr-1"></i>LIFF URL สำหรับ Rich Menu</p>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly id="liffFullUrl" class="flex-1 px-4 py-2.5 bg-green-50 border border-green-200 rounded-lg font-mono text-sm text-green-700">
                            <button type="button" onclick="copyLiffUrl()" class="px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600">
                                <i class="fas fa-copy mr-1"></i>คัดลอก
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">ใช้ URL นี้ตั้งค่าใน Rich Menu ของ LINE Official Account</p>
                    </div>
                    
                    <!-- Available Pages -->
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="font-medium text-gray-700 mb-3"><i class="fas fa-list mr-1"></i>หน้าที่รองรับใน Unified LIFF</p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>หน้าหลัก (เภสัชกรว่าง)</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>บัตรสมาชิก</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>ร้านค้า / สินค้า</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>ออเดอร์ของฉัน</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>ประวัติแต้ม</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>แลกของรางวัล</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>นัดหมายเภสัชกร</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>การนัดหมายของฉัน</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 p-4 rounded-xl">
                        <p class="font-medium text-yellow-700 mb-2"><i class="fas fa-lightbulb mr-1"></i>วิธีสร้าง LIFF App</p>
                        <ol class="text-yellow-600 text-sm list-decimal list-inside space-y-1">
                            <li>ไปที่ <a href="https://developers.line.biz/console/" target="_blank" class="underline font-medium">LINE Developers Console</a></li>
                            <li>เลือก Provider → Channel (LINE Login)</li>
                            <li>ไปที่ LIFF → Add</li>
                            <li>ตั้งชื่อ เช่น "Unified App"</li>
                            <li>Size: <strong>Full</strong> (แนะนำ)</li>
                            <li>Endpoint URL: <code class="bg-white px-1 rounded"><?= BASE_URL ?>liff-app.php</code></li>
                            <li>Scopes: openid, profile</li>
                            <li>คัดลอก LIFF ID มาใส่ในช่องด้านบน</li>
                        </ol>
                    </div>xxxxxx">
                    
                    <div id="richMenuSection" class="hidden">
                        <label class="block text-sm font-medium mb-2">Rich Menu</label>
                        <div id="richMenuList" class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-gray-500 text-sm">กดบันทึกก่อนเพื่อจัดการ Rich Menu</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-5 border-t flex justify-between bg-gray-50">
                <button type="button" id="deleteBtn" onclick="deleteAccount()" class="px-4 py-2 text-red-500 hover:bg-red-50 rounded-lg hidden">
                    <i class="fas fa-trash mr-1"></i>ลบบัญชี
                </button>
                <div class="flex gap-2 ml-auto">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 border rounded-lg hover:bg-gray-100">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 shadow">
                        <i class="fas fa-save mr-1"></i>บันทึก
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Test Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6">
        <div id="testResult" class="text-center py-8"></div>
        <button onclick="closeTestModal()" class="w-full mt-4 px-4 py-2.5 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium">ปิด</button>
    </div>
</div>

<!-- Stats Modal -->
<div id="statsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden">
        <div class="p-5 border-b bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
            <h3 class="font-bold"><i class="fas fa-chart-bar mr-2"></i>สถิติบัญชี</h3>
        </div>
        <div id="statsContent" class="p-6"></div>
        <div class="p-4 border-t bg-gray-50">
            <button onclick="closeStatsModal()" class="w-full px-4 py-2.5 bg-gray-200 rounded-lg hover:bg-gray-300 font-medium">ปิด</button>
        </div>
    </div>
</div>

<script>
let currentAccountId = null;

function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').innerHTML = '<i class="fab fa-line mr-2"></i>เพิ่มบัญชี LINE';
    document.getElementById('deleteBtn').classList.add('hidden');
    document.getElementById('richMenuSection').classList.add('hidden');
    document.getElementById('accountForm').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('auto_reply_enabled').checked = true;
    document.getElementById('shop_enabled').checked = true;
    showTab('basic');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editAccount(account) {
    currentAccountId = account.id;
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = account.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-cog mr-2"></i>ตั้งค่าบัญชี: ' + account.name;
    document.getElementById('deleteBtn').classList.remove('hidden');
    document.getElementById('richMenuSection').classList.remove('hidden');
    
    document.getElementById('name').value = account.name || '';
    document.getElementById('channel_id').value = account.channel_id || '';
    document.getElementById('channel_secret').value = account.channel_secret || '';
    document.getElementById('channel_access_token').value = account.channel_access_token || '';
    document.getElementById('basic_id').value = account.basic_id || '';
    document.getElementById('liff_id').value = account.liff_id || '';
    document.getElementById('welcome_message').value = account.welcome_message || '';
    document.getElementById('is_active').checked = account.is_active == 1;
    document.getElementById('is_default').checked = account.is_default == 1;
    document.getElementById('auto_reply_enabled').checked = account.auto_reply_enabled != 0;
    document.getElementById('shop_enabled').checked = account.shop_enabled != 0;
    
    document.querySelectorAll('input[name="bot_mode"]').forEach(el => {
        el.checked = el.value === (account.bot_mode || 'shop');
    });
    
    // Show LIFF URL if LIFF ID exists
    updateLiffUrl();
    
    showTab('basic');
}

// Update LIFF URL when LIFF ID changes
document.getElementById('liff_id')?.addEventListener('input', updateLiffUrl);

function updateLiffUrl() {
    const liffId = document.getElementById('liff_id')?.value;
    const urlSection = document.getElementById('liffUrlSection');
    const urlInput = document.getElementById('liffFullUrl');
    
    if (liffId && liffId.trim()) {
        const url = 'https://liff.line.me/' + liffId.trim();
        urlInput.value = url;
        urlSection.classList.remove('hidden');
    } else {
        urlSection.classList.add('hidden');
    }
}

function copyLiffUrl() {
    const urlInput = document.getElementById('liffFullUrl');
    urlInput.select();
    document.execCommand('copy');
    alert('คัดลอก LIFF URL แล้ว!');
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('คัดลอกแล้ว!');
    }).catch(() => {
        // Fallback
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('คัดลอกแล้ว!');
    });
}

function deleteAccount() {
    if (!currentAccountId || !confirm('ต้องการลบบัญชีนี้? ข้อมูลทั้งหมดจะถูกลบ')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${currentAccountId}">`;
    document.body.appendChild(form);
    form.submit();
}

function copyWebhook(id) {
    const input = document.getElementById('webhook_' + id);
    input.select();
    document.execCommand('copy');
    alert('คัดลอก Webhook URL แล้ว!');
}

function copyLiffMain(id) {
    const input = document.getElementById('liff_' + id);
    input.select();
    document.execCommand('copy');
    alert('คัดลอก LIFF Main URL แล้ว!\n\nใช้ URL นี้ตั้งค่า Rich Menu ใน LINE Official Account Manager');
}

function testConnection(accountId) {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
    document.getElementById('testResult').innerHTML = '<i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i><p class="mt-3 text-gray-500">กำลังทดสอบ...</p>';
    
    fetch('line-accounts.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: `action=test&id=${accountId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('testResult').innerHTML = `
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                <h3 class="text-xl font-bold text-green-600">เชื่อมต่อสำเร็จ!</h3>
                ${data.data?.displayName ? `<p class="text-gray-600 mt-2 text-lg">${data.data.displayName}</p>` : ''}
                ${data.data?.pictureUrl ? `<img src="${data.data.pictureUrl}" class="w-20 h-20 rounded-full mx-auto mt-4 border-4 border-green-200">` : ''}
            `;
        } else {
            document.getElementById('testResult').innerHTML = `
                <i class="fas fa-times-circle text-6xl text-red-500 mb-4"></i>
                <h3 class="text-xl font-bold text-red-600">เชื่อมต่อไม่สำเร็จ</h3>
                <p class="text-gray-600 mt-2">${data.message || 'กรุณาตรวจสอบ credentials'}</p>
            `;
        }
    })
    .catch(err => {
        document.getElementById('testResult').innerHTML = `<i class="fas fa-exclamation-triangle text-6xl text-yellow-500 mb-4"></i><p class="text-gray-600">${err.message}</p>`;
    });
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

function showStats(accountId) {
    document.getElementById('statsModal').classList.remove('hidden');
    document.getElementById('statsModal').classList.add('flex');
    document.getElementById('statsContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';
    
    // Fetch stats from database
    fetch(`api/ajax_handler.php?action=account_stats&account_id=${accountId}`)
    .then(r => r.json())
    .then(data => {
        document.getElementById('statsContent').innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600">${data.users || 0}</div>
                    <div class="text-sm text-blue-500">ผู้ใช้ทั้งหมด</div>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-green-600">${data.messages || 0}</div>
                    <div class="text-sm text-green-500">ข้อความ</div>
                </div>
                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600">${data.orders || 0}</div>
                    <div class="text-sm text-purple-500">ออเดอร์</div>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-yellow-600">฿${(data.revenue || 0).toLocaleString()}</div>
                    <div class="text-sm text-yellow-500">รายได้</div>
                </div>
            </div>
        `;
    })
    .catch(() => {
        document.getElementById('statsContent').innerHTML = '<p class="text-center text-gray-500">ไม่สามารถโหลดข้อมูลได้</p>';
    });
}

function closeStatsModal() {
    document.getElementById('statsModal').classList.add('hidden');
    document.getElementById('statsModal').classList.remove('flex');
}

// Close modals on backdrop click
['modal', 'testModal', 'statsModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', e => {
        if (e.target.id === id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
