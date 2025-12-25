<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h2>Debug Session & Role</h2>";

echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>admin_user</h3>";
if (isset($_SESSION['admin_user'])) {
    echo "<pre>" . print_r($_SESSION['admin_user'], true) . "</pre>";
    echo "Role: " . ($_SESSION['admin_user']['role'] ?? 'NOT SET') . "<br>";
} else {
    echo "❌ admin_user NOT in session<br>";
}

echo "<h3>current_bot_id</h3>";
echo "Value: " . ($_SESSION['current_bot_id'] ?? 'NOT SET') . "<br>";
