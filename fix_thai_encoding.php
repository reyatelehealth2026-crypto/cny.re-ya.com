<?php
/**
 * Fix  Encoding Script
 * Run this once to fix encoding issues
 */

require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Fix  Encoding</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #06C755; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Fix  Encoding</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>1. Testing Connection</h2>";
    echo "<p class='success'>✅ Database connected successfully</p>";
    
    // Check current charset
    echo "<h2>2. Current Database Charset</h2>";
    $stmt = $db->query("SELECT @@character_set_database, @@collation_database");
    $charset = $stmt->fetch();
    echo "<pre>Character Set: " . $charset['@@character_set_database'] . "\nCollation: " . $charset['@@collation_database'] . "</pre>";
    
    // Fix database charset
    echo "<h2>3. Fixing Database Charset</h2>";
    
    $tables = [
        'users' => ['display_name', 'status_message'],
        'messages' => ['content'],
        'auto_replies' => ['keyword', 'reply_content'],
        'products' => ['name', 'description'],
        'product_categories' => ['name', 'description'],
        'orders' => ['shipping_name', 'shipping_address', 'note'],
        'order_items' => ['product_name'],
        'broadcasts' => ['title', 'content'],
        'groups' => ['name', 'description'],
        'templates' => ['name', 'content'],
        'welcome_settings' => ['text_content'],
        'shop_settings' => ['shop_name', 'welcome_message', 'bank_accounts'],
        'line_accounts' => ['name'],
        'admin_users' => ['display_name'],
        'scheduled_messages' => ['title', 'content'],
        'rich_menus' => ['name', 'chat_bar_text']
    ];
    
    $fixed = 0;
    $errors = 0;
    
    foreach ($tables as $table => $columns) {
        try {
            // Check if table exists
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                echo "<p class='info'>⏭️ Table '$table' does not exist, skipping...</p>";
                continue;
            }
            
            // Convert table
            $db->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p class='success'>✅ Fixed table: $table</p>";
            $fixed++;
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error fixing $table: " . $e->getMessage() . "</p>";
            $errors++;
        }
    }
    
    echo "<h2>4. Summary</h2>";
    echo "<p>Tables fixed: <strong>$fixed</strong></p>";
    echo "<p>Errors: <strong>$errors</strong></p>";
    
    // Test  text
    echo "<h2>5. Testing  Text</h2>";
    echo "<p>ทดสอบภาษาไทย: สวัสดีครับ/ค่ะ 🎉</p>";
    
    // Test insert and read
    try {
        $testText = "ทดสอบภาษาไทย " . date('Y-m-d H:i:s');
        
        // Try to insert into a test
        $stmt = $db->query("SHOW TABLES LIKE 'analytics'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare("INSERT INTO analytics (event_type, event_data) VALUES (?, ?)");
            $stmt->execute(['encoding_test', json_encode(['text' => $testText], JSON_UNESCAPED_UNICODE)]);
            
            $stmt = $db->query("SELECT * FROM analytics WHERE event_type = 'encoding_test' ORDER BY id DESC LIMIT 1");
            $row = $stmt->fetch();
            
            if ($row) {
                $data = json_decode($row['event_data'], true);
                echo "<p class='success'>✅  text saved and retrieved: " . htmlspecialchars($data['text'] ?? 'N/A') . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='info'>ℹ️ Could not test insert: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ Done!</h2>";
    echo "<p>Encoding fix completed. <a href='auth/login.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
