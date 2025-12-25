<?php
/**
 * Fix Missing Columns for Checkout
 * แก้ไข columns ที่หายไปในตาราง transactions และ points_history
 */
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔧 Fix Checkout Columns</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Fix transactions table
    echo "<h3>1. Fixing transactions table...</h3>";
    
    $transactionColumns = [
        'transaction_type' => "ALTER TABLE transactions ADD COLUMN `transaction_type` ENUM('purchase','booking','dispense') DEFAULT 'purchase' AFTER line_account_id",
        'order_number' => "ALTER TABLE transactions ADD COLUMN `order_number` VARCHAR(50) NULL AFTER transaction_type",
        'user_id' => "ALTER TABLE transactions ADD COLUMN `user_id` INT NULL AFTER order_number",
        'total_amount' => "ALTER TABLE transactions ADD COLUMN `total_amount` DECIMAL(10,2) DEFAULT 0 AFTER user_id",
        'shipping_fee' => "ALTER TABLE transactions ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0 AFTER total_amount",
        'grand_total' => "ALTER TABLE transactions ADD COLUMN `grand_total` DECIMAL(10,2) DEFAULT 0 AFTER shipping_fee",
        'delivery_info' => "ALTER TABLE transactions ADD COLUMN `delivery_info` TEXT NULL AFTER grand_total",
        'payment_method' => "ALTER TABLE transactions ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'transfer' AFTER delivery_info",
        'status' => "ALTER TABLE transactions ADD COLUMN `status` ENUM('pending','confirmed','processing','shipped','delivered','cancelled','completed') DEFAULT 'pending'",
        'payment_status' => "ALTER TABLE transactions ADD COLUMN `payment_status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER status",
        'notes' => "ALTER TABLE transactions ADD COLUMN `notes` TEXT NULL",
        'created_at' => "ALTER TABLE transactions ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE transactions ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($transactionColumns as $col => $sql) {
        try {
            $check = $db->query("SHOW COLUMNS FROM transactions LIKE '$col'");
            if ($check->rowCount() == 0) {
                $db->exec($sql);
                echo "<p>✅ Added: transactions.$col</p>";
            } else {
                echo "<p>⏭️ Exists: transactions.$col</p>";
            }
        } catch (Exception $e) {
            echo "<p>⚠️ $col: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Fix points_history table
    echo "<h3>2. Fixing points_history table...</h3>";
    
    // Check if table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'points_history'")->rowCount() > 0;
    
    if (!$tableExists) {
        $db->exec("
            CREATE TABLE points_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_account_id INT NULL,
                user_id INT NOT NULL,
                points INT NOT NULL DEFAULT 0,
                type ENUM('earn','redeem','bonus','adjust','expire') DEFAULT 'earn',
                description VARCHAR(255) NULL,
                balance_after INT DEFAULT 0,
                reference_type VARCHAR(50) NULL,
                reference_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_type (type),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Created: points_history table</p>";
    } else {
        echo "<p>⏭️ Exists: points_history table</p>";
        
        // Check columns
        $pointsColumns = [
            'type' => "ALTER TABLE points_history ADD COLUMN `type` ENUM('earn','redeem','bonus','adjust','expire') DEFAULT 'earn' AFTER points",
            'description' => "ALTER TABLE points_history ADD COLUMN `description` VARCHAR(255) NULL AFTER type",
            'balance_after' => "ALTER TABLE points_history ADD COLUMN `balance_after` INT DEFAULT 0 AFTER description"
        ];
        
        foreach ($pointsColumns as $col => $sql) {
            try {
                $check = $db->query("SHOW COLUMNS FROM points_history LIKE '$col'");
                if ($check->rowCount() == 0) {
                    $db->exec($sql);
                    echo "<p>✅ Added: points_history.$col</p>";
                } else {
                    echo "<p>⏭️ Exists: points_history.$col</p>";
                }
            } catch (Exception $e) {
                echo "<p>⚠️ $col: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // 3. Fix member_tiers table
    echo "<h3>3. Fixing member_tiers table...</h3>";
    
    $tiersExists = $db->query("SHOW TABLES LIKE 'member_tiers'")->rowCount() > 0;
    
    if (!$tiersExists) {
        $db->exec("
            CREATE TABLE member_tiers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_account_id INT NULL,
                tier_code VARCHAR(20) NOT NULL,
                tier_name VARCHAR(50) NOT NULL,
                min_points INT DEFAULT 0,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                point_multiplier DECIMAL(3,2) DEFAULT 1.00,
                color VARCHAR(20) DEFAULT '#CD7F32',
                icon VARCHAR(10) DEFAULT '🥉',
                benefits TEXT NULL,
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_tier (line_account_id, tier_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Insert default tiers
        $db->exec("
            INSERT INTO member_tiers (tier_code, tier_name, min_points, color, icon, sort_order) VALUES
            ('bronze', 'Bronze', 0, '#CD7F32', '🥉', 1),
            ('silver', 'Silver', 500, '#C0C0C0', '🥈', 2),
            ('gold', 'Gold', 2000, '#FFD700', '🥇', 3),
            ('platinum', 'Platinum', 5000, '#E5E4E2', '💎', 4),
            ('vip', 'VIP', 10000, '#9333EA', '👑', 5)
        ");
        echo "<p>✅ Created: member_tiers table with default tiers</p>";
    } else {
        echo "<p>⏭️ Exists: member_tiers table</p>";
    }
    
    // 4. Fix users table columns
    echo "<h3>4. Fixing users table...</h3>";
    
    $userColumns = [
        'member_id' => "ALTER TABLE users ADD COLUMN `member_id` VARCHAR(20) NULL",
        'member_tier' => "ALTER TABLE users ADD COLUMN `member_tier` VARCHAR(20) DEFAULT 'bronze'",
        'points' => "ALTER TABLE users ADD COLUMN `points` INT DEFAULT 0",
        'is_registered' => "ALTER TABLE users ADD COLUMN `is_registered` TINYINT(1) DEFAULT 0",
        'registered_at' => "ALTER TABLE users ADD COLUMN `registered_at` DATETIME NULL",
        'first_name' => "ALTER TABLE users ADD COLUMN `first_name` VARCHAR(100) NULL",
        'last_name' => "ALTER TABLE users ADD COLUMN `last_name` VARCHAR(100) NULL",
        'birthday' => "ALTER TABLE users ADD COLUMN `birthday` DATE NULL",
        'gender' => "ALTER TABLE users ADD COLUMN `gender` ENUM('male','female','other') NULL",
        'weight' => "ALTER TABLE users ADD COLUMN `weight` DECIMAL(5,2) NULL",
        'height' => "ALTER TABLE users ADD COLUMN `height` DECIMAL(5,2) NULL",
        'medical_conditions' => "ALTER TABLE users ADD COLUMN `medical_conditions` TEXT NULL",
        'drug_allergies' => "ALTER TABLE users ADD COLUMN `drug_allergies` TEXT NULL",
        'total_spent' => "ALTER TABLE users ADD COLUMN `total_spent` DECIMAL(12,2) DEFAULT 0",
        'total_orders' => "ALTER TABLE users ADD COLUMN `total_orders` INT DEFAULT 0"
    ];
    
    foreach ($userColumns as $col => $sql) {
        try {
            $check = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
            if ($check->rowCount() == 0) {
                $db->exec($sql);
                echo "<p>✅ Added: users.$col</p>";
            } else {
                echo "<p>⏭️ Exists: users.$col</p>";
            }
        } catch (Exception $e) {
            echo "<p>⚠️ $col: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>✅ Done!</h3>";
    echo "<p><a href='liff-checkout.php'>ทดสอบ Checkout</a> | <a href='liff-register.php?account=1'>ทดสอบสมัครสมาชิก</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
