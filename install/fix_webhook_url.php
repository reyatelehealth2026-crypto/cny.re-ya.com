<?php
/**
 * Fix Webhook URL - Remove Double Slashes
 * แก้ไข webhook URL ที่มี // ซ้ำในฐานข้อมูล
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Fix Webhook URL</h2>";
echo "<p>แก้ไข webhook URL ที่มี // ซ้ำ</p>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all LINE accounts
    $stmt = $db->query("SELECT id, name, webhook_url FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p style='color: orange;'>⚠️ ไม่พบ LINE Account ในระบบ</p>";
        exit;
    }
    
    echo "<h3>📋 LINE Accounts</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Old URL</th><th>New URL</th><th>Status</th>";
    echo "</tr>";
    
    $baseUrl = rtrim(BASE_URL, '/'); // ตัด / ออกจากท้าย
    $fixed = 0;
    $skipped = 0;
    
    foreach ($accounts as $account) {
        $oldUrl = $account['webhook_url'];
        $newUrl = $baseUrl . '/webhook.php?account=' . $account['id'];
        
        // Check if URL has double slashes
        $hasDoubleSlash = strpos($oldUrl, '//webhook.php') !== false;
        
        if ($hasDoubleSlash || $oldUrl !== $newUrl) {
            // Update URL
            $stmt = $db->prepare("UPDATE line_accounts SET webhook_url = ? WHERE id = ?");
            $stmt->execute([$newUrl, $account['id']]);
            
            $bgColor = '#e8f5e9';
            $status = '✅ แก้ไขแล้ว';
            $fixed++;
        } else {
            $bgColor = '#fff';
            $status = '⏭️ ข้าม (ถูกต้องแล้ว)';
            $skipped++;
        }
        
        echo "<tr style='background: {$bgColor};'>";
        echo "<td>{$account['id']}</td>";
        echo "<td><strong>{$account['name']}</strong></td>";
        echo "<td style='font-size: 11px;'>" . htmlspecialchars($oldUrl) . "</td>";
        echo "<td style='font-size: 11px;'>" . htmlspecialchars($newUrl) . "</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>📊 สรุปผลลัพธ์</h3>";
    echo "<ul>";
    echo "<li>✅ แก้ไขแล้ว: <strong>{$fixed}</strong> บอท</li>";
    echo "<li>⏭️ ข้าม: <strong>{$skipped}</strong> บอท</li>";
    echo "<li>📝 รวมทั้งหมด: <strong>" . count($accounts) . "</strong> บอท</li>";
    echo "</ul>";
    
    if ($fixed > 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>⚠️ สิ่งที่ต้องทำต่อ:</h4>";
        echo "<ol>";
        echo "<li>ไปที่ <a href='https://developers.line.biz/console/' target='_blank'>LINE Developers Console</a></li>";
        echo "<li>เลือกแต่ละ Channel ที่แก้ไข</li>";
        echo "<li>ไปที่ <strong>Messaging API</strong> → <strong>Webhook settings</strong></li>";
        echo "<li>อัพเดท Webhook URL ให้ตรงกับ <strong>New URL</strong> ในตารางด้านบน</li>";
        echo "<li>กด <strong>Verify</strong> เพื่อทดสอบ</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>🔗 เครื่องมืออื่นๆ</h3>";
    echo "<ul>";
    echo "<li><a href='debug_line_accounts.php'>Debug LINE Accounts</a></li>";
    echo "<li><a href='check_contact_logs.php'>Check Contact Logs</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}
