<?php
/**
 * Debug Video Call - ตรวจสอบสถานะ Video Call
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug Video Call</h1>";
echo "<p>BASE_URL: <code>" . BASE_URL . "</code></p>";
echo "<p>Customer Link: <code>" . rtrim(BASE_URL, '/') . "/liff-video-call-pro.php</code></p>";

// Check tables
echo "<h2>📊 Tables</h2>";
try {
    $tables = $db->query("SHOW TABLES LIKE 'video%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Video tables: " . implode(', ', $tables) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check video_calls table
echo "<h2>📞 All Video Calls</h2>";
try {
    $stmt = $db->query("SELECT * FROM video_calls ORDER BY created_at DESC LIMIT 20");
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($calls)) {
        echo "<p style='color:orange'>⚠️ ไม่มีข้อมูลใน video_calls</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Room ID</th><th>User ID</th><th>Display Name</th><th>Status</th><th>Created</th><th>Answered</th><th>Ended</th></tr>";
        foreach ($calls as $c) {
            $statusColor = $c['status'] === 'ringing' ? 'green' : ($c['status'] === 'active' ? 'blue' : 'gray');
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td><small>{$c['room_id']}</small></td>";
            echo "<td>{$c['user_id']}</td>";
            echo "<td>" . htmlspecialchars($c['display_name'] ?? '-') . "</td>";
            echo "<td style='color:$statusColor;font-weight:bold'>{$c['status']}</td>";
            echo "<td>{$c['created_at']}</td>";
            echo "<td>{$c['answered_at']}</td>";
            echo "<td>{$c['ended_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check pending/ringing calls
echo "<h2>🔔 Pending/Ringing Calls</h2>";
try {
    $stmt = $db->query("SELECT * FROM video_calls WHERE status IN ('pending', 'ringing') ORDER BY created_at DESC");
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending)) {
        echo "<p>ไม่มีสายที่รอรับ</p>";
    } else {
        echo "<p style='color:green'>✅ มี " . count($pending) . " สายที่รอรับ</p>";
        echo "<pre>" . print_r($pending, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check signals
echo "<h2>📡 Recent Signals</h2>";
try {
    $stmt = $db->query("SELECT * FROM video_call_signals ORDER BY created_at DESC LIMIT 20");
    $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($signals)) {
        echo "<p>ไม่มี signals</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Call ID</th><th>Type</th><th>From</th><th>Processed</th><th>Created</th></tr>";
        foreach ($signals as $s) {
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>{$s['call_id']}</td>";
            echo "<td>{$s['signal_type']}</td>";
            echo "<td>" . ($s['from_who'] ?? '-') . "</td>";
            echo "<td>" . ($s['processed'] ? '✓' : '-') . "</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test API
echo "<h2>🧪 Test API</h2>";
echo "<p><a href='api/video-call.php?action=debug' target='_blank'>Open API Debug</a></p>";
echo "<p><a href='api/video-call.php?action=check_calls&account_id=1' target='_blank'>Check Calls (account_id=1)</a></p>";

// Create test call
echo "<h2>➕ Create Test Call</h2>";
if (isset($_POST['create_test'])) {
    try {
        $roomId = 'test_' . uniqid() . '_' . time();
        $stmt = $db->prepare("INSERT INTO video_calls (room_id, display_name, status, created_at) VALUES (?, 'ทดสอบ', 'ringing', NOW())");
        $stmt->execute([$roomId]);
        echo "<p style='color:green'>✅ สร้างสายทดสอบสำเร็จ! ID: " . $db->lastInsertId() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
<form method="POST">
    <button type="submit" name="create_test" style="padding:10px 20px;background:#10b981;color:white;border:none;border-radius:5px;cursor:pointer">
        ➕ สร้างสายทดสอบ
    </button>
</form>

<h2>🔗 Links</h2>
<ul>
    <li><a href="video-call-pro.php" target="_blank">Admin Video Call (video-call-pro.php)</a></li>
    <li><a href="liff-video-call-pro.php" target="_blank">Customer Video Call (liff-video-call-pro.php)</a></li>
</ul>
