<?php
/**
 * Fix AI mode to sales - Direct MySQL
 */
header('Content-Type: text/plain; charset=utf-8');

// Load config
$configFile = __DIR__ . '/../config/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

// Get DB credentials
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$name = defined('DB_NAME') ? DB_NAME : '';
$user = defined('DB_USER') ? DB_USER : '';
$pass = defined('DB_PASS') ? DB_PASS : '';

if (empty($name)) {
    echo "Error: Database not configured\n";
    exit;
}

try {
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: {$name}\n\n";
    
    // Check if ai_mode column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM ai_settings LIKE 'ai_mode'");
    if ($stmt->rowCount() == 0) {
        echo "Column ai_mode does not exist. Adding...\n";
        $pdo->exec("ALTER TABLE ai_settings ADD COLUMN ai_mode VARCHAR(20) DEFAULT 'sales'");
        echo "Column added.\n\n";
    }
    
    // Update all to sales mode
    $pdo->exec("UPDATE ai_settings SET ai_mode = 'sales'");
    echo "Updated ai_mode to 'sales'\n\n";
    
    // Show current settings
    $stmt = $pdo->query("SELECT id, line_account_id, ai_mode, is_enabled, model FROM ai_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current AI Settings:\n";
    foreach ($rows as $row) {
        echo "- ID: {$row['id']}, Line: {$row['line_account_id']}, Mode: {$row['ai_mode']}, Enabled: {$row['is_enabled']}, Model: {$row['model']}\n";
    }
    
    echo "\nDone!";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
