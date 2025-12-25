<?php
/**
 * ตรวจสอบ LIFF ID ทั้งหมดในระบบ
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 ตรวจสอบ LIFF ID ทั้งหมด</h1>";
echo "<style>body{font-family:sans-serif;padding:20px} table{border-collapse:collapse;margin:20px 0} td,th{border:1px solid #ddd;padding:10px} .ok{color:green} .missing{color:red}</style>";

// 1. Config LIFF IDs
echo "<h2>1. LIFF ID ใน config.php</h2>";
echo "<table>";
echo "<tr><th>Constant</th><th>ใช้สำหรับ</th><th>ค่า</th><th>สถานะ</th></tr>";

$liffShare = defined('LIFF_SHARE_ID') ? LIFF_SHARE_ID : '';
$liffVideo = defined('LIFF_ID') ? LIFF_ID : '';

echo "<tr><td>LIFF_SHARE_ID</td><td>ปุ่มแชร์ Auto Reply</td><td>" . ($liffShare ?: '<em>ว่าง</em>') . "</td>";
echo "<td class='" . ($liffShare ? 'ok' : 'missing') . "'>" . ($liffShare ? '✅ OK' : '❌ ยังไม่ตั้งค่า') . "</td></tr>";

echo "<tr><td>LIFF_ID</td><td>Video Call</td><td>" . ($liffVideo ?: '<em>ว่าง</em>') . "</td>";
echo "<td class='" . ($liffVideo ? 'ok' : 'missing') . "'>" . ($liffVideo ? '✅ OK' : '⚠️ ใช้ Guest Mode') . "</td></tr>";
echo "</table>";

// 2. Database LIFF IDs
echo "<h2>2. LIFF ID ใน Database (line_accounts)</h2>";
$stmt = $db->query("SELECT id, name, liff_id, is_active, is_default FROM line_accounts ORDER BY is_default DESC, id ASC");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>ชื่อ</th><th>LIFF ID</th><th>Active</th><th>Default</th><th>ใช้สำหรับ</th></tr>";
foreach ($accounts as $a) {
    $liff = $a['liff_id'] ?? '';
    echo "<tr>";
    echo "<td>{$a['id']}</td>";
    echo "<td>{$a['name']}</td>";
    echo "<td class='" . ($liff ? 'ok' : 'missing') . "'>" . ($liff ?: '<em>ว่าง</em>') . "</td>";
    echo "<td>" . ($a['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td>" . ($a['is_default'] ? '⭐' : '') . "</td>";
    echo "<td>Shop / Checkout</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Summary
echo "<h2>3. สรุป</h2>";
$hasShopLiff = false;
foreach ($accounts as $a) {
    if (!empty($a['liff_id'])) $hasShopLiff = true;
}

echo "<ul>";
echo "<li><strong>Auto Reply Share:</strong> " . ($liffShare ? "✅ พร้อมใช้งาน" : "❌ ต้องตั้งค่า LIFF_SHARE_ID ใน config.php") . "</li>";
echo "<li><strong>Video Call:</strong> " . ($liffVideo ? "✅ พร้อมใช้งาน" : "⚠️ ใช้ Guest Mode (ไม่ระบุตัวตนผู้โทร)") . "</li>";
echo "<li><strong>Shop / Checkout:</strong> " . ($hasShopLiff ? "✅ พร้อมใช้งาน" : "❌ ต้องตั้งค่า liff_id ใน LINE Accounts") . "</li>";
echo "</ul>";

// 4. Links
echo "<h2>4. ลิงก์ตั้งค่า</h2>";
echo "<ul>";
echo "<li><a href='line-accounts.php'>📱 LINE Accounts</a> - ตั้งค่า LIFF ID สำหรับ Shop/Checkout</li>";
echo "<li><a href='setup_liff.php'>⚙️ Setup LIFF</a> - ตั้งค่า LIFF ID อย่างง่าย</li>";
echo "</ul>";

echo "<h2>5. วิธีสร้าง LIFF App</h2>";
echo "<ol>";
echo "<li>ไปที่ <a href='https://developers.line.biz/' target='_blank'>LINE Developers Console</a></li>";
echo "<li>เลือก Provider → Channel → LIFF</li>";
echo "<li>กด 'Add' เพื่อสร้าง LIFF App</li>";
echo "<li>ตั้งค่า Size: Full, Endpoint URL ตามต้องการ</li>";
echo "<li>Copy LIFF ID มาใส่ในระบบ</li>";
echo "</ol>";

echo "<h3>Endpoint URLs ที่ต้องใช้:</h3>";
echo "<table>";
echo "<tr><th>LIFF App</th><th>Endpoint URL</th></tr>";
echo "<tr><td>Shop/Checkout</td><td>" . BASE_URL . "liff-shop.php</td></tr>";
echo "<tr><td>Video Call</td><td>" . BASE_URL . "liff-video-call-pro.php</td></tr>";
echo "<tr><td>Share</td><td>" . BASE_URL . "liff-share.php</td></tr>";
echo "</table>";
