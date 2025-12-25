<?php
/**
 * Fix Telegram Settings - เพิ่ม columns ที่ขาด
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Fix Telegram Settings</h1>";

$columns = [
    'notify_new_order' => "ALTER TABLE telegram_settings ADD COLUMN notify_new_order TINYINT(1) DEFAULT 1",
    'notify_payment' => "ALTER TABLE telegram_settings ADD COLUMN notify_payment TINYINT(1) DEFAULT 1",
    'notify_unfollow' => "ALTER TABLE telegram_settings ADD COLUMN notify_unfollow TINYINT(1) DEFAULT 1",
];

foreach ($columns as $col => $sql) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'telegram_settings' 
                              AND COLUMN_NAME = ?");
        $stmt->execute([$col]);
        $exists = (int)$stmt->fetchColumn();
        
        if ($exists) {
            echo "<p>✓ Column <strong>$col</strong> มีอยู่แล้ว</p>";
        } else {
            $db->exec($sql);
            echo "<p style='color:green;'>✓ เพิ่ม column <strong>$col</strong> สำเร็จ</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Error $col: " . $e->getMessage() . "</p>";
    }
}

echo "<p><strong>เสร็จสิ้น!</strong></p>";
echo "<p><a href='telegram.php'>กลับไปหน้า Telegram Settings</a></p>";
