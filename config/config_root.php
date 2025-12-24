<?php
/**
 * LINE CRM - Configuration for likesms.net (ROOT)
 * ใช้สำหรับติดตั้งที่ root ไม่ใช่ /v1
 * 
 * วิธีใช้:
 * 1. สร้างฐานข้อมูลใน cPanel
 * 2. แก้ไข DB_NAME, DB_USER, DB_PASS ด้านล่าง
 * 3. Rename ไฟล์นี้เป็น config.php
 * 4. เข้า https://likesms.net/install/
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

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'zseqjlsz_linecrm');    // แก้ไขตาม cPanel
define('DB_USER', 'zseqjlsz_linecrm');    // แก้ไขตาม cPanel
define('DB_PASS', 'YOUR_PASSWORD_HERE');  // รหัสผ่านที่ตั้ง

// LIFF IDs (ตั้งค่าทีหลังได้)
define('LIFF_SHARE_ID', '');
define('LIFF_ID', '');

// App Configuration - ใช้ที่ ROOT
define('APP_NAME', 'LINECRM');
define('APP_URL', 'https://likesms.net');
define('BASE_URL', 'https://likesms.net');
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
