<?php
/**
 * Sync CNY Products with CNY ID
 * ใช้ ID จาก CNY API เป็น ID ในระบบ LINECRM
 * Standalone version - ไม่ต้องพึ่ง method ใหม่ใน CnyPharmacyAPI
 */

ini_set('memory_limit', '512M');
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// ==================== AUTO-DETECT TABLE ====================
function getItemsTable($db) {
    // Use products table as primary
    return 'products';
}

function getCategoriesTable($db) {
    try {
        $db->query("SELECT 1 FROM item_categories LIMIT 1");
        return 'item_categories';
    } catch (Exception $e) {}
    try {
        $db->query("SELECT 1 FROM product_categories LIMIT 1");
        return 'product_categories';
    } catch (Exception $e) {}
    return null;
}

$ITEMS_TABLE = getItemsTable($db);
$CATEGORIES_TABLE = getCategoriesTable($db);

// ==================== HELPER FUNCTIONS ====================

/**
 * Parse category name from CNY format
 */
function parseCategoryName($cnyCategoryCode) {
    if (empty($cnyCategoryCode)) return null;
    
    $parts = explode('-', $cnyCategoryCode);
    $thaiParts = [];
    $foundThai = false;
    
    foreach ($parts as $part) {
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $part)) {
            $foundThai = true;
        }
        if ($foundThai) {
            $thaiParts[] = $part;
        }
    }
    
    if (!empty($thaiParts)) {
        return implode('-', $thaiParts);
    }
    
    if (count($parts) > 2) {
        return implode('-', array_slice($parts, 2));
    }
    
    return $cnyCategoryCode;
}

/**
 * Get or create category
 */
