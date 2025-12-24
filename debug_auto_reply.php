<?php
/**
 * Debug Auto Reply - ตรวจสอบว่าทำไม Auto Reply ไม่ทำงาน
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_GET['account_id'] ?? 1;
$testMessage = $_GET['msg'] ?? 'สวัสดี';

echo "<h1>🔍 Debug Auto Reply</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:1200px;margin:0 auto}
.box{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin:15px 0}
.success{color:#059669;background:#D1FAE5;padding:10px;border-radius:8px;margin:5px 0}
.error{color:#DC2626;background:#FEE2E2;padding:10px;border-radius:8px;margin:5px 0}
.info{color:#2563EB;background:#DBEAFE;padding:10px;border-radius:8px;margin:5px 0}
table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #E5E7EB;text-align:left}
th{background:#F3F4F6}</style>";

// 1. LINE Account Info
echo "<div class='box'><h2>1. LINE Account</h2>";
$stmt = $db->prepare("SELECT id, name, bot_mode FROM line_accounts WHERE id = ?");
$stmt->execute([$lineAccountId]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
if ($account) {
    echo "<p>ID: <strong>{$account['id']}</strong> | Name: <strong>{$account['name']}</strong> | Bot Mode: <strong>{$account['bot_mode']}</strong></p>";
} else {
    echo "<p class='error'>❌ ไม่พบ LINE Account ID: $lineAccountId</p>";
}
echo "</div>";

// 2. All Auto Reply Rules
echo "<div class='box'><h2>2. Auto Reply Rules ทั้งหมด</h2>";
$stmt = $db->query("SELECT * FROM auto_replies ORDER BY line_account_id, priority DESC");
$allRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($allRules)) {
    echo "<p class='error'>❌ ไม่มี Auto Reply rules ในระบบ</p>";
} else {
    echo "<p>พบ " . count($allRules) . " rules:</p>";
    echo "<table><tr><th>ID</th><th>line_account_id</th><th>Keyword</th><th>Match Type</th><th>Reply Type</th><th>Active</th><th>Priority</th></tr>";
    foreach ($allRules as $rule) {
        $activeClass = $rule['is_active'] ? 'background:#D1FAE5' : 'background:#FEE2E2';
        echo "<tr style='{$activeClass}'>";
        echo "<td>{$rule['id']}</td>";
        echo "<td>" . ($rule['line_account_id'] ?? 'NULL (global)') . "</td>";
        echo "<td><strong>{$rule['keyword']}</strong></td>";
        echo "<td>{$rule['match_type']}</td>";
        echo "<td>{$rule['reply_type']}</td>";
        echo "<td>" . ($rule['is_active'] ? '✅' : '❌') . "</td>";
        echo "<td>{$rule['priority']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 3. Rules for this account
echo "<div class='box'><h2>3. Rules สำหรับ Account ID: {$lineAccountId}</h2>";
$stmt = $db->prepare("SELECT * FROM auto_replies WHERE is_active = 1 AND (line_account_id = ? OR line_account_id IS NULL) ORDER BY line_account_id DESC, priority DESC");
$stmt->execute([$lineAccountId]);
$accountRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($accountRules)) {
    echo "<p class='error'>❌ ไม่มี Active rules สำหรับ account นี้</p>";
    echo "<p class='info'>💡 ตรวจสอบว่า:<br>- rule มี is_active = 1<br>- rule มี line_account_id = {$lineAccountId} หรือ NULL</p>";
} else {
    echo "<p class='success'>✅ พบ " . count($accountRules) . " active rules</p>";
    echo "<table><tr><th>ID</th><th>Keyword</th><th>Match Type</th><th>Reply Content (50 chars)</th></tr>";
    foreach ($accountRules as $rule) {
        echo "<tr>";
        echo "<td>{$rule['id']}</td>";
        echo "<td><strong>{$rule['keyword']}</strong></td>";
        echo "<td>{$rule['match_type']}</td>";
        echo "<td>" . htmlspecialchars(mb_substr($rule['reply_content'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 4. Test Matching
echo "<div class='box'><h2>4. ทดสอบ Matching</h2>";
echo "<form method='GET'>";
echo "<input type='hidden' name='account_id' value='{$lineAccountId}'>";
echo "<input type='text' name='msg' value='" . htmlspecialchars($testMessage) . "' placeholder='พิมพ์ข้อความทดสอบ' style='padding:10px;width:300px;border:1px solid #ccc;border-radius:8px'>";
echo "<button type='submit' style='padding:10px 20px;background:#3B82F6;color:white;border:none;border-radius:8px;margin-left:10px;cursor:pointer'>ทดสอบ</button>";
echo "</form>";

echo "<h3>ทดสอบข้อความ: \"<strong>{$testMessage}</strong>\"</h3>";

$matchFound = false;
foreach ($accountRules as $rule) {
    $matched = false;
    $matchReason = '';
    
    switch ($rule['match_type']) {
        case 'exact':
            $matched = (mb_strtolower($testMessage) === mb_strtolower($rule['keyword']));
            $matchReason = "exact: '{$testMessage}' === '{$rule['keyword']}'";
            break;
        case 'contains':
            $matched = (mb_stripos($testMessage, $rule['keyword']) !== false);
            $matchReason = "contains: '{$rule['keyword']}' in '{$testMessage}'";
            break;
        case 'starts_with':
            $matched = (mb_stripos($testMessage, $rule['keyword']) === 0);
            $matchReason = "starts_with: '{$testMessage}' starts with '{$rule['keyword']}'";
            break;
        case 'regex':
            $matched = @preg_match('/' . $rule['keyword'] . '/iu', $testMessage);
            $matchReason = "regex: /{$rule['keyword']}/i";
            break;
    }
    
    if ($matched) {
        echo "<div class='success'>✅ MATCH! Rule ID: {$rule['id']} | Keyword: \"{$rule['keyword']}\" | {$matchReason}</div>";
        echo "<p><strong>Reply:</strong> " . htmlspecialchars(mb_substr($rule['reply_content'], 0, 200)) . "</p>";
        $matchFound = true;
        break; // First match wins
    } else {
        echo "<div class='info'>❌ No match: Rule ID: {$rule['id']} | Keyword: \"{$rule['keyword']}\" | {$matchReason}</div>";
    }
}

if (!$matchFound) {
    echo "<div class='error' style='font-size:18px;margin-top:20px'>❌ ไม่มี rule ที่ match กับข้อความ \"{$testMessage}\"</div>";
}
echo "</div>";

// 5. Quick Test Keywords
echo "<div class='box'><h2>5. ทดสอบด่วน</h2>";
$quickTests = ['สวัสดี', 'hello', 'ราคา', 'price', 'ขอบคุณ', 'thanks', 'เมนู', 'menu'];
echo "<p>คลิกเพื่อทดสอบ:</p>";
foreach ($quickTests as $test) {
    echo "<a href='?account_id={$lineAccountId}&msg=" . urlencode($test) . "' style='display:inline-block;padding:8px 16px;margin:5px;background:#E5E7EB;border-radius:20px;text-decoration:none;color:#374151'>{$test}</a>";
}
echo "</div>";

// 6. Dev Logs
echo "<div class='box'><h2>6. Dev Logs (auto reply related)</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM dev_logs WHERE (message LIKE '%auto%' OR message LIKE '%general%' OR source LIKE '%webhook%') ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "<p class='info'>ไม่มี logs</p>";
    } else {
        foreach ($logs as $log) {
            $color = $log['log_type'] === 'error' ? '#FEE2E2' : ($log['log_type'] === 'info' ? '#DBEAFE' : '#F3F4F6');
            echo "<div style='background:{$color};padding:10px;margin:5px 0;border-radius:8px;font-size:12px'>";
            echo "<strong>{$log['created_at']}</strong> [{$log['log_type']}] {$log['source']}: {$log['message']}";
            if ($log['data']) {
                $data = json_decode($log['data'], true);
                if ($data) echo "<br><code>" . htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE)) . "</code>";
            }
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";
