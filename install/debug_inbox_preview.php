<?php
/**
 * Debug Inbox Preview - Check where preview message comes from
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug Inbox Preview</h2>";

// Get jame.ver user
$stmt = $db->prepare("SELECT id, display_name FROM users WHERE display_name LIKE '%jame%' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User jame.ver not found";
    exit;
}

$userId = $user['id'];
echo "<p>User: {$user['display_name']} (ID: {$userId})</p>";

// Get last 10 messages ordered by created_at DESC
echo "<h3>Messages ordered by created_at DESC:</h3>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
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

// Get last 10 messages ordered by id DESC
echo "<h3>Messages ordered by id DESC:</h3>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
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

// Test the subquery
echo "<h3>Subquery result (should be latest by created_at):</h3>";
$stmt = $db->prepare("
    SELECT 
        (SELECT content FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 1) as last_msg,
        (SELECT message_type FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 1) as last_type,
        (SELECT created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 1) as last_time
");
$stmt->execute([$userId, $userId, $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p>Last Message: " . htmlspecialchars($result['last_msg'] ?? '') . "</p>";
echo "<p>Last Type: " . ($result['last_type'] ?? '') . "</p>";
echo "<p>Last Time: " . ($result['last_time'] ?? '') . "</p>";

// Check if there's image message
echo "<h3>Image messages for this user:</h3>";
$stmt = $db->prepare("SELECT id, content, message_type, direction, created_at FROM messages WHERE user_id = ? AND message_type = 'image' ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($images)) {
    echo "<p>No image messages found</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Content</th><th>Type</th><th>Direction</th><th>Created At</th></tr>";
    foreach ($images as $msg) {
        $content = htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 80));
        echo "<tr>";
        echo "<td>{$msg['id']}</td>";
        echo "<td>{$content}</td>";
        echo "<td>{$msg['message_type']}</td>";
        echo "<td>{$msg['direction']}</td>";
        echo "<td>{$msg['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
