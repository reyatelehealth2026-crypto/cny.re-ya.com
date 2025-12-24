<?php
/**
 * Debug LIFF ID - ตรวจสอบ LIFF ID ในฐานข้อมูล
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug LIFF ID</h2>";

// 1. Check line_accounts table structure
echo "<h3>1. โครงสร้างตาราง line_accounts</h3>";
$cols = $db->query("SHOW COLUMNS FROM line_accounts")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
foreach ($cols as $col) {
    $highlight = ($col['Field'] === 'liff_id') ? 'style="background:yellow"' : '';
    echo "<tr {$highlight}><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

// Check if liff_id column exists
$hasLiffId = false;
foreach ($cols as $col) {
    if ($col['Field'] === 'liff_id') {
        $hasLiffId = true;
        break;
    }
}

if (!$hasLiffId) {
    echo "<p style='color:red'>❌ คอลัมน์ liff_id ไม่มีในตาราง! กำลังเพิ่ม...</p>";
    try {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN liff_id VARCHAR(50) DEFAULT NULL");
        echo "<p style='color:green'>✅ เพิ่มคอลัมน์ liff_id สำเร็จ!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// 2. Check all accounts
echo "<h3>2. ข้อมูลบัญชี LINE ทั้งหมด</h3>";
$accounts = $db->query("SELECT id, name, channel_id, liff_id, is_active, is_default FROM line_accounts")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Channel ID</th><th>LIFF ID</th><th>Active</th><th>Default</th></tr>";
foreach ($accounts as $acc) {
    $liffStyle = empty($acc['liff_id']) ? 'style="color:red"' : 'style="color:green;font-weight:bold"';
    echo "<tr>";
    echo "<td>{$acc['id']}</td>";
    echo "<td>{$acc['name']}</td>";
    echo "<td>{$acc['channel_id']}</td>";
    echo "<td {$liffStyle}>" . ($acc['liff_id'] ?: '(ว่าง)') . "</td>";
    echo "<td>" . ($acc['is_active'] ? '✓' : '✗') . "</td>";
    echo "<td>" . ($acc['is_default'] ? '⭐' : '') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Test query for account 1
echo "<h3>3. ทดสอบ Query สำหรับ Account ID 1</h3>";
$stmt = $db->prepare("SELECT id, name, liff_id FROM line_accounts WHERE id = ?");
$stmt->execute([1]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($account);
echo "</pre>";

// 4. Update LIFF ID form
echo "<h3>4. อัพเดท LIFF ID</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liff_id'])) {
    $newLiffId = trim($_POST['liff_id']);
    $accountId = (int)$_POST['account_id'];
    
    $stmt = $db->prepare("UPDATE line_accounts SET liff_id = ? WHERE id = ?");
    $stmt->execute([$newLiffId ?: null, $accountId]);
    
    echo "<p style='color:green'>✅ อัพเดท LIFF ID สำเร็จ! <a href='debug_liff_id.php'>รีเฟรช</a></p>";
}

echo "<form method='POST'>";
echo "<select name='account_id'>";
foreach ($accounts as $acc) {
    echo "<option value='{$acc['id']}'>{$acc['id']} - {$acc['name']}</option>";
}
echo "</select>";
echo "<input type='text' name='liff_id' placeholder='LIFF ID เช่น 2001234567-aBcDeFgH' style='width:300px;padding:5px'>";
echo "<button type='submit' style='padding:5px 15px;background:#06C755;color:white;border:none;cursor:pointer'>บันทึก</button>";
echo "</form>";

echo "<hr>";
echo "<p>หลังจากตั้งค่า LIFF ID แล้ว ทดสอบที่: <a href='liff-main.php?debug=1'>liff-main.php?debug=1</a></p>";
