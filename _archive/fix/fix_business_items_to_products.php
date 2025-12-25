<?php
/**
 * Fix Script: Replace business_items with products in PHP files
 * 
 * วิธีใช้: php fix_business_items_to_products.php
 * 
 * Script นี้จะ:
 * 1. ค้นหาไฟล์ที่ใช้ business_items
 * 2. แสดงรายการไฟล์ที่จะแก้ไข
 * 3. ถามยืนยันก่อนแก้ไข
 * 4. สร้าง backup ก่อนแก้ไข
 * 5. Replace business_items เป็น products
 */

echo "===========================================\n";
echo "Fix: Replace business_items → products\n";
echo "===========================================\n\n";

// Files to update (main files only, not migration/fix files)
$filesToUpdate = [
    'liff-shop.php',
    'liff-checkout.php',
    'liff-order-detail.php',
    'shop/promotions.php',
    'shop/products-grid.php',
    'shop/product-detail.php',
    'user-detail.php',
    'sync_categories_from_cny.php',
    'sync_products_with_sku_id.php',
    'sync_cny_with_id.php',
    'sync_categories_from_manufacturer.php',
    'test_ai_product_flex.php',
    'test_checkout.php',
    'test_search.php',
    'fix_missing_products.php',
    'fix_cart_fk.php',
    'index.php',
    'api/shop-products.php',
];

// Patterns to replace
$replacements = [
    // Table name in queries
    'FROM business_items' => 'FROM products',
    'INTO business_items' => 'INTO products',
    'UPDATE business_items' => 'UPDATE products',
    'JOIN business_items' => 'JOIN products',
    'TABLE business_items' => 'TABLE products',
    
    // Table name with backticks
    'FROM `business_items`' => 'FROM `products`',
    'INTO `business_items`' => 'INTO `products`',
    'UPDATE `business_items`' => 'UPDATE `products`',
    'JOIN `business_items`' => 'JOIN `products`',
    'TABLE `business_items`' => 'TABLE `products`',
    
    // SHOW COLUMNS
    'SHOW COLUMNS FROM business_items' => 'SHOW COLUMNS FROM products',
    'SHOW COLUMNS FROM `business_items`' => 'SHOW COLUMNS FROM `products`',
    
    // Variable assignments
    "'business_items'" => "'products'",
    '"business_items"' => '"products"',
    '$table = \'business_items\'' => '$table = \'products\'',
    'return \'business_items\'' => 'return \'products\'',
    
    // SELECT 1 check
    'SELECT 1 FROM business_items' => 'SELECT 1 FROM products',
    'SELECT 1 FROM `business_items`' => 'SELECT 1 FROM `products`',
];

// Find files with business_items
echo "📂 Scanning files...\n\n";

$foundFiles = [];
foreach ($filesToUpdate as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "⚠️  Not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($path);
    if (strpos($content, 'business_items') !== false) {
        $count = substr_count($content, 'business_items');
        $foundFiles[$file] = $count;
        echo "✓ $file ($count occurrences)\n";
    }
}

if (empty($foundFiles)) {
    echo "\n✅ No files need updating!\n";
    exit(0);
}

echo "\n📊 Found " . count($foundFiles) . " files to update\n";
echo "   Total occurrences: " . array_sum($foundFiles) . "\n\n";

// Confirm
echo "⚠️  This will:\n";
echo "   1. Create backup of each file (.bak)\n";
echo "   2. Replace 'business_items' with 'products'\n\n";

echo "Continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 'yes') {
    echo "Cancelled.\n";
    exit(0);
}
fclose($handle);

echo "\n🚀 Processing...\n\n";

$updated = 0;
$errors = [];

foreach ($foundFiles as $file => $count) {
    $path = __DIR__ . '/' . $file;
    
    // Create backup
    $backupPath = $path . '.bak';
    if (!copy($path, $backupPath)) {
        $errors[] = "Failed to backup: $file";
        continue;
    }
    
    // Read content
    $content = file_get_contents($path);
    $originalContent = $content;
    
    // Apply replacements
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Check if changed
    if ($content !== $originalContent) {
        if (file_put_contents($path, $content)) {
            $newCount = substr_count($content, 'business_items');
            echo "✅ $file (replaced, remaining: $newCount)\n";
            $updated++;
        } else {
            $errors[] = "Failed to write: $file";
            // Restore backup
            copy($backupPath, $path);
        }
    } else {
        echo "⏭️  $file (no changes needed)\n";
    }
}

echo "\n===========================================\n";
echo "📊 Results:\n";
echo "   Updated: $updated files\n";
echo "   Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n❌ Errors:\n";
    foreach ($errors as $err) {
        echo "   - $err\n";
    }
}

echo "\n💡 Backup files created with .bak extension\n";
echo "   To remove backups: find . -name '*.bak' -delete\n";
echo "\n✅ Done!\n";
