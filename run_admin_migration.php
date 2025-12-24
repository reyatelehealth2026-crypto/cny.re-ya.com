<?php
/**
 * Run Admin Users Migration
 * สร้างตารางสำหรับระบบจัดการผู้ดูแล
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Running Admin Users Migration</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:900px;margin:0 auto;} .ok{color:green;} .error{color:red;} .warn{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

try {
    // 1. Check/Create admin_users table
    echo "<h2>1. ตรวจสอบตาราง admin_users</h2>";
    try {
        $db->query("SELECT 1 FROM admin_users LIMIT 1");
        echo "<p class='ok'>✅ admin_users มีอยู่แล้ว</p>";
        
        // Check if role column has super_admin
        $stmt = $db->query("SHOW COLUMNS FROM admin_users LIKE 'role'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($col && strpos($col['Type'], 'super_admin') === false) {
            echo "<p class='warn'>⚠️ กำลังเพิ่ม super_admin ใน role enum...</p>";
            $db->exec("ALTER TABLE admin_users MODIFY COLUMN role ENUM('super_admin', 'admin', 'staff', 'user') DEFAULT 'admin'");
            echo "<p class='ok'>✅ อัพเดท role enum สำเร็จ</p>";
        }
    } catch (Exception $e) {
        echo "<p class='warn'>⚠️ กำลังสร้างตาราง admin_users...</p>";
        $db->exec("
            CREATE TABLE admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                display_name VARCHAR(100),
                avatar VARCHAR(500),
                role ENUM('super_admin', 'admin', 'staff', 'user') DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                last_login TIMESTAMP NULL,
                login_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_role (role),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>✅ สร้าง admin_users สำเร็จ</p>";
    }
    
    // 2. Create admin_bot_access table
    echo "<h2>2. ตรวจสอบตาราง admin_bot_access</h2>";
    try {
        $db->query("SELECT 1 FROM admin_bot_access LIMIT 1");
        echo "<p class='ok'>✅ admin_bot_access มีอยู่แล้ว</p>";
    } catch (Exception $e) {
        echo "<p class='warn'>⚠️ กำลังสร้างตาราง admin_bot_access...</p>";
        $db->exec("
            CREATE TABLE admin_bot_access (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                line_account_id INT NOT NULL,
                can_view TINYINT(1) DEFAULT 1,
                can_edit TINYINT(1) DEFAULT 1,
                can_broadcast TINYINT(1) DEFAULT 1,
                can_manage_users TINYINT(1) DEFAULT 1,
                can_manage_shop TINYINT(1) DEFAULT 1,
                can_view_analytics TINYINT(1) DEFAULT 1,
                granted_by INT,
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_admin_bot (admin_id, line_account_id),
                INDEX idx_admin (admin_id),
                INDEX idx_bot (line_account_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>✅ สร้าง admin_bot_access สำเร็จ</p>";
    }
    
    // 3. Create admin_activity_log table
    echo "<h2>3. ตรวจสอบตาราง admin_activity_log</h2>";
    try {
        $db->query("SELECT 1 FROM admin_activity_log LIMIT 1");
        echo "<p class='ok'>✅ admin_activity_log มีอยู่แล้ว</p>";
    } catch (Exception $e) {
        echo "<p class='warn'>⚠️ กำลังสร้างตาราง admin_activity_log...</p>";
        $db->exec("
            CREATE TABLE admin_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                line_account_id INT,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin (admin_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>✅ สร้าง admin_activity_log สำเร็จ</p>";
    }
    
    // 4. Update first admin to super_admin
    echo "<h2>4. ตั้งค่า Super Admin</h2>";
    $stmt = $db->query("SELECT id, username, role FROM admin_users ORDER BY id ASC LIMIT 1");
    $firstAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($firstAdmin) {
        if ($firstAdmin['role'] !== 'super_admin') {
            $db->exec("UPDATE admin_users SET role = 'super_admin' WHERE id = {$firstAdmin['id']}");
            echo "<p class='ok'>✅ อัพเดท '{$firstAdmin['username']}' เป็น Super Admin</p>";
        } else {
            echo "<p class='ok'>✅ '{$firstAdmin['username']}' เป็น Super Admin อยู่แล้ว</p>";
        }
    } else {
        // Create default super admin
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admin_users (username, password, display_name, role, is_active) VALUES ('admin', '{$defaultPassword}', 'Super Admin', 'super_admin', 1)");
        echo "<p class='ok'>✅ สร้าง Super Admin ใหม่ (admin/password)</p>";
    }
    
    // 5. Verify all tables
    echo "<h2>5. ตรวจสอบผลลัพธ์</h2>";
    $tables = ['admin_users', 'admin_bot_access', 'admin_activity_log'];
    foreach ($tables as $table) {
        try {
            $db->query("SELECT 1 FROM {$table} LIMIT 1");
            echo "<p class='ok'>✅ {$table} - OK</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ {$table} - NOT FOUND</p>";
        }
    }
    
    // 6. Show admins
    echo "<h2>6. รายชื่อผู้ดูแล</h2>";
    $stmt = $db->query("SELECT id, username, display_name, role, is_active FROM admin_users ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f5f5f5;'><th>ID</th><th>Username</th><th>Display Name</th><th>Role</th><th>Active</th></tr>";
    foreach ($admins as $admin) {
        $roleColor = $admin['role'] === 'super_admin' ? 'color:red;font-weight:bold;' : '';
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>{$admin['display_name']}</td>";
        echo "<td style='{$roleColor}'>{$admin['role']}</td>";
        echo "<td>" . ($admin['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Migration completed!</h2>";
    echo "<p><a href='auth/login.php' style='padding:10px 20px;background:#06C755;color:white;text-decoration:none;border-radius:8px;'>→ Go to Login</a></p>";
    echo "<p style='margin-top:10px;'><a href='admin-users.php'>→ Manage Admin Users</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
