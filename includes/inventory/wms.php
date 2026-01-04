<?php
/**
 * WMS Tab - Pick-Pack-Ship Warehouse Management
 * Tab content for inventory/index.php
 * 
 * Sub-tabs: dashboard, pick, pack, ship, exceptions
 * 
 * @package WMS
 * @version 1.0.0
 */

// Initialize WMS Service
require_once __DIR__ . '/../../classes/WMSService.php';
require_once __DIR__ . '/../../classes/WMSPrintService.php';

$lineAccountId = $_SESSION['current_bot_id'] ?? null;
$wmsService = new WMSService($db, $lineAccountId);
$printService = new WMSPrintService($db, $lineAccountId);

// Get current staff ID
$staffId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1;

// Check if WMS tables exist
$wmsTablesExist = false;
try {
    $db->query("SELECT 1 FROM wms_activity_logs LIMIT 1");
    $wmsTablesExist = true;
} catch (Exception $e) {}

// Define WMS sub-tabs
$wmsSubTabs = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
    'pick' => ['label' => 'Pick Queue', 'icon' => 'fas fa-hand-pointer'],
    'pack' => ['label' => 'Pack Station', 'icon' => 'fas fa-box'],
    'ship' => ['label' => 'Ship Queue', 'icon' => 'fas fa-shipping-fast'],
    'exceptions' => ['label' => 'Exceptions', 'icon' => 'fas fa-exclamation-circle'],
];

// Get active WMS sub-tab
$wmsSubTab = $_GET['wms_tab'] ?? 'dashboard';
if (!isset($wmsSubTabs[$wmsSubTab])) {
    $wmsSubTab = 'dashboard';
}

