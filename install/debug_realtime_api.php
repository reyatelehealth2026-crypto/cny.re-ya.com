<?php
/**
 * Debug Realtime API - Check conversation list data
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Realtime API</h2>";

// Check all line_account_ids
$stmt = $db->query("SELECT DISTINCT line_account_id FROM users");
$accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<p>Available line_account_ids: " . implode(', ', $accounts) . "</p>";

// Use first available or default
$lineAccountId = $accounts[0] ?? 1;
echo "<p>Using line_account_id: {$lineAccountId}</p>";

// Get conversation list with latest message - FIXED QUERY
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.display_name,
        u.line_account_id,
        (SELECT content FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT message_type FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_type,
        (SELECT created_at FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_time,
        (SELECT direction FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_direction,
        (SELECT id FROM messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as message_id
    FROM users u
    WHERE EXISTS (SELECT 1 FROM messages WHERE user_id = u.id)
    ORDER BY last_time DESC
    LIMIT 10
");
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Conversations from API Query (Fixed):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>User ID</th><th>Name</th><th>Account</th><th>Last Message</th><th>Type</th><th>Time</th><th>Direction</th><th>Msg ID</th></tr>";

foreach ($conversations as $conv) {
    $msg = htmlspecialchars(mb_substr($conv['last_message'] ?? '', 0, 50));
    echo "<tr>";
    echo "<td>{$conv['id']}</td>";
    echo "<td>{$conv['display_name']}</td>";
    echo "<td>{$conv['line_account_id']}</td>";
    echo "<td>{$msg}</td>";
    echo "<td>{$conv['last_type']}</td>";
    echo "<td>{$conv['last_time']}</td>";
    echo "<td>{$conv['last_direction']}</td>";
    echo "<td>{$conv['message_id']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific user - Kratae
echo "<h3>Check Kratae's messages:</h3>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = (SELECT id FROM users WHERE display_name LIKE '%Kratae%' LIMIT 1) ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Content</th><th>Type</th><th>Direction</th><th>Created At</th></tr>";
foreach ($messages as $msg) {
    $content = htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 50));
    echo "<tr>";
    echo "<td>{$msg['id']}</td>";
    echo "<td>{$content}</td>";
    echo "<td>{$msg['message_type']}</td>";
    echo "<td>{$msg['direction']}</td>";
    echo "<td>{$msg['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check jame.ver's messages
echo "<h3>Check jame.ver's messages:</h3>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = (SELECT id FROM users WHERE display_name LIKE '%jame%' LIMIT 1) ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Content</th><th>Type</th><th>Direction</th><th>Created At</th></tr>";
foreach ($messages as $msg) {
    $content = htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 50));
    echo "<tr>";
    echo "<td>{$msg['id']}</td>";
    echo "<td>{$content}</td>";
    echo "<td>{$msg['message_type']}</td>";
    echo "<td>{$msg['direction']}</td>";
    echo "<td>{$msg['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";
