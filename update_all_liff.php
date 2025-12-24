<?php
/**
 * Update All LIFF IDs
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Update All LIFF IDs</h2>";

$liffIds = [
    'liff_id' => '2008477880-wmRN2Aln',           // หน้าหลัก (Main)
    'liff_shop_id' => '2008477880-SOcaMdr0',      // Shop
    'liff_checkout_id' => '2008477880-Qo97wjzg',  // Checkout (สั่งซื้อผ่านแอป)
    'liff_video_id' => '2008477880-FDhymfKU',     // Video Call (LIVE ADMIN)
    'liff_share_id' => '2008680740-0nYlU5tK',     // Share (จาก config เดิม)
];

try {
    $db = Database::getInstance()->getConnection();
    
    // Check columns exist
    $columns = $db->query("SHOW COLUMNS FROM line_accounts")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>📋 Current Columns:</h3>";
    echo "<p>" . implode(', ', $columns) . "</p>";
    
    // Add missing columns
    $neededColumns = ['liff_id', 'liff_shop_id', 'liff_checkout_id', 'liff_video_id', 'liff_share_id'];
    foreach ($neededColumns as $col) {
        if (!in_array($col, $columns)) {
            $db->exec("ALTER TABLE line_accounts ADD COLUMN {$col} VARCHAR(50) DEFAULT NULL");
            echo "<p style='color:green;'>✅ Added column: {$col}</p>";
        }
    }
    
    if (isset($_GET['update'])) {
        // Update all accounts
        $stmt = $db->prepare("UPDATE line_accounts SET 
            liff_id = ?,
            liff_shop_id = ?,
            liff_checkout_id = ?,
            liff_video_id = ?,
            liff_share_id = ?
        ");
        $stmt->execute([
            $liffIds['liff_id'],
            $liffIds['liff_shop_id'],
            $liffIds['liff_checkout_id'],
            $liffIds['liff_video_id'],
            $liffIds['liff_share_id']
        ]);
        
        echo "<div style='background:#d4edda;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3 style='color:#155724;'>✅ Updated All LIFF IDs!</h3>";
        echo "<ul>";
        foreach ($liffIds as $key => $value) {
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Show current data
    echo "<h3>📊 Current LINE Accounts:</h3>";
    $stmt = $db->query("SELECT id, name, liff_id, liff_shop_id, liff_checkout_id, liff_video_id, liff_share_id, is_default FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Name</th><th>Main</th><th>Shop</th><th>Checkout</th><th>Video</th><th>Share</th><th>Default</th></tr>";
    foreach ($accounts as $acc) {
        echo "<tr>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td>" . ($acc['liff_id'] ?: '-') . "</td>";
        echo "<td>" . ($acc['liff_shop_id'] ?: '-') . "</td>";
        echo "<td>" . ($acc['liff_checkout_id'] ?: '-') . "</td>";
        echo "<td>" . ($acc['liff_video_id'] ?: '-') . "</td>";
        echo "<td>" . ($acc['liff_share_id'] ?: '-') . "</td>";
        echo "<td>" . ($acc['is_default'] ? '✅' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!isset($_GET['update'])) {
        echo "<br><br>";
        echo "<h3>🔄 LIFF IDs to Update:</h3>";
        echo "<ul>";
        foreach ($liffIds as $key => $value) {
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";
        echo "<p><a href='?update=1' style='display:inline-block;padding:12px 24px;background:#06C755;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>🔄 Update All LIFF IDs</a></p>";
    }
    
    echo "<hr>";
    echo "<h3>🔗 Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='https://liff.line.me/{$liffIds['liff_id']}' target='_blank'>🏠 Main Menu</a></li>";
    echo "<li><a href='https://liff.line.me/{$liffIds['liff_shop_id']}' target='_blank'>🛒 Shop</a></li>";
    echo "<li><a href='https://liff.line.me/{$liffIds['liff_video_id']}' target='_blank'>📹 Video Call</a></li>";
    echo "<li><a href='liff-main.php?debug=1'>🔍 Debug Main</a></li>";
    echo "<li><a href='liff-shop.php?debug=1'>🔍 Debug Shop</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
