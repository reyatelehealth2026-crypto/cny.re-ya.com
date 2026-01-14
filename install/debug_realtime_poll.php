<?php
/**
 * Debug Realtime Poll - Check what data API returns
 */
header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Debug Realtime Poll</h1>";

// Test with line_account_id = 3
$lineAccountId = 3;
$lastCheck = date('Y-m-d H:i:s', strtotime('-5 minutes'));

echo "<h2>1. Session Info:</h2>";
echo "<pre>";
echo "current_bot_id in session: " . ($_SESSION['current_bot_id'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h2>2. Testing API with line_account_id = $lineAccountId:</h2>";

// Simulate the API query
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.display_name,
        u.picture_url,
        u.line_user_id,
        (SELECT content FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT message_type FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_type,
        (SELECT created_at FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_time,
        (SELECT direction FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_direction,
        (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND direction = 'incoming' AND is_read = 0) as unread_count
    FROM users u
    WHERE u.line_account_id = ?
    AND EXISTS (SELECT 1 FROM messages WHERE user_id = u.id)
    ORDER BY last_time DESC
    LIMIT 10
");
$stmt->execute([$lineAccountId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Last Message</th><th>Type</th><th>Direction</th><th>Time</th></tr>";
foreach ($conversations as $conv) {
    $lastMsg = $conv['last_message'];
    if (strlen($lastMsg) > 50) {
        $lastMsg = mb_substr($lastMsg, 0, 50) . '...';
    }
    echo "<tr>";
    echo "<td>{$conv['id']}</td>";
    echo "<td>{$conv['display_name']}</td>";
    echo "<td>" . htmlspecialchars($lastMsg) . "</td>";
    echo "<td>{$conv['last_type']}</td>";
    echo "<td>{$conv['last_direction']}</td>";
    echo "<td>{$conv['last_time']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. Latest messages for jame.ver (ID 15):</h2>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = 15 ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Content</th><th>Type</th><th>Direction</th><th>Created At</th></tr>";
foreach ($messages as $msg) {
    $content = $msg['content'];
    if (strlen($content) > 50) {
        $content = mb_substr($content, 0, 50) . '...';
    }
    echo "<tr>";
    echo "<td>{$msg['id']}</td>";
    echo "<td>" . htmlspecialchars($content) . "</td>";
    echo "<td>{$msg['message_type']}</td>";
    echo "<td>{$msg['direction']}</td>";
    echo "<td>{$msg['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. Test API Response (simulated):</h2>";

// Format like API does
$formattedConversations = [];
foreach ($conversations as $conv) {
    $lastMsg = $conv['last_message'];
    if ($conv['last_type'] === 'image') {
        $lastMsg = '📷 รูปภาพ';
    } elseif ($conv['last_type'] === 'sticker') {
        $lastMsg = '😊 สติกเกอร์';
    } elseif ($conv['last_type'] === 'file') {
        $lastMsg = '📎 ไฟล์';
    } elseif ($conv['last_type'] === 'location') {
        $lastMsg = '📍 ตำแหน่ง';
    } elseif (strlen($lastMsg) > 50) {
        $lastMsg = mb_substr($lastMsg, 0, 50) . '...';
    }
    
    $formattedConversations[] = [
        'id' => (int)$conv['id'],
        'display_name' => $conv['display_name'] ?: 'ไม่ระบุชื่อ',
        'last_message' => $lastMsg,
        'last_type' => $conv['last_type'],
        'last_direction' => $conv['last_direction'],
        'last_time' => $conv['last_time']
    ];
}

echo "<pre>" . json_encode($formattedConversations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>5. Check if jame.ver's last message is correct:</h2>";
$jameConv = array_filter($formattedConversations, fn($c) => $c['id'] === 15);
if (!empty($jameConv)) {
    $jame = array_values($jameConv)[0];
    echo "<p><strong>jame.ver last_message:</strong> " . htmlspecialchars($jame['last_message']) . "</p>";
    echo "<p><strong>jame.ver last_direction:</strong> " . $jame['last_direction'] . "</p>";
    
    // Check actual latest message
    $stmt = $db->prepare("SELECT content, direction FROM messages WHERE user_id = 15 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Actual latest message:</strong> " . htmlspecialchars($actual['content']) . "</p>";
    echo "<p><strong>Actual direction:</strong> " . $actual['direction'] . "</p>";
    
    if ($jame['last_message'] === $actual['content'] || ($jame['last_message'] === '📷 รูปภาพ' && $actual['content'] !== $jame['last_message'])) {
        echo "<p style='color:green'>✓ API returns correct data</p>";
    } else {
        echo "<p style='color:red'>✗ API returns WRONG data!</p>";
    }
}
