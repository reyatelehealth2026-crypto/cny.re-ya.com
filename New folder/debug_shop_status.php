<?php
/**
 * Debug Shop Status - ตรวจสอบสถานะร้านค้า
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Shop Status</h2>";

// 1. แสดงข้อมูลใน shop_settings
echo "<h3>1. ข้อมูลใน shop_settings:</h3>";
try {
    $stmt = $db->query("SELECT id, line_account_id, shop_name, is_open FROM shop_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr style='background:#eee'><th>ID</th><th>line_account_id</th><th>shop_name</th><th>is_open</th></tr>";
    foreach ($rows as $row) {
        $bgColor = $row['is_open'] ? '#d4edda' : '#f8d7da';
        $status = $row['is_open'] ? '✅ เปิด' : '❌ ปิด';
        echo "<tr style='background:{$bgColor}'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['line_account_id']}</td>";
        echo "<td>{$row['shop_name']}</td>";
        echo "<td><strong>{$status}</strong> ({$row['is_open']})</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. แสดงข้อมูล LINE Accounts
echo "<h3>2. LINE Accounts:</h3>";
try {
    $stmt = $db->query("SELECT id, name, basic_id, is_active FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr style='background:#eee'><th>ID</th><th>Name</th><th>Basic ID</th><th>Active</th></tr>";
    foreach ($accounts as $acc) {
        echo "<tr>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td>{$acc['basic_id']}</td>";
        echo "<td>" . ($acc['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. ทดสอบ BusinessBot
echo "<h3>3. ทดสอบ BusinessBot.isShopOpen():</h3>";
require_once 'classes/LineAPI.php';
require_once 'classes/FlexTemplates.php';
require_once 'classes/BusinessBot.php';

foreach ($accounts as $acc) {
    echo "<div style='margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:5px'>";
    echo "<strong>LINE Account: {$acc['name']} (ID: {$acc['id']})</strong><br>";
    
    $line = new LineAPI();
    $bot = new BusinessBot($db, $line, $acc['id']);
    
    $isOpen = $bot->isShopOpen();
    $color = $isOpen ? 'green' : 'red';
    $status = $isOpen ? '✅ ร้านเปิด (isShopOpen = true)' : '❌ ร้านปิด (isShopOpen = false)';
    
    echo "<span style='color:{$color}; font-size:18px'>{$status}</span>";
    echo "</div>";
}

// 4. ดู dev_logs ล่าสุด
echo "<h3>4. Dev Logs ล่าสุด (BusinessBot):</h3>";
try {
    $stmt = $db->query("SELECT * FROM dev_logs WHERE source LIKE 'BusinessBot%' ORDER BY id DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "<p>ไม่มี logs</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='font-size:12px'>";
        echo "<tr style='background:#eee'><th>ID</th><th>Source</th><th>Message</th><th>Data</th><th>Time</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['source']}</td>";
            echo "<td>{$log['message']}</td>";
            echo "<td><pre style='max-width:300px;overflow:auto'>" . htmlspecialchars($log['data'] ?? '') . "</pre></td>";
            echo "<td>{$log['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>dev_logs table not found</p>";
}

echo "<hr><p><a href='user/shop-settings.php'>ไปหน้าตั้งค่าร้านค้า</a></p>";
