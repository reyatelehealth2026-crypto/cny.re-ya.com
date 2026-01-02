<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<br><b>ERROR:</b> [$errno] $errstr in $errfile on line $errline<br>";
    return true;
});

echo "Step 1: PHP OK<br>";

require_once 'config/config.php';
echo "Step 2: Config OK<br>";

require_once 'config/database.php';
echo "Step 3: Database class OK<br>";

$db = Database::getInstance()->getConnection();
echo "Step 4: Database connection OK<br>";

echo "<br>Step 5: Including header line by line...<br>";

// Simulate header.php step by step
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "5.1: Session OK<br>";

require_once 'includes/auth_check.php';
echo "5.2: Auth check OK<br>";

// Check if isUser function exists
if (function_exists('isUser')) {
    echo "5.3: isUser function exists<br>";
    $isUserResult = isUser();
    echo "5.4: isUser() = " . ($isUserResult ? 'true' : 'false') . "<br>";
} else {
    echo "5.3: isUser function NOT FOUND<br>";
}

// Check currentUser
echo "5.5: currentUser role = " . ($currentUser['role'] ?? 'NOT SET') . "<br>";

echo "<br>Step 6: Full header include...<br>";
flush();
ob_flush();

try {
    require_once 'includes/header.php';
    echo "Step 7: Header OK<br>";
} catch (Throwable $e) {
    echo "<br><b>EXCEPTION:</b> " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
