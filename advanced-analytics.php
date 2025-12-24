<?php
/**
 * Advanced Analytics - Entry Point
 * MVC Pattern Implementation
 */

// Load and register Autoloader
require_once __DIR__ . '/app/Core/Autoloader.php';

$autoloader = new \App\Core\Autoloader();
$autoloader->addNamespace('App', __DIR__ . '/app');
$autoloader->register();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Check admin access
if (!isAdmin() && !isSuperAdmin()) {
    header('Location: auth/login.php');
    exit;
}

// Route actions
$action = $_GET['action'] ?? 'dashboard';

// Initialize controller
$controller = new App\Controllers\AnalyticsController();

// Handle API requests
switch ($action) {
    case 'api_stats':
        $controller->apiStats();
        break;
        
    case 'api_realtime':
        $controller->apiRealtime();
        break;
        
    case 'api_funnel':
        $controller->apiFunnel();
        break;
        
    case 'export':
        $controller->export();
        break;
        
    case 'dashboard':
    default:
        // Include header for dashboard view
        $pageTitle = 'Advanced Analytics';
        include __DIR__ . '/includes/header.php';
        $controller->dashboard();
        include __DIR__ . '/includes/footer.php';
        break;
}
