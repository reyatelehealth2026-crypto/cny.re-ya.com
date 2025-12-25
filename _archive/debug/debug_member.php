<?php
/**
 * Debug Member Registration
 * ตรวจสอบข้อมูลสมาชิกในระบบ
 */
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Debug Member Registration</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Check users table structure
    echo "<h3>1. Users Table Structure</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasIsRegistered = false;
    $hasLineAccountId = false;
    $hasMemberId = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'is_registered') $hasIsRegistered = true;
        if ($col['Field'] === 'line_account_id') $hasLineAccountId = true;
        if ($col['Field'] === 'member_id') $hasMemberId = true;
    }
    
    echo "<ul>";
    echo "<li>is_registered column: " . ($hasIsRegistered ? "✅ Yes" : "❌ No") . "</li>";
    echo "<li>line_account_id column: " . ($hasLineAccountId ? "✅ Yes" : "❌ No") . "</li>";
    echo "<li>member_id column: " . ($hasMemberId ? "✅ Yes" : "❌ No") . "</li>";
    echo "</ul>";
    
    // 2. Check registered users
    echo "<h3>2. Registered Users</h3>";
    $stmt = $db->query("SELECT id, line_user_id, line_account_id, first_name, last_name, phone, member_id, is_registered, registered_at FROM users WHERE is_registered = 1 ORDER BY id DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>❌ ไม่พบสมาชิกที่ลงทะเบียนแล้ว</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>LINE User ID</th><th>Account ID</th><th>Name</th><th>Phone</th><th>Member ID</th><th>Registered</th><th>Registered At</th></tr>";
        foreach ($users as $u) {
            echo "<tr>";
            echo "<td>{$u['id']}</td>";
            echo "<td>" . substr($u['line_user_id'] ?? '', 0, 10) . "...</td>";
            echo "<td>{$u['line_account_id']}</td>";
            echo "<td>{$u['first_name']} {$u['last_name']}</td>";
            echo "<td>{$u['phone']}</td>";
            echo "<td>{$u['member_id']}</td>";
            echo "<td>" . ($u['is_registered'] ? '✅' : '❌') . "</td>";
            echo "<td>{$u['registered_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check all users (including non-registered)
    echo "<h3>3. All Users (Last 10)</h3>";
    $stmt = $db->query("SELECT id, line_user_id, line_account_id, display_name, first_name, is_registered, created_at FROM users ORDER BY id DESC LIMIT 10");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>LINE User ID</th><th>Account ID</th><th>Display Name</th><th>First Name</th><th>Registered</th><th>Created</th></tr>";
    foreach ($allUsers as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>" . substr($u['line_user_id'] ?? '', 0, 15) . "...</td>";
        echo "<td>{$u['line_account_id']}</td>";
        echo "<td>{$u['display_name']}</td>";
        echo "<td>{$u['first_name']}</td>";
        echo "<td>" . ($u['is_registered'] ? '✅' : '❌') . "</td>";
        echo "<td>{$u['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Test API check
    if (isset($_GET['test_user'])) {
        $testUserId = $_GET['test_user'];
        $testAccountId = $_GET['account'] ?? 1;
        
        echo "<h3>4. Test API Check for: $testUserId</h3>";
        
        $stmt = $db->prepare("
            SELECT id, line_user_id, line_account_id, member_id, is_registered, first_name, last_name
            FROM users 
            WHERE line_user_id = ? AND (line_account_id = ? OR line_account_id IS NULL)
        ");
        $stmt->execute([$testUserId, $testAccountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<pre>" . print_r($result, true) . "</pre>";
        } else {
            echo "<p>❌ ไม่พบ user นี้ในระบบ</p>";
            
            // Try without account filter
            $stmt = $db->prepare("SELECT * FROM users WHERE line_user_id = ?");
            $stmt->execute([$testUserId]);
            $result2 = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result2) {
                echo "<p>⚠️ พบ user แต่ line_account_id ไม่ตรง:</p>";
                echo "<pre>" . print_r($result2, true) . "</pre>";
            }
        }
    }
    
    // 5. Check line_accounts
    echo "<h3>5. LINE Accounts</h3>";
    $stmt = $db->query("SELECT id, name, is_default, is_active FROM line_accounts LIMIT 5");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Default</th><th>Active</th></tr>";
    foreach ($accounts as $a) {
        echo "<tr>";
        echo "<td>{$a['id']}</td>";
        echo "<td>{$a['name']}</td>";
        echo "<td>" . ($a['is_default'] ? '✅' : '') . "</td>";
        echo "<td>" . ($a['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><strong>Test specific user:</strong> Add ?test_user=Uxxxxx&account=1 to URL</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
