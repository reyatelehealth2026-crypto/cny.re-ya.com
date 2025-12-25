<?php
/**
 * Sync Categories from Manufacturer
 * สร้างหมวดหมู่อัตโนมัติจากผู้ผลิต/ผู้จัดจำหน่ายที่พบในสินค้า
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// Auto-detect tables
function getItemsTable($db) {
    // Use products table as primary
    return 'products';
}

function getCategoriesTable($db) {
    try { $db->query("SELECT 1 FROM item_categories LIMIT 1"); return 'item_categories'; } catch (Exception $e) {}
    try { $db->query("SELECT 1 FROM product_categories LIMIT 1"); return 'product_categories'; } catch (Exception $e) {}
    return null;
}

$ITEMS_TABLE = getItemsTable($db);
$CATEGORIES_TABLE = getCategoriesTable($db);

// Get stats
$stats = [
    'manufacturers' => [],
    'categories_created' => 0,
    'products_updated' => 0
];

// Get all unique manufacturers
$manufacturers = [];
try {
    $stmt = $db->query("SELECT DISTINCT manufacturer FROM {$ITEMS_TABLE} WHERE manufacturer IS NOT NULL AND manufacturer != '' ORDER BY manufacturer");
    $manufacturers = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Get existing categories
$existingCategories = [];
try {
    $stmt = $db->query("SELECT id, name, manufacturer_code FROM {$CATEGORIES_TABLE}");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCategories[strtoupper($row['name'])] = $row['id'];
        if (!empty($row['manufacturer_code'])) {
            $existingCategories[strtoupper($row['manufacturer_code'])] = $row['id'];
        }
    }
} catch (Exception $e) {}

// Ensure manufacturer_code column exists
try {
    $stmt = $db->query("SHOW COLUMNS FROM {$CATEGORIES_TABLE} LIKE 'manufacturer_code'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE {$CATEGORIES_TABLE} ADD COLUMN manufacturer_code VARCHAR(100) NULL AFTER name");
    }
} catch (Exception $e) {}

// Count products per manufacturer
$manufacturerCounts = [];
try {
    $stmt = $db->query("SELECT manufacturer, COUNT(*) as cnt FROM {$ITEMS_TABLE} WHERE manufacturer IS NOT NULL AND manufacturer != '' GROUP BY manufacturer ORDER BY cnt DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $manufacturerCounts[$row['manufacturer']] = $row['cnt'];
    }
} catch (Exception $e) {}

$stats['manufacturers'] = $manufacturerCounts;

// Process action
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_categories') {
        // Create categories from selected manufacturers
        $selected = $_POST['manufacturers'] ?? [];
        $created = 0;
        $updated = 0;
        
        foreach ($selected as $manufacturer) {
            $manufacturer = trim($manufacturer);
            if (empty($manufacturer)) continue;
            
            $upperName = strtoupper($manufacturer);
            
            // Check if category exists
            if (isset($existingCategories[$upperName])) {
                // Update existing category with manufacturer_code
                $catId = $existingCategories[$upperName];
                $stmt = $db->prepare("UPDATE {$CATEGORIES_TABLE} SET manufacturer_code = ? WHERE id = ? AND (manufacturer_code IS NULL OR manufacturer_code = '')");
                $stmt->execute([$manufacturer, $catId]);
                continue;
            }
            
            // Create new category
            try {
                $stmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$CATEGORIES_TABLE}");
                $nextOrder = $stmt->fetchColumn();
                
                $stmt = $db->prepare("INSERT INTO {$CATEGORIES_TABLE} (line_account_id, name, manufacturer_code, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$currentBotId, $manufacturer, $manufacturer, $nextOrder]);
                $created++;
                
                $existingCategories[$upperName] = $db->lastInsertId();
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
        
        $stats['categories_created'] = $created;
        $message = "✅ สร้างหมวดหมู่ใหม่ {$created} รายการ";
    }
    
    if ($action === 'assign_categories') {
        // Assign products to categories based on manufacturer
        $assigned = 0;
        
        foreach ($manufacturerCounts as $manufacturer => $count) {
            $upperName = strtoupper($manufacturer);
            if (!isset($existingCategories[$upperName])) continue;
            
            $catId = $existingCategories[$upperName];
            
            // Update products with this manufacturer to use this category
            $stmt = $db->prepare("UPDATE {$ITEMS_TABLE} SET category_id = ? WHERE manufacturer = ? AND (category_id IS NULL OR category_id = 0)");
            $stmt->execute([$catId, $manufacturer]);
            $assigned += $stmt->rowCount();
        }
        
        $stats['products_updated'] = $assigned;
        $message = "✅ อัพเดทหมวดหมู่สินค้า {$assigned} รายการ";
    }
    
    if ($action === 'create_and_assign') {
        // Create categories and assign in one go
        $selected = $_POST['manufacturers'] ?? [];
        $created = 0;
        $assigned = 0;
        
        foreach ($selected as $manufacturer) {
            $manufacturer = trim($manufacturer);
            if (empty($manufacturer)) continue;
            
            $upperName = strtoupper($manufacturer);
            $catId = null;
            
            // Check if category exists
            if (isset($existingCategories[$upperName])) {
                $catId = $existingCategories[$upperName];
            } else {
                // Create new category
                try {
                    $stmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$CATEGORIES_TABLE}");
                    $nextOrder = $stmt->fetchColumn();
                    
                    $stmt = $db->prepare("INSERT INTO {$CATEGORIES_TABLE} (line_account_id, name, manufacturer_code, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([$currentBotId, $manufacturer, $manufacturer, $nextOrder]);
                    $catId = $db->lastInsertId();
                    $created++;
                    
                    $existingCategories[$upperName] = $catId;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            // Assign products
            if ($catId) {
                $stmt = $db->prepare("UPDATE {$ITEMS_TABLE} SET category_id = ? WHERE manufacturer = ?");
                $stmt->execute([$catId, $manufacturer]);
                $assigned += $stmt->rowCount();
            }
        }
        
        $stats['categories_created'] = $created;
        $stats['products_updated'] = $assigned;
        $message = "✅ สร้างหมวดหมู่ {$created} รายการ, อัพเดทสินค้า {$assigned} รายการ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Categories from Manufacturer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-tags text-green-500 mr-2"></i>
                สร้างหมวดหมู่จากผู้ผลิต/ผู้จัดจำหน่าย
            </h1>
            <p class="text-gray-600">สร้างหมวดหมู่อัตโนมัติจาก manufacturer ที่พบในสินค้า</p>
            
            <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
                <div class="bg-blue-50 rounded-lg p-3">
                    <div class="text-blue-600 font-semibold">Items Table</div>
                    <div class="text-blue-800 font-mono"><?= $ITEMS_TABLE ?></div>
                </div>
                <div class="bg-green-50 rounded-lg p-3">
                    <div class="text-green-600 font-semibold">Categories Table</div>
                    <div class="text-green-800 font-mono"><?= $CATEGORIES_TABLE ?></div>
                </div>
                <div class="bg-purple-50 rounded-lg p-3">
                    <div class="text-purple-600 font-semibold">พบผู้ผลิต</div>
                    <div class="text-purple-800 font-bold text-xl"><?= count($manufacturers) ?> ราย</div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="mainForm">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-industry mr-2 text-orange-500"></i>
                        รายชื่อผู้ผลิต/ผู้จัดจำหน่าย (<?= count($manufacturerCounts) ?>)
                    </h2>
                    <div class="flex gap-2">
                        <button type="button" onclick="selectAll()" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                            <i class="fas fa-check-double mr-1"></i>เลือกทั้งหมด
                        </button>
                        <button type="button" onclick="selectNone()" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                            <i class="fas fa-times mr-1"></i>ไม่เลือก
                        </button>
                        <button type="button" onclick="selectNew()" class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                            <i class="fas fa-plus mr-1"></i>เลือกเฉพาะใหม่
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto p-2">
                    <?php foreach ($manufacturerCounts as $manufacturer => $count): 
                        $exists = isset($existingCategories[strtoupper($manufacturer)]);
                    ?>
                    <label class="flex items-center p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition <?= $exists ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200' ?>">
                        <input type="checkbox" name="manufacturers[]" value="<?= htmlspecialchars($manufacturer) ?>" 
                               class="manufacturer-checkbox w-4 h-4 text-green-600 rounded" 
                               data-exists="<?= $exists ? '1' : '0' ?>"
                               <?= !$exists ? 'checked' : '' ?>>
                        <div class="ml-3 flex-1 min-w-0">
                            <div class="font-medium text-gray-800 truncate"><?= htmlspecialchars($manufacturer) ?></div>
                            <div class="text-xs text-gray-500"><?= number_format($count) ?> สินค้า</div>
                        </div>
                        <?php if ($exists): ?>
                        <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-600 text-xs rounded-full">มีแล้ว</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($manufacturerCounts)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>ไม่พบข้อมูลผู้ผลิตในสินค้า</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-cogs mr-2 text-blue-500"></i>
                    เลือกการดำเนินการ
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button type="submit" name="action" value="create_categories" 
                            class="p-4 border-2 border-blue-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 transition text-left">
                        <div class="text-blue-600 font-semibold mb-1">
                            <i class="fas fa-folder-plus mr-2"></i>สร้างหมวดหมู่
                        </div>
                        <div class="text-sm text-gray-600">สร้างหมวดหมู่ใหม่จากผู้ผลิตที่เลือก (ไม่อัพเดทสินค้า)</div>
                    </button>
                    
                    <button type="submit" name="action" value="assign_categories" 
                            class="p-4 border-2 border-orange-200 rounded-xl hover:border-orange-400 hover:bg-orange-50 transition text-left">
                        <div class="text-orange-600 font-semibold mb-1">
                            <i class="fas fa-link mr-2"></i>จัดหมวดหมู่สินค้า
                        </div>
                        <div class="text-sm text-gray-600">อัพเดทสินค้าให้อยู่ในหมวดหมู่ตามผู้ผลิต (เฉพาะหมวดหมู่ที่มีอยู่)</div>
                    </button>
                    
                    <button type="submit" name="action" value="create_and_assign" 
                            class="p-4 border-2 border-green-300 bg-green-50 rounded-xl hover:border-green-500 hover:bg-green-100 transition text-left">
                        <div class="text-green-600 font-semibold mb-1">
                            <i class="fas fa-magic mr-2"></i>สร้าง + จัดหมวดหมู่
                        </div>
                        <div class="text-sm text-gray-600">สร้างหมวดหมู่ใหม่และอัพเดทสินค้าทั้งหมดในครั้งเดียว</div>
                    </button>
                </div>
            </div>
        </form>
        
        <div class="mt-6 text-center text-gray-500 text-sm">
            <a href="shop/categories.php" class="text-green-600 hover:underline">
                <i class="fas fa-folder mr-1"></i>จัดการหมวดหมู่
            </a>
            <span class="mx-2">|</span>
            <a href="shop/products.php" class="text-green-600 hover:underline">
                <i class="fas fa-box mr-1"></i>จัดการสินค้า
            </a>
            <span class="mx-2">|</span>
            <a href="sync-dashboard.php" class="text-green-600 hover:underline">
                <i class="fas fa-sync mr-1"></i>Sync Dashboard
            </a>
        </div>
    </div>
    
    <script>
    function selectAll() {
        document.querySelectorAll('.manufacturer-checkbox').forEach(cb => cb.checked = true);
    }
    
    function selectNone() {
        document.querySelectorAll('.manufacturer-checkbox').forEach(cb => cb.checked = false);
    }
    
    function selectNew() {
        document.querySelectorAll('.manufacturer-checkbox').forEach(cb => {
            cb.checked = cb.dataset.exists === '0';
        });
    }
    </script>
</body>
</html>
