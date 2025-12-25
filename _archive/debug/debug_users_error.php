<?php
/**
 * Debug Users Error - หา error ที่ซ่อนอยู่
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Start<br>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Step 2: Session OK<br>";

require_once 'config/config.php';
echo "Step 3: Config OK<br>";

require_once 'config/database.php';
echo "Step 4: Database OK<br>";

$db = Database::getInstance()->getConnection();
echo "Step 5: DB Connection OK<br>";

$pageTitle = 'Customers';

// Skip header.php for now
require_once 'includes/auth_check.php';
echo "Step 6: Auth OK<br>";

// Get filter parameters
$tagFilter = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
echo "Step 7: Params OK<br>";

// Get tag info if filtering
$currentTag = null;
if ($tagFilter) {
    try {
        $stmt = $db->prepare("SELECT * FROM user_tags WHERE id = ?");
        $stmt->execute([$tagFilter]);
        $currentTag = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Tag error: " . $e->getMessage() . "<br>";
    }
}
echo "Step 8: Tag filter OK<br>";

// Build query
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
echo "Step 9: Where clause OK<br>";

// Get total count
$countSql = "SELECT COUNT(*) FROM users u WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);
echo "Step 10: Count OK - Total: {$totalUsers}<br>";

// Get users with their tags
try {
    echo "Step 11: Starting users query<br>";
    
    // Check if extra columns exist
    $hasExtraCols = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'real_name'");
        $hasExtraCols = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo "Extra cols check error: " . $e->getMessage() . "<br>";
    }
    echo "Step 12: Extra cols check OK - hasExtraCols: " . ($hasExtraCols ? 'true' : 'false') . "<br>";
    
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

    echo "Step 13: SQL prepared<br>";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Step 14: Query OK - Found: " . count($users) . " users<br>";
    
} catch (Exception $e) {
    echo "Step 11-14 ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    
    // Fallback
    $sql = "SELECT u.* FROM users u WHERE {$whereClause} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Fallback OK - Found: " . count($users) . " users<br>";
}

// Get all tags for filter dropdown
echo "Step 15: Getting tags<br>";
$allTags = [];
$currentBotId = $_SESSION['current_bot_id'] ?? null;
echo "currentBotId: " . ($currentBotId ?? 'NULL') . "<br>";

try {
    $stmt = $db->prepare("SELECT * FROM user_tags WHERE line_account_id = ? OR line_account_id IS NULL ORDER BY name");
    $stmt->execute([$currentBotId]);
    $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Step 16: Tags OK - Found: " . count($allTags) . " tags<br>";
} catch (Exception $e) {
    echo "Step 16 ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ All steps completed!</h2>";
echo "<p>Users found: " . count($users) . "</p>";

if (count($users) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Tags</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>" . htmlspecialchars($u['display_name'] ?? '') . "</td><td>" . htmlspecialchars($u['tags'] ?? '-') . "</td></tr>";
    }
    echo "</table>";
}
?>
