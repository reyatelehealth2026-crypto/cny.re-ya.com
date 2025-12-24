<?php
/**
 * Fix Consent Columns
 * เพิ่ม columns ที่จำเป็นสำหรับ consent PDPA
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Fix Consent Columns</h2>";
echo "<pre>";

$results = [];

// 1. เพิ่ม columns ใน users table
$userColumns = [
    'consent_privacy' => "ALTER TABLE users ADD COLUMN consent_privacy TINYINT(1) DEFAULT 0",
    'consent_terms' => "ALTER TABLE users ADD COLUMN consent_terms TINYINT(1) DEFAULT 0",
    'consent_health_data' => "ALTER TABLE users ADD COLUMN consent_health_data TINYINT(1) DEFAULT 0",
    'consent_date' => "ALTER TABLE users ADD COLUMN consent_date DATETIME NULL"
];

echo "📋 Checking users table columns...\n";
foreach ($userColumns as $col => $sql) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec($sql);
            echo "✅ Added column: users.$col\n";
            $results[] = "Added users.$col";
        } else {
            echo "⏭️ Column exists: users.$col\n";
        }
    } catch (Exception $e) {
        echo "❌ Error adding users.$col: " . $e->getMessage() . "\n";
    }
}

// 2. เพิ่ม liff_consent_id ใน line_accounts
echo "\n📋 Checking line_accounts table...\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_consent_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN liff_consent_id VARCHAR(50) NULL COMMENT 'LIFF ID สำหรับหน้า consent'");
        echo "✅ Added column: line_accounts.liff_consent_id\n";
        $results[] = "Added line_accounts.liff_consent_id";
    } else {
        echo "⏭️ Column exists: line_accounts.liff_consent_id\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 3. เพิ่ม columns ใน shop_settings
$shopColumns = [
    'privacy_policy_version' => "ALTER TABLE shop_settings ADD COLUMN privacy_policy_version VARCHAR(20) DEFAULT '1.0'",
    'terms_version' => "ALTER TABLE shop_settings ADD COLUMN terms_version VARCHAR(20) DEFAULT '1.0'"
];

echo "\n📋 Checking shop_settings table...\n";
foreach ($shopColumns as $col => $sql) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec($sql);
            echo "✅ Added column: shop_settings.$col\n";
            $results[] = "Added shop_settings.$col";
        } else {
            echo "⏭️ Column exists: shop_settings.$col\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// 4. สร้างตาราง user_consents ถ้ายังไม่มี
echo "\n📋 Checking user_consents table...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'user_consents'");
    if ($stmt->rowCount() == 0) {
        $db->exec("
            CREATE TABLE user_consents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                consent_type ENUM('privacy_policy', 'terms_of_service', 'marketing', 'health_data') NOT NULL,
                consent_version VARCHAR(20) NOT NULL DEFAULT '1.0',
                is_accepted TINYINT(1) NOT NULL DEFAULT 0,
                accepted_at DATETIME NULL,
                withdrawn_at DATETIME NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_consent (user_id, consent_type),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Created table: user_consents\n";
        $results[] = "Created user_consents table";
    } else {
        echo "⏭️ Table exists: user_consents\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 5. สร้างตาราง consent_logs ถ้ายังไม่มี
echo "\n📋 Checking consent_logs table...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'consent_logs'");
    if ($stmt->rowCount() == 0) {
        $db->exec("
            CREATE TABLE consent_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                consent_type VARCHAR(50) NOT NULL,
                action ENUM('accept', 'withdraw', 'update') NOT NULL,
                consent_version VARCHAR(20) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                details JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_consent (user_id, consent_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Created table: consent_logs\n";
        $results[] = "Created consent_logs table";
    } else {
        echo "⏭️ Table exists: consent_logs\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 Summary:\n";
if (empty($results)) {
    echo "✅ All columns and tables already exist!\n";
} else {
    foreach ($results as $r) {
        echo "  - $r\n";
    }
}
echo "</pre>";
