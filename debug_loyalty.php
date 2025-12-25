<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Loyalty Points</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;}</style>";

// Step 1
echo "<h2>1. Config</h2>";
try {
    require_once 'config/config.php';
    echo "<p class='ok'>✅ config.php loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
    exit;
}

// Step 2
echo "<h2>2. Database</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
    exit;
}

// Step 3
echo "<h2>3. Check Tables</h2>";
$tables = ['points_settings', 'points_transactions', 'rewards', 'reward_redemptions', 'points_tiers'];
foreach ($tables as $table) {
    try {
        $db->query("SELECT 1 FROM {$table} LIMIT 1");
        echo "<p class='ok'>✅ {$table} exists</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ {$table} NOT FOUND</p>";
    }
}

// Step 4
echo "<h2>4. LoyaltyPoints Class</h2>";
try {
    require_once 'classes/LoyaltyPoints.php';
    echo "<p class='ok'>✅ LoyaltyPoints.php loaded</p>";
    
    $loyalty = new LoyaltyPoints($db, null);
    echo "<p class='ok'>✅ LoyaltyPoints instantiated</p>";
    
    $settings = $loyalty->getSettings();
    echo "<p class='ok'>✅ getSettings() works</p>";
    echo "<pre>" . print_r($settings, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><a href='run_loyalty_migration.php'>Run Migration</a> | <a href='liff-redeem-points.php'>Loyalty (LIFF)</a></p>";
