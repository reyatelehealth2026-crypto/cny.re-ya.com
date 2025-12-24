<?php
/**
 * LINE OA Manager - Configuration for likesms.net
 * Copy this file to config.php after uploading to new host
 */

// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=utf-8');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// แก้ไขข้อมูลด้านล่างให้ตรงกับโฮสต์ใหม่
// ============================================

// Database Credentials - แก้ไขให้ตรงกับฐานข้อมูลใหม่
// *** สำคัญ: ต้องสร้างฐานข้อมูลใน cPanel ก่อน ***
define('DB_HOST', 'localhost');
define('DB_NAME', 'likesmsn_linecrm');    // ชื่อฐานข้อมูล (ปกติจะเป็น prefix_dbname)
define('DB_USER', 'likesmsn_linecrm');    // username (ปกติจะเป็น prefix_username)
define('DB_PASS', 'YOUR_PASSWORD_HERE');  // password ที่ตั้งใน cPanel

// LIFF IDs
define('LIFF_SHARE_ID', '');  // LIFF ID สำหรับ Share
define('LIFF_ID', '');        // LIFF ID สำหรับ Video Call

// App Configuration - URL ใหม่
define('APP_NAME', 'LINECRM');
define('APP_URL', 'https://likesms.net/v1');
define('BASE_URL', 'https://likesms.net/v1');
define('TIMEZONE', 'Asia/Bangkok');

// LINE API (ตั้งค่าผ่านหน้า Admin)
define('LINE_CHANNEL_ACCESS_TOKEN', '');
define('LINE_CHANNEL_SECRET', '');

// OpenAI (Optional)
define('OPENAI_API_KEY', '');

// Telegram Notifications (Optional)
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

// Table Names
define('TABLE_USERS', 'users');
define('TABLE_GROUPS', 'groups');

// Initialize
date_default_timezone_set(TIMEZONE);
