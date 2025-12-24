<?php
/**
 * Debug Session after Login
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Debug Session</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow:auto;}</style>";

echo "<h2>1. Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>2. Check admin_user in session</h2>";
if (isset($_SESSION['admin_user'])) {
    echo "<p style='color:green'>✅ admin_user exists in session</p>";
    echo "<pre>";
    print_r($_SESSION['admin_user']);
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ admin_user NOT in session</p>";
}

echo "<h2>3. Database Check</h2>";
try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $db = Database::getInstance()->getConnection();
    echo "<p style='color:green'>✅ Database connected</p>";
    
    // Check admin_users table structure
    echo "<h3>admin_users columns:</h3>";
    $stmt = $db->query("DESCRIBE admin_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} {$col['Null']} {$col['Default']}\n";
    }
    echo "</pre>";
    
    // Check if login_count exists
    $hasLoginCount = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'login_count') {
            $hasLoginCount = true;
            break;
        }
    }
    
    if (!$hasLoginCount) {
        echo "<p style='color:orange'>⚠️ login_count column missing - adding it...</p>";
        try {
            $db->exec("ALTER TABLE admin_users ADD COLUMN login_count INT DEFAULT 0");
            echo "<p style='color:green'>✅ Added login_count column</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>✅ login_count column exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>4. Test AdminAuth</h2>";
try {
    require_once 'classes/AdminAuth.php';
    $auth = new AdminAuth($db);
    echo "<p style='color:green'>✅ AdminAuth loaded</p>";
    
    echo "<p>isLoggedIn: " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "</p>";
    
    if ($auth->isLoggedIn()) {
        echo "<p>Current User:</p>";
        echo "<pre>";
        print_r($auth->getCurrentUser());
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ AdminAuth Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='auth/login.php'>Go to Login</a> | <a href='index.php'>Go to Dashboard</a></p>";
