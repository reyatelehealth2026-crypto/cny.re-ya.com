<?php
/**
 * Fix ALL missing columns for new installation
 * เข้า: https://likesms.net/v1/fix_all_columns.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Fix All Missing Columns</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
</style>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Database connected</p>";
    
    $fixes = [];
    
    // Helper function to check and add column
    function addColumnIfMissing($db, $table, $column, $definition, &$fixes) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
                $fixes[] = "Added $table.$column";
                return true;
            }
        } catch (Exception $e) {
            $fixes[] = "Error $table.$column: " . $e->getMessage();
        }
        return false;
    }
    
    // ========== admin_users ==========
    echo "<h2>📋 admin_users</h2>";
    addColumnIfMissing($db, 'admin_users', 'login_count', 'INT DEFAULT 0', $fixes);
    addColumnIfMissing($db, 'admin_users', 'last_login', 'DATETIME NULL', $fixes);
    addColumnIfMissing($db, 'admin_users', 'is_active', 'TINYINT(1) DEFAULT 1', $fixes);
    addColumnIfMissing($db, 'admin_users', 'role', "ENUM('super_admin','admin','staff') DEFAULT 'admin'", $fixes);
    
    // ========== line_accounts ==========
    echo "<h2>📋 line_accounts</h2>";
    addColumnIfMissing($db, 'line_accounts', 'liff_id', 'VARCHAR(50) NULL', $fixes);
    addColumnIfMissing($db, 'line_accounts', 'bot_mode', "ENUM('shop','general','auto_reply_only') DEFAULT 'shop'", $fixes);
    addColumnIfMissing($db, 'line_accounts', 'welcome_message', 'TEXT NULL', $fixes);
    addColumnIfMissing($db, 'line_accounts', 'auto_reply_enabled', 'TINYINT(1) DEFAULT 1', $fixes);
    addColumnIfMissing($db, 'line_accounts', 'shop_enabled', 'TINYINT(1) DEFAULT 1', $fixes);
    addColumnIfMissing($db, 'line_accounts', 'rich_menu_id', 'VARCHAR(100) NULL', $fixes);
    addColumnIfMissing($db, 'line_accounts', 'settings', 'LONGTEXT NULL', $fixes);
    
    // ========== users ==========
    echo "<h2>📋 users</h2>";
    addColumnIfMissing($db, 'users', 'account_id', 'INT NULL', $fixes);
    addColumnIfMissing($db, 'users', 'loyalty_points', 'INT DEFAULT 0', $fixes);
    addColumnIfMissing($db, 'users', 'total_spent', 'DECIMAL(12,2) DEFAULT 0', $fixes);
    addColumnIfMissing($db, 'users', 'order_count', 'INT DEFAULT 0', $fixes);
    addColumnIfMissing($db, 'users', 'membership_level', "ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze'", $fixes);
    addColumnIfMissing($db, 'users', 'phone', 'VARCHAR(20) NULL', $fixes);
    addColumnIfMissing($db, 'users', 'email', 'VARCHAR(100) NULL', $fixes);
    addColumnIfMissing($db, 'users', 'address', 'TEXT NULL', $fixes);
    addColumnIfMissing($db, 'users', 'notes', 'TEXT NULL', $fixes);
    addColumnIfMissing($db, 'users', 'tags', 'VARCHAR(500) NULL', $fixes);
    addColumnIfMissing($db, 'users', 'source', 'VARCHAR(50) NULL', $fixes);
    addColumnIfMissing($db, 'users', 'last_interaction', 'DATETIME NULL', $fixes);
    
    // ========== messages ==========
    echo "<h2>📋 messages</h2>";
    addColumnIfMissing($db, 'messages', 'account_id', 'INT NULL', $fixes);
    addColumnIfMissing($db, 'messages', 'is_read', 'TINYINT(1) DEFAULT 0', $fixes);
    
    // ========== Create missing tables ==========
    echo "<h2>📋 Create Missing Tables</h2>";
    
    // user_states
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS user_states (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) NOT NULL,
            account_id INT NULL,
            state VARCHAR(50) DEFAULT 'idle',
            state_data TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_account (user_id, account_id)
        )");
        $fixes[] = "Created/verified user_states table";
    } catch (Exception $e) {}
    
    // business_items (products)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS business_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            sale_price DECIMAL(10,2) NULL,
            image_url VARCHAR(500) NULL,
            category_id INT NULL,
            stock INT DEFAULT 0,
            sku VARCHAR(50) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified business_items table";
    } catch (Exception $e) {}
    
    // Add missing columns to business_items
    addColumnIfMissing($db, 'business_items', 'sale_price', 'DECIMAL(10,2) NULL', $fixes);
    addColumnIfMissing($db, 'business_items', 'category_id', 'INT NULL', $fixes);
    addColumnIfMissing($db, 'business_items', 'sku', 'VARCHAR(50) NULL', $fixes);
    
    // product_categories
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            image_url VARCHAR(500) NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified product_categories table";
    } catch (Exception $e) {}
    
    // cart_items
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) NOT NULL,
            account_id INT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified cart_items table";
    } catch (Exception $e) {}
    
    // orders
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NULL,
            user_id VARCHAR(100) NOT NULL,
            order_number VARCHAR(50) NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status ENUM('pending','confirmed','paid','shipped','completed','cancelled') DEFAULT 'pending',
            shipping_address TEXT NULL,
            phone VARCHAR(20) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified orders table";
    } catch (Exception $e) {}
    
    // order_items
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified order_items table";
    } catch (Exception $e) {}
    
    // payment_slips
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS payment_slips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NULL,
            user_id VARCHAR(100) NOT NULL,
            order_id INT NULL,
            image_url VARCHAR(500) NULL,
            amount DECIMAL(12,2) NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified payment_slips table";
    } catch (Exception $e) {}
    
    // loyalty_points_history
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS loyalty_points_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) NOT NULL,
            account_id INT NULL,
            points INT NOT NULL,
            type ENUM('earn','redeem','adjust','expire') DEFAULT 'earn',
            description VARCHAR(255) NULL,
            reference_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $fixes[] = "Created/verified loyalty_points_history table";
    } catch (Exception $e) {}
    
    // Show results
    echo "<h2>📊 Results</h2>";
    if (count($fixes) > 0) {
        echo "<table><tr><th>#</th><th>Action</th></tr>";
        foreach ($fixes as $i => $fix) {
            echo "<tr><td>" . ($i+1) . "</td><td>$fix</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ No changes needed - all columns exist</p>";
    }
    
    echo "<br><br>";
    echo "<a href='auth/login.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>🔐 Login</a>";
    echo "<a href='index.php' style='padding:10px 20px; background:#2196F3; color:white; text-decoration:none; border-radius:5px;'>🏠 Home</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
