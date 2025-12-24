<?php
/**
 * Check Session - ดูข้อมูล session ปัจจุบัน
 */
session_start();

echo "<h2>🔐 Session Info</h2>";

if (isset($_SESSION['admin_user'])) {
    echo "<p style='color:green'>✅ Logged in</p>";
    echo "<pre>" . print_r($_SESSION['admin_user'], true) . "</pre>";
    
    $role = $_SESSION['admin_user']['role'] ?? 'unknown';
    echo "<p><strong>Role:</strong> {$role}</p>";
    
    if ($role === 'user') {
        echo "<p style='color:orange'>⚠️ คุณ login เป็น 'user' - จะถูก redirect ไป user/dashboard.php เมื่อเข้าหน้า admin</p>";
        echo "<p>ถ้าต้องการเข้าหน้า users.php ต้อง login เป็น 'admin'</p>";
    } else if ($role === 'admin') {
        echo "<p style='color:green'>✅ คุณเป็น admin - สามารถเข้าหน้า users.php ได้</p>";
    }
} else {
    echo "<p style='color:red'>❌ Not logged in</p>";
}

echo "<h3>Full Session</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
