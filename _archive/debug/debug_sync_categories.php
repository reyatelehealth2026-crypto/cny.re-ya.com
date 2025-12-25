<?php
/**
 * Debug Sync Categories
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Sync Categories</h2><pre>";

// Step 1: Config
echo "Step 1: Loading config...\n";
try {
    require_once 'config/config.php';
    echo "✅ Config loaded\n";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Database
echo "\nStep 2: Loading database...\n";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit;
}

// Step 3: Check product_categories table
echo "\nStep 3: Checking product_categories table...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'product_categories'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table exists\n";
        
        // Show columns
        $stmt = $db->query("SHOW COLUMNS FROM product_categories");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(', ', $columns) . "\n";
        
        // Check for cny_code
        if (in_array('cny_code', $columns)) {
            echo "✅ cny_code column exists\n";
        } else {
            echo "⚠️ cny_code column NOT found - will be added\n";
        }
    } else {
        echo "⚠️ Table does not exist - will be created\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Step 4: Load CnyPharmacyAPI
echo "\nStep 4: Loading CnyPharmacyAPI...\n";
try {
    require_once 'classes/CnyPharmacyAPI.php';
    echo "✅ Class loaded\n";
    
    $api = new CnyPharmacyAPI($db, 1);
    echo "✅ Instance created\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
    exit;
}

// Step 5: Test parseCategoryName
echo "\nStep 5: Testing parseCategoryName...\n";
$testCases = [
    'GIS-04-แก้ท้องเสีย-ท้องผูก',
    'VIT-01-วิตามิน',
    'MED-02-ยาแก้ปวด',
    'SKIN-03-ผิวหนัง-ความงาม'
];

// Use reflection to test private method
$reflection = new ReflectionClass($api);
$method = $reflection->getMethod('parseCategoryName');
$method->setAccessible(true);

foreach ($testCases as $test) {
    $result = $method->invoke($api, $test);
    echo "  '{$test}' => '{$result}'\n";
}

// Step 6: Test getOrCreateCategory
echo "\nStep 6: Testing getOrCreateCategory...\n";
try {
    $categoryId = $api->getOrCreateCategory('TEST-01-ทดสอบหมวดหมู่');
    if ($categoryId) {
        echo "✅ Category created/found: ID = {$categoryId}\n";
    } else {
        echo "⚠️ Category not created (returned null)\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Step 7: Get categories
echo "\nStep 7: Getting CNY categories...\n";
try {
    $categories = $api->getCnyCategories();
    echo "Found " . count($categories) . " CNY categories\n";
    foreach ($categories as $cat) {
        echo "  - {$cat['name']} ({$cat['cny_code']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n</pre><p><a href='sync_update_categories.php'>← ไปหน้า Sync Categories</a></p>";