<?php
/**
 * Debug Bot Mode
 * ตรวจสอบว่า bot_mode ถูกอ่านค่าถูกต้องหรือไม่
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_GET['account_id'] ?? 3;

echo "<h1>🔍 Debug Bot Mode</h1>";
echo "<style>body{font-family:sans-serif;padding:20px}
.box{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin:15px 0}
.success{color:#059669}.error{color:#DC2626}.info{color:#2563EB}
pre{background:#F3F4F6;padding:15px;border-radius:8px;overflow-x:auto}</style>";

echo "<p>LINE Account ID: <strong>$lineAccountId</strong></p>";

// 1. Check if bot_mode column exists
echo "<div class='box'><h2>1. ตรวจสอบ Column bot_mode</h2>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'bot_mode'");
    if ($stmt->rowCount() > 0) {
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Column bot_mode มีอยู่</p>";
        echo "<pre>" . print_r($col, true) . "</pre>";
    } else {
        echo "<p class='error'>❌ Column bot_mode ไม่มี!</p>";
        echo "<p>รัน: <code>ALTER TABLE line_accounts ADD COLUMN bot_mode ENUM('shop', 'general', 'auto_reply_only') DEFAULT 'shop'</code></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 2. Check current bot_mode value
echo "<div class='box'><h2>2. ค่า bot_mode ปัจจุบัน</h2>";
try {
    $stmt = $db->prepare("SELECT id, name, bot_mode, is_active FROM line_accounts WHERE id = ?");
    $stmt->execute([$lineAccountId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($account) {
        echo "<pre>" . print_r($account, true) . "</pre>";
        $mode = $account['bot_mode'] ?? 'NOT SET';
        echo "<p>bot_mode = <strong style='font-size:24px;color:" . ($mode === 'general' ? '#DC2626' : '#059669') . "'>{$mode}</strong></p>";
    } else {
        echo "<p class='error'>❌ ไม่พบ LINE Account ID: $lineAccountId</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. All accounts
echo "<div class='box'><h2>3. LINE Accounts ทั้งหมด</h2>";
try {
    $stmt = $db->query("SELECT id, name, bot_mode, is_active FROM line_accounts ORDER BY id");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%'>";
    echo "<tr style='background:#F3F4F6'><th>ID</th><th>Name</th><th>Bot Mode</th><th>Active</th></tr>";
    foreach ($accounts as $acc) {
        $modeColor = $acc['bot_mode'] === 'general' ? '#DC2626' : ($acc['bot_mode'] === 'shop' ? '#059669' : '#D97706');
        echo "<tr>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td style='color:$modeColor;font-weight:bold'>{$acc['bot_mode']}</td>";
        echo "<td>" . ($acc['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Test BusinessBot
echo "<div class='box'><h2>4. ทดสอบ BusinessBot</h2>";
try {
    require_once 'classes/LineAPI.php';
    require_once 'classes/BusinessBot.php';
    
    // Get account credentials
    $stmt = $db->prepare("SELECT * FROM line_accounts WHERE id = ?");
    $stmt->execute([$lineAccountId]);
    $acc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($acc) {
        $line = new LineAPI($acc['channel_access_token'], $acc['channel_secret']);
        $bot = new BusinessBot($db, $line, $lineAccountId);
        $botMode = $bot->getBotMode();
        
        echo "<p>BusinessBot->getBotMode() = <strong style='font-size:24px;color:" . ($botMode === 'general' ? '#DC2626' : '#059669') . "'>{$botMode}</strong></p>";
        
        if ($botMode === 'general') {
            echo "<p class='success'>✅ โหมด general - บอทจะไม่ตอบกลับข้อความทั่วไป</p>";
        } else {
            echo "<p class='info'>ℹ️ โหมด {$botMode} - บอทจะตอบกลับตามปกติ</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 5. Quick update form
echo "<div class='box'><h2>5. เปลี่ยน Bot Mode</h2>";
if (isset($_POST['new_mode'])) {
    $newMode = $_POST['new_mode'];
    $accId = $_POST['account_id'];
    try {
        $stmt = $db->prepare("UPDATE line_accounts SET bot_mode = ? WHERE id = ?");
        $stmt->execute([$newMode, $accId]);
        echo "<p class='success'>✅ เปลี่ยน bot_mode เป็น '$newMode' สำเร็จ!</p>";
        echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
echo "<form method='POST'>";
echo "<input type='hidden' name='account_id' value='$lineAccountId'>";
echo "<select name='new_mode' style='padding:10px;font-size:16px'>";
echo "<option value='shop'>🛒 shop - ร้านค้าเต็มรูปแบบ</option>";
echo "<option value='general'>💬 general - ทั่วไป (ไม่ตอบกลับอัตโนมัติ)</option>";
echo "<option value='auto_reply_only'>🤖 auto_reply_only - Auto Reply เท่านั้น</option>";
echo "</select>";
echo "<button type='submit' style='padding:10px 20px;background:#10B981;color:white;border:none;border-radius:8px;margin-left:10px;cursor:pointer'>เปลี่ยน</button>";
echo "</form>";
echo "</div>";

// 6. Recent dev_logs
echo "<div class='box'><h2>6. Dev Logs ล่าสุด (bot_mode related)</h2>";
try {
    $stmt = $db->query("SELECT * FROM dev_logs WHERE message LIKE '%bot_mode%' OR message LIKE '%general%' OR source LIKE '%BusinessBot%' ORDER BY created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($logs) {
        foreach ($logs as $log) {
            echo "<div style='background:#F9FAFB;padding:10px;margin:5px 0;border-radius:8px;font-size:12px'>";
            echo "<strong>{$log['created_at']}</strong> [{$log['log_type']}] {$log['source']}: {$log['message']}";
            if ($log['data']) {
                echo "<pre style='margin:5px 0;font-size:11px'>" . htmlspecialchars(substr($log['data'], 0, 500)) . "</pre>";
            }
            echo "</div>";
        }
    } else {
        echo "<p class='info'>ℹ️ ไม่พบ logs</p>";
    }
} catch (Exception $e) {
    echo "<p class='info'>ℹ️ ตาราง dev_logs อาจไม่มี</p>";
}
echo "</div>";
