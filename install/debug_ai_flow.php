<?php
/**
 * Debug AI Flow - ตรวจสอบ flow การทำงานของ AI
 * เปิดไฟล์นี้ในเบราว์เซอร์เพื่อดูสถานะ
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug AI Flow</h1>";
echo "<style>
body{font-family:sans-serif;padding:20px;max-width:1200px;margin:0 auto;} 
table{border-collapse:collapse;margin:10px 0;width:100%;} 
th,td{border:1px solid #ccc;padding:8px;text-align:left;} 
.ok{color:green;font-weight:bold;} 
.error{color:red;font-weight:bold;} 
.warning{color:orange;font-weight:bold;}
.box{background:#f5f5f5;padding:15px;border-radius:8px;margin:10px 0;}
pre{background:#1e1e1e;color:#d4d4d4;padding:15px;border-radius:8px;overflow-x:auto;}
</style>";

// ดึง line_account_id
$stmt = $db->query("SELECT id, name FROM line_accounts LIMIT 1");
$account = $stmt->fetch(PDO::FETCH_ASSOC);
$lineAccountId = $account ? $account['id'] : null;

echo "<p>Testing with LINE Account: <b>" . ($account['name'] ?? 'N/A') . "</b> (ID: {$lineAccountId})</p>";

// ===== 1. ตรวจสอบ ai_settings =====
echo "<h2>1. ai_settings Table</h2>";
$stmt = $db->prepare("SELECT * FROM ai_settings WHERE line_account_id = ?");
$stmt->execute([$lineAccountId]);
$aiSettings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($aiSettings) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
    
    $fields = [
        'is_enabled' => ['expected' => 1, 'label' => 'Is Enabled'],
        'ai_mode' => ['expected' => 'sales', 'label' => 'AI Mode'],
        'gemini_api_key' => ['expected' => 'not_empty', 'label' => 'Gemini API Key'],
        'model' => ['expected' => 'any', 'label' => 'Model'],
        'sender_name' => ['expected' => 'any', 'label' => 'Sender Name'],
    ];
    
    foreach ($fields as $field => $config) {
        $value = $aiSettings[$field] ?? null;
        $displayValue = $field === 'gemini_api_key' ? (empty($value) ? 'EMPTY' : 'SET (' . strlen($value) . ' chars)') : $value;
        
        $status = '';
        if ($config['expected'] === 1) {
            $status = $value == 1 ? '<span class="ok">✅ OK</span>' : '<span class="error">❌ Should be 1</span>';
        } elseif ($config['expected'] === 'not_empty') {
            $status = !empty($value) ? '<span class="ok">✅ OK</span>' : '<span class="error">❌ EMPTY!</span>';
        } elseif ($config['expected'] === 'sales') {
            $status = $value === 'sales' ? '<span class="ok">✅ OK</span>' : '<span class="warning">⚠️ Current: ' . $value . '</span>';
        } else {
            $status = '<span class="ok">ℹ️</span>';
        }
        
        echo "<tr><td>{$config['label']}</td><td>{$displayValue}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ ไม่พบข้อมูลใน ai_settings สำหรับ line_account_id = {$lineAccountId}</p>";
}

// ===== 2. ตรวจสอบ ai_chat_settings =====
echo "<h2>2. ai_chat_settings Table (Fallback)</h2>";
$stmt = $db->prepare("SELECT * FROM ai_chat_settings WHERE line_account_id = ?");
$stmt->execute([$lineAccountId]);
$chatSettings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($chatSettings) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>is_enabled</td><td>" . ($chatSettings['is_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>gemini_api_key</td><td>" . (empty($chatSettings['gemini_api_key']) ? 'EMPTY' : 'SET (' . strlen($chatSettings['gemini_api_key']) . ' chars)') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ ไม่พบข้อมูลใน ai_chat_settings</p>";
}

// ===== 3. ทดสอบ GeminiChat =====
echo "<h2>3. Test GeminiChat Class</h2>";
require_once __DIR__ . '/../classes/GeminiChat.php';

$gemini = new GeminiChat($db, $lineAccountId);
$geminiSettings = $gemini->getSettings();

echo "<table>";
echo "<tr><td>isEnabled()</td><td>" . ($gemini->isEnabled() ? '<span class="ok">✅ Yes</span>' : '<span class="error">❌ No</span>') . "</td></tr>";
echo "<tr><td>getMode()</td><td><b>" . $gemini->getMode() . "</b></td></tr>";
echo "<tr><td>settings[is_enabled]</td><td>" . ($geminiSettings['is_enabled'] ? 'true' : 'false') . "</td></tr>";
echo "<tr><td>settings[ai_mode]</td><td>" . ($geminiSettings['ai_mode'] ?? 'N/A') . "</td></tr>";
echo "</table>";

if (!$gemini->isEnabled()) {
    echo "<div class='box'>";
    echo "<h3>🔴 GeminiChat ไม่ enabled!</h3>";
    echo "<p>สาเหตุที่เป็นไปได้:</p>";
    echo "<ul>";
    echo "<li>ai_settings.is_enabled = 0</li>";
    echo "<li>ai_settings.gemini_api_key ว่างเปล่า</li>";
    echo "<li>ai_chat_settings.gemini_api_key ว่างเปล่า (fallback)</li>";
    echo "</ul>";
    echo "</div>";
}

// ===== 4. ทดสอบ PharmacyAIAdapter =====
echo "<h2>4. Test PharmacyAIAdapter Class</h2>";
require_once __DIR__ . '/../modules/AIChat/Adapters/PharmacyAIAdapter.php';

$pharmacy = new \Modules\AIChat\Adapters\PharmacyAIAdapter($db, $lineAccountId);

echo "<table>";
echo "<tr><td>isEnabled()</td><td>" . ($pharmacy->isEnabled() ? '<span class="ok">✅ Yes</span>' : '<span class="error">❌ No</span>') . "</td></tr>";
echo "</table>";

// ===== 5. สรุปปัญหา =====
echo "<h2>5. 📋 สรุปการวิเคราะห์</h2>";
echo "<div class='box'>";

$problems = [];
$solutions = [];

// Check ai_settings
if (!$aiSettings) {
    $problems[] = "ไม่มีข้อมูลใน ai_settings";
    $solutions[] = "INSERT INTO ai_settings (line_account_id, is_enabled, ai_mode) VALUES ({$lineAccountId}, 1, 'sales')";
} else {
    if ($aiSettings['is_enabled'] != 1) {
        $problems[] = "ai_settings.is_enabled = 0";
        $solutions[] = "UPDATE ai_settings SET is_enabled = 1 WHERE line_account_id = {$lineAccountId}";
    }
    if (empty($aiSettings['gemini_api_key'])) {
        $problems[] = "ai_settings.gemini_api_key ว่างเปล่า";
        if ($chatSettings && !empty($chatSettings['gemini_api_key'])) {
            $solutions[] = "Copy API key จาก ai_chat_settings";
        } else {
            $solutions[] = "ต้องใส่ Gemini API Key ในหน้าตั้งค่า AI";
        }
    }
}

// Check GeminiChat
if (!$gemini->isEnabled()) {
    $problems[] = "GeminiChat.isEnabled() = false → ทำให้ไม่เข้า Sales Mode";
}

// Check PharmacyAI
if ($pharmacy->isEnabled() && !$gemini->isEnabled()) {
    $problems[] = "PharmacyAI enabled แต่ GeminiChat disabled → ระบบจะ fallthrough ไปใช้ PharmacyAI";
}

if (empty($problems)) {
    echo "<p class='ok'>✅ ไม่พบปัญหา - ระบบควรทำงานได้ปกติ</p>";
} else {
    echo "<h3>🔴 พบปัญหา:</h3>";
    echo "<ul>";
    foreach ($problems as $p) {
        echo "<li class='error'>{$p}</li>";
    }
    echo "</ul>";
    
    echo "<h3>🔧 วิธีแก้ไข:</h3>";
    echo "<ul>";
    foreach ($solutions as $s) {
        echo "<li>{$s}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// ===== 6. ดู dev_logs ล่าสุด =====
echo "<h2>6. 📜 Dev Logs ล่าสุด (AI related)</h2>";
try {
    $stmt = $db->query("SELECT * FROM dev_logs WHERE source LIKE 'AI%' OR source LIKE 'ai%' ORDER BY created_at DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($logs) {
        echo "<table>";
        echo "<tr><th>Time</th><th>Source</th><th>Message</th><th>Data</th></tr>";
        foreach ($logs as $log) {
            $data = json_decode($log['data'] ?? '{}', true);
            $dataStr = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
            echo "<tr>";
            echo "<td style='white-space:nowrap'>" . substr($log['created_at'], 11) . "</td>";
            echo "<td>{$log['source']}</td>";
            echo "<td>{$log['message']}</td>";
            echo "<td><pre style='margin:0;font-size:11px;max-width:400px;overflow:auto'>{$dataStr}</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ไม่พบ logs</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// ===== 7. Quick Fix =====
echo "<h2>7. 🚀 Quick Fix</h2>";

if (isset($_POST['fix_all'])) {
    try {
        // 1. Copy API key from ai_chat_settings if needed
        if ($chatSettings && !empty($chatSettings['gemini_api_key'])) {
            $apiKey = $chatSettings['gemini_api_key'];
        } else {
            $apiKey = $aiSettings['gemini_api_key'] ?? '';
        }
        
        // 2. Check if ai_settings exists
        $stmt = $db->prepare("SELECT id FROM ai_settings WHERE line_account_id = ?");
        $stmt->execute([$lineAccountId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $db->prepare("UPDATE ai_settings SET is_enabled = 1, ai_mode = 'sales', gemini_api_key = COALESCE(NULLIF(?, ''), gemini_api_key) WHERE line_account_id = ?");
            $stmt->execute([$apiKey, $lineAccountId]);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO ai_settings (line_account_id, is_enabled, ai_mode, gemini_api_key, model) VALUES (?, 1, 'sales', ?, 'gemini-2.0-flash')");
            $stmt->execute([$lineAccountId, $apiKey]);
        }
        
        echo "<p class='ok'>✅ แก้ไขสำเร็จ! กรุณา refresh หน้านี้</p>";
        echo "<p><a href=''>🔄 Refresh</a></p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>

<form method="POST">
    <button type="submit" name="fix_all" style="padding:15px 30px;background:#10b981;color:white;border:none;border-radius:8px;cursor:pointer;font-size:16px;">
        🔧 Fix All: Set is_enabled=1, ai_mode='sales', copy API key
    </button>
</form>

<hr>
<h2>8. 🧪 Test AI Response</h2>
<form method="POST">
    <input type="text" name="test_message" placeholder="พิมพ์ข้อความทดสอบ..." style="padding:10px;width:300px;border:1px solid #ccc;border-radius:4px;">
    <button type="submit" name="test_ai" style="padding:10px 20px;background:#3b82f6;color:white;border:none;border-radius:4px;cursor:pointer;">
        🤖 Test AI
    </button>
</form>

<?php
if (isset($_POST['test_ai']) && !empty($_POST['test_message'])) {
    $testMessage = $_POST['test_message'];
    echo "<h3>Testing: \"{$testMessage}\"</h3>";
    
    // Reload GeminiChat
    $gemini = new GeminiChat($db, $lineAccountId);
    
    echo "<p>GeminiChat.isEnabled(): " . ($gemini->isEnabled() ? 'Yes' : 'No') . "</p>";
    echo "<p>GeminiChat.getMode(): " . $gemini->getMode() . "</p>";
    
    if ($gemini->isEnabled()) {
        $response = $gemini->generateResponse($testMessage, null, []);
        echo "<div class='box'>";
        echo "<h4>AI Response:</h4>";
        echo "<p>" . nl2br(htmlspecialchars($response ?? 'NULL')) . "</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>GeminiChat not enabled - cannot test</p>";
    }
}
?>
