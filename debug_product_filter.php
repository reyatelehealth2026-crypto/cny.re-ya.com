<?php
/**
 * Debug Product Filter
 * ตรวจสอบว่าทำไมหน้า products แสดงไม่ครบ
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Debug Product Filter</h1>";
echo "<pre>";

// 1. Total products
$stmt = $db->query("SELECT COUNT(*) FROM business_items");
$total = $stmt->fetchColumn();
echo "1. Total products in business_items: $total\n\n";

// 2. Check line_account_id distribution
echo "2. Products by line_account_id:\n";
$stmt = $db->query("SELECT line_account_id, COUNT(*) as cnt FROM business_items GROUP BY line_account_id ORDER BY cnt DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $accId = $row['line_account_id'] ?? 'NULL';
    echo "   line_account_id = $accId: {$row['cnt']} products\n";
}

// 3. Check current bot ID from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentBotId = $_SESSION['current_line_account_id'] ?? $_SESSION['line_account_id'] ?? null;
echo "\n3. Current Bot ID from session: " . ($currentBotId ?? 'NULL') . "\n";

// 4. Count with filter (same as products.php)
$stmt = $db->prepare("SELECT COUNT(*) FROM business_items WHERE (line_account_id = ? OR line_account_id IS NULL)");
$stmt->execute([$currentBotId]);
$filteredCount = $stmt->fetchColumn();
echo "\n4. Products with filter (line_account_id = $currentBotId OR NULL): $filteredCount\n";

// 5. Products with NULL line_account_id
$stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE line_account_id IS NULL");
$nullCount = $stmt->fetchColumn();
echo "\n5. Products with NULL line_account_id: $nullCount\n";

// 6. Products with specific line_account_id
if ($currentBotId) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM business_items WHERE line_account_id = ?");
    $stmt->execute([$currentBotId]);
    $specificCount = $stmt->fetchColumn();
    echo "6. Products with line_account_id = $currentBotId: $specificCount\n";
}

// 7. Solution
echo "\n========== SOLUTION ==========\n";
if ($nullCount < $total && $currentBotId) {
    echo "ปัญหา: สินค้าส่วนใหญ่มี line_account_id ที่ไม่ตรงกับบอทปัจจุบัน\n";
    echo "วิธีแก้: รัน SQL นี้เพื่อ reset line_account_id:\n\n";
    echo "UPDATE business_items SET line_account_id = NULL;\n";
    echo "-- หรือ --\n";
    echo "UPDATE business_items SET line_account_id = $currentBotId;\n";
}

echo "</pre>";

// Quick fix button
if (isset($_POST['fix_null'])) {
    $stmt = $db->exec("UPDATE business_items SET line_account_id = NULL");
    echo "<p style='color:green;'>✓ Reset line_account_id เป็น NULL แล้ว!</p>";
    echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
}

if (isset($_POST['fix_current'])) {
    $stmt = $db->prepare("UPDATE business_items SET line_account_id = ?");
    $stmt->execute([$currentBotId]);
    echo "<p style='color:green;'>✓ Set line_account_id = $currentBotId แล้ว!</p>";
    echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
}

echo "<hr>";
echo "<form method='POST' style='margin:10px;'>";
echo "<button type='submit' name='fix_null' style='padding:10px 20px;background:#10B981;color:white;border:none;border-radius:5px;cursor:pointer;'>🔧 Reset line_account_id = NULL (แสดงทุกบอท)</button>";
echo "</form>";

echo "<form method='POST' style='margin:10px;'>";
echo "<button type='submit' name='fix_current' style='padding:10px 20px;background:#3B82F6;color:white;border:none;border-radius:5px;cursor:pointer;'>🔧 Set line_account_id = $currentBotId (บอทปัจจุบัน)</button>";
echo "</form>";
