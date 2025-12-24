<?php
/**
 * Debug Rich Menu
 */
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/LineAPI.php';
require_once 'classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Rich Menu Debug</h2>";

// 1. Check current bot
$currentBotId = $_GET['bot_id'] ?? $_SESSION['current_bot_id'] ?? 1;
echo "<h3>1. Current Bot ID: {$currentBotId}</h3>";

// 2. Get LINE Account info
$stmt = $db->prepare("SELECT * FROM line_accounts WHERE id = ?");
$stmt->execute([$currentBotId]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($account) {
    echo "<p>✅ LINE Account: {$account['name']}</p>";
    echo "<p>Channel ID: " . substr($account['channel_id'] ?? 'N/A', 0, 10) . "...</p>";
    echo "<p>Access Token: " . (empty($account['channel_access_token']) ? '❌ ไม่มี' : '✅ มี (' . strlen($account['channel_access_token']) . ' chars)') . "</p>";
} else {
    echo "<p>❌ ไม่พบ LINE Account ID: {$currentBotId}</p>";
}

// 3. Check rich_menus table
echo "<h3>2. ตาราง rich_menus</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'rich_menus'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ ตาราง rich_menus มีอยู่</p>";
        
        // Show columns
        $stmt = $db->query("DESCRIBE rich_menus");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columns: " . implode(', ', $columns) . "</p>";
        
        // Count menus
        $stmt = $db->query("SELECT COUNT(*) FROM rich_menus");
        $count = $stmt->fetchColumn();
        echo "<p>จำนวน Rich Menu ในระบบ: {$count}</p>";
        
        // List menus
        $stmt = $db->query("SELECT id, name, line_rich_menu_id, is_default, created_at FROM rich_menus ORDER BY created_at DESC LIMIT 5");
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($menus) {
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>LINE Menu ID</th><th>Default</th><th>Created</th></tr>";
            foreach ($menus as $m) {
                echo "<tr><td>{$m['id']}</td><td>{$m['name']}</td><td>" . substr($m['line_rich_menu_id'] ?? '', 0, 20) . "...</td><td>" . ($m['is_default'] ? '✅' : '') . "</td><td>{$m['created_at']}</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ ตาราง rich_menus ไม่มี</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 4. Test LINE API
echo "<h3>3. ทดสอบ LINE API</h3>";
$line = null;
$result = null;
$default = null;

try {
    $lineManager = new LineAccountManager($db);
    $line = $lineManager->getLineAPI($currentBotId);
    
    if ($line) {
        echo "<p>✅ LineAPI instance created</p>";
        
        // Set default first if requested
        if (isset($_GET['set_default'])) {
            $menuId = $_GET['set_default'];
            echo "<div style='background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;'>";
            echo "<h4>🔧 ตั้ง Default Rich Menu: " . substr($menuId, 0, 30) . "...</h4>";
            $setResult = $line->setDefaultRichMenu($menuId);
            if ($setResult['code'] === 200) {
                echo "<p>✅ ตั้ง Default สำเร็จ!</p>";
                // Update DB
                $db->query("UPDATE rich_menus SET is_default = 0");
                $stmt = $db->prepare("UPDATE rich_menus SET is_default = 1 WHERE line_rich_menu_id = ?");
                $stmt->execute([$menuId]);
            } else {
                echo "<p>❌ Error: " . json_encode($setResult['body']) . "</p>";
            }
            echo "</div>";
        }
        
        // Get rich menu list from LINE
        echo "<h4>Rich Menus จาก LINE API:</h4>";
        $result = $line->getRichMenuList();
        
        // Get default rich menu
        $default = $line->getDefaultRichMenu();
        $defaultId = $default['body']['richMenuId'] ?? null;
        
        if ($defaultId) {
            echo "<p style='color:green;font-weight:bold;'>✅ Default Rich Menu: " . substr($defaultId, 0, 40) . "...</p>";
        } else {
            echo "<p style='color:red;font-weight:bold;'>❌ ยังไม่ได้ตั้ง Default Rich Menu!</p>";
        }
    } else {
        echo "<p>❌ ไม่สามารถสร้าง LineAPI instance</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 6. Sync from LINE
echo "<h3>4. Sync Rich Menus จาก LINE</h3>";
if (isset($result['body']['richmenus']) && is_array($result['body']['richmenus'])) {
    echo "<table border='1' cellpadding='5'><tr><th>Rich Menu ID</th><th>Name</th><th>Chat Bar</th><th>Size</th><th>Actions</th></tr>";
    foreach ($result['body']['richmenus'] as $rm) {
        $rmId = $rm['richMenuId'];
        $isDefault = (isset($default['body']['richMenuId']) && $default['body']['richMenuId'] === $rmId) ? '✅ DEFAULT' : '';
        echo "<tr>";
        echo "<td>" . substr($rmId, 0, 20) . "... {$isDefault}</td>";
        echo "<td>{$rm['name']}</td>";
        echo "<td>{$rm['chatBarText']}</td>";
        echo "<td>{$rm['size']['width']}x{$rm['size']['height']}</td>";
        echo "<td><a href='?bot_id={$currentBotId}&set_default={$rmId}'>ตั้งเป็น Default</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>ไม่พบ Rich Menu ใน LINE</p>";
}

echo "<hr><p><a href='rich-menu.php'>กลับไปหน้า Rich Menu</a></p>";
