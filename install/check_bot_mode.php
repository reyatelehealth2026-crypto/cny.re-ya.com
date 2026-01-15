<?php
/**
 * Check Bot Mode - ตรวจสอบว่า bot_mode ตั้งค่าเป็นอะไร
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "=== Bot Mode Check ===\n\n";

// Get all LINE accounts
$stmt = $db->query("SELECT id, account_name, bot_mode FROM line_accounts ORDER BY id");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($accounts)) {
    echo "❌ No LINE accounts found\n";
    exit(1);
}

echo "Found " . count($accounts) . " LINE account(s):\n\n";

foreach ($accounts as $account) {
    echo "Account ID: {$account['id']}\n";
    echo "Name: {$account['account_name']}\n";
    echo "Bot Mode: {$account['bot_mode']}\n";
    
    if ($account['bot_mode'] === 'shop') {
        echo "✅ Shop mode - Bot will respond to commands and auto-reply\n";
    } elseif ($account['bot_mode'] === 'general') {
        echo "⚠️  General mode - Bot will ONLY respond to auto-reply rules\n";
    } else {
        echo "❓ Unknown mode\n";
    }
    
    // Check auto-reply rules for this account
    $stmt = $db->prepare("SELECT COUNT(*) FROM auto_reply_rules WHERE (line_account_id = ? OR line_account_id IS NULL) AND is_enabled = 1");
    $stmt->execute([$account['id']]);
    $ruleCount = $stmt->fetchColumn();
    
    echo "Auto-reply rules: {$ruleCount} enabled\n";
    
    if ($ruleCount > 0) {
        echo "\nEnabled auto-reply keywords:\n";
        $stmt = $db->prepare("SELECT keyword, reply_type FROM auto_reply_rules WHERE (line_account_id = ? OR line_account_id IS NULL) AND is_enabled = 1 ORDER BY priority DESC");
        $stmt->execute([$account['id']]);
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rules as $rule) {
            echo "  - \"{$rule['keyword']}\" ({$rule['reply_type']})\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "\n💡 Tip: If bot_mode is 'general', bot will ONLY respond to auto-reply keywords.\n";
echo "   To enable full bot features, set bot_mode to 'shop'.\n";
