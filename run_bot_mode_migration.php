<?php
/**
 * Run Bot Mode Migration
 * เพิ่ม column bot_mode ในตาราง line_accounts
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🤖 Bot Mode Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // ตรวจสอบว่ามี column bot_mode หรือยัง
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'bot_mode'");
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'bot_mode' already exists in line_accounts table\n";
    } else {
        // เพิ่ม column
        $sql = "ALTER TABLE line_accounts 
                ADD COLUMN bot_mode ENUM('shop', 'general', 'auto_reply_only') DEFAULT 'shop' 
                COMMENT 'โหมดบอท: shop=ร้านค้าเต็มรูปแบบ, general=ทั่วไป(ไม่มีร้านค้า), auto_reply_only=ตอบกลับอัตโนมัติเท่านั้น'
                AFTER is_default";
        
        $db->exec($sql);
        echo "✅ Added 'bot_mode' column to line_accounts table\n";
    }
    
    // แสดงข้อมูลปัจจุบัน
    echo "\n📋 Current LINE Accounts:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $db->query("SELECT id, name, bot_mode, is_active FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "No accounts found.\n";
    } else {
        foreach ($accounts as $acc) {
            $mode = $acc['bot_mode'] ?? 'shop';
            $status = $acc['is_active'] ? '✅' : '❌';
            echo sprintf("ID: %d | %s | Mode: %s | %s\n", 
                $acc['id'], 
                $acc['name'], 
                $mode,
                $status
            );
        }
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='line-accounts.php'>← กลับไปหน้าจัดการบัญชี LINE</a></p>";
?>
