<?php
/**
 * Add shop location columns to shop_settings table
 * สำหรับแสดงตำแหน่งร้านในหน้า checkout (รับที่ร้าน)
 */

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🏪 Adding Shop Location Columns</h2>";

$columns = [
    'shop_address' => "VARCHAR(500) DEFAULT NULL COMMENT 'ที่อยู่ร้าน'",
    'shop_lat' => "DECIMAL(10,8) DEFAULT NULL COMMENT 'Latitude'",
    'shop_lng' => "DECIMAL(11,8) DEFAULT NULL COMMENT 'Longitude'",
    'contact_phone' => "VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรร้าน'",
    'promptpay_number' => "VARCHAR(20) DEFAULT NULL COMMENT 'เลขพร้อมเพย์'"
];

foreach ($columns as $column => $definition) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE shop_settings ADD COLUMN $column $definition");
            echo "<p style='color:green'>✓ Added column: $column</p>";
        } else {
            echo "<p style='color:gray'>- Column exists: $column</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error adding $column: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>✅ Done!</h3>";
echo "<p>คุณสามารถตั้งค่าที่อยู่ร้านได้ที่หน้า Shop Settings</p>";
?>
