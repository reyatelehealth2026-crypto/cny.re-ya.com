<?php
/**
 * Auto Run All Migrations
 * ตรวจสอบตารางที่ยังไม่มีและรัน migration อัตโนมัติ
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Auto Migration</title>";
echo "<style>
body { font-family: sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
.btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 5px; }
.btn-primary { background: #06C755; color: white; }
.btn-danger { background: #dc3545; color: white; }
pre { background: #f5f5f5; padding: 10px; overflow: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🔧 Auto Migration System</h1>";

// ตารางทั้งหมดที่ระบบต้องการ
$requiredTables = [
    // Core tables
    'users' => 'database/install.sql',
    'messages' => 'database/install.sql',
    'analytics' => 'database/install.sql',
    'ai_settings' => 'database/install.sql',
    'templates' => 'database/install.sql',
    
    // Line accounts
    'line_accounts' => 'database/migration_add_line_account_id.sql',
    
    // Shop tables
    'products' => 'database/migration_shop_complete.sql',
    'product_categories' => 'database/migration_shop_complete.sql',
    'orders' => 'database/migration_shop_complete.sql',
    'order_items' => 'database/migration_shop_complete.sql',
    'cart_items' => 'database/migration_shop_complete.sql',
    'shop_settings' => 'database/migration_shop_complete.sql',
    
    // Business items (alternative shop)
    'business_items' => 'database/migration_v2.5_business_items.sql',
    'business_settings' => 'database/migration_v2.5_business_items.sql',
    'item_categories' => 'database/migration_v2.5_business_items.sql',
    
    // User tags
    'user_tags' => 'database/migration_advanced_crm.sql',
    'user_tag_assignments' => 'database/migration_advanced_crm.sql',
    
    // Auto tags
    'auto_tag_rules' => 'database/migration_auto_tags.sql',
    'auto_tag_logs' => 'database/migration_auto_tags.sql',
    
    // Broadcast
    'broadcast_campaigns' => 'database/migration_broadcast_tracking.sql',
    'broadcast_items' => 'database/migration_broadcast_tracking.sql',
    'broadcast_clicks' => 'database/migration_broadcast_tracking.sql',
    
    // CRM
    'customer_segments' => 'database/migration_advanced_crm.sql',
    'tracked_links' => 'database/migration_advanced_crm.sql',
    'link_clicks' => 'database/migration_advanced_crm.sql',
    
    // Drip campaigns
    'drip_campaigns' => 'database/migration_advanced_crm.sql',
    'drip_campaign_steps' => 'database/migration_advanced_crm.sql',
    'drip_campaign_progress' => 'database/migration_advanced_crm.sql',
    
    // Auto reply
    'auto_replies' => 'database/migration_auto_reply_upgrade.sql',
    
    // Payment
    'payment_slips' => 'database/migration_shop_complete.sql',
    
    // Welcome settings
    'welcome_settings' => 'database/migration_welcome_settings.sql',
    
    // Line groups
    'line_groups' => 'database/migration_line_groups.sql',
    'line_group_members' => 'database/migration_line_groups.sql',
    'line_group_messages' => 'database/migration_line_groups.sql',
    
    // Account events
    'account_events' => 'database/migration_account_events.sql',
    'account_followers' => 'database/migration_account_events.sql',
    'account_daily_stats' => 'database/migration_account_events.sql',
    
    // Dev logs
    'dev_logs' => 'database/migration_dev_logs.sql',
    
    // Flex share
    'shared_flex_messages' => 'database/migration_share_flex.sql',
    
    // Admin users
    'admin_users' => 'database/migration_admin_users.sql',
    
    // User details
    'user_notes' => 'database/migration_user_details.sql',
    'user_custom_fields' => 'database/migration_user_details.sql',
];

// ตรวจสอบตารางที่มีอยู่
function tableExists($db, $table) {
    try {
        $stmt = $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ดึงรายการตารางทั้งหมด
function getAllTables($db) {
    $stmt = $db->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// รัน SQL file - สร้าง connection ใหม่เพื่อหลีกเลี่ยง unbuffered query error
function runSqlFile($db, $file) {
    if (!file_exists($file)) {
        return ['success' => false, 'message' => "ไม่พบไฟล์: {$file}"];
    }
    
    // สร้าง connection ใหม่สำหรับแต่ละไฟล์
    try {
        $newDb = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_EMULATE_PREPARES => true
            ]
        );
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Connection error: " . $e->getMessage()];
    }
    
    $sql = file_get_contents($file);
    
    // ลบ comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // แยก statements ด้วย ; แต่ระวัง ; ใน string
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        
        if (!$inString && ($char === '"' || $char === "'")) {
            $inString = true;
            $stringChar = $char;
        } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i-1] !== '\\')) {
            $inString = false;
        }
        
        if ($char === ';' && !$inString) {
            $stmt = trim($current);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    // เพิ่ม statement สุดท้าย
    $stmt = trim($current);
    if (!empty($stmt)) {
        $statements[] = $stmt;
    }
    
    $success = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $newDb->exec($statement);
            $success++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'already exists') !== false || 
                strpos($msg, 'Duplicate column') !== false ||
                strpos($msg, 'Duplicate key') !== false ||
                strpos($msg, 'Can\'t DROP') !== false ||
                strpos($msg, 'check that column/key exists') !== false) {
                $skipped++;
            } else {
                $errors[] = substr($msg, 0, 200);
            }
        }
    }
    
    // ปิด connection
    $newDb = null;
    
    return [
        'success' => true,
        'executed' => $success,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

// ตรวจสอบสถานะ
$existingTables = getAllTables($db);
$missingTables = [];
$migrationFiles = [];

foreach ($requiredTables as $table => $file) {
    if (!in_array($table, $existingTables)) {
        $missingTables[$table] = $file;
        if (!in_array($file, $migrationFiles) && file_exists($file)) {
            $migrationFiles[] = $file;
        }
    }
}

// Handle POST - Run migrations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_all'])) {
    echo "<h2>🚀 Running Migrations...</h2>";
    
    $filesToRun = $_POST['files'] ?? [];
    
    if (empty($filesToRun)) {
        echo "<p class='warning'>⚠️ ไม่มีไฟล์ที่เลือก</p>";
    } else {
        foreach ($filesToRun as $file) {
            echo "<h3>📄 {$file}</h3>";
            $result = runSqlFile($db, $file);
            
            if ($result['success']) {
                echo "<p class='success'>✅ Executed: {$result['executed']} statements</p>";
                if ($result['skipped'] > 0) {
                    echo "<p class='warning'>⚠️ Skipped: {$result['skipped']} (already exists)</p>";
                }
                if (!empty($result['errors'])) {
                    echo "<p class='error'>❌ Errors:</p><ul>";
                    foreach ($result['errors'] as $err) {
                        echo "<li>" . htmlspecialchars($err) . "</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<p class='error'>❌ {$result['message']}</p>";
            }
        }
    }
    
    echo "<hr><p><a href='run_all_migrations.php'>🔄 ตรวจสอบอีกครั้ง</a></p>";
    echo "</body></html>";
    exit;
}

// แสดงสถานะตาราง
echo "<h2>📊 สถานะตาราง</h2>";
echo "<table>";
echo "<tr><th>ตาราง</th><th>สถานะ</th><th>Migration File</th></tr>";

$tableStatus = [];
foreach ($requiredTables as $table => $file) {
    $exists = in_array($table, $existingTables);
    $tableStatus[$table] = $exists;
    
    $statusIcon = $exists ? "<span class='success'>✅ มีแล้ว</span>" : "<span class='error'>❌ ยังไม่มี</span>";
    $fileExists = file_exists($file) ? $file : "<span class='warning'>{$file} (ไม่พบไฟล์)</span>";
    
    echo "<tr><td>{$table}</td><td>{$statusIcon}</td><td>{$fileExists}</td></tr>";
}
echo "</table>";

// สรุป
$totalTables = count($requiredTables);
$existingCount = count(array_filter($tableStatus));
$missingCount = $totalTables - $existingCount;

echo "<h2>📈 สรุป</h2>";
echo "<p>ตารางทั้งหมด: <strong>{$totalTables}</strong></p>";
echo "<p class='success'>มีแล้ว: <strong>{$existingCount}</strong></p>";
echo "<p class='error'>ยังไม่มี: <strong>{$missingCount}</strong></p>";

// แสดงปุ่มรัน migration
if ($missingCount > 0 && !empty($migrationFiles)) {
    echo "<h2>🚀 รัน Migration</h2>";
    echo "<form method='POST'>";
    echo "<p>เลือกไฟล์ที่ต้องการรัน:</p>";
    
    foreach ($migrationFiles as $file) {
        $checked = 'checked';
        echo "<label style='display:block;margin:5px 0;'>";
        echo "<input type='checkbox' name='files[]' value='{$file}' {$checked}> {$file}";
        echo "</label>";
    }
    
    echo "<br><button type='submit' name='run_all' value='1' class='btn btn-primary'>▶️ รัน Migration ที่เลือก</button>";
    echo "</form>";
} elseif ($missingCount > 0) {
    echo "<p class='warning'>⚠️ มีตารางที่ขาดหายแต่ไม่พบไฟล์ migration</p>";
} else {
    echo "<p class='success'>✅ ตารางครบถ้วนแล้ว!</p>";
}

// แสดงตารางที่มีอยู่ในฐานข้อมูลทั้งหมด
echo "<h2>📋 ตารางทั้งหมดในฐานข้อมูล</h2>";
echo "<p>จำนวน: " . count($existingTables) . " ตาราง</p>";
echo "<div style='column-count:3;'>";
foreach ($existingTables as $t) {
    $inRequired = isset($requiredTables[$t]);
    $style = $inRequired ? '' : 'color:#888;';
    echo "<div style='{$style}'>{$t}</div>";
}
echo "</div>";

echo "<hr><p><small>⚠️ ลบไฟล์นี้หลังใช้งานเสร็จเพื่อความปลอดภัย</small></p>";
echo "</body></html>";
