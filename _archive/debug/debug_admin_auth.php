
<?php
/**
 * Debug Admin Auth Issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Admin Auth</h2>";

// 1. Test config
echo "<h3>1. Config Check</h3>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ Config loaded<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Test database connection
echo "<h3>2. Database Connection</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// 3. Check admin_users table
echo "<h3>3. admin_users Table</h3>";
try {
    $result = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->rowCount() > 0) {
        echo "✅ Table exists<br>";
        
        // Show structure
        $cols = $db->query("DESCRIBE admin_users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($cols);
        echo "</pre>";
        
        // Show data
        $users = $db->query("SELECT id, username, role, is_active FROM admin_users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Users:</h4><pre>";
        print_r($users);
        echo "</pre>";
    } else {
        echo "❌ Table does NOT exist<br>";
        echo "Creating table...<br>";
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                display_name VARCHAR(100),
                role VARCHAR(20) DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                last_login TIMESTAMP NULL,
                login_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Table created<br>";
        
        // Create default admin
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admin_users (username, password, display_name, role, is_active) VALUES (?, ?, 'Super Admin', 'super_admin', 1)");
        $stmt->execute(['admin', $defaultPassword]);
        echo "✅ Default admin created (admin/password)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 4. Test AdminAuth class
echo "<h3>4. AdminAuth Class</h3>";
try {
    require_once __DIR__ . '/classes/AdminAuth.php';
    $auth = new AdminAuth($db);
    echo "✅ AdminAuth instantiated<br>";
    
    // Test login
    echo "<h4>Testing login with admin/password:</h4>";
    $result = $auth->login('admin', 'password');
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ AdminAuth error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><a href='auth/login.php'>Go to Login Page</a>";
