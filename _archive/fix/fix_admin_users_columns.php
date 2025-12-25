<?php
/**
 * Fix admin_users missing columns
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix admin_users columns</h2>";

$columns = [
    'last_login' => 'TIMESTAMP NULL',
    'login_count' => 'INT DEFAULT 0'
];

foreach ($columns as $column => $definition) {
    try {
        $db->query("SELECT {$column} FROM admin_users LIMIT 1");
        echo "✅ Column '{$column}' already exists<br>";
    } catch (Exception $e) {
        try {
            $db->exec("ALTER TABLE admin_users ADD COLUMN {$column} {$definition}");
            echo "✅ Added column '{$column}'<br>";
        } catch (Exception $e2) {
            echo "❌ Failed to add '{$column}': " . $e2->getMessage() . "<br>";
        }
    }
}

echo "<hr>Done! <a href='auth/login.php'>Go to Login</a>";
