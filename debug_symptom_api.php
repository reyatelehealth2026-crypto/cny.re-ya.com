<?php
/**
 * Debug Symptom Assessment API
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Symptom Assessment API</h2>";

// 1. Test config
echo "<h3>1. Test Config</h3>";
require_once __DIR__ . '/config/config.php';
echo "✅ config.php loaded<br>";

// 2. Test database
echo "<h3>2. Test Database</h3>";
require_once __DIR__ . '/config/database.php';
echo "✅ database.php loaded<br>";

require_once __DIR__ . '/modules/Core/Database.php';
$db = \Modules\Core\Database::getInstance()->getConnection();
echo "✅ Database connected<br>";

// 3. Test API Key
echo "<h3>3. Test API Key</h3>";
$apiKey = null;
try {
    $stmt = $db->prepare("SELECT gemini_api_key FROM ai_chat_settings WHERE is_enabled = 1 LIMIT 1");
    $stmt->execute();
    $apiKey = $stmt->fetchColumn();
    
    if ($apiKey) {
        echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...<br>";
    } else {
        echo "❌ No API Key found<br>";
    }
} catch (Exception $e) {
    echo "❌ API Key error: " . $e->getMessage() . "<br>";
}

// 4. Test table exists
echo "<h3>4. Test Tables</h3>";
$tables = ['symptom_assessments', 'business_items', 'ai_chat_settings'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            echo "✅ {$table}: exists ({$count} rows)<br>";
        } else {
            echo "⚠️ {$table}: NOT EXISTS<br>";
        }
    } catch (Exception $e) {
        echo "❌ {$table}: " . $e->getMessage() . "<br>";
    }
}

// 5. Test Gemini API directly
echo "<h3>5. Test Gemini API</h3>";
if ($apiKey) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    
    $data = [
        'contents' => [['parts' => [['text' => 'ตอบว่า OK']]]],
        'generationConfig' => ['maxOutputTokens' => 10]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ Gemini API working (HTTP {$httpCode})<br>";
    } else {
        echo "❌ Gemini API error (HTTP {$httpCode})<br>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
} else {
    echo "⚠️ Skip - no API key<br>";
}

// 6. Test API via HTTP
echo "<h3>6. Test API via HTTP</h3>";
$testData = [
    'symptoms' => ['ปวดหัว'],
    'severity' => 5
];

$apiUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/v1/api/symptom-assessment.php';
echo "URL: {$apiUrl}<br>";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}<br>";
if ($error) {
    echo "cURL Error: {$error}<br>";
}

if ($response) {
    $result = json_decode($response, true);
    if ($result) {
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "Raw response:<br><pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    }
} else {
    echo "No response<br>";
}

echo "<h3>Done</h3>";
