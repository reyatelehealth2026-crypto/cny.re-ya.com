<?php
/**
 * Check Categories Sync
 * ตรวจสอบว่า categories จาก CNY API มีครบหรือไม่
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Check Categories Sync</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #1E293B; } h2 { color: #475569; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; }
.success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #E2E8F0; }
th { background: #F8FAFC; font-weight: 600; }
tr:hover { background: #F8FAFC; }
.check { color: #10B981; font-size: 18px; } .cross { color: #EF4444; font-size: 18px; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.badge-success { background: #D1FAE5; color: #065F46; }
.badge-error { background: #FEE2E2; color: #991B1B; }
.badge-warning { background: #FEF3C7; color: #92400E; }
.summary { display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0; }
.summary-item { flex: 1; min-width: 150px; padding: 20px; border-radius: 12px; text-align: center; }
.summary-item h3 { margin: 0; font-size: 32px; }
.summary-item p { margin: 5px 0 0; color: #64748B; font-size: 14px; }
.btn { display: inline-block; padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; }
.btn:hover { background: #059669; }
.btn-blue { background: #3B82F6; } .btn-blue:hover { background: #2563EB; }
pre { background: #1E293B; color: #10B981; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🔍 Check Categories Sync</h1>";

// ========== Expected Categories from CNY ==========
$expectedCategories = [
    'CNS' => 'ระบบประสาทส่วนกลาง',
    'IMM' => 'แก้แพ้และภูมิคุ้มกัน',
    'MSJ' => 'ระบบกล้ามเนื้อและข้อ',
    'RIS' => 'ระบบทางเดินหายใจ',
    'HCD' => 'อุปกรณ์ดูแลสุขภาพ',
    'VIT' => 'วิตามิน เกลือแร่',
    'HER' => 'สมุนไพรและยาแผนโบราณ',
    'FMC' => 'สินค้าอุปโภค-บริโภค',
    'HOR' => 'ฮอร์โมน ยาคุมกำเนิด สูตินรี และระบบทางเดินปัสสาวะ',
    'CDS' => 'ระบบหัวใจและหลอดเลือด',
    'INF' => 'การติดเชื้อ',
    'GIS' => 'ระบบทางเดินอาหาร',
    'SKI' => 'ระบบผิวหนัง',
    'NUT' => 'ผลิตภัณฑ์เสริมอาหารและโภชนาการ',
    'COS' => 'เวชสำอางค์และเครื่องสำอางค์',
    'SHP' => 'ค่าขนส่ง',
    'END' => 'ระบบต่อมไร้ท่อ',
    'ENT' => 'ตา หู จมูก และช่องปาก',
    'OFC' => 'อุปกรณ์สำนักงานภายในร้าน',
    'ELE' => 'อุปกรณ์เครื่องใช้ไฟฟ้า',
    'OTC' => 'ยาสามัญประจำบ้าน',
    'SM' => 'สินค้าสมนาคุณ',
];

// ========== 1. Check Categories Table ==========
echo "<div class='card'>";
echo "<h2>1. ตรวจสอบตาราง Categories</h2>";

// Detect table name
$catTable = 'item_categories';
try {
    $db->query("SELECT 1 FROM item_categories LIMIT 1");
} catch (Exception $e) {
    $catTable = 'product_categories';
    try {
        $db->query("SELECT 1 FROM product_categories LIMIT 1");
    } catch (Exception $e2) {
        $catTable = null;
    }
}

if (!$catTable) {
    echo "<p class='error'>❌ ไม่พบตาราง categories (item_categories หรือ product_categories)</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✓ ใช้ตาราง: <code>$catTable</code></p>";

// Check columns
$columns = [];
try {
    $stmt = $db->query("SHOW COLUMNS FROM $catTable");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    echo "<p>Columns: <code>" . implode(', ', $columns) . "</code></p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check if cny_code column exists
$hasCnyCode = in_array('cny_code', $columns);
if (!$hasCnyCode) {
    echo "<p class='warning'>⚠️ ไม่มี column <code>cny_code</code> - จะเช็คจากชื่อแทน</p>";
}

echo "</div>";

// ========== 2. Check Each Category ==========
echo "<div class='card'>";
echo "<h2>2. ตรวจสอบ Categories ที่ต้องการ</h2>";

$found = 0;
$missing = 0;
$results = [];

foreach ($expectedCategories as $code => $name) {
    $exists = false;
    $dbRecord = null;
    
    try {
        if ($hasCnyCode) {
            // Search by cny_code
            $stmt = $db->prepare("SELECT * FROM $catTable WHERE cny_code = ? OR name LIKE ? LIMIT 1");
            $stmt->execute([$code, "%$code%"]);
        } else {
            // Search by name
            $stmt = $db->prepare("SELECT * FROM $catTable WHERE name LIKE ? OR name LIKE ? LIMIT 1");
            $stmt->execute(["%$code%", "%$name%"]);
        }
        $dbRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        $exists = $dbRecord !== false;
    } catch (Exception $e) {
        // Ignore
    }
    
    if ($exists) {
        $found++;
    } else {
        $missing++;
    }
    
    $results[$code] = [
        'code' => $code,
        'expected_name' => $name,
        'exists' => $exists,
        'db_record' => $dbRecord
    ];
}

// Summary
echo "<div class='summary'>";
echo "<div class='summary-item' style='background: #D1FAE5;'><h3>$found</h3><p>พบแล้ว</p></div>";
echo "<div class='summary-item' style='background: " . ($missing > 0 ? '#FEE2E2' : '#D1FAE5') . ";'><h3>$missing</h3><p>ไม่พบ</p></div>";
echo "<div class='summary-item' style='background: #E0E7FF;'><h3>" . count($expectedCategories) . "</h3><p>ทั้งหมด</p></div>";
echo "</div>";

// Table
echo "<table>";
echo "<tr><th>#</th><th>Code</th><th>ชื่อที่คาดหวัง</th><th>สถานะ</th><th>ข้อมูลใน DB</th></tr>";

$i = 1;
foreach ($results as $code => $r) {
    $status = $r['exists'] 
        ? "<span class='check'>✓</span> <span class='badge badge-success'>พบ</span>" 
        : "<span class='cross'>✗</span> <span class='badge badge-error'>ไม่พบ</span>";
    
    $dbInfo = '';
    if ($r['db_record']) {
        $dbName = $r['db_record']['name'] ?? '-';
        $dbId = $r['db_record']['id'] ?? '-';
        $dbCnyCode = $r['db_record']['cny_code'] ?? '-';
        $dbInfo = "ID: $dbId, Name: $dbName" . ($hasCnyCode ? ", CNY: $dbCnyCode" : "");
    } else {
        $dbInfo = "<span class='warning'>-</span>";
    }
    
    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td><strong>$code</strong></td>";
    echo "<td>{$r['expected_name']}</td>";
    echo "<td>$status</td>";
    echo "<td style='font-size:12px;'>$dbInfo</td>";
    echo "</tr>";
    $i++;
}
echo "</table>";
echo "</div>";

// ========== 3. All Categories in DB ==========
echo "<div class='card'>";
echo "<h2>3. Categories ทั้งหมดใน Database</h2>";

try {
    $stmt = $db->query("SELECT * FROM $catTable ORDER BY id");
    $allCats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>จำนวนทั้งหมด: <strong>" . count($allCats) . "</strong> รายการ</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th>";
    if ($hasCnyCode) echo "<th>CNY Code</th>";
    echo "<th>Active</th><th>Products</th></tr>";
    
    foreach ($allCats as $cat) {
        $isActive = ($cat['is_active'] ?? 1) ? '✓' : '✗';
        
        // Count products
        $productCount = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM business_items WHERE category_id = ?");
            $stmt->execute([$cat['id']]);
            $productCount = $stmt->fetchColumn();
        } catch (Exception $e) {}
        
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>{$cat['name']}</td>";
        if ($hasCnyCode) echo "<td>" . ($cat['cny_code'] ?? '-') . "</td>";
        echo "<td>$isActive</td>";
        echo "<td>$productCount</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ========== 4. Products without Category ==========
echo "<div class='card'>";
echo "<h2>4. สินค้าที่ไม่มี Category</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE category_id IS NULL OR category_id = 0");
    $noCategory = $stmt->fetchColumn();
    
    if ($noCategory > 0) {
        echo "<p class='warning'>⚠️ พบสินค้าที่ไม่มี category: <strong>$noCategory</strong> รายการ</p>";
        
        // Check if cny_category_code column exists
        $hasCnyCatCode = false;
        try {
            $checkCol = $db->query("SHOW COLUMNS FROM business_items LIKE 'cny_category_code'");
            $hasCnyCatCode = $checkCol->rowCount() > 0;
        } catch (Exception $e) {}
        
        // Show sample
        if ($hasCnyCatCode) {
            $stmt = $db->query("SELECT id, name, sku, cny_category_code FROM business_items WHERE category_id IS NULL OR category_id = 0 LIMIT 10");
        } else {
            $stmt = $db->query("SELECT id, name, sku FROM business_items WHERE category_id IS NULL OR category_id = 0 LIMIT 10");
        }
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($samples) > 0) {
            echo "<table><tr><th>ID</th><th>Name</th><th>SKU</th>";
            if ($hasCnyCatCode) echo "<th>CNY Category Code</th>";
            echo "</tr>";
            foreach ($samples as $p) {
                echo "<tr><td>{$p['id']}</td><td>" . mb_substr($p['name'], 0, 50) . "</td><td>{$p['sku']}</td>";
                if ($hasCnyCatCode) echo "<td>" . ($p['cny_category_code'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='success'>✓ สินค้าทุกรายการมี category แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ========== 5. Add Missing Categories ==========
echo "<div class='card'>";
echo "<h2>5. เพิ่ม Categories ที่ขาด</h2>";

if (isset($_POST['add_missing'])) {
    $added = 0;
    foreach ($results as $code => $r) {
        if (!$r['exists']) {
            try {
                if ($hasCnyCode) {
                    $stmt = $db->prepare("INSERT INTO $catTable (name, cny_code, is_active) VALUES (?, ?, 1)");
                    $stmt->execute([$r['expected_name'], $code]);
                } else {
                    $stmt = $db->prepare("INSERT INTO $catTable (name, is_active) VALUES (?, 1)");
                    $stmt->execute([$code . ' - ' . $r['expected_name']]);
                }
                $added++;
                echo "<p class='success'>✓ เพิ่ม: $code - {$r['expected_name']}</p>";
            } catch (Exception $e) {
                echo "<p class='error'>✗ Error adding $code: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    if ($added > 0) {
        echo "<p class='success'><strong>เพิ่มสำเร็จ $added รายการ!</strong></p>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    } else {
        echo "<p class='warning'>ไม่มี category ที่ต้องเพิ่ม</p>";
    }
}

if ($missing > 0) {
    echo "<form method='POST'>";
    echo "<p>พบ $missing categories ที่ยังไม่มีในระบบ</p>";
    echo "<button type='submit' name='add_missing' class='btn'>➕ เพิ่ม Categories ที่ขาด</button>";
    echo "</form>";
} else {
    echo "<p class='success'>✓ มี categories ครบทุกรายการแล้ว!</p>";
}
echo "</div>";

// ========== 6. Sync from CNY API ==========
echo "<div class='card'>";
echo "<h2>6. Sync จาก CNY API</h2>";
echo "<p>หากต้องการ sync categories จาก CNY API โดยตรง:</p>";
echo "<a href='sync_categories_from_manufacturer.php' class='btn btn-blue'>🔄 Sync Categories from CNY</a>";
echo "</div>";

// ========== 7. Auto-assign Category to Products ==========
echo "<div class='card'>";
echo "<h2>7. Auto-assign Category ให้สินค้า</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE category_id IS NULL OR category_id = 0");
    $noCategoryCount = $stmt->fetchColumn();
    
    if ($noCategoryCount > 0) {
        echo "<p>พบสินค้า <strong>$noCategoryCount</strong> รายการที่ยังไม่มี category</p>";
        
        if (isset($_POST['auto_assign'])) {
            $assigned = 0;
            $errors = 0;
            
            // Get all categories with cny_code
            $catMap = [];
            try {
                if ($hasCnyCode) {
                    $stmt = $db->query("SELECT id, cny_code, name FROM $catTable WHERE cny_code IS NOT NULL");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $catMap[$row['cny_code']] = $row['id'];
                    }
                }
            } catch (Exception $e) {}
            
            // Also map by expected categories
            foreach ($expectedCategories as $code => $name) {
                if (!isset($catMap[$code])) {
                    try {
                        $stmt = $db->prepare("SELECT id FROM $catTable WHERE name LIKE ? OR name LIKE ? LIMIT 1");
                        $stmt->execute(["%$code%", "%$name%"]);
                        $catId = $stmt->fetchColumn();
                        if ($catId) {
                            $catMap[$code] = $catId;
                        }
                    } catch (Exception $e) {}
                }
            }
            
            echo "<p>Category Map: " . count($catMap) . " รายการ</p>";
            
            // Get products without category
            $stmt = $db->query("SELECT id, sku, name FROM business_items WHERE category_id IS NULL OR category_id = 0");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $p) {
                $categoryId = null;
                $sku = $p['sku'] ?? '';
                $name = $p['name'] ?? '';
                
                // Try to extract category code from SKU
                foreach ($catMap as $code => $catId) {
                    if (stripos($sku, $code) === 0 || stripos($sku, $code . '-') === 0 || stripos($sku, $code . '_') === 0) {
                        $categoryId = $catId;
                        break;
                    }
                }
                
                // Try keyword matching if no category found
                if (!$categoryId) {
                    $keywordMap = [
                        'วิตามิน' => 'VIT', 'vitamin' => 'VIT',
                        'สมุนไพร' => 'HER', 'ยาแก้ปวด' => 'CNS',
                        'ยาแก้ไอ' => 'RIS', 'ยาแก้แพ้' => 'IMM',
                        'ครีม' => 'SKI', 'โลชั่น' => 'SKI',
                        'แชมพู' => 'COS', 'ยาลดกรด' => 'GIS',
                    ];
                    
                    foreach ($keywordMap as $keyword => $code) {
                        if (stripos($name, $keyword) !== false && isset($catMap[$code])) {
                            $categoryId = $catMap[$code];
                            break;
                        }
                    }
                }
                
                if ($categoryId) {
                    try {
                        $stmt = $db->prepare("UPDATE business_items SET category_id = ? WHERE id = ?");
                        $stmt->execute([$categoryId, $p['id']]);
                        $assigned++;
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
            }
            
            echo "<p class='success'>✓ Assign สำเร็จ: <strong>$assigned</strong> รายการ</p>";
            if ($errors > 0) echo "<p class='warning'>⚠️ Errors: $errors</p>";
            
            $remaining = $noCategoryCount - $assigned;
            if ($remaining > 0) {
                echo "<p class='warning'>⚠️ ยังเหลือ: $remaining รายการ (ต้อง assign manual หรือ set default)</p>";
            }
            echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
        }
        
        echo "<form method='POST'>";
        echo "<p>ระบบจะพยายาม assign category โดยดูจาก SKU และชื่อสินค้า</p>";
        echo "<button type='submit' name='auto_assign' class='btn'>🔄 Auto-assign Categories</button>";
        echo "</form>";
    } else {
        echo "<p class='success'>✓ สินค้าทุกรายการมี category แล้ว!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ========== 8. Set Default Category ==========
echo "<div class='card'>";
echo "<h2>8. Set Default Category สำหรับสินค้าที่เหลือ</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE category_id IS NULL OR category_id = 0");
    $remainingCount = $stmt->fetchColumn();
    
    if ($remainingCount > 0) {
        echo "<p>ยังมีสินค้า <strong>$remainingCount</strong> รายการที่ไม่มี category</p>";
        
        if (isset($_POST['set_default'])) {
            $defaultCatId = (int)$_POST['default_cat_id'];
            if ($defaultCatId > 0) {
                $stmt = $db->prepare("UPDATE business_items SET category_id = ? WHERE category_id IS NULL OR category_id = 0");
                $stmt->execute([$defaultCatId]);
                $affected = $stmt->rowCount();
                echo "<p class='success'>✓ Set default category สำเร็จ: $affected รายการ</p>";
                echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
            }
        }
        
        // Get categories for dropdown
        $stmt = $db->query("SELECT id, name FROM $catTable ORDER BY name");
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<form method='POST'>";
        echo "<p>เลือก category เริ่มต้นสำหรับสินค้าที่เหลือ:</p>";
        echo "<select name='default_cat_id' style='padding:10px;width:300px;border:1px solid #ddd;border-radius:4px;'>";
        foreach ($cats as $c) {
            echo "<option value='{$c['id']}'>{$c['name']}</option>";
        }
        echo "</select>";
        echo "<button type='submit' name='set_default' class='btn' style='margin-left:10px;'>✓ Set Default</button>";
        echo "</form>";
    } else {
        echo "<p class='success'>✓ ไม่มีสินค้าที่ต้อง set default category</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<p style='text-align:center;color:#94A3B8;margin-top:20px;'>Generated at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
