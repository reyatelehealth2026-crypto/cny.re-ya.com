<?php
/**
 * Fix Member Card Issues
 * 1. Create consent tables
 * 2. Add required columns
 * 3. Auto-consent for registered users
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix Member Card Issues</h2>";

// 1. Create user_consents table
echo "<h3>1. Create Consent Tables</h3>";
try {
    // Check if user_consents table exists
    $stmt = $db->query("SHOW TABLES LIKE 'user_consents'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating user_consents table...</p>";
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
                UNIQUE KEY unique_user_consent (user_id, consent_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color:green'>✅ user_consents table created!</p>";
    } else {
        echo "<p style='color:green'>✅ user_consents table exists</p>";
    }
    
    // Check if consent_logs table exists
    $stmt = $db->query("SHOW TABLES LIKE 'consent_logs'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating consent_logs table...</p>";
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
        echo "<p style='color:green'>✅ consent_logs table created!</p>";
    } else {
        echo "<p style='color:green'>✅ consent_logs table exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Add consent columns to users table
echo "<h3>2. Add Consent Columns to Users</h3>";
try {
    $columns = ['consent_privacy', 'consent_terms', 'consent_health_data', 'consent_date'];
    foreach ($columns as $col) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            if ($col == 'consent_date') {
                $db->exec("ALTER TABLE users ADD COLUMN $col DATETIME NULL");
            } else {
                $db->exec("ALTER TABLE users ADD COLUMN $col TINYINT(1) DEFAULT 0");
            }
            echo "<p style='color:green'>✅ Added $col column</p>";
        }
    }
    echo "<p style='color:green'>✅ All consent columns exist</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. Check user consents
echo "<h3>3. Check User Consents</h3>";
$lineUserId = $_GET['line_user_id'] ?? '';

if ($lineUserId) {
    try {
        // Get user
        $stmt = $db->prepare("SELECT id, display_name, is_registered FROM users WHERE line_user_id = ?");
        $stmt->execute([$lineUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>User: {$user['display_name']} (ID: {$user['id']}, Registered: " . ($user['is_registered'] ? 'Yes' : 'No') . ")</p>";
            
            // Get consents
            $stmt = $db->prepare("
                SELECT consent_type, is_accepted, accepted_at
                FROM user_consents 
                WHERE user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $consents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $consentMap = [];
            foreach ($consents as $c) {
                $consentMap[$c['consent_type']] = $c;
            }
            
            $requiredTypes = ['privacy_policy', 'terms_of_service'];
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Type</th><th>Required</th><th>Accepted</th><th>Date</th></tr>";
            $allOk = true;
            foreach ($requiredTypes as $type) {
                $c = $consentMap[$type] ?? null;
                $accepted = $c && $c['is_accepted'] ? '✅ Yes' : '❌ No';
                $date = $c['accepted_at'] ?? '-';
                if (!$c || !$c['is_accepted']) $allOk = false;
                echo "<tr><td>$type</td><td>✅ Yes</td><td>$accepted</td><td>$date</td></tr>";
            }
            echo "</table>";
            
            if (!$allOk) {
                echo "<p style='color:orange'>⚠️ Missing required consents!</p>";
                
                if (isset($_GET['auto_consent'])) {
                    echo "<p>Auto-consenting...</p>";
                    
                    foreach ($requiredTypes as $type) {
                        $stmt = $db->prepare("
                            INSERT INTO user_consents (user_id, consent_type, consent_version, is_accepted, accepted_at, ip_address)
                            VALUES (?, ?, '1.0', 1, NOW(), '127.0.0.1')
                            ON DUPLICATE KEY UPDATE is_accepted = 1, accepted_at = NOW()
                        ");
                        $stmt->execute([$user['id'], $type]);
                    }
                    
                    // Update user flags
                    $stmt = $db->prepare("UPDATE users SET consent_privacy = 1, consent_terms = 1, consent_date = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    echo "<p style='color:green'>✅ Auto-consented! Refresh to verify.</p>";
                } else {
                    echo "<p><a href='?line_user_id=$lineUserId&auto_consent=1' style='padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px;'>Click to Auto-Consent</a></p>";
                }
            } else {
                echo "<p style='color:green'>✅ All required consents OK!</p>";
            }
        } else {
            echo "<p style='color:red'>User not found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    // Show user list
    echo "<p>Select a user to check/fix consents:</p>";
    $stmt = $db->query("SELECT id, line_user_id, display_name, is_registered FROM users ORDER BY id DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Registered</th><th>Action</th></tr>";
    foreach ($users as $u) {
        $reg = $u['is_registered'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['display_name']}</td>";
        echo "<td>$reg</td>";
        echo "<td><a href='?line_user_id={$u['line_user_id']}'>Check Consent</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Bulk auto-consent for all registered users
    echo "<h4>Bulk Auto-Consent</h4>";
    if (isset($_GET['bulk_consent'])) {
        $stmt = $db->query("SELECT id FROM users WHERE is_registered = 1");
        $regUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($regUsers as $u) {
            foreach (['privacy_policy', 'terms_of_service'] as $type) {
                $stmt = $db->prepare("
                    INSERT INTO user_consents (user_id, consent_type, consent_version, is_accepted, accepted_at, ip_address)
                    VALUES (?, ?, '1.0', 1, NOW(), '127.0.0.1')
                    ON DUPLICATE KEY UPDATE is_accepted = 1, accepted_at = NOW()
                ");
                $stmt->execute([$u['id'], $type]);
            }
            $stmt = $db->prepare("UPDATE users SET consent_privacy = 1, consent_terms = 1, consent_date = NOW() WHERE id = ?");
            $stmt->execute([$u['id']]);
            $count++;
        }
        echo "<p style='color:green'>✅ Auto-consented $count registered users!</p>";
    } else {
        echo "<p><a href='?bulk_consent=1' style='padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Auto-Consent ALL Registered Users</a></p>";
    }
}

echo "<hr><p style='color:green; font-weight:bold;'>Done!</p>";
