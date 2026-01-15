<?php
/**
 * Run Auto Reply Rules Migration
 * สร้างตาราง auto_reply_rules สำหรับกำหนดกฎการตอบกลับอัตโนมัติ
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Auto Reply Rules Migration</h2>";
echo "<p>กำลังสร้างตาราง auto_reply_rules...</p>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $sql = file_get_contents(__DIR__ . '/../database/migration_auto_reply_rules.sql');
    
    if ($sql === false) {
        throw new Exception("ไม่สามารถอ่านไฟล์ migration ได้");
    }
    
    // Execute migration
    $db->exec($sql);
    
    echo "<p style='color: green;'>✅ สร้างตาราง auto_reply_rules สำเร็จ!</p>";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE auto_reply_rules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 โครงสร้างตาราง</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check sample data
    $stmt = $db->query("SELECT COUNT(*) as count FROM auto_reply_rules");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 ข้อมูลเริ่มต้น</h3>";
    echo "<p>มีกฎการตอบกลับอัตโนมัติ: <strong>{$count['count']}</strong> รายการ</p>";
    
    if ($count['count'] > 0) {
        $stmt = $db->query("SELECT * FROM auto_reply_rules ORDER BY priority DESC");
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Keyword</th><th>Match Type</th><th>Response</th><th>Priority</th><th>Active</th>";
        echo "</tr>";
        
        foreach ($rules as $rule) {
            $bgColor = $rule['is_active'] ? '#e8f5e9' : '#ffebee';
            echo "<tr style='background: {$bgColor};'>";
            echo "<td>{$rule['id']}</td>";
            echo "<td><strong>{$rule['keyword']}</strong></td>";
            echo "<td>{$rule['match_type']}</td>";
            echo "<td>" . htmlspecialchars(mb_substr($rule['response_content'], 0, 50)) . "...</td>";
            echo "<td>{$rule['priority']}</td>";
            echo "<td>" . ($rule['is_active'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Migration เสร็จสมบูรณ์!</h3>";
    echo "<p>ตอนนี้สามารถใช้งาน Auto Reply Rules ได้แล้ว</p>";
    echo "<p><a href='../auto-reply.php'>ไปที่หน้าจัดการ Auto Reply</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Check if table already exists
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p style='color: orange;'>⚠️ ตารางมีอยู่แล้ว - ไม่ต้องทำอะไร</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}
