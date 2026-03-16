<?php
// deploy-retail.php - รันบน browser แล้วจะดึงไฟล์ทั้งหมดจาก GitHub

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🚀 Deploying Retail B2C Files...</h2>";
echo "<pre>";

$baseUrl = 'https://raw.githubusercontent.com/reyatelehealth2026-crypto/cny.re-ya.com/master/';

$files = [
    // LIFF JS
    'liff/assets/js/retail-shop.js' => 'liff/assets/js/',
    'liff/assets/js/router.js' => 'liff/assets/js/',
    
    // LIFF CSS  
    'liff/assets/css/retail-shop.css' => 'liff/assets/css/',
    
    // API
    'api/retail-products.php' => 'api/',
    'api/retail-cart.php' => 'api/',
    'api/retail-checkout.php' => 'api/',
    'api/retail-payment.php' => 'api/',
    
    // Cron
    'cron/sync-retail-products.php' => 'cron/',
    'cron/release-expired-cart-reservations.php' => 'cron/',
];

$success = 0;
$failed = 0;

foreach ($files as $file => $dir) {
    // Create directory if not exists
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Created: $dir\n";
    }
    
    // Download file
    $url = $baseUrl . $file;
    $content = @file_get_contents($url);
    
    if ($content && file_put_contents($file, $content)) {
        echo "✅ $file\n";
        $success++;
    } else {
        echo "❌ $file (Error)\n";
        $failed++;
    }
}

echo "\n";
echo "==================\n";
echo "Success: $success\n";
echo "Failed: $failed\n";
echo "==================\n";

echo "\n";
if ($failed === 0) {
    echo "✨ <a href='/liff/?mode=retail'>เปิด Retail Shop</a>\n";
} else {
    echo "⚠️ บางไฟล์ล้มเหลว ลองรีเฟรชหรือเช็ค permissions\n";
}

echo "\n🗑️ <b>ลบไฟล์นี้หลังใช้งานเสร็จ</b>\n";
echo "</pre>";
