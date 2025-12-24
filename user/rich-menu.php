<?php
/**
 * User Rich Menu - จัดการ Rich Menu
 */
$pageTitle = 'Rich Menu';
require_once '../includes/user_header.php';

// Get rich menus
$stmt = $db->prepare("SELECT * FROM rich_menus WHERE line_account_id = ? ORDER BY id DESC");
$stmt->execute([$currentBotId]);
$richMenus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4 flex justify-between items-center">
    <div>
        <span class="text-gray-600">Rich Menu ทั้งหมด <?= count($richMenus) ?> รายการ</span>
    </div>
    <a href="../rich-menu.php" target="_blank" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
        <i class="fas fa-external-link-alt mr-2"></i>จัดการ Rich Menu
    </a>
</div>

<div class="bg-white rounded-xl shadow p-6">
    <div class="text-center py-8">
        <i class="fas fa-th-large text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 mb-4">การจัดการ Rich Menu ต้องใช้งานผ่านหน้า Admin</p>
        <p class="text-sm text-gray-400">กรุณาติดต่อผู้ดูแลระบบเพื่อตั้งค่า Rich Menu</p>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
