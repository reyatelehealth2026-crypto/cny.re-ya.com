<?php
/**
 * เพิ่ม columns reply_token ใน users table
 */

// รองรับทั้ง config.php และ confFig.php
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
} elseif (file_exists(__DIR__ . '/config/confFig.php')) {
    require_once __DIR__ . '/config/confFig.php';
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Fix Reply Token Columns</h2>";
echo "<pre>";

// ตรวจสอบว่ามี column อยู่แล้วหรือไม่
$stmt = $db->query("SHOW COLUMNS FROM users LIKE 'reply_token'");
$hasReplyToken = $stmt->rowCount() > 0;

$stmt = $db->query("SHOW COLUMNS FROM users LIKE 'reply_token_expires'");
$hasExpires = $stmt->rowCount() > 0;

try {
    if (!$hasReplyToken) {
        $db->exec("ALTER TABLE users ADD COLUMN reply_token VARCHAR(255) DEFAULT NULL");
        echo "✓ Added reply_token column\n";
    } else {
        echo "⏭ reply_token column already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

try {
    if (!$hasExpires) {
        $db->exec("ALTER TABLE users ADD COLUMN reply_token_expires DATETIME DEFAULT NULL");
        echo "✓ Added reply_token_expires column\n";
    } else {
        echo "⏭ reply_token_expires column already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// ตรวจสอบ
$stmt = $db->query("SHOW COLUMNS FROM users LIKE 'reply_token%'");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n--- Current Columns ---\n";
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

echo "</pre>";
echo "<p>✅ Done! <a href='messages.php'>Go to Messages</a></p>";
