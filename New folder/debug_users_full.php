<?php
/**
 * Debug Users Full - จำลองการทำงานของ users.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Users Full</h2>";

// Step 1: Session
echo "<h3>1. Session Start</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session started ✅</p>";

// Step 2: Config
echo "<h3>2. Load Config</h3>";
try {
    require_once 'config/config.php';
    echo "<p>config.php loaded ✅</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ config.php error: " . $e->getMessage() . "</p>";
}

// Step 3: Database
echo "<h3>3. Load Database</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p>database.php loaded ✅</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ database.php error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 4: Check auth_check
echo "<h3>4. Auth Check</h3>";
try {
    require_once 'includes/auth_check.php';
    echo "<p>auth_check.php loaded ✅</p>";
    echo "<p>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</p>";
    echo "<p>isUser(): " . (isUser() ? 'true' : 'false') . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ auth_check.php error: " . $e->getMessage() . "</p>";
}

// Step 5: Simulate header.php logic (without actual header)
echo "<h3>5. Header Logic Check</h3>";
if (isUser()) {
    echo "<p style='color:orange'>⚠️ isUser() = true - จะถูก redirect!</p>";
    if (empty($currentUser['line_account_id'])) {
        echo "<p>→ Redirect to: auth/setup-account.php</p>";
    } else {
        echo "<p>→ Redirect to: user/dashboard.php</p>";
    }
} else {
    echo "<p style='color:green'>✅ isUser() = false - ไม่ถูก redirect</p>";
}

// Step 6: Query users
echo "<h3>6. Query Users</h3>";
$pageTitle = 'Customers';

$tagFilter = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereConditions = ["1=1"];
$params = [];

if ($tagFilter) {
    $whereConditions[] = "EXISTS (SELECT 1 FROM user_tag_assignments uta WHERE uta.user_id = u.id AND uta.tag_id = ?)";
    $params[] = $tagFilter;
}

if ($search) {
    $whereConditions[] = "(u.display_name LIKE ? OR u.line_user_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countSql = "SELECT COUNT(*) FROM users u WHERE {$whereClause}";
echo "<p>Count SQL: <code>{$countSql}</code></p>";

try {
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
    echo "<p style='color:green'>✅ Total: {$totalUsers} users, {$totalPages} pages</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Count error: " . $e->getMessage() . "</p>";
}

// Get users
try {
    $hasExtraCols = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'real_name'");
        $hasExtraCols = $stmt->rowCount() > 0;
    } catch (Exception $e) {}
    
    $extraCols = $hasExtraCols ? ", u.real_name, u.phone, u.email, u.birthday" : ", NULL as real_name, NULL as phone, NULL as email, NULL as birthday";
    
    $sql = "SELECT u.*{$extraCols},
            (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM user_tags t 
             JOIN user_tag_assignments uta ON t.id = uta.tag_id 
             WHERE uta.user_id = u.id) as tags,
            (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.id) as message_count,
            (SELECT MAX(created_at) FROM messages m WHERE m.user_id = u.id) as last_message_at
            FROM users u 
            WHERE {$whereClause}
            ORDER BY u.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color:green'>✅ Found " . count($users) . " users</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
        echo "<tr style='background:#f0f0f0'><th>ID</th><th>Display Name</th><th>Picture</th><th>LINE Account</th><th>Blocked</th><th>Tags</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>" . htmlspecialchars($user['display_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['picture_url'] ? "<img src='{$user['picture_url']}' width='40' height='40' style='border-radius:50%'>" : '-') . "</td>";
            echo "<td>" . ($user['line_account_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($user['is_blocked'] ? '❌ Blocked' : '✅ Active') . "</td>";
            echo "<td>" . htmlspecialchars($user['tags'] ?? '-') . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ ไม่พบ users</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>7. สรุป</h3>";
echo "<p>ถ้าเห็น users ที่นี่แต่ไม่เห็นที่ users.php แสดงว่าปัญหาอยู่ที่ header.php หรือ CSS/JS</p>";
echo "<p><a href='users.php' target='_blank'>เปิด users.php ในแท็บใหม่</a></p>";
?>
