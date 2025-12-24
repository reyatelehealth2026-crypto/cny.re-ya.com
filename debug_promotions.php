<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Start<br>";

echo "Step 2: Config...<br>";
require_once 'config/config.php';
echo "Step 2: OK<br>";

echo "Step 3: Database...<br>";
require_once 'config/database.php';
echo "Step 3: OK<br>";

echo "Step 4: Session...<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session OK<br>";

echo "Step 5: Including header.php...<br>";
flush();
ob_flush();

$pageTitle = 'Test Page';
require_once 'includes/header.php';

echo "Step 6: Header loaded OK!<br>";
echo "<h1>If you see this, header works!</h1>";

require_once 'includes/footer.php';
