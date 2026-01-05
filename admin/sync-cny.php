<?php
/**
 * Admin page to sync CNY products to business_items table
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CnyPharmacyAPI.php';

// Allow access with key or login
$setupKey = $_GET['key'] ?? '';
$validKey = 'cny2024';
$isLoggedIn = isset($_SESSION['user_id']);

if ($setupKey !== $validKey && !$isLoggedIn) {
    header('Location: ../auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$cnyApi = new CnyPharmacyAPI($db);

// Get current stats
$stats = $cnyApi->getSyncStats();
$step = $_GET['step'] ?? 'check';
$output = '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ซิงค์สินค้า CNY → business_items</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-sync-alt text-blue-500 mr-3"></i>
                ซิงค์สินค้า CNY → business_items
            </h1>

            <?php if ($step === 'check'): ?>
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600"><?= number_format($stats['total'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">สินค้าทั้งหมด</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600"><?= number_format($stats['with_sku'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">มี SKU (จาก CNY)</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600"><?= number_format($stats['active'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">Active</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-red-600"><?= number_format($stats['out_of_stock'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">หมด Stock</div>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                    <p class="text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Sync สินค้าจาก CNY Pharmacy API ไปยังตาราง <code class="bg-blue-100 px-1 rounded">business_items</code>
                    </p>
                    <p class="text-blue-600 text-sm mt-2">
                        • สินค้าใหม่จะถูกสร้าง | สินค้าเดิมจะถูกอัพเดท<br>
                        • รองรับ: name_en, generic_name, manufacturer, unit, base_unit, usage_instructions
                    </p>
                </div>

                <div class="space-y-3">
                    <a href="?key=<?= $setupKey ?>&step=migrate" 
                       class="block w-full text-center px-6 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-semibold">
                        <i class="fas fa-database mr-2"></i>
                        Step 1: เพิ่ม Columns ใหม่ (ถ้ายังไม่มี)
                    </a>

                    <a href="?key=<?= $setupKey ?>&step=sync&batch=50" 
                       class="block w-full text-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Step 2: Sync จาก API (50 รายการ)
                    </a>

                    <a href="?key=<?= $setupKey ?>&step=sync&batch=200" 
                       class="block w-full text-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Step 2: Sync จาก API (200 รายการ)
                    </a>

                    <a href="?key=<?= $setupKey ?>&step=sync_all" 
                       class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold">
                        <i class="fas fa-cloud-download-alt mr-2"></i>
                        Step 2: Sync ทั้งหมด (ใช้เวลานาน)
                    </a>

                    <a href="../sync-dashboard.php" 
                       class="block w-full text-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        ไป Sync Dashboard (Continuous Sync)
                    </a>

                    <a href="../inventory/index.php?tab=products" 
                       class="block w-full text-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">
                        <i class="fas fa-boxes mr-2"></i>
                        ดูสินค้าใน Inventory
                    </a>
                </div>

            <?php elseif ($step === 'migrate'): ?>
                <?php
                set_time_limit(60);
                ob_start();
                
                try {
                    echo "🔧 Adding new columns to business_items...\n\n";
                    
                    $migrationFile = __DIR__ . '/../database/migration_product_cny_fields.sql';
                    if (file_exists($migrationFile)) {
                        $sql = file_get_contents($migrationFile);
                        $db->exec($sql);
                        echo "✓ Migration executed successfully!\n";
                    } else {
                        // Manual migration
                        $columns = [
                            "name_en VARCHAR(500) NULL",
                            "generic_name VARCHAR(500) NULL", 
                            "manufacturer VARCHAR(255) NULL",
                            "usage_instructions TEXT NULL",
                            "base_unit VARCHAR(50) NULL"
                        ];
                        
                        foreach ($columns as $col) {
                            $colName = explode(' ', $col)[0];
                            try {
                                $db->exec("ALTER TABLE business_items ADD COLUMN {$col}");
                                echo "✓ Added column: {$colName}\n";
                            } catch (PDOException $e) {
                                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                                    echo "○ Column exists: {$colName}\n";
                                } else {
                                    echo "✗ Error adding {$colName}: " . $e->getMessage() . "\n";
                                }
                            }
                        }
                    }
                    
                    echo "\n✓ Migration complete!\n";
                    
                } catch (Exception $e) {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                }
                
                $output = ob_get_clean();
                ?>

                <div class="bg-gray-900 text-green-400 p-6 rounded-lg mb-6 font-mono text-sm whitespace-pre-wrap">
<?= htmlspecialchars($output) ?>
                </div>

                <a href="?key=<?= $setupKey ?>&step=check" 
                   class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับ
                </a>

            <?php elseif ($step === 'sync' || $step === 'sync_all'): ?>
                <?php
                set_time_limit(600);
                ini_set('memory_limit', '512M');
                ob_start();
                
                $batchSize = isset($_GET['batch']) ? intval($_GET['batch']) : 0;
                
                try {
                    echo "🚀 Starting CNY Sync to business_items...\n\n";
                    
                    $options = [
                        'update_existing' => true,
                        'auto_category' => true
                    ];
                    
                    if ($batchSize > 0) {
                        $options['limit'] = $batchSize;
                        $options['offset'] = 0;
                        echo "📦 Batch size: {$batchSize}\n\n";
                    } else {
                        echo "📦 Syncing ALL products...\n\n";
                    }
                    
                    $result = $cnyApi->syncAllProducts($options);
                    
                    if ($result['success']) {
                        $s = $result['stats'];
                        echo "╔══════════════════════════════════════╗\n";
                        echo "║         SYNC COMPLETED               ║\n";
                        echo "╚══════════════════════════════════════╝\n\n";
                        echo "📊 Statistics:\n";
                        echo "   Total processed: " . ($s['total'] ?? 0) . "\n";
                        echo "   Created: " . ($s['created'] ?? 0) . "\n";
                        echo "   Updated: " . ($s['updated'] ?? 0) . "\n";
                        echo "   Skipped: " . ($s['skipped'] ?? 0) . "\n";
                        
                        if (!empty($s['errors'])) {
                            echo "\n⚠️ Errors (" . count($s['errors']) . "):\n";
                            foreach (array_slice($s['errors'], 0, 5) as $err) {
                                echo "   - {$err['sku']}: {$err['error']}\n";
                            }
                        }
                    } else {
                        echo "✗ Sync failed: " . ($result['error'] ?? 'Unknown error') . "\n";
                    }
                    
                } catch (Exception $e) {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                }
                
                $output = ob_get_clean();
                ?>

                <div class="bg-gray-900 text-green-400 p-6 rounded-lg mb-6 font-mono text-sm whitespace-pre-wrap overflow-auto max-h-96">
<?= htmlspecialchars($output) ?>
                </div>

                <div class="space-x-3">
                    <a href="?key=<?= $setupKey ?>&step=check" 
                       class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับ
                    </a>
                    <a href="../inventory/index.php?tab=products" 
                       class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        <i class="fas fa-boxes mr-2"></i>
                        ดูสินค้า
                    </a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
