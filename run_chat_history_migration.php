<?php
/**
 * Run Chat History Migration
 * เพิ่มความสามารถในการเก็บประวัติแชทสำหรับ AI Context
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>🔄 Chat History Migration</h1>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Add indexes to messages table
    echo "1. Adding indexes to messages table...\n";
    try {
        $db->exec("ALTER TABLE messages ADD INDEX idx_user_created (user_id, created_at)");
        echo "   ✅ Added idx_user_created\n";
    } catch (Exception $e) {
        echo "   ⚠️ idx_user_created: " . $e->getMessage() . "\n";
    }
    
    try {
        $db->exec("ALTER TABLE messages ADD INDEX idx_user_direction (user_id, direction)");
        echo "   ✅ Added idx_user_direction\n";
    } catch (Exception $e) {
        echo "   ⚠️ idx_user_direction: " . $e->getMessage() . "\n";
    }
    
    // 2. Create ai_chat_logs table
    echo "\n2. Creating ai_chat_logs table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS ai_chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        user_message TEXT,
        ai_response TEXT,
        conversation_context JSON COMMENT 'เก็บ context ที่ส่งให้ AI',
        response_time_ms INT COMMENT 'เวลาที่ AI ใช้ตอบ (milliseconds)',
        model_used VARCHAR(50) DEFAULT 'gemini-2.0-flash',
        tokens_used INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_account (line_account_id),
        INDEX idx_user (user_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ ai_chat_logs table ready\n";
    
    // 3. Create conversation_sessions table
    echo "\n3. Creating conversation_sessions table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS conversation_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        user_id INT NOT NULL,
        session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        session_end TIMESTAMP NULL,
        message_count INT DEFAULT 0,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        session_data JSON COMMENT 'เก็บข้อมูล session เช่น อาการที่ซักถามไปแล้ว',
        status ENUM('active', 'completed', 'expired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_status (user_id, status),
        INDEX idx_account (line_account_id),
        INDEX idx_last_activity (last_activity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ conversation_sessions table ready\n";
    
    // 4. Add medical columns to users table
    echo "\n4. Adding medical columns to users table...\n";
    
    $columns = [
        'medical_conditions' => "TEXT COMMENT 'โรคประจำตัว'",
        'drug_allergies' => "TEXT COMMENT 'แพ้ยา'",
        'current_medications' => "TEXT COMMENT 'ยาที่ใช้อยู่'"
    ];
    
    foreach ($columns as $col => $def) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE users ADD COLUMN $col $def");
                echo "   ✅ Added $col column\n";
            } else {
                echo "   ⚠️ $col column already exists\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error adding $col: " . $e->getMessage() . "\n";
        }
    }
    
    // 5. Update ai_chat_settings table
    echo "\n5. Updating ai_chat_settings table...\n";
    
    $settingsColumns = [
        'context_messages' => "INT DEFAULT 10 COMMENT 'จำนวนข้อความที่ส่งเป็น context'",
        'session_timeout' => "INT DEFAULT 30 COMMENT 'timeout session (นาที)'",
        'enable_medical_context' => "TINYINT(1) DEFAULT 0 COMMENT 'ใช้ข้อมูลสุขภาพใน context'"
    ];
    
    foreach ($settingsColumns as $col => $def) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM ai_chat_settings LIKE '$col'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE ai_chat_settings ADD COLUMN $col $def");
                echo "   ✅ Added $col column\n";
            } else {
                echo "   ⚠️ $col column already exists\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error adding $col: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Migration completed successfully!\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n📋 Summary:\n";
    echo "- Messages table: indexes added for faster history lookup\n";
    echo "- ai_chat_logs: stores AI conversation logs\n";
    echo "- conversation_sessions: manages chat sessions\n";
    echo "- users table: medical info columns added\n";
    echo "- ai_chat_settings: context settings added\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
