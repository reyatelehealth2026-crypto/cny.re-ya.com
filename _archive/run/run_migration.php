<?php
/**
 * Run SQL Migration Files
 * เปิดไฟล์นี้ผ่าน browser เพื่อรัน migration
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// รายการ migration files ที่ต้องการรัน
$migrations = [
    'database/migration_broadcast_tracking.sql' => 'Broadcast Tracking & Auto Tag',
    // เพิ่มไฟล์อื่นๆ ตามต้องการ
    // 'database/migration_unified_shop.sql' => 'Unified Shop',
    // 'database/migration_advanced_crm.sql' => 'Advanced CRM',
];

echo "<h2>🔧 Run SQL Migrations</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;overflow:auto;}</style>";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    $file = $_POST['file'] ?? '';
    
    if (!file_exists($file)) {
        echo "<p class='error'>❌ ไม่พบไฟล์: {$file}</p>";
    } else {
        echo "<h3>Running: {$file}</h3>";
        
        $sql = file_get_contents($file);
        
        // แยก SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $success = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) continue;
            
            try {
                $db->exec($statement);
                $success++;
            } catch (PDOException $e) {
                // ข้าม error ที่ไม่สำคัญ เช่น table already exists
                $msg = $e->getMessage();
                if (strpos($msg, 'already exists') !== false || 
                    strpos($msg, 'Duplicate column') !== false ||
                    strpos($msg, 'Duplicate key') !== false) {
                    echo "<p style='color:orange'>⚠️ Skipped: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
                } else {
                    echo "<p class='error'>❌ Error: " . htmlspecialchars($msg) . "</p>";
                    echo "<pre>" . htmlspecialchars(substr($statement, 0, 200)) . "</pre>";
                    $errors++;
                }
            }
        }
        
        echo "<hr>";
        echo "<p class='success'>✅ สำเร็จ: {$success} statements</p>";
        if ($errors > 0) {
            echo "<p class='error'>❌ Errors: {$errors}</p>";
        }
        echo "<p><a href='run_migration.php'>← กลับ</a></p>";
        exit;
    }
}

// Show migration list
echo "<h3>เลือก Migration ที่ต้องการรัน:</h3>";

foreach ($migrations as $file => $name) {
    $exists = file_exists($file);
    echo "<form method='POST' style='margin:10px 0;'>";
    echo "<input type='hidden' name='file' value='{$file}'>";
    echo "<button type='submit' name='run' value='1' " . (!$exists ? 'disabled' : '') . " style='padding:10px 20px;cursor:pointer;'>";
    echo "▶️ {$name}";
    echo "</button>";
    echo " <small>({$file})</small>";
    if (!$exists) echo " <span class='error'>- ไม่พบไฟล์</span>";
    echo "</form>";
}

// Custom SQL
echo "<hr>";
echo "<h3>หรือรัน SQL โดยตรง:</h3>";
echo "<form method='POST'>";
echo "<textarea name='custom_sql' rows='10' style='width:100%;font-family:monospace;' placeholder='วาง SQL ที่นี่...'></textarea><br><br>";
echo "<button type='submit' name='run_custom' value='1' style='padding:10px 20px;'>▶️ Run Custom SQL</button>";
echo "</form>";

// Handle custom SQL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_custom'])) {
    $sql = $_POST['custom_sql'] ?? '';
    if (!empty($sql)) {
        echo "<h3>Running Custom SQL:</h3>";
        try {
            $db->exec($sql);
            echo "<p class='success'>✅ สำเร็จ!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
