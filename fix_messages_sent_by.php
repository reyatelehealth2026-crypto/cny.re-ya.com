<?php
/**
 * Fix Messages - Add sent_by column
 * เพิ่ม column sent_by เพื่อ track ว่าข้อความส่งโดยใคร (ai, admin, system)
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Fix Messages - Add sent_by column</h2>";
echo "<pre>";

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'sent_by'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE messages ADD COLUMN sent_by VARCHAR(50) NULL COMMENT 'ai, admin, system, webhook' AFTER direction");
        echo "✅ Added column: messages.sent_by\n";
    } else {
        echo "⏭️ Column exists: messages.sent_by\n";
    }
    
    // Also add ai_model column to track which AI model was used
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'ai_model'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE messages ADD COLUMN ai_model VARCHAR(100) NULL COMMENT 'AI model used (gemini, openai, etc)' AFTER sent_by");
        echo "✅ Added column: messages.ai_model\n";
    } else {
        echo "⏭️ Column exists: messages.ai_model\n";
    }
    
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
