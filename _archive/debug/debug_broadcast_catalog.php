<?php
/**
 * Debug Broadcast Catalog - Check Flex Message Structure
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/UnifiedShop.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? null;

if (!$currentBotId) {
    require_once 'classes/LineAccountManager.php';
    $lineManager = new LineAccountManager($db);
    $defaultAccount = $lineManager->getDefaultAccount();
    if ($defaultAccount) {
        $currentBotId = $defaultAccount['id'];
    }
}

$shop = new UnifiedShop($db, null, $currentBotId);
$products = $shop->getItems(['in_stock' => true], 10);

echo "<h1>Debug Broadcast Catalog</h1>";
echo "<style>body{font-family:monospace;padding:20px;} pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow:auto;max-height:600px;} .error{color:red;} .ok{color:green;}</style>";

if (empty($products)) {
    echo "<p class='error'>❌ No products found</p>";
    exit;
}

echo "<p class='ok'>✅ Found " . count($products) . " products</p>";

// Build flex message
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
                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'spacing' => 'xs', 'paddingAll' => 'xs',
                'contents' => [
                    ['type' => 'image', 'url' => $imageUrl, 'size' => 'full', 'aspectRatio' => '1:1', 'aspectMode' => 'cover'],
                    ['type' => 'text', 'text' => mb_substr($p['name'], 0, 10) . (mb_strlen($p['name']) > 10 ? '..' : ''), 'size' => 'xxs', 'color' => '#333', 'wrap' => false],
                    ['type' => 'text', 'text' => '฿' . number_format($p['sale_price'] ?: $p['price']), 'size' => 'xs', 'color' => '#06C755', 'weight' => 'bold']
                ]
            ];
        }
        while (count($rowContents) < $cols) $rowContents[] = ['type' => 'box', 'layout' => 'vertical', 'contents' => [], 'flex' => 1];
        $rows[] = ['type' => 'box', 'layout' => 'horizontal', 'contents' => $rowContents, 'spacing' => 'sm'];
    }
    
    $header = $total > 1 ? "{$title} ({$page}/{$total})" : $title;
    
    return [
        'type' => 'bubble', 'size' => 'giga',
        'header' => ['type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg', 'backgroundColor' => '#f8fafc', 'contents' => [
            ['type' => 'text', 'text' => $header, 'weight' => 'bold', 'size' => 'md', 'flex' => 1],
            ['type' => 'text', 'text' => "{$allCount} รายการ", 'size' => 'xs', 'color' => '#888', 'align' => 'end']
        ]],
        'body' => ['type' => 'box', 'layout' => 'vertical', 'contents' => $rows, 'spacing' => 'sm', 'paddingAll' => 'md'],
        'footer' => ['type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'md', 'contents' => [
            ['type' => 'button', 'action' => ['type' => 'message', 'label' => '🛒 ดูทั้งหมด', 'text' => 'shop'], 'style' => 'primary', 'color' => '#06C755', 'height' => 'sm'],
            ['type' => 'button', 'action' => ['type' => 'message', 'label' => '🛍️ ตะกร้า', 'text' => 'cart'], 'style' => 'secondary', 'height' => 'sm', 'margin' => 'sm']
        ]]
    ];
}

$flex = buildBubble(array_slice($products, 0, 3), 3, 'สินค้าแนะนำ', 1, 1, count($products));

echo "<h2>1. Flex Message Structure</h2>";
echo "<pre>";
echo json_encode($flex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

// Validate structure
echo "<h2>2. Validation</h2>";

function validateFlex($flex, $path = '') {
    $errors = [];
    
    if (!isset($flex['type'])) {
        $errors[] = "$path: Missing 'type'";
        return $errors;
    }
    
    $type = $flex['type'];
    
    // Check required properties for each type
    switch ($type) {
        case 'bubble':
            if (!isset($flex['body'])) $errors[] = "$path: bubble missing 'body'";
            break;
        case 'box':
            if (!isset($flex['layout'])) $errors[] = "$path: box missing 'layout'";
            if (!isset($flex['contents'])) $errors[] = "$path: box missing 'contents'";
            break;
        case 'text':
            if (!isset($flex['text'])) $errors[] = "$path: text missing 'text'";
            break;
        case 'image':
            if (!isset($flex['url'])) $errors[] = "$path: image missing 'url'";
            break;
        case 'button':
            if (!isset($flex['action'])) $errors[] = "$path: button missing 'action'";
            break;
    }
    
    // Check for invalid properties
    $validProps = [
        'bubble' => ['type', 'size', 'header', 'hero', 'body', 'footer', 'styles'],
        'box' => ['type', 'layout', 'contents', 'flex', 'spacing', 'margin', 'paddingAll', 'paddingTop', 'paddingBottom', 'paddingStart', 'paddingEnd', 'backgroundColor', 'borderColor', 'borderWidth', 'cornerRadius', 'width', 'height', 'maxWidth', 'maxHeight', 'minWidth', 'minHeight', 'position', 'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd', 'action'],
        'text' => ['type', 'text', 'size', 'color', 'weight', 'align', 'margin', 'flex', 'wrap', 'maxLines', 'style', 'decoration', 'action'],
        'image' => ['type', 'url', 'size', 'aspectRatio', 'aspectMode', 'backgroundColor', 'action'],
        'button' => ['type', 'action', 'style', 'color', 'height', 'margin', 'adjustMode'],
    ];
    
    if (isset($validProps[$type])) {
        foreach ($flex as $key => $value) {
            if (!in_array($key, $validProps[$type])) {
                $errors[] = "$path: Invalid property '$key' for type '$type'";
            }
        }
    }
    
    // Recursively check nested elements
    if (isset($flex['contents']) && is_array($flex['contents'])) {
        foreach ($flex['contents'] as $i => $content) {
            $errors = array_merge($errors, validateFlex($content, "$path.contents[$i]"));
        }
    }
    
    if (isset($flex['header']) && is_array($flex['header'])) {
        $errors = array_merge($errors, validateFlex($flex['header'], "$path.header"));
    }
    
    if (isset($flex['body']) && is_array($flex['body'])) {
        $errors = array_merge($errors, validateFlex($flex['body'], "$path.body"));
    }
    
    if (isset($flex['footer']) && is_array($flex['footer'])) {
        $errors = array_merge($errors, validateFlex($flex['footer'], "$path.footer"));
    }
    
    if (isset($flex['action']) && is_array($flex['action'])) {
        $errors = array_merge($errors, validateFlex($flex['action'], "$path.action"));
    }
    
    return $errors;
}

$errors = validateFlex($flex);

if (empty($errors)) {
    echo "<p class='ok'>✅ Flex message structure is valid</p>";
} else {
    echo "<p class='error'>❌ Found " . count($errors) . " errors:</p>";
    echo "<ul>";
    foreach ($errors as $err) {
        echo "<li class='error'>$err</li>";
    }
    echo "</ul>";
}

// Check the full message
echo "<h2>3. Full Message to Send</h2>";
$message = [
    'type' => 'flex',
    'altText' => 'สินค้าแนะนำ',
    'contents' => $flex
];

echo "<pre>";
echo json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

// Validate flex contents only (not the wrapper message)
$flexErrors = validateFlex($flex);
if (empty($flexErrors)) {
    echo "<p class='ok'>✅ Flex contents is valid</p>";
} else {
    echo "<p class='error'>❌ Flex has errors:</p>";
    echo "<ul>";
    foreach ($flexErrors as $err) {
        echo "<li class='error'>$err</li>";
    }
    echo "</ul>";
}

// Test actual send
echo "<h2>4. Test Send (Dry Run)</h2>";
if (isset($_GET['send']) && $_GET['send'] === '1') {
    require_once 'classes/LineAccountManager.php';
    $lineManager = new LineAccountManager($db);
    $line = $lineManager->getLineAPI($currentBotId);
    
    if ($line) {
        $result = $line->broadcastMessage([$message]);
        echo "<p>HTTP Code: {$result['code']}</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p class='error'>No LINE API configured</p>";
    }
} else {
    echo "<p><a href='?send=1' class='error' onclick='return confirm(\"Send broadcast?\")'>Click to test send</a></p>";
}
