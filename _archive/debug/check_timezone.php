<?php
/**
 * Check & Fix Timezone
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🕐 Check Timezone</h1>";

// 1. PHP Timezone
echo "<h2>1. PHP Timezone</h2>";
echo "<p>Current PHP Timezone: <strong>" . date_default_timezone_get() . "</strong></p>";
echo "<p>Current PHP Time: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";

// 2. MySQL Timezone
echo "<h2>2. MySQL Timezone</h2>";
$stmt = $db->query("SELECT @@global.time_zone AS global_tz, @@session.time_zone AS session_tz, NOW() AS mysql_now");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>MySQL Global Timezone: <strong>{$row['global_tz']}</strong></p>";
echo "<p>MySQL Session Timezone: <strong>{$row['session_tz']}</strong></p>";
echo "<p>MySQL NOW(): <strong>{$row['mysql_now']}</strong></p>";

// 3. Compare
echo "<h2>3. Time Comparison</h2>";
$phpTime = strtotime(date('Y-m-d H:i:s'));
$mysqlTime = strtotime($row['mysql_now']);
$diff = $phpTime - $mysqlTime;
echo "<p>Difference: <strong>{$diff} seconds</strong></p>";

if (abs($diff) > 60) {
    echo "<p style='color:orange;'>⚠️ PHP และ MySQL มีเวลาต่างกัน!</p>";
} else {
    echo "<p style='color:green;'>✅ PHP และ MySQL มีเวลาตรงกัน</p>";
}

// 4. Fix MySQL Session Timezone
echo "<h2>4. Fix MySQL Session Timezone</h2>";
if (isset($_GET['fix'])) {
    try {
        $db->exec("SET time_zone = '+07:00'");
        echo "<p style='color:green;'>✅ Set MySQL session timezone to +07:00 (Bangkok)</p>";
        
        // Verify
        $stmt = $db->query("SELECT NOW() AS mysql_now");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>MySQL NOW() after fix: <strong>{$row['mysql_now']}</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?fix=1' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>🔧 Fix MySQL Timezone</a></p>";
}

// 5. Recent messages time check
echo "<h2>5. Recent Messages Time</h2>";
$stmt = $db->query("SELECT id, created_at, content FROM messages ORDER BY created_at DESC LIMIT 5");
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr><th>ID</th><th>DB Time (created_at)</th><th>Formatted Thai</th><th>Content</th></tr>";
foreach ($msgs as $m) {
    $thaiTime = date('d/m/Y H:i:s', strtotime($m['created_at']));
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td>{$m['created_at']}</td>";
    echo "<td><strong>{$thaiTime}</strong></td>";
    echo "<td>" . htmlspecialchars(mb_substr($m['content'], 0, 30)) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 6. Server time
echo "<h2>6. Server Info</h2>";
echo "<p>Server Time (shell): <strong>" . shell_exec('date') . "</strong></p>";

echo "<hr><p><a href='inbox'>← Back to Inbox</a></p>";
