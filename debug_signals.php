<?php
/**
 * Debug Video Call Signals
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get call ID from query
$callId = $_GET['call_id'] ?? null;

echo "<h1>🔍 Debug Video Call Signals</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:1000px;margin:0 auto}
table{width:100%;border-collapse:collapse;margin:15px 0}
th,td{padding:10px;border:1px solid #ddd;text-align:left}
th{background:#f5f5f5}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin:15px 0}
pre{background:#1f2937;color:#10b981;padding:15px;border-radius:8px;overflow-x:auto;font-size:12px}</style>";

// Check columns
echo "<div class='box'><h2>1. ตรวจสอบ Columns</h2>";
$cols = $db->query("DESCRIBE video_call_signals")->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
foreach ($cols as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table></div>";

// Recent calls
echo "<div class='box'><h2>2. การโทรล่าสุด</h2>";
$calls = $db->query("SELECT * FROM video_calls ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>ID</th><th>Status</th><th>Display Name</th><th>Created</th><th>Action</th></tr>";
foreach ($calls as $call) {
    $selected = $callId == $call['id'] ? 'style="background:#d1fae5"' : '';
    echo "<tr {$selected}>";
    echo "<td>{$call['id']}</td>";
    echo "<td>{$call['status']}</td>";
    echo "<td>{$call['display_name']}</td>";
    echo "<td>{$call['created_at']}</td>";
    echo "<td><a href='?call_id={$call['id']}'>ดู Signals</a></td>";
    echo "</tr>";
}
echo "</table></div>";

// Signals for selected call
if ($callId) {
    echo "<div class='box'><h2>3. Signals สำหรับ Call #{$callId}</h2>";
    
    $signals = $db->prepare("SELECT * FROM video_call_signals WHERE call_id = ? ORDER BY created_at ASC");
    $signals->execute([$callId]);
    $sigs = $signals->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sigs)) {
        echo "<p style='color:red'>❌ ไม่พบ signals!</p>";
    } else {
        echo "<p>พบ " . count($sigs) . " signals</p>";
        echo "<table><tr><th>ID</th><th>Type</th><th>From</th><th>Processed</th><th>Created</th><th>Data (50 chars)</th></tr>";
        foreach ($sigs as $sig) {
            $from = $sig['from_who'] ?? 'N/A';
            $processed = isset($sig['processed']) ? ($sig['processed'] ? '✅' : '❌') : 'N/A';
            $data = substr($sig['signal_data'], 0, 50) . '...';
            echo "<tr>";
            echo "<td>{$sig['id']}</td>";
            echo "<td><strong>{$sig['signal_type']}</strong></td>";
            echo "<td>{$from}</td>";
            echo "<td>{$processed}</td>";
            echo "<td>{$sig['created_at']}</td>";
            echo "<td><code>{$data}</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for answer
        $hasOffer = false;
        $hasAnswer = false;
        foreach ($sigs as $sig) {
            if ($sig['signal_type'] === 'offer') $hasOffer = true;
            if ($sig['signal_type'] === 'answer') $hasAnswer = true;
        }
        
        echo "<h3>สถานะ:</h3>";
        echo "<p>" . ($hasOffer ? "✅" : "❌") . " Offer (จากลูกค้า)</p>";
        echo "<p>" . ($hasAnswer ? "✅" : "❌") . " Answer (จากแอดมิน)</p>";
        
        if ($hasOffer && !$hasAnswer) {
            echo "<p style='color:orange'><strong>⚠️ ปัญหา: มี Offer แต่ไม่มี Answer!</strong></p>";
            echo "<p>แอดมินอาจยังไม่ได้รับ offer หรือไม่ได้ส่ง answer กลับ</p>";
        }
    }
    echo "</div>";
}

// Test API
echo "<div class='box'><h2>4. ทดสอบ API</h2>";
if ($callId) {
    echo "<p>ทดสอบ get_signals สำหรับ customer:</p>";
    $url = BASE_URL . "api/video-call.php?action=get_signals&call_id={$callId}&for=customer";
    echo "<p><a href='{$url}' target='_blank'>{$url}</a></p>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
echo "</div>";
?>
