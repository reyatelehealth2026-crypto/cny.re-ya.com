<?php
/**
 * Fix Shop Settings - อัพเดท line_account_id ที่ว่างอยู่
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Fix Shop Settings</h2>";

// แสดงสถานะก่อน fix
echo "<h3>ก่อน Fix:</h3>";
$stmt = $db->query("SELECT id, line_account_id, shop_name, is_open FROM shop_settings");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>line_account_id</th><th>shop_name</th><th>is_open</th></tr>";
foreach ($rows as $row) {
    $accId = $row['line_account_id'] ?: '<span style="color:red">NULL</span>';
    echo "<tr><td>{$row['id']}</td><td>{$accId}</td><td>{$row['shop_name']}</td><td>{$row['is_open']}</td></tr>";
}
echo "</table>";

// ถ้ากด Fix
if (isset($_GET['fix'])) {
    echo "<h3>กำลัง Fix...</h3>";
    
    // ลบ record ที่ไม่มี line_account_id (เก็บไว้แค่ record ที่มี)
    $stmt = $db->query("DELETE FROM shop_settings WHERE line_account_id IS NULL OR line_account_id = 0");
    echo "<p>ลบ record ที่ไม่มี line_account_id: {$stmt->rowCount()} รายการ</p>";
    
    // สร้าง settings ใหม่สำหรับ LINE Account ที่ยังไม่มี
    $stmt = $db->query("SELECT id, name FROM line_accounts WHERE is_active = 1");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($accounts as $acc) {
        // เช็คว่ามี settings แล้วหรือยัง
        $stmt = $db->prepare("SELECT id FROM shop_settings WHERE line_account_id = ?");
        $stmt->execute([$acc['id']]);
        if (!$stmt->fetch()) {
            // สร้างใหม่
            $stmt = $db->prepare("INSERT INTO shop_settings (line_account_id, shop_name, is_open, shipping_fee, free_shipping_min) VALUES (?, ?, 1, 50, 500)");
            $stmt->execute([$acc['id'], $acc['name']]);
            echo "<p style='color:green'>✅ สร้าง settings สำหรับ: {$acc['name']} (ID: {$acc['id']})</p>";
        } else {
            echo "<p>⏭️ มี settings แล้ว: {$acc['name']} (ID: {$acc['id']})</p>";
        }
    }
    
    echo "<h3>หลัง Fix:</h3>";
    $stmt = $db->query("SELECT id, line_account_id, shop_name, is_open FROM shop_settings ORDER BY line_account_id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>line_account_id</th><th>shop_name</th><th>is_open</th></tr>";
    foreach ($rows as $row) {
        echo "<tr><td>{$row['id']}</td><td>{$row['line_account_id']}</td><td>{$row['shop_name']}</td><td>{$row['is_open']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p style='color:green; font-size:18px'>✅ Fix เสร็จสิ้น!</p>";
} else {
    echo "<p><a href='?fix=1' style='padding:10px 20px; background:#ef4444; color:white; text-decoration:none; border-radius:5px;' onclick=\"return confirm('ยืนยันการ Fix? จะลบ record ที่ไม่มี line_account_id')\">🔧 Fix Now</a></p>";
}

echo "<hr><p><a href='debug_shop_status.php'>กลับไปหน้า Debug</a></p>";
