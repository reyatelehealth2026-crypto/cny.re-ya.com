<?php
/**
 * Debug Member Card - ตรวจสอบปัญหาการแสดงบัตรสมาชิก
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Member Card</h2>";

// 1. Check users table structure
echo "<h3>1. Users Table Structure (is_registered column)</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_registered'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col) {
        echo "<pre>" . print_r($col, true) . "</pre>";
    } else {
        echo "<p style='color:red'>❌ Column 'is_registered' NOT FOUND!</p>";
        
        // Add column if missing
        echo "<p>Adding column...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN is_registered TINYINT(1) DEFAULT 0");
        echo "<p style='color:green'>✅ Column added!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Check recent users
echo "<h3>2. Recent Users (last 10)</h3>";
try {
    $stmt = $db->query("SELECT id, line_user_id, display_name, first_name, member_id, is_registered, registered_at, created_at FROM users ORDER BY id DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>LINE User ID</th><th>Display Name</th><th>First Name</th><th>Member ID</th><th>is_registered</th><th>Registered At</th></tr>";
    foreach ($users as $u) {
        $regStatus = $u['is_registered'] ? '✅ Yes' : '❌ No';
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>" . substr($u['line_user_id'] ?? '', 0, 10) . "...</td>";
        echo "<td>{$u['display_name']}</td>";
        echo "<td>{$u['first_name']}</td>";
        echo "<td>{$u['member_id']}</td>";
        echo "<td>{$regStatus}</td>";
        echo "<td>{$u['registered_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. Check users with member_id but is_registered = 0
echo "<h3>3. Users with member_id but is_registered = 0 or NULL</h3>";
try {
    $stmt = $db->query("SELECT id, display_name, first_name, member_id, is_registered FROM users WHERE member_id IS NOT NULL AND (is_registered = 0 OR is_registered IS NULL)");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p style='color:orange'>⚠️ Found " . count($users) . " users with member_id but not marked as registered:</p>";
        echo "<ul>";
        foreach ($users as $u) {
            echo "<li>ID: {$u['id']}, Name: {$u['first_name']}, Member ID: {$u['member_id']}</li>";
        }
        echo "</ul>";
        
        // Fix them
        echo "<p>Fixing...</p>";
        $db->exec("UPDATE users SET is_registered = 1 WHERE member_id IS NOT NULL AND (is_registered = 0 OR is_registered IS NULL)");
        echo "<p style='color:green'>✅ Fixed!</p>";
    } else {
        echo "<p style='color:green'>✅ No issues found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 4. Test API
echo "<h3>4. Test API get_card</h3>";
if (isset($_GET['test_user'])) {
    $testUserId = $_GET['test_user'];
    $lineAccountId = $_GET['account'] ?? 1;
    
    $stmt = $db->prepare("SELECT * FROM users WHERE line_user_id = ?");
    $stmt->execute([$testUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>" . print_r($user, true) . "</pre>";
    } else {
        echo "<p>User not found</p>";
    }
} else {
    echo "<p>Add ?test_user=LINE_USER_ID to test specific user</p>";
}

echo "<hr><p>Done!</p>";
