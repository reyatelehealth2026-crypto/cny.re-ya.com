<?php
/**
 * User Profile - โปรไฟล์ผู้ใช้
 */
$pageTitle = 'โปรไฟล์';
require_once '../includes/user_header.php';

$success = '';
$error = '';

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($displayName) || empty($email)) {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'รูปแบบอีเมลไม่ถูกต้อง';
        } else {
            // Check if email is taken by another user
            $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $currentUser['id']]);
            if ($stmt->fetch()) {
                $error = 'อีเมลนี้ถูกใช้แล้ว';
            } else {
                $stmt = $db->prepare("UPDATE admin_users SET display_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$displayName, $email, $currentUser['id']]);
                
                $_SESSION['admin_user']['display_name'] = $displayName;
                $_SESSION['admin_user']['email'] = $email;
                $currentUser = $_SESSION['admin_user'];
                
                $success = 'อัพเดทโปรไฟล์สำเร็จ';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            $error = 'กรุณากรอกรหัสผ่าน';
        } elseif (strlen($newPassword) < 6) {
            $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $user['password'])) {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $currentUser['id']]);
                
                $success = 'เปลี่ยนรหัสผ่านสำเร็จ';
            }
        }
    }
}

// Get user data
$stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-2xl mx-auto">
    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-600 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- Profile Info -->
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold"><i class="fas fa-user text-green-500 mr-2"></i>ข้อมูลโปรไฟล์</h2>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>
                <input type="text" value="<?= htmlspecialchars($userData['username']) ?>" disabled 
                       class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500">
                <p class="text-xs text-gray-400 mt-1">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อที่แสดง</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($userData['display_name']) ?>" required 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
        </form>
    </div>
    
    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold"><i class="fas fa-lock text-orange-500 mr-2"></i>เปลี่ยนรหัสผ่าน</h2>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="change_password">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านปัจจุบัน</label>
                <input type="password" name="current_password" required 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่</label>
                <input type="password" name="new_password" required minlength="6"
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">อย่างน้อย 6 ตัวอักษร</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" required 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
            </button>
        </form>
    </div>
    
    <!-- LINE Account Info -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold"><i class="fab fa-line text-green-500 mr-2"></i>บัญชี LINE ที่ใช้งาน</h2>
        </div>
        <div class="p-6">
            <?php if ($lineAccount): ?>
            <div class="flex items-center">
                <div class="w-16 h-16 rounded-xl bg-green-100 flex items-center justify-center overflow-hidden">
                    <?php if ($lineAccount['picture_url']): ?>
                    <img src="<?= htmlspecialchars($lineAccount['picture_url']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <i class="fab fa-line text-green-500 text-3xl"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-4">
                    <div class="font-semibold text-lg"><?= htmlspecialchars($lineAccount['name']) ?></div>
                    <?php if ($lineAccount['basic_id']): ?>
                    <div class="text-gray-500"><?= htmlspecialchars($lineAccount['basic_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4 p-4 bg-blue-50 rounded-lg text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                หากต้องการเปลี่ยนบัญชี LINE กรุณาติดต่อผู้ดูแลระบบ
            </div>
            <?php else: ?>
            <div class="text-center text-gray-400">
                <i class="fab fa-line text-5xl mb-4"></i>
                <p>ยังไม่ได้เลือกบัญชี LINE</p>
                <a href="../auth/setup-account.php" class="mt-4 inline-block px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    เลือกบัญชี LINE
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
