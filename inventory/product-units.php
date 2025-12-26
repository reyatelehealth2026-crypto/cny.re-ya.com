<?php
/**
 * Product Units Management - จัดการหน่วยสินค้า (Multi-Unit)
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_SESSION['current_bot_id'] ?? null;
$pageTitle = 'จัดการหน่วยสินค้า';

// Check if table exists
$tableExists = false;
try {
    $db->query("SELECT 1 FROM product_units LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {}

// Get products with units
$products = [];
$productUnits = [];
if ($tableExists) {
    // Check which columns exist in business_items
    $cols = $db->query("SHOW COLUMNS FROM business_items")->fetchAll(PDO::FETCH_COLUMN);
    $hasCostPrice = in_array('cost_price', $cols);
    $hasUnit = in_array('unit', $cols);
    
    // Build dynamic query
    $selectCols = "id, name, sku";
    $selectCols .= $hasUnit ? ", unit" : ", 'ชิ้น' as unit";
    $selectCols .= $hasCostPrice ? ", cost_price" : ", 0 as cost_price";
    $selectCols .= ", price";
    
    // Get all products
    $stmt = $db->prepare("SELECT {$selectCols} FROM business_items WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all units grouped by product
    $stmt = $db->query("SELECT * FROM product_units WHERE is_active = 1 ORDER BY product_id, factor");
    $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allUnits as $u) {
        if (!isset($productUnits[$u['product_id']])) {
            $productUnits[$u['product_id']] = [];
        }
        $productUnits[$u['product_id']][] = $u;
    }
}

require_once __DIR__ . '/../includes/header.php';

if (!$tableExists):
?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
    <i class="fas fa-database text-yellow-500 text-4xl mb-3"></i>
    <h3 class="text-lg font-semibold text-yellow-700 mb-2">ยังไม่ได้ติดตั้งระบบ Multi-Unit</h3>
    <p class="text-yellow-600 mb-4">กรุณา run migration script เพื่อสร้างตาราง</p>
    <div class="bg-white rounded-lg p-4 text-left max-w-lg mx-auto">
        <code class="text-xs bg-gray-100 p-2 rounded block">database/migration_product_units.sql</code>
    </div>
</div>
<?php else: ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-600">กำหนดหน่วยสินค้าหลายหน่วยต่อสินค้า เช่น ขวด, โหล, กล่อง</p>
        </div>
    </div>

    <!-- Products List -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-4 border-b">
            <input type="text" id="searchProduct" placeholder="ค้นหาสินค้า..." 
                   class="w-full px-4 py-2 border rounded-lg" onkeyup="filterProducts()">
        </div>
        <div class="divide-y" id="productList">
            <?php foreach ($products as $p): 
                $units = $productUnits[$p['id']] ?? [];
            ?>
            <div class="product-item p-4 hover:bg-gray-50" data-name="<?= strtolower($p['name'] . ' ' . $p['sku']) ?>">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-medium"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="text-sm text-gray-500">SKU: <?= htmlspecialchars($p['sku'] ?? '-') ?> | หน่วยหลัก: <?= htmlspecialchars($p['unit'] ?? 'ชิ้น') ?></p>
                    </div>
                    <button onclick="openUnitModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', '<?= htmlspecialchars($p['unit'] ?? 'ชิ้น') ?>', <?= $p['cost_price'] ?? 0 ?>, <?= $p['price'] ?? 0 ?>)" 
                            class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm">
                        <i class="fas fa-plus mr-1"></i>เพิ่มหน่วย
                    </button>
                </div>
                
                <?php if (!empty($units)): ?>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <?php foreach ($units as $u): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm">
                        <div>
                            <span class="font-medium"><?= htmlspecialchars($u['unit_name']) ?></span>
                            <?php if ($u['is_base_unit']): ?>
                            <span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-xs">หลัก</span>
                            <?php endif; ?>
                            <span class="text-gray-500 ml-2">x<?= $u['factor'] ?></span>
                            <?php if ($u['cost_price']): ?>
                            <span class="text-gray-500 ml-2">฿<?= number_format($u['cost_price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="editUnit(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-1 text-blue-600 hover:bg-blue-50 rounded">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteUnit(<?= $u['id'] ?>)" class="p-1 text-red-600 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="mt-2 text-sm text-gray-400">ยังไม่มีหน่วยเพิ่มเติม</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Unit Modal -->
<div id="unitModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold" id="modalTitle">เพิ่มหน่วยสินค้า</h3>
            <button onclick="closeUnitModal()" class="p-2 hover:bg-gray-100 rounded"><i class="fas fa-times"></i></button>
        </div>
        <form id="unitForm" class="p-4 space-y-4">
            <input type="hidden" name="product_id" id="productId">
            <input type="hidden" name="unit_id" id="unitId">
            
            <div>
                <label class="block text-sm font-medium mb-1">สินค้า</label>
                <input type="text" id="productName" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-50">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อหน่วย *</label>
                    <input type="text" name="unit_name" required class="w-full px-3 py-2 border rounded-lg" placeholder="เช่น โหล, กล่อง">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">รหัสหน่วย</label>
                    <input type="text" name="unit_code" class="w-full px-3 py-2 border rounded-lg" placeholder="เช่น DOZ, BOX">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">ตัวคูณ (Factor) *</label>
                <input type="number" name="factor" min="0.0001" step="0.0001" value="1" required class="w-full px-3 py-2 border rounded-lg">
                <p class="text-xs text-gray-500 mt-1">เช่น โหล = 12, กล่อง 24 ชิ้น = 24</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ราคาทุน/หน่วย</label>
                    <input type="number" name="cost_price" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ราคาขาย/หน่วย</label>
                    <input type="number" name="sale_price" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">บาร์โค้ด</label>
                <input type="text" name="barcode" class="w-full px-3 py-2 border rounded-lg">
            </div>
            
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_base_unit" class="w-4 h-4 rounded">
                    <span class="text-sm">หน่วยหลัก</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_purchase_unit" checked class="w-4 h-4 rounded">
                    <span class="text-sm">ใช้สั่งซื้อ</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_sale_unit" checked class="w-4 h-4 rounded">
                    <span class="text-sm">ใช้ขาย</span>
                </label>
            </div>
            
            <div class="flex gap-2 pt-4 border-t">
                <button type="button" onclick="closeUnitModal()" class="flex-1 px-4 py-2 bg-gray-200 rounded-lg">ยกเลิก</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterProducts() {
    const search = document.getElementById('searchProduct').value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        const name = item.dataset.name;
        item.style.display = name.includes(search) ? '' : 'none';
    });
}

function openUnitModal(productId, productName, defaultUnit, costPrice, salePrice) {
    document.getElementById('unitForm').reset();
    document.getElementById('unitId').value = '';
    document.getElementById('productId').value = productId;
    document.getElementById('productName').value = productName;
    document.getElementById('modalTitle').textContent = 'เพิ่มหน่วยสินค้า';
    
    // Set default values
    document.querySelector('[name="cost_price"]').value = costPrice || '';
    document.querySelector('[name="sale_price"]').value = salePrice || '';
    
    document.getElementById('unitModal').classList.remove('hidden');
    document.getElementById('unitModal').classList.add('flex');
}

function editUnit(unit) {
    document.getElementById('unitForm').reset();
    document.getElementById('unitId').value = unit.id;
    document.getElementById('productId').value = unit.product_id;
    document.getElementById('productName').value = unit.product_name || '';
    document.getElementById('modalTitle').textContent = 'แก้ไขหน่วยสินค้า';
    
    document.querySelector('[name="unit_name"]').value = unit.unit_name;
    document.querySelector('[name="unit_code"]').value = unit.unit_code || '';
    document.querySelector('[name="factor"]').value = unit.factor;
    document.querySelector('[name="cost_price"]').value = unit.cost_price || '';
    document.querySelector('[name="sale_price"]').value = unit.sale_price || '';
    document.querySelector('[name="barcode"]').value = unit.barcode || '';
    document.querySelector('[name="is_base_unit"]').checked = unit.is_base_unit == 1;
    document.querySelector('[name="is_purchase_unit"]').checked = unit.is_purchase_unit == 1;
    document.querySelector('[name="is_sale_unit"]').checked = unit.is_sale_unit == 1;
    
    document.getElementById('unitModal').classList.remove('hidden');
    document.getElementById('unitModal').classList.add('flex');
}

function closeUnitModal() {
    document.getElementById('unitModal').classList.add('hidden');
    document.getElementById('unitModal').classList.remove('flex');
}

document.getElementById('unitForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Convert checkboxes
    data.is_base_unit = document.querySelector('[name="is_base_unit"]').checked ? 1 : 0;
    data.is_purchase_unit = document.querySelector('[name="is_purchase_unit"]').checked ? 1 : 0;
    data.is_sale_unit = document.querySelector('[name="is_sale_unit"]').checked ? 1 : 0;
    
    const action = data.unit_id ? 'update_product_unit' : 'create_product_unit';
    
    const res = await fetch('../api/inventory.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const result = await res.json();
    
    if (result.success) {
        location.reload();
    } else {
        alert(result.message || 'Error');
    }
});

async function deleteUnit(unitId) {
    if (!confirm('ลบหน่วยนี้?')) return;
    
    const res = await fetch('../api/inventory.php?action=delete_product_unit&unit_id=' + unitId, { method: 'POST' });
    const result = await res.json();
    
    if (result.success) {
        location.reload();
    } else {
        alert(result.message || 'Error');
    }
}
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
