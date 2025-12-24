<?php
/**
 * Export Products - ส่งออกสินค้าจากตาราง business_items
 * รองรับ CSV และ Excel format
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_SESSION['line_account_id'] ?? $_SESSION['current_bot_id'] ?? null;

// Check which columns exist
$hasIsFeatured = false;
$hasIsBestseller = false;
try {
    $cols = $db->query("SHOW COLUMNS FROM business_items")->fetchAll(PDO::FETCH_COLUMN);
    $hasIsFeatured = in_array('is_featured', $cols);
    $hasIsBestseller = in_array('is_bestseller', $cols);
} catch (Exception $e) {}

// Get export format
$format = $_GET['format'] ?? 'csv';
$categoryId = $_GET['category'] ?? '';
$featured = $_GET['featured'] ?? '';
$bestseller = $_GET['bestseller'] ?? '';

// Build query
$where = ["1=1"];
$params = [];

if ($lineAccountId) {
    $where[] = "(bi.line_account_id = ? OR bi.line_account_id IS NULL)";
    $params[] = $lineAccountId;
}

if ($categoryId) {
    $where[] = "bi.category_id = ?";
    $params[] = $categoryId;
}

if ($featured === '1' && $hasIsFeatured) {
    $where[] = "COALESCE(bi.is_featured, 0) = 1";
}

if ($bestseller === '1' && $hasIsBestseller) {
    $where[] = "COALESCE(bi.is_bestseller, 0) = 1";
}

$whereClause = implode(' AND ', $where);

// Build dynamic select
$featuredCol = $hasIsFeatured ? "COALESCE(bi.is_featured, 0)" : "0";
$bestsellerCol = $hasIsBestseller ? "COALESCE(bi.is_bestseller, 0)" : "0";

// Get products with category name
$sql = "SELECT 
    bi.id,
    bi.sku,
    bi.barcode,
    bi.name,
    bi.generic_name,
    bi.description,
    bi.price,
    bi.sale_price,
    bi.stock,
    bi.unit,
    bi.manufacturer,
    bi.usage_instructions,
    bi.image_url,
    COALESCE(bi.is_active, 1) as is_active,
    $featuredCol as is_featured,
    $bestsellerCol as is_bestseller,
    ic.name as category_name,
    ic.cny_code as category_code,
    bi.created_at,
    bi.updated_at
FROM business_items bi
LEFT JOIN item_categories ic ON bi.category_id = ic.id
WHERE $whereClause
ORDER BY bi.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate filename
$filename = 'products_' . date('Y-m-d_His');

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // BOM for Excel UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'ID',
        'SKU',
        'Barcode',
        'ชื่อสินค้า',
        'ชื่อสามัญ',
        'รายละเอียด',
        'ราคา',
        'ราคาลด',
        'คงเหลือ',
        'หน่วย',
        'ผู้ผลิต',
        'วิธีใช้',
        'รูปภาพ',
        'เปิดใช้งาน',
        'สินค้าเด่น',
        'Best Seller',
        'หมวดหมู่',
        'รหัสหมวด',
        'วันที่สร้าง',
        'วันที่แก้ไข'
    ]);
    
    // Data rows
    foreach ($products as $p) {
        fputcsv($output, [
            $p['id'],
            $p['sku'],
            $p['barcode'],
            $p['name'],
            $p['generic_name'],
            $p['description'],
            $p['price'],
            $p['sale_price'],
            $p['stock'],
            $p['unit'],
            $p['manufacturer'],
            $p['usage_instructions'],
            $p['image_url'],
            $p['is_active'] ? 'Yes' : 'No',
            $p['is_featured'] ? 'Yes' : 'No',
            $p['is_bestseller'] ? 'Yes' : 'No',
            $p['category_name'],
            $p['category_code'],
            $p['created_at'],
            $p['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
    
} else {
    // Show export page with options
    $pageTitle = 'ส่งออกสินค้า';
    
    // Get categories for filter
    $categories = [];
    try {
        $stmt = $db->query("SELECT id, name, cny_code FROM item_categories WHERE is_active = 1 ORDER BY id");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    // Count products
    $totalProducts = count($products);
    
    require_once __DIR__ . '/../includes/header.php';
    ?>
    
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-file-export text-green-600 mr-2"></i>ส่งออกสินค้า
            </h2>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    พบสินค้าทั้งหมด <strong><?= number_format($totalProducts) ?></strong> รายการ
                </p>
            </div>
            
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['cny_code'] ? $cat['cny_code'] . ' - ' : '') ?><?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ตัวกรองพิเศษ</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="featured" value="1" <?= $featured === '1' ? 'checked' : '' ?> class="mr-2">
                                <span class="text-sm">⭐ สินค้าเด่น</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="bestseller" value="1" <?= $bestseller === '1' ? 'checked' : '' ?> class="mr-2">
                                <span class="text-sm">🔥 Best Seller</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-filter mr-2"></i>กรองข้อมูล
                    </button>
                    
                    <button type="submit" name="format" value="csv" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-file-csv mr-2"></i>ดาวน์โหลด CSV
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Preview -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-800">ตัวอย่างข้อมูล (10 รายการแรก)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">SKU</th>
                            <th class="px-3 py-2 text-left">ชื่อสินค้า</th>
                            <th class="px-3 py-2 text-right">ราคา</th>
                            <th class="px-3 py-2 text-center">คงเหลือ</th>
                            <th class="px-3 py-2 text-left">หมวดหมู่</th>
                            <th class="px-3 py-2 text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($products, 0, 10) as $p): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2"><?= $p['id'] ?></td>
                            <td class="px-3 py-2 font-mono text-xs"><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                            <td class="px-3 py-2 max-w-xs truncate"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="px-3 py-2 text-right">
                                <?php if ($p['sale_price']): ?>
                                <span class="text-red-600">฿<?= number_format($p['sale_price']) ?></span>
                                <?php else: ?>
                                ฿<?= number_format($p['price']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-center"><?= number_format($p['stock'] ?? 0) ?></td>
                            <td class="px-3 py-2 text-xs"><?= htmlspecialchars($p['category_code'] ?? '-') ?></td>
                            <td class="px-3 py-2 text-center">
                                <?php if ($p['is_featured']): ?><span class="text-yellow-500">⭐</span><?php endif; ?>
                                <?php if ($p['is_bestseller']): ?><span class="text-red-500">🔥</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalProducts > 10): ?>
            <div class="p-3 bg-gray-50 text-center text-sm text-gray-500">
                ... และอีก <?= number_format($totalProducts - 10) ?> รายการ
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    require_once __DIR__ . '/../includes/footer.php';
}
