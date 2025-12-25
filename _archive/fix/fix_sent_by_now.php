<?php
/**
 * Auto Fix sent_by Column - เปลี่ยนจาก ENUM เป็น VARCHAR อัตโนมัติ
 * เปิดไฟล์นี้แล้วจะแก้ไขทันที
 */
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix sent_by</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:800px;margin:0 auto}";
echo ".success{color:green;background:#D1FAE5;padding:10px;border-radius:5px;margin:10px 0}";
echo ".error{color:red;background:#FEE2E2;padding:10px;border-radius:5px;margin:10px 0}";
echo ".info{color:#1E40AF;background:#DBEAFE;padding:10px;border-radius:5px;margin:10px 0}";
echo "pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto}</style></head><body>";

echo "<h1>🔧 Auto Fix sent_by Column</h1>";

try {
    // 1. Check current type
    echo "<h2>1. ตรวจสอบ Column ปัจจุบัน</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'sent_by'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$col) {
        echo "<div class='error'>❌ ไม่พบ column sent_by ในตาราง messages</div>";
        echo "<p>กำลังเพิ่ม column...</p>";
        $db->exec("ALTER TABLE messages ADD COLUMN sent_by VARCHAR(100) DEFAULT NULL");
        echo "<div class='success'>✅ เพิ่ม column sent_by เรียบร้อย</div>";
    } else {
        echo "<div class='info'>Type ปัจจุบัน: <strong>{$col['Type']}</strong></div>";
        
        if (strpos($col['Type'], 'enum') !== false) {
            echo "<p>⚠️ Column เป็น ENUM - กำลังเปลี่ยนเป็น VARCHAR...</p>";
            
            // Change ENUM to VARCHAR(100)
            $db->exec("ALTER TABLE messages MODIFY COLUMN sent_by VARCHAR(100) DEFAULT NULL");
            echo "<div class='success'>✅ เปลี่ยน Column เป็น VARCHAR(100) เรียบร้อย!</div>";
            
            // Verify
            $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'sent_by'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div class='info'>Type ใหม่: <strong>{$col['Type']}</strong></div>";
        } else {
            echo "<div class='success'>✅ Column เป็น VARCHAR อยู่แล้ว - ไม่ต้องแก้ไข</div>";
        }
    }
    
    // 2. Test insert
    echo "<h2>2. ทดสอบ Insert</h2>";
    $adminUser = $_SESSION['admin_user'] ?? [];
    $adminName = !empty($adminUser['username']) ? $adminUser['username'] : 'test_admin';
    $testSentBy = 'admin:' . $adminName;
    
    // หา user_id และ line_account_id ที่มีอยู่จริง
    $stmt = $db->query("SELECT id, line_account_id FROM users LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<div class='error'>❌ ไม่พบ user ในระบบ - ไม่สามารถทดสอบได้</div>";
    } else {
        $testUserId = $testUser['id'];
        $testBotId = $testUser['line_account_id'];
        
        echo "<p>ทดสอบ insert ด้วย sent_by = <strong>$testSentBy</strong></p>";
        echo "<p><small>ใช้ user_id: $testUserId, line_account_id: $testBotId</small></p>";
        
        $stmt = $db->prepare("INSERT INTO messages (line_account_id, user_id, direction, message_type, content, sent_by, created_at, is_read) VALUES (?, ?, 'outgoing', 'text', 'TEST_FIX_AUTO', ?, NOW(), 0)");
        $stmt->execute([$testBotId, $testUserId, $testSentBy]);
        $newId = $db->lastInsertId();
    
    $stmt = $db->prepare("SELECT sent_by FROM messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($row['sent_by'] === $testSentBy) {
            echo "<div class='success' style='font-size:18px'>✅ SUCCESS! sent_by ทำงานถูกต้องแล้ว!</div>";
            echo "<p>ค่าที่บันทึก: <strong>{$row['sent_by']}</strong></p>";
        } else {
            echo "<div class='error'>❌ ยังไม่ทำงาน - ค่าที่ได้: " . ($row['sent_by'] ?: 'NULL') . "</div>";
        }
        
        // Cleanup test
        $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$newId]);
        echo "<p><small>ลบข้อความทดสอบแล้ว</small></p>";
    }
    
    // 3. Session info
    echo "<h2>3. Session Info</h2>";
    echo "<pre>";
    echo "Session ID: " . session_id() . "\n";
    echo "admin_user: " . json_encode($adminUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "</pre>";
    
    // 4. Recent messages
    echo "<h2>4. ข้อความล่าสุด (ตรวจสอบ sent_by)</h2>";
    $stmt = $db->query("SELECT id, content, sent_by, direction, created_at FROM messages ORDER BY id DESC LIMIT 10");
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%'>";
    echo "<tr style='background:#f0f0f0'><th>ID</th><th>Content</th><th>sent_by</th><th>Direction</th><th>Time</th></tr>";
    foreach ($msgs as $m) {
        $sentByDisplay = $m['sent_by'] ?: '<span style="color:red">NULL</span>';
        $bgColor = empty($m['sent_by']) && $m['direction'] === 'outgoing' ? '#FEE2E2' : '';
        echo "<tr style='background:$bgColor'>";
        echo "<td>{$m['id']}</td>";
        echo "<td>" . htmlspecialchars(mb_substr($m['content'] ?? '', 0, 30)) . "</td>";
        echo "<td>$sentByDisplay</td>";
        echo "<td>{$m['direction']}</td>";
        echo "<td>{$m['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='inbox' style='padding:10px 20px;background:#10B981;color:white;text-decoration:none;border-radius:5px'>← กลับไป Inbox</a></p>";
echo "<p><a href='test_polling.php'>🔄 ทดสอบ Polling</a></p>";
echo "</body></html>";
