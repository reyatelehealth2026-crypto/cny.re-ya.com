<?php
/**
 * Fix Users Table - เพิ่ม columns สำหรับระบบสมาชิก
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix Users Columns</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:900px;margin:0 auto}";
echo ".success{color:green;background:#D1FAE5;padding:10px;border-radius:5px;margin:5px 0}";
echo ".error{color:red;background:#FEE2E2;padding:10px;border-radius:5px;margin:5px 0}";
echo ".info{color:#1E40AF;background:#DBEAFE;padding:10px;border-radius:5px;margin:5px 0}";
echo "table{width:100%;border-collapse:collapse;margin:10px 0}th,td{padding:8px;border:1px solid #ddd;text-align:left}";
echo "th{background:#f5f5f5}</style></head><body>";

echo "<h1>🔧 Fix Users Table - Member Columns</h1>";

// Columns ที่ต้องมีสำหรับระบบสมาชิก
$requiredColumns = [
    'first_name' => "VARCHAR(100) DEFAULT NULL COMMENT 'ชื่อ'",
    'last_name' => "VARCHAR(100) DEFAULT NULL COMMENT 'นามสกุล'",
    'birthday' => "DATE DEFAULT NULL COMMENT 'วันเกิด'",
    'gender' => "ENUM('male','female','other') DEFAULT NULL COMMENT 'เพศ'",
    'weight' => "DECIMAL(5,2) DEFAULT NULL COMMENT 'น้ำหนัก (กก.)'",
    'height' => "DECIMAL(5,2) DEFAULT NULL COMMENT 'ส่วนสูง (ซม.)'",
    'medical_conditions' => "TEXT DEFAULT NULL COMMENT 'โรคประจำตัว'",
    'drug_allergies' => "TEXT DEFAULT NULL COMMENT 'ยาที่แพ้'",
    'address' => "TEXT DEFAULT NULL COMMENT 'ที่อยู่'",
    'district' => "VARCHAR(100) DEFAULT NULL COMMENT 'เขต/อำเภอ'",
    'province' => "VARCHAR(100) DEFAULT NULL COMMENT 'จังหวัด'",
    'postal_code' => "VARCHAR(10) DEFAULT NULL COMMENT 'รหัสไปรษณีย์'",
    'member_id' => "VARCHAR(20) DEFAULT NULL COMMENT 'รหัสสมาชิก'",
    'loyalty_points' => "INT DEFAULT 0 COMMENT 'แต้มสะสม'",
    'tier_id' => "INT DEFAULT NULL COMMENT 'ระดับสมาชิก'",
    'is_registered' => "TINYINT(1) DEFAULT 0 COMMENT 'สมัครสมาชิกแล้ว'",
    'registered_at' => "DATETIME DEFAULT NULL COMMENT 'วันที่สมัคร'"
];

echo "<h2>1. ตรวจสอบ Columns ปัจจุบัน</h2>";

// Get existing columns
$existingColumns = [];
$stmt = $db->query("SHOW COLUMNS FROM users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingColumns[$row['Field']] = $row;
}

echo "<table><tr><th>Column</th><th>Status</th><th>Type</th></tr>";

$columnsToAdd = [];
foreach ($requiredColumns as $col => $definition) {
    if (isset($existingColumns[$col])) {
        echo "<tr><td>{$col}</td><td>✅ มีแล้ว</td><td>{$existingColumns[$col]['Type']}</td></tr>";
    } else {
        echo "<tr style='background:#FEF3C7'><td>{$col}</td><td>❌ ไม่มี</td><td>-</td></tr>";
        $columnsToAdd[$col] = $definition;
    }
}
echo "</table>";

if (empty($columnsToAdd)) {
    echo "<div class='success'>✅ ตาราง users มี columns ครบแล้ว!</div>";
} else {
    echo "<h2>2. เพิ่ม Columns ที่ขาด</h2>";
    
    foreach ($columnsToAdd as $col => $definition) {
        try {
            $sql = "ALTER TABLE users ADD COLUMN `{$col}` {$definition}";
            $db->exec($sql);
            echo "<div class='success'>✅ เพิ่ม column `{$col}` สำเร็จ</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error adding `{$col}`: " . $e->getMessage() . "</div>";
        }
    }
    
    // Add index for member_id if added
    if (isset($columnsToAdd['member_id'])) {
        try {
            $db->exec("ALTER TABLE users ADD INDEX idx_member_id (member_id)");
            echo "<div class='success'>✅ เพิ่ม index สำหรับ member_id</div>";
        } catch (Exception $e) {
            // Index might already exist
        }
    }
}

echo "<h2>3. ตรวจสอบผลลัพธ์</h2>";

// Verify
$stmt = $db->query("SHOW COLUMNS FROM users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
foreach ($cols as $col) {
    $highlight = in_array($col['Field'], array_keys($requiredColumns)) ? "style='background:#D1FAE5'" : "";
    echo "<tr {$highlight}><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

echo "<h2>4. ทดสอบ Query</h2>";
try {
    $stmt = $db->query("SELECT id, display_name, first_name, last_name, member_id, is_registered FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>Display Name</th><th>First Name</th><th>Last Name</th><th>Member ID</th><th>Registered</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>" . htmlspecialchars($u['display_name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($u['first_name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($u['last_name'] ?? '-') . "</td>";
        echo "<td>{$u['member_id']}</td>";
        echo "<td>" . ($u['is_registered'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<div class='success'>✅ Query ทำงานได้ปกติ!</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='liff-register.php?account=1' style='padding:10px 20px;background:#11B0A6;color:white;text-decoration:none;border-radius:5px'>🔗 ทดสอบหน้าสมัครสมาชิก</a></p>";
echo "<p><a href='liff-app.php?account=1'>🏠 หน้าหลัก LIFF</a></p>";
echo "</body></html>";