// Get dashboard stats for badges
$dashboardStats = [];
if ($wmsTablesExist) {
    try {
        $dashboardStats = $wmsService->getDashboardStats();
        
        // Add badges to tabs
        $pickCount = $dashboardStats['status_counts']['pending_pick'] ?? 0;
        $packCount = $dashboardStats['status_counts']['picked'] ?? 0;
        $shipCount = ($dashboardStats['status_counts']['packed'] ?? 0) + ($dashboardStats['status_counts']['ready_to_ship'] ?? 0);
        $exceptionCount = $dashboardStats['totals']['on_hold'] ?? 0;
        
        if ($pickCount > 0) $wmsSubTabs['pick']['badge'] = $pickCount;
        if ($packCount > 0) $wmsSubTabs['pack']['badge'] = $packCount;
        if ($shipCount > 0) $wmsSubTabs['ship']['badge'] = $shipCount;
        if ($exceptionCount > 0) {
            $wmsSubTabs['exceptions']['badge'] = $exceptionCount;
            $wmsSubTabs['exceptions']['badgeColor'] = 'red';
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $wmsTablesExist) {
    $action = $_POST['wms_action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'start_pick':
                $orderId = (int)$_POST['order_id'];
                $wmsService->startPicking($orderId, $staffId);
                $response = ['success' => true, 'message' => 'เริ่มหยิบสินค้าแล้ว'];
                break;
                
            case 'complete_pick':
                $orderId = (int)$_POST['order_id'];
                $wmsService->completePicking($orderId);
                $response = ['success' => true, 'message' => 'หยิบสินค้าเสร็จสิ้น'];
                break;
                
            case 'start_pack':
                $orderId = (int)$_POST['order_id'];
                $wmsService->startPacking($orderId, $staffId);
                $response = ['success' => true, 'message' => 'เริ่มแพ็คสินค้าแล้ว'];
                break;
                
            case 'complete_pack':
                $orderId = (int)$_POST['order_id'];
                $packageInfo = [];
                if (!empty($_POST['weight'])) $packageInfo['weight'] = (float)$_POST['weight'];
                if (!empty($_POST['dimensions'])) $packageInfo['dimensions'] = $_POST['dimensions'];
                $wmsService->completePacking($orderId, !empty($packageInfo) ? $packageInfo : null);
                $response = ['success' => true, 'message' => 'แพ็คสินค้าเสร็จสิ้น'];
                break;
                
            case 'assign_tracking':
                $orderId = (int)$_POST['order_id'];
                $carrier = $_POST['carrier'] ?? '';
                $trackingNumber = $_POST['tracking_number'] ?? '';
                $wmsService->assignCarrier($orderId, $carrier, $trackingNumber);
                $response = ['success' => true, 'message' => 'บันทึกเลขพัสดุแล้ว'];
                break;
                
            case 'put_on_hold':
                $orderId = (int)$_POST['order_id'];
                $reason = $_POST['reason'] ?? '';
                $wmsService->putOrderOnHold($orderId, $reason);
                $response = ['success' => true, 'message' => 'พักออเดอร์แล้ว'];
                break;
                
            case 'resolve_exception':
                $orderId = (int)$_POST['order_id'];
                $resolution = $_POST['resolution'] ?? '';
                $newStatus = $_POST['new_status'] ?? null;
                $wmsService->resolveException($orderId, $resolution, $staffId, $newStatus);
                $response = ['success' => true, 'message' => 'แก้ไขปัญหาแล้ว'];
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    $_SESSION['wms_message'] = $response;
    // Use JavaScript redirect since headers already sent by header.php
    $redirectUrl = '?tab=wms&wms_tab=' . $wmsSubTab;
    echo "<script>window.location.href = '{$redirectUrl}';</script>";
    exit;
}

// Get flash message
$wmsMessage = $_SESSION['wms_message'] ?? null;
unset($_SESSION['wms_message']);

if (!$wmsTablesExist): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
    <i class="fas fa-database text-yellow-500 text-4xl mb-3"></i>
    <h3 class="text-lg font-semibold text-yellow-700 mb-2">ยังไม่ได้ติดตั้งระบบ WMS</h3>
    <p class="text-yellow-600 mb-4">กรุณา run migration script เพื่อสร้างตาราง database</p>
    <div class="bg-white rounded-lg p-4 text-left max-w-lg mx-auto">
        <p class="text-sm text-gray-600 mb-2">Run migration:</p>
        <code class="text-xs bg-gray-100 p-2 rounded block">php install/run_wms_migration.php</code>
    </div>
</div>
<?php return; endif; ?>

<div class="space-y-6">
    <!-- Flash Message -->
    <?php if ($wmsMessage): ?>
    <div class="p-4 rounded-lg <?= $wmsMessage['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <i class="fas <?= $wmsMessage['success'] ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($wmsMessage['message']) ?>
    </div>
    <?php endif; ?>

    <!-- WMS Sub-tabs Navigation -->
    <div class="bg-white rounded-xl shadow p-2">
        <div class="flex flex-wrap gap-2">
            <?php foreach ($wmsSubTabs as $key => $subTab): 
                $isActive = ($key === $wmsSubTab);
            ?>
            <a href="?tab=wms&wms_tab=<?= $key ?>" 
               class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition
                      <?= $isActive ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <i class="<?= $subTab['icon'] ?>"></i>
                <span><?= $subTab['label'] ?></span>
                <?php if (isset($subTab['badge'])): ?>
                <span class="px-2 py-0.5 text-xs rounded-full <?= $isActive ? 'bg-white text-purple-600' : 'bg-' . ($subTab['badgeColor'] ?? 'purple') . '-100 text-' . ($subTab['badgeColor'] ?? 'purple') . '-600' ?>">
                    <?= $subTab['badge'] ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sub-tab Content -->
    <?php
    switch ($wmsSubTab) {
        case 'dashboard':
            include __DIR__ . '/wms-dashboard.php';
            break;
        case 'pick':
            include __DIR__ . '/wms-pick.php';
            break;
        case 'pack':
            include __DIR__ . '/wms-pack.php';
            break;
        case 'ship':
            include __DIR__ . '/wms-ship.php';
            break;
        case 'exceptions':
            include __DIR__ . '/wms-exceptions.php';
            break;
        default:
            include __DIR__ . '/wms-dashboard.php';
    }
    ?>
</div>
