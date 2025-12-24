<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP OK<br>";

echo "Step 2: Config...<br>";
require_once __DIR__ . '/config/config.php';
echo "Step 2: Config OK<br>";

echo "Step 3: Database...<br>";
require_once __DIR__ . '/config/database.php';
echo "Step 3: Database OK<br>";

echo "Step 4: Connect (old way)...<br>";
$db = Database::getInstance()->getConnection();
echo "Step 4: Connected OK<br>";

echo "Step 5: Test query...<br>";
$stmt = $db->query("SELECT 1 as test");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Step 5: Query OK - " . $row['test'] . "<br>";

echo "Step 6: Test Modules\\Core\\Database...<br>";
require_once __DIR__ . '/modules/Core/Database.php';
$db2 = \Modules\Core\Database::getInstance()->getConnection();
echo "Step 6: Module DB OK<br>";

echo "<br>All Done!";
