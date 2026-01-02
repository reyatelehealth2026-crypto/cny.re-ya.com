<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP OK<br>";

require_once 'config/config.php';
echo "Step 2: Config OK<br>";

require_once 'config/database.php';
echo "Step 3: Database class OK<br>";

$db = Database::getInstance()->getConnection();
echo "Step 4: Database connection OK<br>";

// Check tables
$tables = ['appointments', 'pharmacists', 'users'];
foreach ($tables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '{$table}'")->fetch();
        echo "Table {$table}: " . ($result ? "EXISTS" : "NOT FOUND") . "<br>";
    } catch (Exception $e) {
        echo "Table {$table}: ERROR - " . $e->getMessage() . "<br>";
    }
}

echo "<br>Step 5: Including header...<br>";
require_once 'includes/header.php';
echo "Step 6: Header OK<br>";
