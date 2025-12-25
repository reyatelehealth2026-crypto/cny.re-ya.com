<?php
/**
 * Debug Catalog JSON - Show exact JSON being sent
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/UnifiedShop.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

$shop = new UnifiedShop($db, null, $currentBotId);
$products = $shop->getItems(['in_stock' => true], 6);

echo "<h1>Debug Catalog JSON</h1>";
echo "<style>body{font-family:monospace;padding:20px;} pre{background:#1e1e1e;color:#d4d4d4;padding:15px;border-radius:5px;overflow:auto;max-height:800px;font-size:12px;} .ok{color:green;} .error{color:red;}</style>";

if (empty($products)) {
    echo "<p class='error'>No products found</p>";
    exit;
}

// Use same function as broadcast-catalog.php
function buildBubble($products, $cols, $title, $page, $total, $allCount) {
    $rows = [];
    foreach (array_chunk($products, $cols) as $rowItems) {
        $rowContents = [];
        foreach ($rowItems as $p) {
            $imageUrl = $p['image_url'] ?: 'https://via.placeholder.com/100x100/06C755/FFFFFF?text=No+Image';
            if (strpos($imageUrl, 'http://') === 0) {
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
            }
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://via.placeholder.com/100x100/06C755/FFFFFF?text=' . urlencode(mb_substr($p['name'], 0, 5));
            }
            
            $rowContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'flex' => 1,
                'spacing' => 'xs',
                'paddingAll' => 'xs',
                'contents' => [
                    ['type' => 'image', 'url' => $imageUrl, 'size' => 'full', 'aspectRatio' => '1:1', 'aspectMode' => 'cover'],
                    ['type' => 'text', 'text' => mb_substr($p['name'], 0, 10) . (mb_strlen($p['name']) > 10 ? '..' : ''), 'size' => 'xxs', 'color' => '#333333', 'wrap' => false],
                    ['type' => 'text', 'text' => '฿' . number_format($p['sale_price'] ?: $p['price']), 'size' => 'xs', 'color' => '#06C755', 'weight' => 'bold']
                ]
            ];
        }
        while (count($rowContents) < $cols) {
            $rowContents[] = ['type' => 'box', 'layout' => 'vertical', 'contents' => [], 'flex' => 1];
        }
        $rows[] = ['type' => 'box', 'layout' => 'horizontal', 'contents' => $rowContents, 'spacing' => 'sm'];
    }
    
    $header = $total > 1 ? "{$title} ({$page}/{$total})" : $title;
    
    return [
        'type' => 'bubble',
        'header' => [
            'type' => 'box',
            'layout' => 'horizontal',
            'paddingAll' => 'lg',
            'backgroundColor' => '#f8fafc',
            'contents' => [
                ['type' => 'text', 'text' => $header, 'weight' => 'bold', 'size' => 'md', 'flex' => 1],
                ['type' => 'text', 'text' => "{$allCount} items", 'size' => 'xs', 'color' => '#888888', 'align' => 'end']
            ]
        ],
        'body' => [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => $rows,
            'spacing' => 'sm',
            'paddingAll' => 'md'
        ],
        'footer' => [
            'type' => 'box',
            'layout' => 'horizontal',
            'paddingAll' => 'md',
            'contents' => [
                ['type' => 'button', 'action' => ['type' => 'message', 'label' => 'View All', 'text' => 'shop'], 'style' => 'primary', 'color' => '#06C755', 'height' => 'sm'],
                ['type' => 'button', 'action' => ['type' => 'message', 'label' => 'Cart', 'text' => 'cart'], 'style' => 'secondary', 'height' => 'sm', 'margin' => 'sm']
            ]
        ]
    ];
}

$flex = buildBubble(array_slice($products, 0, 4), 2, 'Products', 1, 1, count($products));

$flexMessage = [
    'type' => 'flex',
    'altText' => 'Products',
    'contents' => $flex
];

$fullPayload = ['messages' => [$flexMessage]];

echo "<h2>1. Full Payload (what LINE API receives)</h2>";
echo "<pre>" . json_encode($fullPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Validate with LINE Flex Message Simulator format
echo "<h2>2. Flex Contents Only (for LINE Simulator)</h2>";
echo "<pre>" . json_encode($flex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Test send
echo "<h2>3. Test Send</h2>";
if (isset($_GET['send'])) {
    require_once 'classes/LineAccountManager.php';
    $lineManager = new LineAccountManager($db);
    $line = $lineManager->getLineAPI($currentBotId);
    
    if ($line) {
        $result = $line->broadcastMessage([$flexMessage]);
        echo "<p>HTTP Code: <b>{$result['code']}</b></p>";
        if ($result['code'] !== 200) {
            echo "<p class='error'>Error Response:</p>";
            echo "<pre>" . json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<p class='ok'>✅ Success!</p>";
        }
    } else {
        echo "<p class='error'>No LINE API</p>";
    }
} else {
    echo "<p><a href='?send=1' onclick='return confirm(\"Send?\")'>Click to send test broadcast</a></p>";
}

// Copy button
echo "<h2>4. Copy JSON for LINE Simulator</h2>";
echo "<p>Copy the Flex Contents JSON above and paste into <a href='https://developers.line.biz/flex-simulator/' target='_blank'>LINE Flex Message Simulator</a> to validate.</p>";
