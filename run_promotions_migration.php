<?php
/**
 * Run Promotions Migration
 * เพิ่ม columns สำหรับระบบสินค้าเด่น/โปรโมชั่น
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Run Promotions Migration</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #1E293B; } h2 { color: #475569; }
.success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; } .info { color: #3B82F6; }
</style></head><body>";

echo "<h1>🚀 Run Promotions Migration</h1>";

$migrations = [
    'is_featured' => "ALTER TABLE business_items ADD COLUMN is_featured TINYINT(1) DEFAULT 0",
    'is_bestseller' => "ALTER TABLE business_items ADD COLUMN is_bestseller TINYINT(1) DEFAULT 0",
    'is_promotion' => "ALTER TABLE business_items ADD COLUMN is_promotion TINYINT(1) DEFAULT 0",
    'promotion_start' => "ALTER TABLE business_items ADD COLUMN promotion_start DATETIME NULL",
    'promotion_end' => "ALTER TABLE business_items ADD COLUMN promotion_end DATETIME NULL",
    'featured_order' => "ALTER TABLE business_items ADD COLUMN featured_order INT DEFAULT 0",
];

echo "<div class='card'>";
echo "<h2>📋 เพิ่ม Columns ใน business_items</h2>";

foreach ($migrations as $column => $sql) {
    // Check if column exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'business_items' 
                          AND COLUMN_NAME = ?");
    $stmt->execute([$column]);
    $exists = (int)$stmt->fetchColumn();
    
    if ($exists) {
        echo "<p class='info'>✓ Column <strong>$column</strong> มีอยู่แล้ว</p>";
    } else {
        try {
            $db->exec($sql);
            echo "<p class='success'>✓ เพิ่ม column <strong>$column</strong> สำเร็จ</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error เพิ่ม $column: " . $e->getMessage() . "</p>";
        }
    }
}

// Add index
echo "<h2>📋 เพิ่ม Index</h2>";
try {
    $stmt = $db->query("SHOW INDEX FROM business_items WHERE Key_name = 'idx_featured'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE business_items ADD INDEX idx_featured (is_featured)");
        echo "<p class='success'>✓ เพิ่ม index idx_featured สำเร็จ</p>";
    } else {
        echo "<p class='info'>✓ Index idx_featured มีอยู่แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ " . $e->getMessage() . "</p>";
}

echo "</div>";

// Show current structure
echo "<div class='card'>";
echo "<h2>📋 โครงสร้างปัจจุบัน</h2>";
$stmt = $db->query("DESCRIBE business_items");
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#f8fafc;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $highlight = in_array($row['Field'], ['is_featured', 'is_promotion', 'promotion_start', 'promotion_end', 'featured_order']) 
                 ? 'background:#fef3c7;' : '';
    echo "<tr style='$highlight'>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='card'>";
echo "<p class='success'><strong>✓ Migration เสร็จสมบูรณ์!</strong></p>";
echo "<p>ตอนนี้สามารถใช้งานหน้า <a href='shop/promotions.php'>จัดการสินค้าเด่น</a> ได้แล้ว</p>";
echo "</div>";

echo "</body></html>";
