<?php
/**
 * Fix Broadcast Postback Data
 * แปลง postback data จาก JSON เป็น string format
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Fix Broadcast Postback Data</h2>";

// Get all items with JSON postback data
$stmt = $db->query("SELECT * FROM broadcast_items WHERE postback_data LIKE '{%'");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>พบ " . count($items) . " items ที่ใช้ JSON format</p>";

if (empty($items)) {
    echo "<p>✅ ไม่มี items ที่ต้องแก้ไข</p>";
    exit;
}

$fixed = 0;
$errors = 0;

foreach ($items as $item) {
    $jsonData = json_decode($item['postback_data'], true);
    
    if (!$jsonData) {
        echo "<p>❌ Item #{$item['id']}: ไม่สามารถ parse JSON</p>";
        $errors++;
        continue;
    }
    
    $campaignId = $jsonData['campaign_id'] ?? $item['broadcast_id'];
    $productId = $jsonData['product_id'] ?? $item['product_id'];
    
    // สร้าง postback data ใหม่
    $newPostbackData = "broadcast_click_{$campaignId}_{$productId}";
    
    try {
        $stmt = $db->prepare("UPDATE broadcast_items SET postback_data = ? WHERE id = ?");
        $stmt->execute([$newPostbackData, $item['id']]);
        
        echo "<p>✅ Item #{$item['id']} ({$item['item_name']}): {$item['postback_data']} → {$newPostbackData}</p>";
        $fixed++;
    } catch (Exception $e) {
        echo "<p>❌ Item #{$item['id']}: " . $e->getMessage() . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>สรุป</h3>";
echo "<p>✅ แก้ไขสำเร็จ: {$fixed} items</p>";
echo "<p>❌ Error: {$errors} items</p>";

if ($fixed > 0) {
    echo "<p style='color:green; font-weight:bold'>⚠️ Campaign ที่ส่งไปแล้วจะยังใช้ postback data เดิม (JSON) ซึ่ง webhook รองรับทั้ง 2 รูปแบบแล้ว</p>";
}
