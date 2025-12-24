<?php
/**
 * Debug Login Issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Login</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";

// Step 1: Check config
echo "<h2>1. Config</h2>";
try {
    require_once 'config/config.php';
    echo "<p class='ok'>✅ config.php loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ config.php error: " . $e->getMessage() . "</p>";
}

// Step 2: Check database
echo "<h2>2. Database</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 3: Check admin_users table
echo "<h2>3. Admin Users Table</h2>";
try {
    $stmt = $db->query("DESCRIBE admin_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']}\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 4: Check AdminAuth class
echo "<h2>4. AdminAuth Class</h2>";
try {
    require_once 'classes/AdminAuth.php';
    echo "<p class='ok'>✅ AdminAuth.php loaded</p>";
    
    $auth = new AdminAuth($db);
    echo "<p class='ok'>✅ AdminAuth instantiated</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ AdminAuth error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 5: Check users
echo "<h2>5. Admin Users</h2>";
try {
    $stmt = $db->query("SELECT id, username, display_name, role, is_active FROM admin_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 6: Test login
echo "<h2>6. Test Login</h2>";
if (isset($auth)) {
    $testUser = $users[0]['username'] ?? 'admin';
    echo "<p>Testing login for: {$testUser}</p>";
    
    // Get password hash
    $stmt = $db->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->execute([$testUser]);
    $hash = $stmt->fetchColumn();
    echo "<p>Password hash exists: " . ($hash ? 'Yes' : 'No') . "</p>";
    
    // Test with 'password'
    if ($hash && password_verify('password', $hash)) {
        echo "<p class='ok'>✅ Password 'password' is correct</p>";
    } else {
        echo "<p class='error'>❌ Password 'password' is incorrect</p>";
    }
}

echo "<h2>7. Session</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<p><a href='auth/login.php'>Try Login Page</a></p>";
?>
