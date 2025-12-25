<?php
/**
 * Run Wishlist Migration
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Running Wishlist Migration</h2>";

$sql = file_get_contents('database/migration_wishlist.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    
    try {
        $db->exec($stmt);
        echo "✅ " . substr($stmt, 0, 60) . "...<br>";
        $success++;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "⚠️ Already exists: " . substr($stmt, 0, 50) . "...<br>";
        } else {
            echo "❌ Error: " . $e->getMessage() . "<br>";
            $errors++;
        }
    }
}

echo "<br><b>Done!</b> Success: {$success}, Errors: {$errors}";
