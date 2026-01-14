<?php
/**
 * Debug Realtime API - Check conversation list data
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = 1;

echo "<h2>Debug Realtime API</h2>";

// Get conversation list with latest message
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.display_name,
        m_last.content as last_message,
        m_last.message_type as last_type,
        m_last.created_at as last_time,
        m_last.direction as last_direction,
        m_last.id as message_id
    FROM users u
    INNER JOIN (
        SELECT user_id, MAX(created_at) as max_time
        FROM messages
        GROUP BY user_id
    ) m_max ON u.id = m_max.user_id
    INNER JOIN messages m_last ON m_last.user_id = m_max.user_id AND m_last.created_at = m_max.max_time
    WHERE u.line_account_id = ?
    ORDER BY m_last.created_at DESC
    LIMIT 10
");
$stmt->execute([$lineAccountId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Conversations from API Query:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>User ID</th><th>Name</th><th>Last Message</th><th>Type</th><th>Time</th><th>Direction</th><th>Msg ID</th></tr>";

foreach ($conversations as $conv) {
    $msg = htmlspecialchars(mb_substr($conv['last_message'] ?? '', 0, 50));
    echo "<tr>";
    echo "<td>{$conv['id']}</td>";
    echo "<td>{$conv['display_name']}</td>";
    echo "<td>{$msg}</td>";
    echo "<td>{$conv['last_type']}</td>";
    echo "<td>{$conv['last_time']}</td>";
    echo "<td>{$conv['last_direction']}</td>";
    echo "<td>{$conv['message_id']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific user - Kratae (user_id from screenshot)
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
