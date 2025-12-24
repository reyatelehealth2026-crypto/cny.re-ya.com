<?php
/**
 * Run Medical Info Migration
 * เพิ่ม columns สำหรับข้อมูลทางการแพทย์
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<pre>";
echo "=== Medical Info Migration ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Add columns to users table
    $columns = [
        'medical_conditions' => "TEXT DEFAULT NULL COMMENT 'โรคประจำตัว'",
        'drug_allergies' => "TEXT DEFAULT NULL COMMENT 'แพ้ยา'",
        'current_medications' => "TEXT DEFAULT NULL COMMENT 'ยาที่ใช้อยู่'"
    ];
    
    foreach ($columns as $col => $def) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '{$col}'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
            echo "✓ Added column: users.{$col}\n";
        } else {
            echo "- Column users.{$col} already exists\n";
        }
    }
    
    // Create dispensing_records table
    $db->exec("CREATE TABLE IF NOT EXISTS dispensing_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        line_account_id INT DEFAULT NULL,
        user_id INT NOT NULL,
        pharmacist_id INT DEFAULT NULL COMMENT 'ID ของเภสัชกรที่จ่ายยา',
        order_number VARCHAR(50) DEFAULT NULL,
        items JSON NOT NULL COMMENT 'รายการยาที่จ่าย',
        total_amount DECIMAL(10,2) DEFAULT 0,
        payment_method VARCHAR(50) DEFAULT 'cash',
        payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'paid',
        notes TEXT DEFAULT NULL COMMENT 'หมายเหตุการจ่ายยา',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_pharmacist (pharmacist_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created table: dispensing_records\n";
    
    echo "\n✓ Migration completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo '<p><a href="chat.php">Go to Chat</a></p>';
