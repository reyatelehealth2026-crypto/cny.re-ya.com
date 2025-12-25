<?php
/**
 * Run Pharmacist System Migration
 * รัน migration สำหรับระบบเภสัชกร
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>🏥 Pharmacist System Migration</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $sqlFile = __DIR__ . '/database/migration_pharmacist_system.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $db->exec($statement);
            echo "✅ " . substr($statement, 0, 60) . "...\n";
            $success++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⏭️ Skipped (already exists): " . substr($statement, 0, 40) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "   Statement: " . substr($statement, 0, 60) . "...\n";
                $failed++;
            }
        }
    }
    
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Success: $success statements\n";
    if ($failed > 0) {
        echo "❌ Failed: $failed statements\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Also run triage migration if not done
    $triageSqlFile = __DIR__ . '/database/migration_triage_system.sql';
    if (file_exists($triageSqlFile)) {
        echo "\n🔄 Running Triage System Migration...\n";
        
        $triageSql = file_get_contents($triageSqlFile);
        $triageStatements = array_filter(array_map('trim', explode(';', $triageSql)));
        
        foreach ($triageStatements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) continue;
            
            try {
                $db->exec($statement);
                echo "✅ " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ " . substr($e->getMessage(), 0, 60) . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Migration completed!\n";
    echo "\n📋 Next steps:\n";
    echo "1. ไปที่ pharmacist-dashboard.php เพื่อดู Dashboard\n";
    echo "2. ไปที่ triage-analytics.php เพื่อดูสถิติ\n";
    echo "3. ทดสอบระบบโดยส่งข้อความผ่าน LINE\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
