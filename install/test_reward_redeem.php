<?php
/**
 * Test Reward Redemption
 * ทดสอบการแลกของรางวัล
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LoyaltyPoints.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Test Reward Redemption System</h2>";
echo "<hr>";

// Test 1: Check if rewards table exists
echo "<h3>1. ตรวจสอบตาราง rewards</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM rewards");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ ตาราง rewards มีอยู่ จำนวนรางวัล: " . $result['count'] . "<br>";
    
    // Show rewards
    $stmt = $db->query("SELECT id, name, points_required, stock, is_active FROM rewards LIMIT 5");
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($rewards);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check if reward_redemptions table exists
echo "<h3>2. ตรวจสอบตาราง reward_redemptions</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM reward_redemptions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ ตาราง reward_redemptions มีอยู่ จำนวนการแลก: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Test API endpoint
echo "<h3>3. ทดสอบ API Endpoint</h3>";

// Get a test user
$stmt = $db->query("SELECT id, line_user_id, display_name, points, available_points FROM users WHERE line_user_id IS NOT NULL LIMIT 1");
$testUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($testUser) {
    echo "Test User: " . $testUser['display_name'] . " (ID: " . $testUser['id'] . ")<br>";
    echo "LINE User ID: " . $testUser['line_user_id'] . "<br>";
    echo "Points: " . ($testUser['available_points'] ?? $testUser['points']) . "<br><br>";
    
    // Test rewards API
    echo "<strong>Testing GET /api/points-history.php?action=rewards</strong><br>";
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/api/points-history.php?action=rewards&line_user_id=" . $testUser['line_user_id'];
    echo "URL: <a href='$url' target='_blank'>$url</a><br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Response:<br>";
    echo "<pre>";
    $data = json_decode($response, true);
    if ($data) {
        echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "<br>";
        echo "Available Points: " . ($data['available_points'] ?? 'N/A') . "<br>";
        echo "Rewards Count: " . (isset($data['rewards']) ? count($data['rewards']) : 0) . "<br>";
        if (isset($data['rewards']) && count($data['rewards']) > 0) {
            echo "\nFirst Reward:<br>";
            print_r($data['rewards'][0]);
        }
    } else {
        echo $response;
    }
    echo "</pre>";
    
} else {
    echo "❌ ไม่พบ test user<br>";
}

// Test 4: Check LoyaltyPoints class
echo "<h3>4. ทดสอบ LoyaltyPoints Class</h3>";
try {
    $loyalty = new LoyaltyPoints($db, 1);
    $rewards = $loyalty->getRewards(true);
    echo "✅ LoyaltyPoints class ทำงานได้<br>";
    echo "จำนวนรางวัลที่ active: " . count($rewards) . "<br>";
    
    if (count($rewards) > 0) {
        echo "<br>รางวัลแรก:<br>";
        echo "<pre>";
        print_r($rewards[0]);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Check JavaScript console errors
echo "<h3>5. ตรวจสอบ JavaScript</h3>";
echo "<p>เปิด Browser Console (F12) และดูว่ามี error อะไรหรือไม่</p>";
echo "<p>ลอง console.log ดูว่า:</p>";
echo "<ul>";
echo "<li>window.liffApp มีค่าหรือไม่</li>";
echo "<li>window.liffApp.confirmRedeem เป็น function หรือไม่</li>";
echo "<li>มี error จาก API call หรือไม่</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Test Complete</h3>";
echo "<p>ถ้าทุกอย่างผ่าน แต่ยังกดแลกไม่ได้ ให้ตรวจสอบ:</p>";
echo "<ol>";
echo "<li>Browser Console (F12) มี error อะไรหรือไม่</li>";
echo "<li>Network tab ดูว่า API call ถูกส่งหรือไม่</li>";
echo "<li>ลอง hard refresh (Ctrl+Shift+R) เพื่อ clear cache</li>";
echo "<li>ตรวจสอบว่า liff-app.js โหลดถูกต้องหรือไม่</li>";
echo "</ol>";
