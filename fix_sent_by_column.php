<?php
/**
 * Fix sent_by Column - เปลี่ยนจาก ENUM เป็น VARCHAR
 */
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Fix sent_by Column Type</h1>";

// 1. Check current type
echo "<h2>1. Current Column Type</h2>";
$stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'sent_by'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Current Type: <strong style='color:red;'>{$col['Type']}</strong></p>";

if (strpos($col['Type'], 'enum') !== false) {
    echo "<p>⚠️ Column is ENUM - needs to be changed to VARCHAR to store admin names</p>";
    
    if (isset($_GET['fix'])) {
        echo "<h2>2. Fixing Column...</h2>";
        
        try {
            // Change ENUM to VARCHAR(100)
            $db->exec("ALTER TABLE messages MODIFY COLUMN sent_by VARCHAR(100) DEFAULT NULL");
            echo "<p style='color:green;'>✅ Column changed to VARCHAR(100)</p>";
            
            // Verify
            $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'sent_by'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>New Type: <strong style='color:green;'>{$col['Type']}</strong></p>";
            
            // Test insert
            echo "<h2>3. Testing Insert...</h2>";
            $testSentBy = 'admin:test_user';
            $stmt = $db->prepare("INSERT INTO messages (line_account_id, user_id, direction, message_type, content, sent_by, created_at, is_read) VALUES (1, 1, 'outgoing', 'text', 'TEST_FIX', ?, NOW(), 0)");
            $stmt->execute([$testSentBy]);
            $newId = $db->lastInsertId();
            
            $stmt = $db->prepare("SELECT sent_by FROM messages WHERE id = ?");
            $stmt->execute([$newId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row['sent_by'] === $testSentBy) {
                echo "<p style='color:green; font-size:20px;'>✅ SUCCESS! sent_by now works correctly!</p>";
                echo "<p>Saved value: <strong>{$row['sent_by']}</strong></p>";
            } else {
                echo "<p style='color:red;'>❌ Still not working</p>";
            }
            
            // Cleanup
            $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$newId]);
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<p><a href='inbox'>← Go to Inbox</a></p>";
        
    } else {
        echo "<p><a href='?fix=1' style='padding:15px 30px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px; font-size:18px;'>🔧 Fix Column Now</a></p>";
    }
    
} else {
    echo "<p style='color:green;'>✅ Column is already VARCHAR - no fix needed</p>";
}

echo "<hr><p><a href='debug_messages_table.php'>← Back to Debug</a></p>";
