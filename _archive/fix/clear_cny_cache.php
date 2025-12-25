<?php
/**
 * Clear CNY API Cache
 */

$cacheFiles = [
    sys_get_temp_dir() . '/cny_sku_list.json',
    sys_get_temp_dir() . '/cny_products_all.json'
];

echo "<h2>🗑️ Clear CNY Cache</h2>";
echo "<pre>";

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $age = time() - filemtime($file);
        @unlink($file);
        echo "✓ Deleted: " . basename($file) . " (size: " . number_format($size) . " bytes, age: {$age}s)\n";
    } else {
        echo "⏭ Not found: " . basename($file) . "\n";
    }
}

echo "</pre>";
echo "<p>✅ Cache cleared! <a href='sync_cny_batch.php'>Go to Sync</a></p>";
