<?php
/**
 * Run Video Call V2 Migration
 * เพิ่ม columns สำหรับ WebRTC signaling
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🎥 Video Call V2 Migration</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:800px;margin:0 auto}
.success{color:#059669;background:#D1FAE5;padding:10px;border-radius:8px;margin:10px 0}
.error{color:#DC2626;background:#FEE2E2;padding:10px;border-radius:8px;margin:10px 0}
.info{color:#2563EB;background:#DBEAFE;padding:10px;border-radius:8px;margin:10px 0}</style>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if columns exist
    $stmt = $db->query("DESCRIBE video_call_signals");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add from_who column
    if (!in_array('from_who', $columns)) {
        $db->exec("ALTER TABLE video_call_signals ADD COLUMN from_who VARCHAR(20) DEFAULT 'customer' AFTER signal_data");
        echo "<div class='success'>✅ Added column: from_who</div>";
    } else {
        echo "<div class='info'>ℹ️ Column from_who already exists</div>";
    }
    
    // Add processed column
    if (!in_array('processed', $columns)) {
        $db->exec("ALTER TABLE video_call_signals ADD COLUMN processed TINYINT(1) DEFAULT 0 AFTER from_who");
        echo "<div class='success'>✅ Added column: processed</div>";
    } else {
        echo "<div class='info'>ℹ️ Column processed already exists</div>";
    }
    
    // Add index
    try {
        $db->exec("ALTER TABLE video_call_signals ADD INDEX idx_signal_poll (call_id, from_who, processed)");
        echo "<div class='success'>✅ Added index: idx_signal_poll</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<div class='info'>ℹ️ Index already exists</div>";
        } else {
            throw $e;
        }
    }
    
    echo "<div class='success' style='margin-top:20px'><strong>✅ Migration completed!</strong></div>";
    echo "<p><a href='video-call-v2.php'>→ ไปหน้า Video Call V2</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>
