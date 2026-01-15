<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Test</h2>";

echo "<p>1. PHP Version: " . phpversion() . "</p>";

echo "<p>2. Testing config load...</p>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "<p>✅ Config loaded</p>";
    echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Config error: " . $e->getMessage() . "</p>";
}

echo "<p>3. Testing Database class...</p>";
try {
    require_once __DIR__ . '/../classes/Database.php';
    echo "<p>✅ Database class loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Database class error: " . $e->getMessage() . "</p>";
}

echo "<p>4. Testing database connection...</p>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
}

echo "<p>5. Testing dev_logs table...</p>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'dev_logs'");
    $exists = $stmt->rowCount() > 0;
    if ($exists) {
        echo "<p>✅ dev_logs table exists</p>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM dev_logs");
        $count = $stmt->fetchColumn();
        echo "<p>Total logs: {$count}</p>";
    } else {
        echo "<p>❌ dev_logs table does NOT exist!</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Table check error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>✅ All tests completed!</p>";
