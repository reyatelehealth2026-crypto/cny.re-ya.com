<?php
/**
 * Run Video Call Migration
 * สร้างตารางสำหรับระบบ Video Call
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🎥 Video Call Migration</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:800px;margin:0 auto}
.success{color:#059669;background:#D1FAE5;padding:15px;border-radius:8px;margin:10px 0}
.error{color:#DC2626;background:#FEE2E2;padding:15px;border-radius:8px;margin:10px 0}
.info{color:#2563EB;background:#DBEAFE;padding:15px;border-radius:8px;margin:10px 0}
pre{background:#F3F4F6;padding:15px;border-radius:8px;overflow-x:auto}</style>";

$sql = file_get_contents('database/migration_video_calls.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;
    
    try {
        $db->exec($statement);
        $success++;
        
        // Extract table name
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            echo "<div class='success'>✅ สร้างตาราง: {$matches[1]}</div>";
        } elseif (preg_match('/INSERT/i', $statement)) {
            echo "<div class='success'>✅ เพิ่มข้อมูลเริ่มต้น</div>";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<div class='info'>ℹ️ ตารางมีอยู่แล้ว</div>";
        } else {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
            $errors++;
        }
    }
}

echo "<hr>";
echo "<h2>📋 สรุป</h2>";
echo "<p>สำเร็จ: {$success} | ผิดพลาด: {$errors}</p>";

if ($errors === 0) {
    echo "<div class='success' style='font-size:18px'>
        <h3>✅ ติดตั้งสำเร็จ!</h3>
        <p><strong>วิธีใช้งาน:</strong></p>
        <ol>
            <li>แอดมินเปิด: <a href='video-call.php'>video-call.php</a></li>
            <li>แชร์ลิงก์ให้ลูกค้า: <code>" . BASE_URL . "liff-video-call.php</code></li>
            <li>ลูกค้ากดโทร → แอดมินรับสาย</li>
        </ol>
    </div>";
}
?>
