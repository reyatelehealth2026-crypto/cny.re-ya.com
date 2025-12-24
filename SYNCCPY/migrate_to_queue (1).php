#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Migration Script - อัพเกรดจากระบบ sync เดิมเป็น Queue-Based System
 * 
 * รัน: php migrate_to_queue.php
 * 
 * Script นี้จะ:
 * 1. สร้าง tables ใหม่
 * 2. ดึง SKU ทั้งหมดจาก API
 * 3. เพิ่มเข้า queue
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

$projectRoot = dirname(__DIR__);
chdir($projectRoot);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/CnyPharmacyAPI.php';

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     CNY Sync System Migration Tool                    ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $cnyApi = new CnyPharmacyAPI($db);
    
    // Step 1: Create tables
    echo "📦 Step 1: Creating database tables...\n";
    
    $schemaFile = __DIR__ . '/../database/sync_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $sql = file_get_contents($schemaFile);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );
    
    $created = 0;
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $created++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }
    
    echo "   ✓ Created/verified {$created} database objects\n\n";
    
    // Step 2: Test API connection
    echo "🔌 Step 2: Testing API connection...\n";
    
    $testResult = $cnyApi->testConnection();
    if (!$testResult['success']) {
        throw new Exception("API connection failed: " . $testResult['message']);
    }
    
    echo "   ✓ API connection successful\n\n";
    
    // Step 3: Fetch SKUs from API
    echo "📥 Step 3: Fetching SKUs from CNY API...\n";
    
    $skuResult = $cnyApi->getSkuList();
    if (!$skuResult['success']) {
        throw new Exception("Failed to fetch SKUs: " . ($skuResult['error'] ?? 'Unknown error'));
    }
    
    $skus = $skuResult['data'];
    $totalSkus = count($skus);
    
    echo "   ✓ Found {$totalSkus} SKUs\n\n";
    
    // Step 4: Create initial batch
    echo "📋 Step 4: Creating initial sync batch...\n";
    
    $batchName = "Initial Migration " . date('Y-m-d H:i:s');
    
    $stmt = $db->prepare(
        "INSERT INTO sync_batches (batch_name, total_jobs, status) 
         VALUES (?, ?, 'pending')"
    );
    $stmt->execute([$batchName, $totalSkus]);
    $batchId = (int)$db->lastInsertId();
    
    echo "   ✓ Created batch #{$batchId}: {$batchName}\n\n";
    
    // Step 5: Add SKUs to queue
    echo "⚡ Step 5: Adding SKUs to queue...\n";
    echo "   This may take a while for large datasets...\n\n";
    
    $chunkSize = 1000; // Insert 1000 at a time
    $chunks = array_chunk($skus, $chunkSize);
    $totalAdded = 0;
    
    $db->beginTransaction();
    
    try {
        foreach ($chunks as $index => $chunk) {
            $values = [];
            $params = [];
            
            foreach ($chunk as $sku) {
                $values[] = "(?, 5, 3)"; // sku, priority=5, max_attempts=3
                $params[] = $sku;
            }
            
            $sql = "INSERT IGNORE INTO sync_queue (sku, priority, max_attempts) VALUES " 
                 . implode(', ', $values);
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $added = $stmt->rowCount();
            $totalAdded += $added;
            
            $progress = ($index + 1) * $chunkSize;
            $percent = min(100, round(($progress / $totalSkus) * 100));
            
            echo "\r   Progress: {$percent}% ({$totalAdded} jobs added)";
        }
        
        $db->commit();
        echo "\n   ✓ Added {$totalAdded} jobs to queue\n\n";
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
    // Step 6: Update batch
    $stmt = $db->prepare("UPDATE sync_batches SET total_jobs = ? WHERE id = ?");
    $stmt->execute([$totalAdded, $batchId]);
    
    // Summary
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║              MIGRATION COMPLETED!                      ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 Summary:\n";
    echo "   Total SKUs from API: {$totalSkus}\n";
    echo "   Jobs added to queue: {$totalAdded}\n";
    echo "   Batch ID: {$batchId}\n";
    echo "\n";
    
    echo "🚀 Next Steps:\n";
    echo "   1. Open dashboard: http://your-domain.com/public/sync_dashboard.php\n";
    echo "   2. Start worker: php public/sync_worker.php\n";
    echo "   3. Or run in background: nohup php public/sync_worker.php > worker.log 2>&1 &\n";
    echo "\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