function getOrCreateCategory($db, $cnyCategoryCode, $lineAccountId) {
    global $CATEGORIES_TABLE;
    if (empty($cnyCategoryCode) || !$CATEGORIES_TABLE) return null;
    
    $categoryName = parseCategoryName($cnyCategoryCode);
    if (empty($categoryName)) return null;
    
    // Ensure cny_code column exists
    try {
        $stmt = $db->query("SHOW COLUMNS FROM {$CATEGORIES_TABLE} LIKE 'cny_code'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE {$CATEGORIES_TABLE} ADD COLUMN cny_code VARCHAR(100) NULL AFTER name");
        }
    } catch (Exception $e) {}
    
    // Find by cny_code
    try {
        $stmt = $db->prepare("SELECT id FROM {$CATEGORIES_TABLE} WHERE cny_code = ? LIMIT 1");
        $stmt->execute([$cnyCategoryCode]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) return $existing['id'];
    } catch (Exception $e) {}
    
    // Find by name
    try {
        $stmt = $db->prepare("SELECT id FROM {$CATEGORIES_TABLE} WHERE name = ? LIMIT 1");
        $stmt->execute([$categoryName]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $db->prepare("UPDATE {$CATEGORIES_TABLE} SET cny_code = ? WHERE id = ?")->execute([$cnyCategoryCode, $existing['id']]);
            return $existing['id'];
        }
    } catch (Exception $e) {}
    
    // Create new
    try {
        $stmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$CATEGORIES_TABLE}");
        $nextOrder = $stmt->fetchColumn();
        $stmt = $db->prepare("INSERT INTO {$CATEGORIES_TABLE} (line_account_id, name, cny_code, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$lineAccountId, $categoryName, $cnyCategoryCode, $nextOrder]);
        return $db->lastInsertId();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Sync single product with CNY ID
 */
function syncProductWithCnyId($db, $product, $lineAccountId) {
    global $ITEMS_TABLE;
    if (!$ITEMS_TABLE) {
        return ['action' => 'skipped', 'reason' => 'No items table found'];
    }
    
    $cnyId = $product['id'] ?? null;
    $sku = $product['sku'] ?? null;
    
    if (!$cnyId) {
        return ['action' => 'skipped', 'reason' => 'No CNY ID'];
    }
    
    // Get price
    $price = 0;
    $unit = 'ชิ้น';
    $prices = $product['product_price'] ?? [];
    if (!empty($prices)) {
        foreach ($prices as $p) {
            if (strpos($p['customer_group'] ?? '', 'GEN') !== false) {
                $price = floatval($p['price']);
                $unit = $p['unit'] ?? 'ชิ้น';
                break;
            }
        }
        if ($price == 0 && isset($prices[0]['price'])) {
            $price = floatval($prices[0]['price']);
            $unit = $prices[0]['unit'] ?? 'ชิ้น';
        }
    }
    
    // Get category
    $categoryId = null;
    if (!empty($product['category'])) {
        $categoryId = getOrCreateCategory($db, $product['category'], $lineAccountId);
    }
    
    // Build extra_data
    $extraData = json_encode([
        'cny_id' => $cnyId,
        'cny_category' => $product['category'] ?? null,
        'name_en' => $product['name_en'] ?? null,
        'hashtag' => $product['hashtag'] ?? null,
        'properties' => $product['properties_other'] ?? null,
        'all_prices' => $prices,
        'qty_incoming' => $product['qty_incoming'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
    
    // Check if product exists by ID
    $stmt = $db->prepare("SELECT id FROM {$ITEMS_TABLE} WHERE id = ?");
    $stmt->execute([$cnyId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare data
    $name = $product['name'] ?? $product['name_en'] ?? 'Unknown';
    $barcode = $product['barcode'] ?? null;
    $stock = intval($product['qty'] ?? 0);
    $imageUrl = $product['photo_path'] ?? null;
    $isActive = ($product['enable'] ?? '1') == '1' ? 1 : 0;
    $genericName = $product['spec_name'] ?? null;
    $usageInstructions = $product['how_to_use'] ?? null;
    
    // Extract manufacturer from name_en
    $manufacturer = null;
    if (preg_match('/\[([^\]]+)\]/', $product['name_en'] ?? '', $matches)) {
        $manufacturer = $matches[1];
    }
    
    // Build description
    $descParts = [];
    if (!empty($product['properties_other'])) {
        $text = strip_tags($product['properties_other']);
        if ($text) $descParts[] = "สรรพคุณ: " . trim($text);
    }
    if (!empty($product['spec_name'])) {
        $descParts[] = "ส่วนประกอบ: " . $product['spec_name'];
    }
    $description = implode("\n\n", $descParts) ?: null;
    
    if ($existing) {
        // Update existing by CNY ID
        $sql = "UPDATE {$ITEMS_TABLE} SET 
            sku = ?, barcode = ?, name = ?, description = ?, 
            manufacturer = ?, generic_name = ?, usage_instructions = ?,
            price = ?, unit = ?, stock = ?, image_url = ?, 
            is_active = ?, category_id = ?, extra_data = ?
            WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $sku, $barcode, $name, $description,
            $manufacturer, $genericName, $usageInstructions,
            $price, $unit, $stock, $imageUrl,
            $isActive, $categoryId, $extraData,
            $cnyId
        ]);
        return ['action' => 'updated', 'id' => $cnyId];
    }
    
    // Check if product exists by SKU (in case ID doesn't match but SKU does)
    if ($sku) {
        $stmt = $db->prepare("SELECT id FROM {$ITEMS_TABLE} WHERE sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        $existingBySku = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBySku) {
            // Update existing by SKU and change ID to CNY ID
            $oldId = $existingBySku['id'];
            $sql = "UPDATE {$ITEMS_TABLE} SET 
                id = ?, barcode = ?, name = ?, description = ?, 
                manufacturer = ?, generic_name = ?, usage_instructions = ?,
                price = ?, unit = ?, stock = ?, image_url = ?, 
                is_active = ?, category_id = ?, extra_data = ?
                WHERE sku = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $cnyId, $barcode, $name, $description,
                $manufacturer, $genericName, $usageInstructions,
                $price, $unit, $stock, $imageUrl,
                $isActive, $categoryId, $extraData,
                $sku
            ]);
            return ['action' => 'updated', 'id' => $cnyId, 'old_id' => $oldId, 'matched_by' => 'sku'];
        }
    }
    
    // Insert with specific ID
    $sql = "INSERT INTO {$ITEMS_TABLE} 
        (id, sku, barcode, name, description, manufacturer, generic_name, usage_instructions,
         price, unit, stock, image_url, is_active, category_id, line_account_id, extra_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $cnyId, $sku, $barcode, $name, $description, $manufacturer, $genericName, $usageInstructions,
        $price, $unit, $stock, $imageUrl, $isActive, $categoryId, $lineAccountId, $extraData
    ]);
    return ['action' => 'created', 'id' => $cnyId];
}

// ==================== MAIN ====================

$message = '';
$stats = null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    $stats = ['total' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    
    echo "<pre>";
    echo "🚀 Starting CNY Sync with ID...\n";
    echo "📦 Using table: {$ITEMS_TABLE}\n";
    echo "Limit: {$limit}, Offset: {$offset}\n\n";
    
    // Fetch from CNY API
    echo "📡 Fetching products from CNY API...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://manager.cnypharmacy.com/api/get_product_all',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer 90xcKekelCqCAjmgkpI1saJF6N55eiNexcI4hdcYM2M',
            'Accept: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ API Error: HTTP {$httpCode}\n";
        exit;
    }
    
    $products = json_decode($response, true);
    unset($response); // Free memory
    
    if (!is_array($products)) {
        echo "❌ Invalid API response\n";
        exit;
    }
    
    $totalProducts = count($products);
    echo "✅ Found {$totalProducts} products\n\n";
    
    // Apply limit/offset
    $products = array_slice($products, $offset, $limit);
    $stats['total'] = count($products);
    
    echo "Processing {$stats['total']} products (offset: {$offset})...\n\n";
    
    foreach ($products as $i => $product) {
        $cnyId = $product['id'] ?? 'N/A';
        $sku = $product['sku'] ?? 'N/A';
        $name = mb_substr($product['name'] ?? 'Unknown', 0, 30);
        
        try {
            $result = syncProductWithCnyId($db, $product, $currentBotId);
            
            if ($result['action'] === 'created') {
                $stats['created']++;
                echo "✅ [{$i}] Created ID:{$cnyId} SKU:{$sku} - {$name}\n";
            } elseif ($result['action'] === 'updated') {
                $stats['updated']++;
                $matchedBy = isset($result['matched_by']) ? " (matched by {$result['matched_by']}, old ID: {$result['old_id']})" : "";
                echo "🔄 [{$i}] Updated ID:{$cnyId} SKU:{$sku} - {$name}{$matchedBy}\n";
            } else {
                $stats['skipped']++;
                echo "⏭️ [{$i}] Skipped ID:{$cnyId} - {$result['reason']}\n";
            }
        } catch (Exception $e) {
            $stats['errors'][] = ['id' => $cnyId, 'error' => $e->getMessage()];
            echo "❌ [{$i}] Error ID:{$cnyId} - {$e->getMessage()}\n";
        }
        
        // Free memory
        if ($i % 50 == 0) {
            gc_collect_cycles();
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════\n";
    echo "📊 SYNC COMPLETED\n";
    echo "═══════════════════════════════════════\n";
    echo "Total: {$stats['total']}\n";
    echo "Created: {$stats['created']}\n";
    echo "Updated: {$stats['updated']}\n";
    echo "Skipped: {$stats['skipped']}\n";
    echo "Errors: " . count($stats['errors']) . "\n";
    
    if ($offset + $limit < $totalProducts) {
        $nextOffset = $offset + $limit;
        echo "\n<a href='?run=1&limit={$limit}&offset={$nextOffset}'>→ Next batch (offset: {$nextOffset})</a>\n";
    }
    
    echo "</pre>";
    echo "<p><a href='shop/products.php'>← กลับหน้าสินค้า</a></p>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync CNY Products with ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                🔄 Sync CNY Products with ID
            </h1>
            <p class="text-gray-600 mb-6">
                Sync สินค้าจาก CNY API โดยใช้ ID จาก CNY เป็น ID ในระบบ LINECRM
            </p>
            
            <form method="GET" class="space-y-4">
                <input type="hidden" name="run" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนสินค้าต่อ batch</label>
                    <select name="limit" class="w-full px-4 py-2 border rounded-lg">
                        <option value="10">10 รายการ</option>
                        <option value="50" selected>50 รายการ</option>
                        <option value="100">100 รายการ</option>
                        <option value="200">200 รายการ</option>
                        <option value="500">500 รายการ</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เริ่มจากรายการที่ (offset)</label>
                    <input type="number" name="offset" value="0" min="0" class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <button type="submit" class="w-full px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    🚀 เริ่ม Sync
                </button>
            </form>
            
            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-sm text-blue-700">
                <p><strong>หมายเหตุ:</strong></p>
                <ul class="list-disc ml-4 mt-2 space-y-1">
                    <li>ID สินค้าจะใช้ ID จาก CNY API (เช่น 1580)</li>
                    <li>ถ้า ID มีอยู่แล้ว จะอัพเดทข้อมูล</li>
                    <li>หมวดหมู่จะถูกสร้างอัตโนมัติจาก category</li>
                </ul>
            </div>
        </div>
        
        <p class="mt-4 text-center">
            <a href="shop/products.php" class="text-blue-500 hover:underline">← กลับหน้าสินค้า</a>
        </p>
    </div>
</body>
</html>
