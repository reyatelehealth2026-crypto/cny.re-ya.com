<?php
/**
 * Debug Header - ดู error ที่ซ่อนอยู่ใน header.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Header</h2>";

// Step 1
echo "<h3>1. Session</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>✅ Session started</p>";

// Step 2
echo "<h3>2. Config</h3>";
require_once 'config/config.php';
echo "<p>✅ Config loaded</p>";

// Step 3
echo "<h3>3. Database</h3>";
require_once 'config/database.php';
$db = Database::getInstance()->getConnection();
echo "<p>✅ Database connected</p>";

// Step 4
echo "<h3>4. Auth Check</h3>";
require_once 'includes/auth_check.php';
echo "<p>✅ Auth check loaded</p>";
echo "<p>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</p>";
echo "<p>isUser(): " . (isUser() ? 'true' : 'false') . "</p>";

// Step 5 - Simulate header.php
echo "<h3>5. Header Logic</h3>";

if (isUser()) {
    echo "<p style='color:red'>❌ isUser() = true - จะถูก redirect!</p>";
} else {
    echo "<p style='color:green'>✅ isUser() = false - ไม่ถูก redirect</p>";
}

// Step 6 - LINE Accounts query
echo "<h3>6. LINE Accounts Query</h3>";
$lineAccounts = [];
$currentBot = null;
try {
    $stmt = $db->query("SELECT id, name, basic_id, picture_url, is_default FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC, name ASC");
    $lineAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>✅ Query สำเร็จ - พบ " . count($lineAccounts) . " accounts</p>";
    
    if (!empty($lineAccounts)) {
        echo "<pre>" . print_r($lineAccounts, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>⚠️ Error: " . $e->getMessage() . "</p>";
    echo "<p>ตาราง line_accounts อาจไม่มี - แต่ไม่ควรทำให้หน้าพัง</p>";
}

$currentBotId = $currentBot['id'] ?? null;
echo "<p>currentBotId: " . ($currentBotId ?? 'NULL') . "</p>";
echo "<p>SESSION current_bot_id: " . ($_SESSION['current_bot_id'] ?? 'NULL') . "</p>";

// Step 7 - Test users query
echo "<h3>7. Users Query</h3>";
$whereConditions = ["1=1"];
$params = [];
$whereClause = implode(' AND ', $whereConditions);

$countSql = "SELECT COUNT(*) FROM users u WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
echo "<p style='color:green'>✅ Total users: {$totalUsers}</p>";

// Step 8 - Check if output buffering is affecting
echo "<h3>8. Output Buffering</h3>";
echo "<p>ob_get_level(): " . ob_get_level() . "</p>";
echo "<p>ob_get_length(): " . (ob_get_length() ?: 0) . "</p>";

echo "<h3>สรุป</h3>";
echo "<p>ถ้าทุกอย่างผ่าน แต่ users.php ยังไม่แสดง ให้ดู View Source ของ users.php ว่ามี HTML อะไรบ้าง</p>";
?>
