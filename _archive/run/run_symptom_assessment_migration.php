<?php
/**
 * Run Symptom Assessment Migration
 * สร้างตารางสำหรับระบบประเมินอาการ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

use Modules\Core\Database;

echo "=== Symptom Assessment Migration ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // อ่านไฟล์ SQL
    $sqlFile = __DIR__ . '/database/migration_symptom_assessment.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("ไม่พบไฟล์ migration: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // แยก statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($s) { return !empty($s) && strpos($s, '--') !== 0; }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            
            // ดึงชื่อตาราง
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ สร้างตาราง: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ แก้ไขตาราง: {$matches[1]}\n";
            } else {
                echo "✅ รัน statement สำเร็จ\n";
            }
            $success++;
            
        } catch (PDOException $e) {
            // ถ้าตารางมีอยู่แล้ว ไม่ถือว่า error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️ ตารางมีอยู่แล้ว (ข้าม)\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n=== สรุป ===\n";
    echo "สำเร็จ: {$success} statements\n";
    echo "ผิดพลาด: {$errors} statements\n";
    
    // ตรวจสอบตาราง
    echo "\n=== ตรวจสอบตาราง ===\n";
    $tables = ['symptom_assessments', 'symptom_assessment_followups'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            echo "✅ {$table}: มีอยู่ ({$count} records)\n";
        } else {
            echo "❌ {$table}: ไม่พบ\n";
        }
    }
    
    echo "\n✅ Migration เสร็จสิ้น!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
