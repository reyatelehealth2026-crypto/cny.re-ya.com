<?php
/**
 * Reset Admin Password
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔐 Reset Admin Password</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:500px;margin:0 auto;} .ok{color:green;} .error{color:red;} input,button{padding:10px;margin:5px 0;width:100%;box-sizing:border-box;}</style>";

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $newPassword = $_POST['new_password'];
    
    if (empty($newPassword) || strlen($newPassword) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        $message = "✅ เปลี่ยนรหัสผ่านสำเร็จ! รหัสผ่านใหม่: {$newPassword}";
    }
}

// Get all admin users
$stmt = $db->query("SELECT id, username, display_name, role FROM admin_users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($message): ?>
<div style="padding:15px;background:#d4edda;color:#155724;border-radius:5px;margin-bottom:20px;">
    <?= $message ?>
    <br><br>
    <a href="auth/login.php" style="color:#155724;font-weight:bold;">→ ไปหน้า Login</a>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="padding:15px;background:#f8d7da;color:#721c24;border-radius:5px;margin-bottom:20px;">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST" style="background:#f8f9fa;padding:20px;border-radius:10px;">
    <h3>เลือกผู้ใช้และตั้งรหัสผ่านใหม่</h3>
    
    <label>ผู้ใช้:</label>
    <select name="user_id" style="padding:10px;width:100%;margin-bottom:15px;">
        <?php foreach ($users as $user): ?>
        <option value="<?= $user['id'] ?>">
            <?= htmlspecialchars($user['username']) ?> 
            (<?= htmlspecialchars($user['display_name']) ?>) 
            - <?= $user['role'] ?>
        </option>
        <?php endforeach; ?>
    </select>
    
    <label>รหัสผ่านใหม่:</label>
    <input type="text" name="new_password" value="password" placeholder="รหัสผ่านใหม่">
    
    <button type="submit" style="background:#06C755;color:white;border:none;border-radius:5px;cursor:pointer;margin-top:10px;">
        🔐 เปลี่ยนรหัสผ่าน
    </button>
</form>

<div style="margin-top:20px;padding:15px;background:#fff3cd;border-radius:5px;">
    <strong>⚠️ หมายเหตุ:</strong> หลังจากเปลี่ยนรหัสผ่านแล้ว ควรลบไฟล์นี้ทิ้งเพื่อความปลอดภัย
</div>
