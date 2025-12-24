<?php
/**
 * Product Detail View
 * แสดงรายละเอียดสินค้าทั้งหมดที่ import เข้ามา
 */
require_once '../config/config.php';
require_once '../config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'รายละเอียดสินค้า';
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// Get product ID
$productId = $_GET['id'] ?? 0;
if (!$productId) {
    header('Location: products.php');
    exit;
}

// Use products table
$productsTable = 'products';

// Check available columns
$columns = [];
try {
    $stmt = $db->query("SHOW COLUMNS FROM {$productsTable}");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
} catch (Exception $e) {}

// Get product (แสดงทั้งหมด ไม่ filter ตาม line_account_id)
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
    FROM {$productsTable} p 
    LEFT JOIN product_categories c ON p.category_id = c.id 
    WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

$pageTitle = $product['name'];

require_once '../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="products.php" class="p-2 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($product['name']) ?></h1>
                <?php if (!empty($product['sku'])): ?>
                <p class="text-sm text-gray-500">SKU: <?= htmlspecialchars($product['sku']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="products.php?edit=<?= $product['id'] ?>" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                <i class="fas fa-edit mr-2"></i>แก้ไข
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Image -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="aspect-square bg-gray-100">
                    <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" class="w-full h-full object-contain" onerror="this.src='https://via.placeholder.com/400?text=No+Image'">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <i class="fas fa-image text-6xl"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status -->
                <div class="p-4 border-t">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">สถานะ:</span>
                        <?php if ($product['is_active']): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i>เปิดขาย
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm font-medium">
                            <i class="fas fa-pause-circle mr-1"></i>ปิดการขาย
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Price Card -->
            <div class="bg-white rounded-xl shadow p-4 mt-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">💰 ราคาขาย</h3>
                <div class="space-y-2">
                    <?php if (!empty($product['sale_price'])): ?>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-red-500">฿<?= number_format($product['sale_price'], 2) ?></span>
                        <span class="text-lg text-gray-400 line-through">฿<?= number_format($product['price'], 2) ?></span>
                    </div>
                    <div class="text-sm text-red-500 bg-red-50 px-2 py-1 rounded inline-block">
                        <i class="fas fa-tag mr-1"></i>ลด <?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?>%
                    </div>
                    <?php else: ?>
                    <div class="text-3xl font-bold text-green-600">฿<?= number_format($product['price'], 2) ?></div>
                    <?php endif; ?>
                    <?php if (in_array('unit', $columns) && !empty($product['unit'])): ?>
                    <div class="text-sm text-gray-500">ต่อ <?= htmlspecialchars($product['unit']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php 
            // Parse extra_data for price tiers
            $extraDataPrices = [];
            if (in_array('extra_data', $columns) && !empty($product['extra_data'])) {
                $extraDataPrices = json_decode($product['extra_data'], true) ?: [];
            }
            ?>
            
            <!-- All Price Tiers (if available) -->
            <?php if (!empty($extraDataPrices['all_prices']) && count($extraDataPrices['all_prices']) > 1): ?>
            <div class="bg-white rounded-xl shadow p-4 mt-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">
                    <i class="fas fa-tags text-purple-500 mr-1"></i>ราคาตามกลุ่มลูกค้า (<?= count($extraDataPrices['all_prices']) ?> ระดับ)
                </h3>
                <div class="space-y-2">
                    <?php foreach ($extraDataPrices['all_prices'] as $priceItem): ?>
                    <?php 
                        $priceGroup = $priceItem['customer_group'] ?? $priceItem['price_name'] ?? '-';
                        $priceValue = floatval($priceItem['price']);
                        $priceUnit = $priceItem['unit'] ?? '';
                        $isEnabled = ($priceItem['enable'] ?? '1') == '1';
                    ?>
                    <div class="flex items-center justify-between p-2 rounded <?= $isEnabled ? 'bg-gray-50' : 'bg-gray-100 opacity-50' ?>">
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded"><?= htmlspecialchars($priceGroup) ?></span>
                            <?php if (!$isEnabled): ?>
                            <span class="text-xs text-gray-400">(ปิดใช้งาน)</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-green-600">฿<?= number_format($priceValue, 2) ?></span>
                            <?php if ($priceUnit): ?>
                            <span class="text-xs text-gray-400">/<?= htmlspecialchars($priceUnit) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stock Card -->
            <div class="bg-white rounded-xl shadow p-4 mt-4">
                <h3 class="text-sm font-medium text-gray-500 mb-3">📦 สต็อกสินค้า</h3>
                <div class="flex items-center gap-3">
                    <?php if ($product['stock'] <= 0): ?>
                    <span class="text-3xl font-bold text-red-500">0</span>
                    <span class="px-2 py-1 bg-red-100 text-red-600 text-sm rounded">สินค้าหมด</span>
                    <?php elseif ($product['stock'] <= 5): ?>
                    <span class="text-3xl font-bold text-yellow-500"><?= number_format($product['stock']) ?></span>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-600 text-sm rounded">ใกล้หมด</span>
                    <?php else: ?>
                    <span class="text-3xl font-bold text-green-600"><?= number_format($product['stock']) ?></span>
                    <?php endif; ?>
                    <?php if (in_array('unit', $columns) && !empty($product['unit'])): ?>
                    <span class="text-gray-500"><?= htmlspecialchars($product['unit']) ?></span>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Check for incoming stock
                $extraDataStock = [];
                if (in_array('extra_data', $columns) && !empty($product['extra_data'])) {
                    $extraDataStock = json_decode($product['extra_data'], true) ?: [];
                }
                ?>
                <?php if (!empty($extraDataStock['qty_incoming']) && $extraDataStock['qty_incoming'] > 0): ?>
                <div class="mt-3 pt-3 border-t">
                    <div class="flex items-center gap-2 text-orange-600">
                        <i class="fas fa-truck"></i>
                        <span class="text-sm">สินค้ากำลังเข้า:</span>
                        <span class="font-bold">+<?= number_format($extraDataStock['qty_incoming']) ?></span>
                        <?php if (in_array('unit', $columns) && !empty($product['unit'])): ?>
                        <span class="text-sm"><?= htmlspecialchars($product['unit']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right: Details -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow">
                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-info-circle text-blue-500 mr-2"></i>ข้อมูลพื้นฐาน</h3>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            <?php if (in_array('sku', $columns) && !empty($product['sku'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500 w-1/3">รหัสสินค้า (SKU)</td>
                                <td class="py-2 font-medium font-mono"><?= htmlspecialchars($product['sku']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (in_array('barcode', $columns) && !empty($product['barcode'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">บาร์โค้ด</td>
                                <td class="py-2 font-medium font-mono"><?= htmlspecialchars($product['barcode']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <td class="py-2 text-gray-500">ชื่อสินค้า</td>
                                <td class="py-2 font-medium"><?= htmlspecialchars($product['name']) ?></td>
                            </tr>
                            
                            <?php if (in_array('generic_name', $columns) && !empty($product['generic_name'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">ชื่อสามัญยา</td>
                                <td class="py-2 text-blue-600"><?= htmlspecialchars($product['generic_name']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (in_array('manufacturer', $columns) && !empty($product['manufacturer'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">ผู้ผลิต/จัดจำหน่าย</td>
                                <td class="py-2"><?= htmlspecialchars($product['manufacturer']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <td class="py-2 text-gray-500">หมวดหมู่</td>
                                <td class="py-2"><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                            </tr>
                            
                            <?php if (in_array('unit', $columns) && !empty($product['unit'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">หน่วยนับ</td>
                                <td class="py-2"><?= htmlspecialchars($product['unit']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (in_array('item_type', $columns)): ?>
                            <tr>
                                <td class="py-2 text-gray-500">ประเภทสินค้า</td>
                                <td class="py-2">
                                    <?php
                                    $types = [
                                        'physical' => '📦 สินค้าจัดส่ง',
                                        'digital' => '🎮 สินค้าดิจิทัล',
                                        'service' => '💆 บริการ',
                                        'booking' => '📅 จองคิว',
                                        'content' => '📚 เนื้อหา'
                                    ];
                                    echo $types[$product['item_type'] ?? 'physical'] ?? $product['item_type'];
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <!-- Usage Instructions -->
            <?php if (in_array('usage_instructions', $columns) && !empty($product['usage_instructions'])): ?>
            <div class="bg-white rounded-xl shadow">
                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-prescription-bottle-alt text-green-500 mr-2"></i>วิธีใช้ / ขนาดรับประทาน</h3>
                </div>
                <div class="p-4">
                    <p class="text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($product['usage_instructions'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Description -->
            <?php if (!empty($product['description'])): ?>
            <div class="bg-white rounded-xl shadow">
                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-align-left text-purple-500 mr-2"></i>รายละเอียดสินค้า</h3>
                </div>
                <div class="p-4">
                    <p class="text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Extra Data (from API sync) -->
            <?php if (in_array('extra_data', $columns) && !empty($product['extra_data'])): ?>
            <?php $extraData = json_decode($product['extra_data'], true); ?>
            <?php if ($extraData): ?>
            <div class="bg-white rounded-xl shadow">
                <div class="px-4 py-3 border-b bg-gradient-to-r from-orange-50 to-yellow-50 rounded-t-xl">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-database text-orange-500 mr-2"></i>ข้อมูลจาก CNY Pharmacy API</h3>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            <?php if (!empty($extraData['cny_id'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500 w-1/3">CNY Product ID</td>
                                <td class="py-2 font-mono bg-gray-50 px-2 rounded"><?= htmlspecialchars($extraData['cny_id']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($extraData['name_en'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">ชื่อภาษาอังกฤษ</td>
                                <td class="py-2"><?= htmlspecialchars($extraData['name_en']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($extraData['properties'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500 align-top">สรรพคุณ</td>
                                <td class="py-2">
                                    <div class="bg-green-50 p-2 rounded text-green-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($extraData['properties'])) ?></div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($extraData['hashtag'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">Hashtag</td>
                                <td class="py-2">
                                    <?php 
                                    $hashtags = is_array($extraData['hashtag']) ? $extraData['hashtag'] : explode(',', $extraData['hashtag']);
                                    foreach ($hashtags as $tag): 
                                        $tag = trim($tag);
                                        if ($tag):
                                    ?>
                                    <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded mr-1 mb-1">#<?= htmlspecialchars($tag) ?></span>
                                    <?php endif; endforeach; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- All Prices - Moved to separate card above -->
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Timestamps -->
            <div class="bg-white rounded-xl shadow">
                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-clock text-gray-500 mr-2"></i>ข้อมูลระบบ</h3>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            <tr>
                                <td class="py-2 text-gray-500 w-1/3">ID</td>
                                <td class="py-2 font-mono"><?= $product['id'] ?></td>
                            </tr>
                            <?php if (!empty($product['created_at'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">สร้างเมื่อ</td>
                                <td class="py-2"><?= date('d/m/Y H:i', strtotime($product['created_at'])) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($product['updated_at'])): ?>
                            <tr>
                                <td class="py-2 text-gray-500">แก้ไขล่าสุด</td>
                                <td class="py-2"><?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
