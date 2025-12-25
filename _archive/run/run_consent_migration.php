<?php
/**
 * Run Consent Migration
 * สร้างตารางสำหรับระบบ Consent Management
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Consent Migration</title></head><body>";
echo "<h2>🔒 Consent Migration</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Create user_consents table
    echo "<h3>1. Creating user_consents table...</h3>";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_consents (
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
        echo "<p style='color:green'>✅ user_consents created</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p style='color:orange'>⚠️ user_consents already exists</p>";
        } else {
            echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 2. Create consent_logs table
    echo "<h3>2. Creating consent_logs table...</h3>";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS consent_logs (
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
        echo "<p style='color:green'>✅ consent_logs created</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p style='color:orange'>⚠️ consent_logs already exists</p>";
        } else {
            echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 3. Create data_access_logs table
    echo "<h3>3. Creating data_access_logs table...</h3>";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS data_access_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_user_id INT NULL,
                user_id INT NULL,
                action VARCHAR(100) NOT NULL,
                resource_type VARCHAR(50) NOT NULL,
                resource_id INT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                details JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_user (admin_user_id),
                INDEX idx_target_user (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color:green'>✅ data_access_logs created</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p style='color:orange'>⚠️ data_access_logs already exists</p>";
        } else {
            echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 4. Add columns to shop_settings
    echo "<h3>4. Adding columns to shop_settings...</h3>";
    $shopColumns = [
        'pharmacy_license' => "VARCHAR(100) NULL COMMENT 'เลขที่ใบอนุญาตร้านยา'",
        'pharmacist_name' => "VARCHAR(255) NULL COMMENT 'ชื่อเภสัชกรผู้มีหน้าที่ปฏิบัติการ'",
        'pharmacist_license' => "VARCHAR(100) NULL COMMENT 'เลขที่ใบอนุญาตเภสัชกร'",
        'shop_email' => "VARCHAR(255) NULL COMMENT 'อีเมลร้าน'",
        'privacy_policy_version' => "VARCHAR(20) DEFAULT '1.0'",
        'terms_version' => "VARCHAR(20) DEFAULT '1.0'"
    ];
    
    foreach ($shopColumns as $col => $def) {
        try {
            $db->exec("ALTER TABLE shop_settings ADD COLUMN {$col} {$def}");
            echo "<p style='color:green'>✅ Added shop_settings.{$col}</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color:gray'>⏭️ shop_settings.{$col} exists</p>";
            } else {
                echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    // 5. Add columns to users
    echo "<h3>5. Adding columns to users...</h3>";
    $userColumns = [
        'consent_privacy' => "TINYINT(1) DEFAULT 0",
        'consent_terms' => "TINYINT(1) DEFAULT 0",
        'consent_health_data' => "TINYINT(1) DEFAULT 0",
        'consent_date' => "DATETIME NULL"
    ];
    
    foreach ($userColumns as $col => $def) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
            echo "<p style='color:green'>✅ Added users.{$col}</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color:gray'>⏭️ users.{$col} exists</p>";
            } else {
                echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>📋 Verify Tables:</h3>";
    $tables = ['user_consents', 'consent_logs', 'data_access_logs'];
    $allOk = true;
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ {$table}</p>";
        } else {
            echo "<p style='color:red'>❌ {$table} - NOT FOUND</p>";
            $allOk = false;
        }
    }
    
    if ($allOk) {
        echo "<h2 style='color:green'>🎉 Migration Completed Successfully!</h2>";
        echo "<p><a href='consent-management.php'>→ Go to Consent Management</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
