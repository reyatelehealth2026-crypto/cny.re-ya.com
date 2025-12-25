<?php
/**
 * Fix Video Calls Foreign Key
 * ลบ Foreign Key constraint ที่ทำให้เกิด error
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Fix Video Calls Foreign Key</h1>";
echo "<style>body{font-family:sans-serif;padding:20px}
.success{color:#059669;background:#D1FAE5;padding:15px;border-radius:8px;margin:10px 0}
.error{color:#DC2626;background:#FEE2E2;padding:15px;border-radius:8px;margin:10px 0}
.info{color:#2563EB;background:#DBEAFE;padding:15px;border-radius:8px;margin:10px 0}</style>";

// Drop foreign keys
$fks = [
    'video_calls_ibfk_1',
    'video_calls_ibfk_2',
    'video_call_signals_ibfk_1'
];

foreach ($fks as $fk) {
    try {
        $db->exec("ALTER TABLE video_calls DROP FOREIGN KEY {$fk}");
        echo "<div class='success'>✅ ลบ Foreign Key: {$fk}</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "check that column/key exists") !== false || 
            strpos($e->getMessage(), "Can't DROP") !== false) {
            echo "<div class='info'>ℹ️ {$fk} ไม่มีอยู่แล้ว</div>";
        } else {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Also try for signals table
try {
    $db->exec("ALTER TABLE video_call_signals DROP FOREIGN KEY video_call_signals_ibfk_1");
    echo "<div class='success'>✅ ลบ FK จาก video_call_signals</div>";
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ video_call_signals FK ไม่มีหรือลบแล้ว</div>";
}

echo "<hr>";
echo "<div class='success' style='font-size:18px'>✅ เสร็จสิ้น! ลองโทรใหม่อีกครั้ง</div>";
echo "<p><a href='liff-video-call.php'>→ ไปหน้า Video Call</a></p>";
?>
