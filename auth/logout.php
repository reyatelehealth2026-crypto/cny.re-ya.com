<?php
/**
 * Admin Logout
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AdminAuth.php';

$db = Database::getInstance()->getConnection();
$auth = new AdminAuth($db);
$auth->logout();

header('Location: login.php');
exit;
