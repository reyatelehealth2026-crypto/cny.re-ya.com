<?php
/**
 * Complete System Installation Script
 * ติดตั้งระบบทั้งหมดในครั้งเดียว
 * 
 * วิธีใช้: เข้า https://yourdomain.com/v1/install/install_all.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// Security check - ลบไฟล์นี้หลังติดตั้งเสร็จ!
$installKey = $_GET['key'] ?? '';
$requiredKey = 'INSTALL_' . date('Ymd'); // Key เปลี่ยนทุกวัน

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>System Installation</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; } .info { color: #3B82F6; }
h1 { color: #1E293B; } h2 { color: #475569; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; }
pre { background: #1E293B; color: #10B981; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; }
.btn { display: inline-block; padding: 12px 24px; background: #10B981; color: white; text-decoration: none; border-radius: 8px; margin: 5px; }
.btn:hover { background: #059669; }
.btn-warning { background: #F59E0B; } .btn-warning:hover { background: #D97706; }
table { width: 100%; border-collapse: collapse; } th, td { padding: 10px; text-align: left; border-bottom: 1px solid #E2E8F0; }
th { background: #F8FAFC; }
.check { color: #10B981; } .cross { color: #EF4444; }
</style></head><body>";

echo "<h1>🚀 LINE CRM - Complete Installation</h1>";

// Check config
if (!file_exists(__DIR__ . '/../config/config.php')) {
    echo "<div class='card'><h2 class='error'>❌ Config Not Found</h2>";
    echo "<p>กรุณาสร้างไฟล์ <code>config/config.php</code> ก่อน</p>";
    echo "<pre>
&lt;?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Site
define('BASE_URL', 'https://yourdomain.com/v1');
define('SITE_NAME', 'LINE CRM');
</pre></div></body></html>";
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();
$results = [];
$errors = [];

// ============ MIGRATION FILES ============
$migrations = [
    'Core Schema' => [
        'database/install_complete.sql',
    ],
    'POS System' => [
        'database/migration_pos.sql',
        'database/migration_pos_features.sql',
    ],
    'Accounting' => [
        'database/migration_accounting.sql',
    ],
    'Inventory & WMS' => [
        'database/migration_wms.sql',
        'database/migration_put_away_location.sql',
        'database/migration_zone_types.sql',
        'database/migration_add_frozen_zone.sql',
        'database/migration_add_storage_condition.sql',
        'database/migration_stock_movement_value.sql',
        'database/migration_fix_movement_type.sql',
        'database/migration_gr_batch_fields.sql',
    ],
    'Products & Shop' => [
        'database/migration_business_items_html_fields.sql',
        'database/migration_product_cny_fields.sql',
        'database/migration_cny_products.sql',
    ],
    'Inbox & Messaging' => [
        'database/migration_inbox_chat.sql',
        'database/migration_inbox_chat_indexes.sql',
        'database/migration_inbox_v2_performance.sql',
        'database/migration_chat_status.sql',
        'database/migration_multi_assignee.sql',
        'database/migration_mark_as_read_token.sql',
        'database/migration_add_quick_reply_column.sql',
        'database/migration_auto_reply_rules.sql',
        'database/migration_broadcasts_target_type.sql',
    ],
    'Vibe Selling & AI' => [
        'database/migration_vibe_selling_v2.sql',
        'database/migration_ai_sales_mode.sql',
    ],
    'Landing Page & SEO' => [
        'database/migration_landing_page.sql',
        'database/migration_landing_banners.sql',
        'database/migration_health_articles.sql',
    ],
    'Membership & Loyalty' => [
        'database/migration_loyalty_points.sql',
    ],
    'Pharmacy' => [
        'database/migration_pharmacist_notifications.sql',
    ],
    'System Features' => [
        'database/migration_performance_feature_flags.sql',
    ],
];

// ============ RUN INSTALLATION ============
if (isset($_POST['install'])) {
    echo "<div class='card'><h2>📦 Running Installation...</h2><pre>";
    
    foreach ($migrations as $category => $files) {
        echo "\n<span class='info'>== $category ==</span>\n";
        foreach ($files as $file) {
            $path = __DIR__ . '/../' . $file;
            if (file_exists($path)) {
                $sql = file_get_contents($path);
                // Split by delimiter or semicolon
                $statements = preg_split('/;\s*$/m', $sql);
                $success = 0;
                $failed = 0;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
                    try {
                        $db->exec($stmt);
                        $success++;
                    } catch (PDOException $e) {
                        // Ignore duplicate errors
                        if (strpos($e->getMessage(), 'Duplicate') === false && 
                            strpos($e->getMessage(), 'already exists') === false) {
                            $failed++;
                        }
                    }
                }
                $status = $failed > 0 ? "<span class='warning'>⚠️</span>" : "<span class='success'>✓</span>";
                echo "$status $file ($success OK" . ($failed > 0 ? ", $failed skip" : "") . ")\n";
            } else {
                echo "<span class='warning'>⚠️</span> $file (not found)\n";
            }
        }
    }
    
    echo "</pre></div>";
    
    // Create essential columns if missing (MySQL compatible)
    echo "<div class='card'><h2>🔧 Verifying Essential Columns...</h2><pre>";
    
    $essentialColumns = [
        'users' => [
            'reply_token' => "VARCHAR(255) DEFAULT NULL",
            'reply_token_expires' => "DATETIME DEFAULT NULL",
            'line_account_id' => "INT DEFAULT NULL",
            'is_registered' => "TINYINT(1) DEFAULT 0",
            'membership_level' => "VARCHAR(20) DEFAULT 'bronze'",
            'loyalty_points' => "INT DEFAULT 0",
        ],
        'messages' => [
            'is_read' => "TINYINT(1) DEFAULT 0",
            'sent_by' => "VARCHAR(100) DEFAULT NULL",
            'line_account_id' => "INT DEFAULT NULL",
        ],
    ];
    
    foreach ($essentialColumns as $table => $columns) {
        // Check if table exists
        $tableExists = $db->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        if (!$tableExists) {
            echo "<span class='warning'>⚠️</span> Table '$table' not found, skipping...\n";
            continue;
        }
        
        foreach ($columns as $column => $definition) {
            // Check if column exists
            $columnExists = $db->query("SHOW COLUMNS FROM $table LIKE '$column'")->rowCount() > 0;
            if (!$columnExists) {
                try {
                    $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
                    echo "<span class='success'>✓</span> Added $table.$column\n";
                } catch (PDOException $e) {
                    echo "<span class='error'>✗</span> Failed to add $table.$column: " . $e->getMessage() . "\n";
                }
            } else {
                echo "<span class='info'>ℹ️</span> $table.$column already exists\n";
            }
        }
    }
    
    echo "</pre></div>";
    
    // Create default admin
    echo "<div class='card'><h2>👤 Creating Default Admin...</h2><pre>";
    try {
        $checkAdmin = $db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($checkAdmin == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $db->exec("INSERT INTO admin_users (username, password, email, role, is_active) VALUES ('admin', '$password', 'admin@example.com', 'super_admin', 1)");
            echo "<span class='success'>✓</span> Created admin user\n";
            echo "<span class='info'>Username: admin</span>\n";
            echo "<span class='info'>Password: admin123</span>\n";
            echo "<span class='warning'>⚠️ กรุณาเปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ!</span>\n";
        } else {
            echo "<span class='info'>ℹ️</span> Admin user already exists\n";
        }
    } catch (PDOException $e) {
        echo "<span class='error'>✗</span> " . $e->getMessage() . "\n";
    }
    echo "</pre></div>";
    
    // System Verification
    echo "<div class='card'><h2>🔍 System Verification</h2><pre>";
    
    $verifications = [
        'Core Tables' => ['users', 'messages', 'admin_users', 'line_accounts'],
        'POS' => ['pos_sales', 'pos_shifts', 'pos_payments'],
        'Accounting' => ['account_payables', 'account_receivables', 'expenses'],
        'Inventory' => ['products', 'stock_movements', 'batches', 'locations'],
        'WMS' => ['wms_orders', 'wms_picks', 'wms_shipments'],
        'Inbox' => ['chat_templates', 'customer_notes'],
        'Vibe Selling' => ['drug_recommendations', 'ghost_drafts', 'health_profiles'],
        'Landing' => ['landing_faqs', 'landing_testimonials', 'health_articles'],
    ];
    
    foreach ($verifications as $category => $tables) {
        $found = 0;
        foreach ($tables as $table) {
            if ($db->query("SHOW TABLES LIKE '$table'")->rowCount() > 0) {
                $found++;
            }
        }
        $total = count($tables);
        $status = $found == $total ? "<span class='success'>✓</span>" : 
                 ($found > 0 ? "<span class='warning'>⚠️</span>" : "<span class='error'>✗</span>");
        echo "$status $category: $found/$total tables\n";
    }
    
    echo "</pre></div>";
    
    echo "<div class='card' style='background: #D1FAE5;'>";
    echo "<h2 class='success'>✅ Installation Complete!</h2>";
    echo "<p>ระบบติดตั้งเสร็จสมบูรณ์แล้ว</p>";
    echo "<a href='check_system.php' class='btn'>🔍 ตรวจสอบระบบ</a>";
    echo "<a href='../admin/' class='btn btn-warning'>🏠 ไปหน้าหลัก</a>";
    echo "<p class='warning' style='margin-top: 15px;'>⚠️ อย่าลืมลบไฟล์ install_all.php เพื่อความปลอดภัย!</p>";
    echo "</div>";
    
} else {
    // Show installation form
    echo "<div class='card'>";
    echo "<h2>📋 Migration Files to Install</h2>";
    echo "<table><tr><th>Category</th><th>Files</th><th>Status</th></tr>";
    
    foreach ($migrations as $category => $files) {
        $found = 0;
        $total = count($files);
        foreach ($files as $file) {
            if (file_exists(__DIR__ . '/../' . $file)) $found++;
        }
        $status = $found == $total ? "<span class='check'>✓ $found/$total</span>" : "<span class='warning'>⚠️ $found/$total</span>";
        echo "<tr><td><strong>$category</strong></td><td>" . implode('<br>', array_map(function($f) { return basename($f); }, $files)) . "</td><td>$status</td></tr>";
    }
    
    echo "</table></div>";
    
    echo "<div class='card'>";
    echo "<h2>🚀 Start Installation</h2>";
    echo "<p>คลิกปุ่มด้านล่างเพื่อเริ่มติดตั้งระบบ</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='install' class='btn' style='font-size: 18px; padding: 15px 40px;'>🚀 Install Now</button>";
    echo "</form>";
    echo "<p class='warning' style='margin-top: 15px;'>⚠️ หลังติดตั้งเสร็จ กรุณาลบไฟล์นี้ออกเพื่อความปลอดภัย!</p>";
    echo "</div>";
}

echo "</body></html>";
