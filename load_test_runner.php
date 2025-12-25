<?php
/**
 * Load Test Runner - ทำการทดสอบจริง
 */

// Suppress all errors from output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
set_time_limit(300);
ini_set('memory_limit', '512M');

// Error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode([
        'error' => true,
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

set_exception_handler(function($e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
    exit;
});

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Config error: ' . $e->getMessage()]);
    exit;
}

$type = $_GET['type'] ?? 'database';
$concurrentUsers = min(500, max(1, (int)($_GET['users'] ?? 10)));
$requestsPerUser = min(50, max(1, (int)($_GET['requests'] ?? 5)));

$results = [
    'type' => $type,
    'concurrent_users' => $concurrentUsers,
    'requests_per_user' => $requestsPerUser,
    'total_requests' => 0,
    'successful' => 0,
    'failed' => 0,
    'success_rate' => 0,
    'avg_response_time' => 0,
    'min_response_time' => 0,
    'max_response_time' => 0,
    'requests_per_second' => 0,
    'response_times' => [],
    'errors' => [],
];

$startTime = microtime(true);
$responseTimes = [];

// Test functions
function testDatabase($db) {
    $start = microtime(true);
    try {
        // Simulate typical database operations
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT * FROM messages ORDER BY id DESC LIMIT 10");
        $stmt->fetchAll();
        
        // Try business_items first, fallback to products
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE is_active = 1");
            $stmt->fetchColumn();
        } catch (Exception $e) {
            $stmt = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
            $stmt->fetchColumn();
        }
        
        return [
            'success' => true,
            'time' => (microtime(true) - $start) * 1000
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'time' => (microtime(true) - $start) * 1000,
            'error' => $e->getMessage()
        ];
    }
}

function testAPI($baseUrl) {
    $start = microtime(true);
    $endpoints = [
        '/api/shop-products.php?limit=10',
        '/api/checkout.php?action=get_products',
    ];
    
    $endpoint = $endpoints[array_rand($endpoints)];
    
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 400,
        'time' => (microtime(true) - $start) * 1000,
        'http_code' => $httpCode
    ];
}

function testWebhook($baseUrl) {
    $start = microtime(true);
    
    // Simulate LINE webhook payload
    $payload = json_encode([
        'events' => [[
            'type' => 'message',
            'replyToken' => 'test_' . uniqid(),
            'source' => [
                'type' => 'user',
                'userId' => 'U' . str_pad(rand(1, 999999), 32, '0', STR_PAD_LEFT)
            ],
            'message' => [
                'type' => 'text',
                'id' => rand(100000, 999999),
                'text' => 'ทดสอบ'
            ]
        ]]
    ]);
    
    $ch = curl_init($baseUrl . '/webhook.php?test=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Line-Signature: test'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 500,
        'time' => (microtime(true) - $start) * 1000,
        'http_code' => $httpCode
    ];
}

function testChat($db) {
    $start = microtime(true);
    try {
        // Simulate chat operations
        $userId = rand(1, 1000);
        $lineUserId = 'U' . str_pad(rand(1, 999999), 32, '0', STR_PAD_LEFT);
        
        // Get user messages
        $stmt = $db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY id DESC LIMIT 20");
        $stmt->execute([$userId]);
        $stmt->fetchAll();
        
        // Get user info
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $stmt->fetch();
        
        // Simulate message insert (without actually inserting)
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        
        return [
            'success' => true,
            'time' => (microtime(true) - $start) * 1000
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'time' => (microtime(true) - $start) * 1000,
            'error' => $e->getMessage()
        ];
    }
}

// Run tests
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$baseUrl = rtrim(BASE_URL, '/');

$totalRequests = $concurrentUsers * $requestsPerUser;

// Simulate concurrent requests (sequential in PHP, but measures capacity)
for ($user = 0; $user < $concurrentUsers; $user++) {
    for ($req = 0; $req < $requestsPerUser; $req++) {
        $result = null;
        
        switch ($type) {
            case 'database':
                $result = testDatabase($db);
                break;
            case 'api':
                $result = testAPI($baseUrl);
                break;
            case 'webhook':
                $result = testWebhook($baseUrl);
                break;
            case 'chat':
                $result = testChat($db);
                break;
            case 'full':
                // Mix of all tests
                $tests = ['database', 'api', 'chat'];
                $testType = $tests[array_rand($tests)];
                switch ($testType) {
                    case 'database': $result = testDatabase($db); break;
                    case 'api': $result = testAPI($baseUrl); break;
                    case 'chat': $result = testChat($db); break;
                }
                break;
        }
        
        if ($result) {
            $results['total_requests']++;
            $responseTimes[] = $result['time'];
            
            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                if (isset($result['error'])) {
                    $results['errors'][] = $result['error'];
                }
            }
        }
    }
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;

// Calculate statistics
if (!empty($responseTimes)) {
    $results['avg_response_time'] = round(array_sum($responseTimes) / count($responseTimes), 2);
    $results['min_response_time'] = round(min($responseTimes), 2);
    $results['max_response_time'] = round(max($responseTimes), 2);
}

$results['success_rate'] = $results['total_requests'] > 0 
    ? round(($results['successful'] / $results['total_requests']) * 100, 1) 
    : 0;

$results['requests_per_second'] = $totalTime > 0 
    ? round($results['total_requests'] / $totalTime, 2) 
    : 0;

$results['total_time'] = round($totalTime, 2);

// Response time distribution
$distribution = [
    '0-100ms' => 0,
    '100-500ms' => 0,
    '500-1000ms' => 0,
    '1000ms+' => 0
];

foreach ($responseTimes as $time) {
    if ($time < 100) $distribution['0-100ms']++;
    elseif ($time < 500) $distribution['100-500ms']++;
    elseif ($time < 1000) $distribution['500-1000ms']++;
    else $distribution['1000ms+']++;
}

$results['response_times_distribution'] = [
    'labels' => array_keys($distribution),
    'data' => array_values($distribution)
];

// Limit errors array
$results['errors'] = array_slice(array_unique($results['errors']), 0, 5);

echo json_encode($results, JSON_PRETTY_PRINT);
