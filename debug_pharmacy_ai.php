<?php
/**
 * Debug Pharmacy AI - ตรวจสอบการตั้งค่า AI เภสัชกร
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Debug Pharmacy AI</h2>";

// 1. ตรวจสอบ ai_chat_settings
echo "<h3>1. ตาราง ai_chat_settings</h3>";
try {
    $stmt = $db->query("SELECT id, line_account_id, is_enabled, 
                        CASE WHEN gemini_api_key IS NOT NULL AND gemini_api_key != '' THEN 'มี API Key' ELSE 'ไม่มี API Key' END as has_key,
                        model, sender_name
                        FROM ai_chat_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "<p style='color:orange'>⚠️ ไม่มีข้อมูลในตาราง ai_chat_settings</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Line Account ID</th><th>Enabled</th><th>API Key</th><th>Model</th><th>Sender</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['line_account_id']}</td>";
            echo "<td>" . ($row['is_enabled'] ? '✅' : '❌') . "</td>";
            echo "<td>{$row['has_key']}</td>";
            echo "<td>{$row['model']}</td>";
            echo "<td>{$row['sender_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// 2. ตรวจสอบ line_accounts
echo "<h3>2. ตาราง line_accounts (gemini_api_key)</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'gemini_api_key'");
    $col = $stmt->fetch();
    
    if ($col) {
        echo "<p style='color:green'>✅ มี column gemini_api_key</p>";
        
        $stmt = $db->query("SELECT id, name, 
                            CASE WHEN gemini_api_key IS NOT NULL AND gemini_api_key != '' THEN 'มี' ELSE 'ไม่มี' END as has_key
                            FROM line_accounts");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>API Key</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['has_key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ ไม่มี column gemini_api_key ในตาราง line_accounts</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// 3. ทดสอบ PharmacyAIAdapter
echo "<h3>3. ทดสอบ PharmacyAIAdapter</h3>";
try {
    require_once __DIR__ . '/modules/AIChat/Adapters/PharmacyAIAdapter.php';
    
    // ใช้ line_account_id ที่มี API Key
    $stmt = $db->query("SELECT line_account_id FROM ai_chat_settings WHERE is_enabled = 1 AND gemini_api_key IS NOT NULL AND gemini_api_key != '' LIMIT 1");
    $lineAccountId = $stmt->fetchColumn();
    
    if (!$lineAccountId) {
        // Fallback to first line_account
        $stmt = $db->query("SELECT id FROM line_accounts LIMIT 1");
        $lineAccountId = $stmt->fetchColumn();
    }
    
    if ($lineAccountId) {
        echo "<p>ใช้ line_account_id: <strong>{$lineAccountId}</strong></p>";
        
        $adapter = new \Modules\AIChat\Adapters\PharmacyAIAdapter($db, $lineAccountId);
        
        // แสดง Model ที่ใช้
        echo "<p>🤖 Model: <strong>" . $adapter->getModel() . "</strong></p>";
        
        if ($adapter->isEnabled()) {
            echo "<p style='color:green'>✅ PharmacyAIAdapter พร้อมใช้งาน (line_account_id: {$lineAccountId})</p>";
            
            // ทดสอบส่งข้อความ
            echo "<h4>ทดสอบส่งข้อความ:</h4>";
            $adapter->setUserId(1); // ใช้ user_id = 1 สำหรับทดสอบ
            
            $testMessage = "สวัสดีครับ ผมปวดหัวมาก 2 วันแล้ว ควรทานยาอะไรดี";
            $result = $adapter->processMessage($testMessage);
            
            echo "<p><strong>Input:</strong> {$testMessage}</p>";
            echo "<p><strong>Success:</strong> " . ($result['success'] ? 'Yes' : 'No') . "</p>";
            echo "<p><strong>State:</strong> " . ($result['state'] ?? 'N/A') . "</p>";
            
            if ($result['success']) {
                echo "<p><strong>Response:</strong></p>";
                echo "<div style='background:#e8f5e9; padding:15px; border-radius:8px; border-left:4px solid #4caf50;'>" . nl2br(htmlspecialchars($result['response'])) . "</div>";
            } else {
                echo "<p style='color:red'><strong>Error:</strong> " . ($result['error'] ?? 'Unknown') . "</p>";
            }
            
            // ทดสอบคำถามทั่วไป
            echo "<h4>ทดสอบคำถามทั่วไป:</h4>";
            $testMessage2 = "วันนี้อากาศเป็นยังไงบ้าง";
            $result2 = $adapter->processMessage($testMessage2);
            
            echo "<p><strong>Input:</strong> {$testMessage2}</p>";
            echo "<p><strong>Success:</strong> " . ($result2['success'] ? 'Yes' : 'No') . "</p>";
            
            if ($result2['success']) {
                echo "<div style='background:#e3f2fd; padding:15px; border-radius:8px; border-left:4px solid #2196f3;'>" . nl2br(htmlspecialchars($result2['response'])) . "</div>";
            }
        } else {
            echo "<p style='color:red'>❌ PharmacyAIAdapter ไม่พร้อมใช้งาน - ไม่มี API Key</p>";
            echo "<p>กรุณาตั้งค่า Gemini API Key ที่หน้า <a href='ai-chat-settings.php'>AI Chat Settings</a></p>";
        }
    } else {
        echo "<p style='color:red'>❌ ไม่พบ line_accounts</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 4. ตรวจสอบตาราง ai_conversation_history
echo "<h3>4. ตาราง ai_conversation_history</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'ai_conversation_history'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>✅ มีตาราง ai_conversation_history</p>";
    } else {
        echo "<p style='color:orange'>⚠️ ไม่มีตาราง ai_conversation_history - จะสร้างให้</p>";
        
        $db->exec("CREATE TABLE IF NOT EXISTS ai_conversation_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            line_account_id INT DEFAULT NULL,
            role ENUM('user', 'assistant') NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>✅ สร้างตาราง ai_conversation_history แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='ai-chat-settings.php'>➡️ ไปหน้าตั้งค่า AI Chat</a></p>";
