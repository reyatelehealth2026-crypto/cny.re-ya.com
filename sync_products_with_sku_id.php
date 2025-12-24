<?php
/**
 * Sync Products with SKU as ID
 * ใช้ SKU จาก CNY API เป็น ID ของสินค้าในระบบ
 * - เช็ค SKU ก่อน insert/update
 * - ป้องกัน duplicate records
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(300);

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
 * Sync single product - เช็คทั้ง CNY ID และ SKU
 */
function syncProduct($db, $product, $lineAccountId) {
    global $ITEMS_TABLE;
    if (!$ITEMS_TABLE) {
        return ['action' => 'skipped', 'reason' => 'No items table found'];
    }
    
    $cnyId = $product['id'] ?? null;
    $sku = $product['sku'] ?? null;
    
    if (!$cnyId && !$sku) {
        return ['action' => 'skipped', 'reason' => 'No CNY ID or SKU'];
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
    
    // ==================== CHECK EXISTING ====================
    
    // 1. Check by CNY ID first
    $existing = null;
    $matchedBy = null;
    
    if ($cnyId) {
        $stmt = $db->prepare("SELECT id, sku FROM {$ITEMS_TABLE} WHERE id = ? LIMIT 1");
        $stmt->execute([$cnyId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) $matchedBy = 'cny_id';
    }
    
    // 2. Check by SKU if not found by ID
    if (!$existing && $sku) {
        $stmt = $db->prepare("SELECT id, sku FROM {$ITEMS_TABLE} WHERE sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) $matchedBy = 'sku';
    }
    
    // 3. Check by barcode if still not found
    if (!$existing && $barcode) {
        $stmt = $db->prepare("SELECT id, sku FROM {$ITEMS_TABLE} WHERE barcode = ? LIMIT 1");
        $stmt->execute([$barcode]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) $matchedBy = 'barcode';
    }
    
    // ==================== UPDATE OR INSERT ====================
    
    if ($existing) {
        $oldId = $existing['id'];
        
        // Update existing record
        // If matched by SKU/barcode but ID is different, update ID to CNY ID
        if ($matchedBy !== 'cny_id' && $cnyId && $oldId != $cnyId) {
            // Check if CNY ID is already used
            $stmt = $db->prepare("SELECT id FROM {$ITEMS_TABLE} WHERE id = ? LIMIT 1");
            $stmt->execute([$cnyId]);
            $idExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($idExists) {
                // CNY ID already exists, just update by old ID
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
                    $oldId
                ]);
                return ['action' => 'updated', 'id' => $oldId, 'matched_by' => $matchedBy, 'note' => 'CNY ID conflict'];
            } else {
                // Update ID to CNY ID
                $sql = "UPDATE {$ITEMS_TABLE} SET 
                    id = ?, sku = ?, barcode = ?, name = ?, description = ?, 
                    manufacturer = ?, generic_name = ?, usage_instructions = ?,
                    price = ?, unit = ?, stock = ?, image_url = ?, 
                    is_active = ?, category_id = ?, extra_data = ?
                    WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $cnyId, $sku, $barcode, $name, $description,
                    $manufacturer, $genericName, $usageInstructions,
                    $price, $unit, $stock, $imageUrl,
                    $isActive, $categoryId, $extraData,
                    $oldId
                ]);
                return ['action' => 'updated', 'id' => $cnyId, 'old_id' => $oldId, 'matched_by' => $matchedBy];
            }
        } else {
            // Normal update
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
                $oldId
            ]);
            return ['action' => 'updated', 'id' => $oldId, 'matched_by' => $matchedBy];
        }
    }
    
    // Insert new with CNY ID
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

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    $stats = ['total' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    
    echo "<pre>";
    echo "🚀 Starting CNY Sync with SKU Check...\n";
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
    unset($response);
    
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
            $result = syncProduct($db, $product, $currentBotId);
            
            if ($result['action'] === 'created') {
                $stats['created']++;
                echo "✅ [{$i}] Created ID:{$result['id']} SKU:{$sku} - {$name}\n";
            } elseif ($result['action'] === 'updated') {
                $stats['updated']++;
                $extra = '';
                if (isset($result['old_id'])) {
                    $extra = " (ID changed: {$result['old_id']} → {$result['id']})";
                } elseif (isset($result['matched_by'])) {
                    $extra = " (matched by {$result['matched_by']})";
                }
                echo "🔄 [{$i}] Updated ID:{$result['id']} SKU:{$sku} - {$name}{$extra}\n";
            } else {
                $stats['skipped']++;
                echo "⏭️ [{$i}] Skipped - {$result['reason']}\n";
            }
        } catch (Exception $e) {
            $stats['errors'][] = ['id' => $cnyId, 'sku' => $sku, 'error' => $e->getMessage()];
            echo "❌ [{$i}] Error ID:{$cnyId} SKU:{$sku} - {$e->getMessage()}\n";
        }
        
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
    <title>Sync Products with SKU Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                🔄 Sync Products with SKU Check
            </h1>
            <p class="text-gray-600 mb-6">
                Sync สินค้าจาก CNY API โดยเช็คทั้ง CNY ID และ SKU เพื่อป้องกัน duplicate
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
                        <option value="1000">1000 รายการ</option>
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
                <p><strong>การทำงาน:</strong></p>
                <ul class="list-disc ml-4 mt-2 space-y-1">
                    <li>เช็ค CNY ID ก่อน - ถ้าเจอก็ update</li>
                    <li>ถ้าไม่เจอ ID ก็เช็ค SKU - ถ้าเจอก็ update และเปลี่ยน ID เป็น CNY ID</li>
                    <li>ถ้าไม่เจอ SKU ก็เช็ค Barcode</li>
                    <li>ถ้าไม่เจอเลยก็ create ใหม่ด้วย CNY ID</li>
                </ul>
            </div>
            
            <div class="mt-4 p-4 bg-yellow-50 rounded-lg text-sm text-yellow-700">
                <p><strong>⚠️ หมายเหตุ:</strong></p>
                <ul class="list-disc ml-4 mt-2 space-y-1">
                    <li>ถ้ามี duplicate records อยู่แล้ว ควรลบออกก่อน sync</li>
                    <li>Script นี้จะไม่ลบ records ที่ซ้ำ แต่จะ update ตัวที่เจอก่อน</li>
                </ul>
            </div>
        </div>
        
        <p class="mt-4 text-center">
            <a href="shop/products.php" class="text-blue-500 hover:underline">← กลับหน้าสินค้า</a>
            <span class="mx-2">|</span>
            <a href="sync-dashboard.php" class="text-blue-500 hover:underline">Sync Dashboard</a>
        </p>
    </div>
</body>
</html>