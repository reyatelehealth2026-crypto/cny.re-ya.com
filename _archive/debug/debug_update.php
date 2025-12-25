<?php
/**
 * Debug Update - ทดสอบการอัพเดท bot_mode โดยตรง
 */
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAccountManager.php';

header("Content-Type: text/html; charset=utf-8");
echo "<h2>🔧 Debug Update Bot Mode</h2>";
echo "<pre>";

$db = Database::getInstance()->getConnection();

// แสดงข้อมูลปัจจุบัน
echo "=== Current Data ===\n";
$stmt = $db->query("SELECT id, name, bot_mode FROM line_accounts");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($accounts);

// ทดสอบ update โดยตรง
if (isset($_GET['update'])) {
    $id = (int)$_GET['id'];
    $mode = $_GET['mode'];
    
    echo "\n=== Testing Direct SQL Update ===\n";
    echo "ID: $id, Mode: $mode\n";
    
    $stmt = $db->prepare("UPDATE line_accounts SET bot_mode = ? WHERE id = ?");
    $result = $stmt->execute([$mode, $id]);
    
    echo "Execute result: " . ($result ? 'TRUE' : 'FALSE') . "\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";
    
    // ตรวจสอบผลลัพธ์
    echo "\n=== After Update ===\n";
    $stmt = $db->prepare("SELECT id, name, bot_mode FROM line_accounts WHERE id = ?");
    $stmt->execute([$id]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}

// ทดสอบผ่าน Manager
if (isset($_GET['manager'])) {
    $id = (int)$_GET['id'];
    $mode = $_GET['mode'];
    
    echo "\n=== Testing via LineAccountManager ===\n";
    echo "ID: $id, Mode: $mode\n";
    
    $manager = new LineAccountManager($db);
    
    // ตรวจสอบ columnExists
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('columnExists');
    $method->setAccessible(true);
    $hasBotMode = $method->invoke($manager, 'line_accounts', 'bot_mode');
    echo "columnExists('line_accounts', 'bot_mode'): " . ($hasBotMode ? 'TRUE' : 'FALSE') . "\n";
    
    $result = $manager->updateAccount($id, ['bot_mode' => $mode]);
    echo "updateAccount result: " . ($result ? 'TRUE' : 'FALSE') . "\n";
    
    // ตรวจสอบผลลัพธ์
    echo "\n=== After Update ===\n";
    $stmt = $db->prepare("SELECT id, name, bot_mode FROM line_accounts WHERE id = ?");
    $stmt->execute([$id]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}

echo "</pre>";

// แสดงลิงก์ทดสอบ
if (!empty($accounts)) {
    echo "<h3>🧪 Test Links:</h3>";
    foreach ($accounts as $acc) {
        echo "<p><strong>{$acc['name']}</strong> (Current: " . ($acc['bot_mode'] ?? 'NULL') . ")</p>";
        echo "<ul>";
        echo "<li>Direct SQL: ";
        echo "<a href='?update=1&id={$acc['id']}&mode=shop'>Shop</a> | ";
        echo "<a href='?update=1&id={$acc['id']}&mode=general'>General</a> | ";
        echo "<a href='?update=1&id={$acc['id']}&mode=auto_reply_only'>Auto Reply</a>";
        echo "</li>";
        echo "<li>Via Manager: ";
        echo "<a href='?manager=1&id={$acc['id']}&mode=shop'>Shop</a> | ";
        echo "<a href='?manager=1&id={$acc['id']}&mode=general'>General</a> | ";
        echo "<a href='?manager=1&id={$acc['id']}&mode=auto_reply_only'>Auto Reply</a>";
        echo "</li>";
        echo "</ul>";
    }
}

echo "<p><a href='line-accounts.php'>← กลับหน้าจัดการบัญชี LINE</a></p>";
?>
