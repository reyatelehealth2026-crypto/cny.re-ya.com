<?php
/**
 * Run Loyalty Points Migration
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Loyalty Points Migration</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;}</style>";

try {
    // Create tables directly
    $queries = [
        "CREATE TABLE IF NOT EXISTS points_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT,
            points_per_baht DECIMAL(10,2) DEFAULT 1.00,
            min_order_for_points DECIMAL(10,2) DEFAULT 0,
            points_expiry_days INT DEFAULT 365,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_account (line_account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS points_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            line_account_id INT,
            type ENUM('earn', 'redeem', 'expire', 'adjust', 'refund') NOT NULL,
            points INT NOT NULL,
            balance_after INT NOT NULL,
            reference_type VARCHAR(50),
            reference_id INT,
            description VARCHAR(255),
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            points_required INT NOT NULL,
            reward_type ENUM('product', 'discount', 'coupon', 'gift') DEFAULT 'gift',
            reward_value VARCHAR(255),
            stock INT DEFAULT -1,
            max_per_user INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            start_date DATE NULL,
            end_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS reward_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reward_id INT NOT NULL,
            line_account_id INT,
            points_used INT NOT NULL,
            status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
            redemption_code VARCHAR(50) UNIQUE,
            notes TEXT,
            approved_by INT NULL,
            approved_at TIMESTAMP NULL,
            delivered_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS points_tiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            line_account_id INT,
            name VARCHAR(100) NOT NULL,
            min_points INT NOT NULL,
            points_multiplier DECIMAL(3,2) DEFAULT 1.00,
            color VARCHAR(20) DEFAULT '#666666',
            icon VARCHAR(50) DEFAULT 'fa-star',
            benefits TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($queries as $sql) {
        try {
            $db->exec($sql);
            $tableName = preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $sql, $m) ? $m[1] : 'unknown';
            echo "<p class='ok'>✅ Created table: {$tableName}</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<p class='warn'>⚠️ Table already exists</p>";
            } else {
                echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Add columns to users table
    $userColumns = [
        "ALTER TABLE users ADD COLUMN total_points INT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN available_points INT DEFAULT 0", 
        "ALTER TABLE users ADD COLUMN used_points INT DEFAULT 0"
    ];
    
    foreach ($userColumns as $sql) {
        try {
            $db->exec($sql);
            echo "<p class='ok'>✅ Added column to users</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "<p class='warn'>⚠️ Column already exists</p>";
            } else {
                echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Insert default settings
    try {
        $db->exec("INSERT IGNORE INTO points_settings (line_account_id, points_per_baht) VALUES (NULL, 1.00)");
        echo "<p class='ok'>✅ Default settings inserted</p>";
    } catch (Exception $e) {}
    
    // Insert default tiers
    try {
        $db->exec("INSERT IGNORE INTO points_tiers (line_account_id, name, min_points, points_multiplier, color, icon) VALUES
            (NULL, 'Bronze', 0, 1.00, '#CD7F32', 'fa-medal'),
            (NULL, 'Silver', 1000, 1.25, '#C0C0C0', 'fa-medal'),
            (NULL, 'Gold', 5000, 1.50, '#FFD700', 'fa-crown'),
            (NULL, 'Platinum', 15000, 2.00, '#E5E4E2', 'fa-gem')");
        echo "<p class='ok'>✅ Default tiers inserted</p>";
    } catch (Exception $e) {}
    
    echo "<h2>✅ Migration Complete!</h2>";
    echo "<p><a href='liff-redeem-points.php'>Go to Loyalty (LIFF)</a> | <a href='debug_loyalty.php'>Debug</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
