<?php
/**
 * LINE CRM Pharmacy - Configuration Example
 * 
 * คัดลอกไฟล์นี้เป็น config.php และแก้ไขค่าตามระบบของคุณ
 * Copy this file to config.php and modify values for your system
 */

// ============================================
// TIMEZONE - ตั้งค่าเวลาไทย
// ============================================
date_default_timezone_set('Asia/Bangkok');

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// ============================================
// APPLICATION CONFIGURATION
// ============================================
define('APP_NAME', 'LINE CRM Pharmacy');
define('APP_URL', 'https://yourdomain.com/v1');
define('BASE_URL', 'https://yourdomain.com/v1');
define('TIMEZONE', 'Asia/Bangkok');

// ============================================
// LINE API (ตั้งค่าผ่านหน้า Admin Panel)
// ============================================
define('LINE_CHANNEL_ACCESS_TOKEN', '');
define('LINE_CHANNEL_SECRET', '');

// ============================================
// LIFF IDs (ตั้งค่าผ่านหน้า Admin Panel)
// ============================================
define('LIFF_SHARE_ID', '');
define('LIFF_SHOP_ID', '');
define('LIFF_REGISTER_ID', '');
define('LIFF_CHECKOUT_ID', '');

// ============================================
// AI CONFIGURATION (Optional)
// ============================================
// Gemini API Key - ตั้งค่าผ่านหน้า Admin > AI Settings
define('GEMINI_API_KEY', '');

// OpenAI API Key (Optional)
define('OPENAI_API_KEY', '');

// ============================================
// TELEGRAM NOTIFICATIONS (Optional)
// ============================================
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

// ============================================
// CNY PHARMACY API (Optional)
// ============================================
define('CNY_API_URL', '');
define('CNY_API_KEY', '');

// ============================================
// TABLE NAMES
// ============================================
define('TABLE_USERS', 'users');
define('TABLE_GROUPS', 'groups');

// ============================================
// SECURITY
// ============================================
define('ENCRYPTION_KEY', 'change-this-to-random-32-char-string');
define('SESSION_LIFETIME', 7200); // 2 hours

// ============================================
// UPLOAD SETTINGS
// ============================================
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ============================================
// DEBUG MODE (ปิดใน Production)
// ============================================
define('DEBUG_MODE', false);

// ============================================
// INITIALIZATION
// ============================================
// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Error reporting
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}
