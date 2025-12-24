<?php
/**
 * Debug Users - ดูข้อมูล users ทั้งหมด
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Users Data</h2>";

// ดู users ทั้งหมด
echo "<h3>1. Users ทั้งหมด (ไม่มีเงื่อนไข)</h3>";
try {
    $stmt = $db->query("SELECT id, display_name, line_user_id, line_account_id, is_blocked, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total: " . count($users) . " users</strong></p>";
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Display Name</th><th>LINE User ID</th><th>LINE Account ID</th><th>Blocked</th><th>Created</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['display_name'] ?? 'N/A') . "</td>";
        echo "<td>" . substr($user['line_user_id'], 0, 20) . "...</td>";
        echo "<td>" . ($user['line_account_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['is_blocked'] ? '❌ Yes' : '✅ No') . "</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// ค้นหา feelgoods
echo "<h3>2. ค้นหา 'feelgoods'</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE display_name LIKE ?");
    $stmt->execute(['%feelgoods%']);
    $found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($found) > 0) {
        echo "<p style='color:green'>✅ พบ " . count($found) . " รายการ</p>";
        echo "<pre>" . print_r($found, true) . "</pre>";
    } else {
        echo "<p style='color:orange'>⚠️ ไม่พบ user ชื่อ feelgoods</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// ดู users ที่สร้างวันนี้
echo "<h3>3. Users ที่สร้างวันนี้</h3>";
try {
    $stmt = $db->query("SELECT id, display_name, line_account_id, is_blocked, created_at FROM users WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC");
    $todayUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Today: " . count($todayUsers) . " users</strong></p>";
    
    if (count($todayUsers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Display Name</th><th>LINE Account ID</th><th>Blocked</th><th>Created</th></tr>";
        foreach ($todayUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>" . htmlspecialchars($user['display_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['line_account_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($user['is_blocked'] ? '❌ Yes' : '✅ No') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// ดู account_followers
echo "<h3>4. Account Followers (ตาราง account_followers)</h3>";
try {
    $stmt = $db->query("SELECT af.*, la.name as account_name FROM account_followers af LEFT JOIN line_accounts la ON af.line_account_id = la.id ORDER BY af.followed_at DESC LIMIT 20");
    $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Recent followers: " . count($followers) . "</strong></p>";
    
    if (count($followers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Display Name</th><th>Account</th><th>User ID (FK)</th><th>Following</th><th>Followed At</th></tr>";
        foreach ($followers as $f) {
            echo "<tr>";
            echo "<td>{$f['id']}</td>";
            echo "<td>" . htmlspecialchars($f['display_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($f['account_name'] ?? $f['line_account_id']) . "</td>";
            echo "<td>" . ($f['user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($f['is_following'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>{$f['followed_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ ตาราง account_followers ไม่มี หรือ error: " . $e->getMessage() . "</p>";
}

// ดูโครงสร้างตาราง users
echo "<h3>5. โครงสร้างตาราง users</h3>";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// ดู dev_logs ล่าสุด
echo "<h3>6. Dev Logs ล่าสุด (webhook events)</h3>";
try {
    $stmt = $db->query("SELECT * FROM dev_logs WHERE source LIKE '%webhook%' OR source LIKE '%follow%' ORDER BY created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($logs) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Type</th><th>Source</th><th>Message</th><th>Created</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['log_type']}</td>";
            echo "<td>{$log['source']}</td>";
            echo "<td>" . htmlspecialchars(mb_substr($log['message'] ?? '', 0, 50)) . "</td>";
            echo "<td>{$log['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ไม่มี logs</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ ตาราง dev_logs ไม่มี</p>";
}

// ดู line_accounts
echo "<h3>2. LINE Accounts ที่มี</h3>";
try {
    $stmt = $db->query("SELECT id, name, channel_name FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($accounts) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Channel Name</th></tr>";
        foreach ($accounts as $acc) {
            echo "<tr>";
            echo "<td>{$acc['id']}</td>";
            echo "<td>" . htmlspecialchars($acc['name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($acc['channel_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ไม่มี line_accounts</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ ตาราง line_accounts ไม่มี</p>";
}

// ดู session
echo "<h3>3. Session Info</h3>";
session_start();
echo "<p>current_bot_id: " . ($_SESSION['current_bot_id'] ?? 'ไม่ได้ตั้งค่า (default = 1)') . "</p>";

// แนะนำวิธีแก้
echo "<h3>4. วิธีแก้ไข</h3>";
echo "<p>ถ้า users มี line_account_id ที่ไม่ตรงกับ current_bot_id ให้:</p>";
echo "<ul>";
echo "<li>อัพเดท line_account_id ของ users ให้ตรงกับ bot ที่ใช้งาน</li>";
echo "<li>หรือ set เป็น NULL เพื่อให้แสดงในทุก bot</li>";
echo "</ul>";

echo "<h3>5. Quick Fix - อัพเดท users ให้แสดงทั้งหมด</h3>";
if (isset($_GET['fix'])) {
    try {
        $stmt = $db->query("UPDATE users SET line_account_id = 1 WHERE line_account_id IS NULL OR line_account_id != 1");
        $affected = $stmt->rowCount();
        echo "<p style='color:green'>✅ อัพเดทแล้ว {$affected} รายการ</p>";
        echo "<p><a href='users.php'>กลับไปหน้า Users</a></p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?fix=1' onclick=\"return confirm('ต้องการอัพเดท users ทั้งหมดให้ line_account_id = 1?')\">คลิกเพื่อ Fix</a></p>";
}
?>
