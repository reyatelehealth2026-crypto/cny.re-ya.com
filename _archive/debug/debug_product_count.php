<?php
/**
 * Debug: ตรวจสอบจำนวนสินค้าในแต่ละตาราง
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Product Count</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;margin:10px 0;} td,th{border:1px solid #ddd;padding:8px;} th{background:#f5f5f5;}</style>";

// Check session
session_start();
$currentBotId = $_SESSION['current_bot_id'] ?? null;
echo "<p><strong>Current Bot ID:</strong> " . ($currentBotId ?? 'NULL (ไม่ได้ login)') . "</p>";

// Count in each table
echo "<h3>📊 จำนวนสินค้าในแต่ละตาราง</h3>";
echo "<table>";
echo "<tr><th>ตาราง</th><th>ทั้งหมด</th><th>Active</th><th>Bot ID = {$currentBotId}</th><th>Bot ID = {$currentBotId} OR NULL</th></tr>";

$tables = ['products', 'business_items'];
foreach ($tables as $table) {
    try {
        // Total
        $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
        $total = $stmt->fetchColumn();
        
        // Active
        $stmt = $db->query("SELECT COUNT(*) FROM {$table} WHERE is_active = 1");
        $active = $stmt->fetchColumn();
        
        // By bot_id
        $byBot = '-';
        $byBotOrNull = '-';
        if ($currentBotId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE line_account_id = ?");
            $stmt->execute([$currentBotId]);
            $byBot = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE (line_account_id = ? OR line_account_id IS NULL)");
            $stmt->execute([$currentBotId]);
            $byBotOrNull = $stmt->fetchColumn();
        }
        
        echo "<tr><td><strong>{$table}</strong></td><td>{$total}</td><td>{$active}</td><td>{$byBot}</td><td>{$byBotOrNull}</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>{$table}</td><td colspan='4'>❌ ไม่มีตาราง</td></tr>";
    }
}
echo "</table>";

// Check which table UnifiedShop uses
echo "<h3>🔧 UnifiedShop Detection</h3>";
if (file_exists('classes/UnifiedShop.php')) {
    require_once 'classes/UnifiedShop.php';
    $shop = new UnifiedShop($db, null, $currentBotId);
    $itemsTable = $shop->getItemsTable();
    $categoriesTable = $shop->getCategoriesTable();
    echo "<p><strong>Items Table:</strong> {$itemsTable}</p>";
    echo "<p><strong>Categories Table:</strong> {$categoriesTable}</p>";
} else {
    echo "<p>❌ UnifiedShop.php not found</p>";
}

// Check Dashboard logic
echo "<h3>📈 Dashboard Logic (index.php)</h3>";
$productsTable = 'products';
try {
    $db->query("SELECT 1 FROM business_items LIMIT 1");
    $productsTable = 'business_items';
} catch (Exception $e) {}
echo "<p><strong>Dashboard จะใช้ตาราง:</strong> {$productsTable}</p>";

if ($currentBotId) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM {$productsTable} WHERE (line_account_id = ? OR line_account_id IS NULL)");
    $stmt->execute([$currentBotId]);
    $count = $stmt->fetchColumn();
    echo "<p><strong>จำนวนที่ Dashboard ควรแสดง:</strong> {$count}</p>";
}

// Check line_account_id distribution
echo "<h3>📋 การกระจาย line_account_id</h3>";
foreach ($tables as $table) {
    try {
        echo "<p><strong>{$table}:</strong></p>";
        $stmt = $db->query("SELECT line_account_id, COUNT(*) as cnt FROM {$table} GROUP BY line_account_id ORDER BY cnt DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>line_account_id</th><th>จำนวน</th></tr>";
        foreach ($rows as $row) {
            $id = $row['line_account_id'] ?? 'NULL';
            echo "<tr><td>{$id}</td><td>{$row['cnt']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {}
}
?>
