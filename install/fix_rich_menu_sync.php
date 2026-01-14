<?php
/**
 * Fix Rich Menu Sync - ลบ menu ที่ไม่มีบน LINE ออกจาก DB
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/LineAPI.php';

echo "<h2>🔧 Fix Rich Menu Sync</h2>";
echo "<pre>";

// Get LINE API
$stmt = $db->query("SELECT * FROM line_accounts WHERE is_active = 1 LIMIT 1");
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    die("❌ ไม่พบ LINE Account ที่ active");
}

$line = new LineAPI($account['channel_access_token'], $account['channel_secret']);

// 1. Get all menus from LINE
echo "📡 ดึงข้อมูลจาก LINE API...\n";
$lineMenus = $line->getRichMenuList();

$lineMenuIds = [];
if ($lineMenus['code'] === 200 && !empty($lineMenus['body']['richmenus'])) {
    foreach ($lineMenus['body']['richmenus'] as $menu) {
        $lineMenuIds[] = $menu['richMenuId'];
        echo "✅ พบบน LINE: {$menu['richMenuId']} - {$menu['name']}\n";
    }
} else {
    echo "⚠️ ไม่พบ Rich Menu บน LINE หรือ API error\n";
}

echo "\n📊 พบ " . count($lineMenuIds) . " menu บน LINE\n\n";

// 2. Get all menus from DB
echo "🗄️ ตรวจสอบ DB...\n";
$stmt = $db->query("SELECT id, line_rich_menu_id, name FROM rich_menus");
$dbMenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deleted = 0;
$kept = 0;

foreach ($dbMenus as $dbMenu) {
    if (empty($dbMenu['line_rich_menu_id'])) {
        echo "🗑️ ลบ (ไม่มี line_rich_menu_id): ID {$dbMenu['id']} - {$dbMenu['name']}\n";
        $stmt = $db->prepare("DELETE FROM rich_menus WHERE id = ?");
        $stmt->execute([$dbMenu['id']]);
        $deleted++;
    } elseif (!in_array($dbMenu['line_rich_menu_id'], $lineMenuIds)) {
        echo "🗑️ ลบ (ไม่มีบน LINE): ID {$dbMenu['id']} - {$dbMenu['name']} ({$dbMenu['line_rich_menu_id']})\n";
        $stmt = $db->prepare("DELETE FROM rich_menus WHERE id = ?");
        $stmt->execute([$dbMenu['id']]);
        $deleted++;
    } else {
        echo "✅ เก็บไว้: ID {$dbMenu['id']} - {$dbMenu['name']}\n";
        $kept++;
    }
}

echo "\n";
echo "📊 สรุป:\n";
echo "   - ลบออก: {$deleted} รายการ\n";
echo "   - เก็บไว้: {$kept} รายการ\n";
echo "   - บน LINE: " . count($lineMenuIds) . " รายการ\n";

// 3. Add missing menus from LINE to DB
echo "\n🔄 เพิ่ม menu จาก LINE ที่ยังไม่มีใน DB...\n";
$added = 0;

if (!empty($lineMenus['body']['richmenus'])) {
    foreach ($lineMenus['body']['richmenus'] as $lineMenu) {
        $richMenuId = $lineMenu['richMenuId'];
        
        $stmt = $db->prepare("SELECT id FROM rich_menus WHERE line_rich_menu_id = ?");
        $stmt->execute([$richMenuId]);
        
        if (!$stmt->fetch()) {
            // Check if has image
            $hasImage = $line->getRichMenuImage($richMenuId) ? 'มีรูป' : 'ไม่มีรูป';
            
            $stmt = $db->prepare("INSERT INTO rich_menus (line_rich_menu_id, name, chat_bar_text, size_height, areas, line_account_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $richMenuId,
                $lineMenu['name'] ?? 'Synced Menu',
                $lineMenu['chatBarText'] ?? 'Menu',
                $lineMenu['size']['height'] ?? 1686,
                json_encode($lineMenu['areas'] ?? []),
                $account['id']
            ]);
            echo "➕ เพิ่ม: {$lineMenu['name']} ({$hasImage})\n";
            $added++;
        }
    }
}

echo "\n✅ เพิ่มใหม่: {$added} รายการ\n";
echo "\n🎉 เสร็จสิ้น! กลับไปหน้า Rich Menu แล้ว refresh\n";
echo "</pre>";

echo "<p><a href='../rich-menu.php?tab=static' class='btn'>← กลับหน้า Rich Menu</a></p>";
