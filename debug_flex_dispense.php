<?php
/**
 * Debug Flex Dispense
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';
require_once 'classes/FlexTemplates.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Flex Dispense</h2>";

// 1. Test FlexTemplates class
echo "<h3>1. Test FlexTemplates Class</h3>";
try {
    $testItem = [
        'name' => 'Paracetamol 500mg',
        'product_id' => 1,
        'price' => 50,
        'qty' => 10,
        'unit' => 'เม็ด',
        'isMedicine' => true,
        'indication' => 'แก้ปวด ลดไข้',
        'dosage' => 1,
        'dosageUnit' => 'เม็ด',
        'frequency' => '3',
        'mealTiming' => 'after',
        'timeOfDay' => ['morning', 'noon', 'evening'],
        'usageType' => 'internal',
        'specialInstructions' => ['drink_water'],
        'notes' => 'ทานหลังอาหารทันที'
    ];
    
    $shopInfo = [
        'name' => 'ร้านยาทดสอบ',
        'address' => '123 ถ.สุขุมวิท กรุงเทพฯ',
        'phone' => '02-123-4567',
        'open_hours' => '08:00-22:00 น.'
    ];
    
    $flex = FlexTemplates::medicineLabel($testItem, $shopInfo, 'คุณทดสอบ', 'https://example.com/checkout');
    
    echo "✅ FlexTemplates::medicineLabel() works<br>";
    echo "<pre style='background:#f5f5f5;padding:10px;max-height:300px;overflow:auto'>" . json_encode($flex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // Test toMessage
    $message = FlexTemplates::toMessage($flex, '💊 ทดสอบซองยา');
    echo "<h4>Full Message:</h4>";
    echo "<pre style='background:#e8f5e9;padding:10px;max-height:200px;overflow:auto'>" . json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 2. Test Carousel
echo "<h3>2. Test Carousel</h3>";
try {
    $items = [
        $testItem,
        [
            'name' => 'Amoxicillin 500mg',
            'product_id' => 2,
            'price' => 80,
            'qty' => 21,
            'unit' => 'แคปซูล',
            'isMedicine' => true,
            'indication' => 'ฆ่าเชื้อ',
            'dosage' => 1,
            'dosageUnit' => 'แคปซูล',
            'frequency' => '3',
            'mealTiming' => 'after',
            'timeOfDay' => ['morning', 'noon', 'evening'],
            'usageType' => 'internal',
            'specialInstructions' => ['take_until_finish'],
            'notes' => ''
        ]
    ];
    
    $carousel = FlexTemplates::medicineLabelsCarousel($items, $shopInfo, 'คุณทดสอบ', 'https://example.com/checkout');
    echo "✅ FlexTemplates::medicineLabelsCarousel() works<br>";
    echo "Bubbles count: " . count($carousel['contents']) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 3. Test LINE API
echo "<h3>3. Test LINE API Send</h3>";
if (isset($_GET['send_test'])) {
    $userId = $_GET['user_id'] ?? 1;
    
    try {
        $stmt = $db->prepare("SELECT line_user_id, line_account_id, display_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "❌ User not found<br>";
        } else {
            echo "User: " . $user['display_name'] . "<br>";
            echo "LINE User ID: " . $user['line_user_id'] . "<br>";
            
            $lineManager = new LineAccountManager($db);
            $line = $lineManager->getLineAPI($user['line_account_id']);
            
            // Create simple flex
            $flex = FlexTemplates::medicineLabel($testItem, $shopInfo, $user['display_name'], null);
            $message = FlexTemplates::toMessage($flex, '💊 ทดสอบซองยา');
            
            echo "<h4>Sending message...</h4>";
            echo "<pre>" . json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            $result = $line->pushMessage($user['line_user_id'], [$message]);
            
            echo "<h4>Result:</h4>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            if ($result['code'] === 200) {
                echo "✅ Message sent successfully!<br>";
            } else {
                echo "❌ Failed to send: " . ($result['error'] ?? 'Unknown error') . "<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    // Get users for testing
    $stmt = $db->query("SELECT id, display_name FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Select a user to send test Flex message:</p>";
    foreach ($users as $u) {
        echo "<a href='?send_test=1&user_id={$u['id']}' style='margin-right:10px;padding:5px 10px;background:#06C755;color:white;text-decoration:none;border-radius:5px'>Send to {$u['display_name']}</a> ";
    }
}

// 4. Check recent dispense records
echo "<h3>4. Recent Dispense Records</h3>";
try {
    $stmt = $db->query("SELECT * FROM dispensing_records ORDER BY created_at DESC LIMIT 3");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $r) {
        echo "<div style='background:#f5f5f5;padding:10px;margin:5px 0;border-radius:5px'>";
        echo "<strong>#{$r['order_number']}</strong> - User ID: {$r['user_id']} - ฿" . number_format($r['total_amount'], 2) . "<br>";
        echo "Payment: {$r['payment_method']} | Status: {$r['payment_status']}<br>";
        echo "Created: {$r['created_at']}<br>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 5. Check error log
echo "<h3>5. Recent Error Log</h3>";
$errorLog = @file_get_contents('error_log');
if ($errorLog) {
    $lines = array_slice(explode("\n", $errorLog), -20);
    echo "<pre style='background:#fff3cd;padding:10px;max-height:200px;overflow:auto'>" . htmlspecialchars(implode("\n", $lines)) . "</pre>";
} else {
    echo "No error log found or empty<br>";
}
?>
