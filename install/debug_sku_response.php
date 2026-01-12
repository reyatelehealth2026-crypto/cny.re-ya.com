<?php
/**
 * Debug: Check why /sku commands don't get response
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug /sku Response Issue</h1>";

// 1. Check AI Settings
echo "<h2>1. AI Settings</h2>";
$stmt = $db->query("SELECT * FROM ai_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($settings) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><td>is_enabled</td><td>" . ($settings['is_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>ai_mode</td><td>" . ($settings['ai_mode'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>gemini_api_key</td><td>" . (empty($settings['gemini_api_key']) ? '❌ Empty' : '✅ Set (' . strlen($settings['gemini_api_key']) . ' chars)') . "</td></tr>";
    echo "<tr><td>model</td><td>" . ($settings['model'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>auto_load_products</td><td>" . ($settings['auto_load_products'] ?? 'N/A') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color:red'>❌ No AI settings found!</p>";
}

// 2. Check recent AI logs for /sku
echo "<h2>2. Recent AI Logs (last 30)</h2>";
$stmt = $db->query("
    SELECT * FROM dev_logs 
    WHERE source LIKE 'AI%' OR source = 'webhook' OR source = 'debug'
    ORDER BY created_at DESC 
    LIMIT 30
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;font-size:12px'>";
echo "<tr style='background:#f0f0f0'><th>Time</th><th>Source</th><th>Message</th><th>Data</th></tr>";
foreach ($logs as $log) {
    $rowColor = 'white';
    if (strpos($log['message'], 'failed') !== false || strpos($log['message'], 'error') !== false) {
        $rowColor = '#ffe0e0';
    } elseif (strpos($log['message'], 'success') !== false) {
        $rowColor = '#e0ffe0';
    } elseif (strpos($log['message'], 'null') !== false || strpos($log['message'], 'warning') !== false) {
        $rowColor = '#fff0e0';
    }
    
    echo "<tr style='background:{$rowColor}'>";
    echo "<td nowrap>" . $log['created_at'] . "</td>";
    echo "<td>" . htmlspecialchars($log['source']) . "</td>";
    echo "<td>" . htmlspecialchars($log['message']) . "</td>";
    echo "<td><pre style='max-width:500px;overflow:auto;margin:0;white-space:pre-wrap'>" . htmlspecialchars(mb_substr($log['data'] ?? '', 0, 800)) . "</pre></td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check if GeminiChat is enabled
echo "<h2>3. GeminiChat Status Check</h2>";
require_once __DIR__ . '/../classes/GeminiChat.php';

$lineAccountId = $settings['line_account_id'] ?? 1;
$gemini = new GeminiChat($db, $lineAccountId);

echo "<table border='1' cellpadding='5'>";
echo "<tr><td>isEnabled()</td><td>" . ($gemini->isEnabled() ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>getMode()</td><td>" . $gemini->getMode() . "</td></tr>";
$geminiSettings = $gemini->getSettings();
echo "<tr><td>is_enabled (setting)</td><td>" . ($geminiSettings['is_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "</table>";

// 4. Test generateResponse
echo "<h2>4. Test generateResponse</h2>";
$testMessage = "sku0001";
echo "<p>Testing with message: <b>$testMessage</b></p>";

$startTime = microtime(true);
$response = $gemini->generateResponse($testMessage, null, []);
$elapsed = round((microtime(true) - $startTime) * 1000);

echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Response</td><td>" . ($response ? htmlspecialchars(mb_substr($response, 0, 500)) : '<span style="color:red">NULL</span>') . "</td></tr>";
echo "<tr><td>Elapsed</td><td>{$elapsed} ms</td></tr>";
echo "</table>";

// 5. Check products
echo "<h2>5. Products in Database</h2>";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM business_items WHERE is_active = 1");
$cnt = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Active products: <b>" . $cnt['cnt'] . "</b></p>";

$stmt = $db->query("SELECT name, sku, price FROM business_items WHERE is_active = 1 LIMIT 5");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($products) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Name</th><th>SKU</th><th>Price</th></tr>";
    foreach ($products as $p) {
        echo "<tr><td>" . htmlspecialchars($p['name']) . "</td><td>" . htmlspecialchars($p['sku'] ?? 'N/A') . "</td><td>" . number_format($p['price'] ?? 0) . "</td></tr>";
    }
    echo "</table>";
}

echo "<hr><p><small>Generated at: " . date('Y-m-d H:i:s') . "</small></p>";
