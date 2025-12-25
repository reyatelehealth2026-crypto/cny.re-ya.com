<?php
/**
 * Setup Categories - เพิ่ม 22 Categories มาตรฐาน CNY
 * รันครั้งเดียวเพื่อเพิ่ม categories ทั้งหมด
 * - ลบ categories เก่าที่แยกตามผู้ผลิต
 * - เพิ่ม 22 categories มาตรฐานใหม่
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Categories</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #1E293B; } h2 { color: #475569; }
.success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; } .info { color: #3B82F6; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #E2E8F0; }
th { background: #F8FAFC; }
.btn { display: inline-block; padding: 12px 24px; background: #10B981; color: white; text-decoration: none; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; margin: 5px; }
.btn:hover { background: #059669; }
.btn-blue { background: #3B82F6; } .btn-blue:hover { background: #2563EB; }
.btn-red { background: #EF4444; } .btn-red:hover { background: #DC2626; }
.btn-orange { background: #F59E0B; } .btn-orange:hover { background: #D97706; }
.step { display: inline-block; width: 30px; height: 30px; background: #3B82F6; color: white; border-radius: 50%; text-align: center; line-height: 30px; margin-right: 10px; }
.highlight { background: #FEF3C7; padding: 15px; border-radius: 8px; border-left: 4px solid #F59E0B; margin: 15px 0; }
</style></head><body>";

echo "<h1>🏷️ Setup Categories - 22 หมวดหมู่มาตรฐาน CNY</h1>";

echo "<div class='highlight'>";
echo "<strong>📋 ขั้นตอนการใช้งาน:</strong><br>";
echo "<span class='step'>1</span> ลบ Categories เก่า (ที่แยกตามผู้ผลิต)<br>";
echo "<span class='step'>2</span> เพิ่ม 22 Categories มาตรฐานใหม่<br>";
echo "<span class='step'>3</span> Auto-assign Category ให้สินค้า (ดูจาก SKU)<br>";
echo "</div>";

// 22 Categories มาตรฐาน
$categories = [
    ['code' => 'CNS', 'name' => 'ระบบประสาทส่วนกลาง', 'icon' => '🧠'],
    ['code' => 'IMM', 'name' => 'แก้แพ้และภูมิคุ้มกัน', 'icon' => '🛡️'],
    ['code' => 'MSJ', 'name' => 'ระบบกล้ามเนื้อและข้อ', 'icon' => '💪'],
    ['code' => 'RIS', 'name' => 'ระบบทางเดินหายใจ', 'icon' => '🫁'],
    ['code' => 'HCD', 'name' => 'อุปกรณ์ดูแลสุขภาพ', 'icon' => '🩺'],
    ['code' => 'VIT', 'name' => 'วิตามิน เกลือแร่', 'icon' => '💊'],
    ['code' => 'HER', 'name' => 'สมุนไพรและยาแผนโบราณ', 'icon' => '🌿'],
    ['code' => 'FMC', 'name' => 'สินค้าอุปโภค-บริโภค', 'icon' => '🛒'],
    ['code' => 'HOR', 'name' => 'ฮอร์โมน ยาคุมกำเนิด สูตินรี และระบบทางเดินปัสสาวะ', 'icon' => '💉'],
    ['code' => 'CDS', 'name' => 'ระบบหัวใจและหลอดเลือด', 'icon' => '❤️'],
    ['code' => 'INF', 'name' => 'การติดเชื้อ', 'icon' => '🦠'],
    ['code' => 'GIS', 'name' => 'ระบบทางเดินอาหาร', 'icon' => '🍽️'],
    ['code' => 'SKI', 'name' => 'ระบบผิวหนัง', 'icon' => '🧴'],
    ['code' => 'NUT', 'name' => 'ผลิตภัณฑ์เสริมอาหารและโภชนาการ', 'icon' => '🥗'],
    ['code' => 'COS', 'name' => 'เวชสำอางค์และเครื่องสำอางค์', 'icon' => '💄'],
    ['code' => 'SHP', 'name' => 'ค่าขนส่ง', 'icon' => '🚚'],
    ['code' => 'END', 'name' => 'ระบบต่อมไร้ท่อ', 'icon' => '🔬'],
    ['code' => 'ENT', 'name' => 'ตา หู จมูก และช่องปาก', 'icon' => '👁️'],
    ['code' => 'OFC', 'name' => 'อุปกรณ์สำนักงานภายในร้าน', 'icon' => '📎'],
    ['code' => 'ELE', 'name' => 'อุปกรณ์เครื่องใช้ไฟฟ้า', 'icon' => '🔌'],
    ['code' => 'OTC', 'name' => 'ยาสามัญประจำบ้าน', 'icon' => '💊'],
    ['code' => 'SM',  'name' => 'สินค้าสมนาคุณ', 'icon' => '🎁'],
];

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
    echo "<div class='card'><p class='error'>❌ ไม่พบตาราง categories - กรุณาสร้างตารางก่อน</p></div>";
    echo "</body></html>";
    exit;
}

echo "<div class='card'>";
echo "<p class='info'>📋 ใช้ตาราง: <strong>$catTable</strong></p>";

// ========== STEP 1: Delete Old Categories ==========
echo "</div>";

echo "<div class='card'>";
echo "<h2><span class='step'>1</span> ลบ Categories เก่า</h2>";

// Get current categories
$stmt = $db->query("SELECT * FROM $catTable ORDER BY id");
$currentCats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$catCount = count($currentCats);

if (isset($_POST['delete_all_categories'])) {
    try {
        // Reset category_id in products first
        $stmt = $db->exec("UPDATE business_items SET category_id = NULL");
        echo "<p class='info'>🔄 Reset category_id ในสินค้าทั้งหมดแล้ว</p>";
        
        // Delete all categories
        $stmt = $db->exec("DELETE FROM $catTable");
        echo "<p class='success'>✓ ลบ categories เก่าทั้งหมดแล้ว ($catCount รายการ)</p>";
        
        // Reset auto increment
        $db->exec("ALTER TABLE $catTable AUTO_INCREMENT = 1");
        echo "<p class='info'>🔄 Reset AUTO_INCREMENT แล้ว</p>";
        
        echo "<script>setTimeout(function(){ location.reload(); }, 1500);</script>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    if ($catCount > 0) {
        echo "<p>พบ <strong>$catCount</strong> categories เก่าในระบบ</p>";
        
        // Show sample of old categories
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Products</th></tr>";
        $shown = 0;
        foreach ($currentCats as $c) {
            if ($shown >= 10) {
                echo "<tr><td colspan='3' style='text-align:center;color:#94A3B8;'>... และอีก " . ($catCount - 10) . " รายการ</td></tr>";
                break;
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM business_items WHERE category_id = ?");
            $stmt->execute([$c['id']]);
            $productCount = $stmt->fetchColumn();
            echo "<tr><td>{$c['id']}</td><td>{$c['name']}</td><td>$productCount</td></tr>";
            $shown++;
        }
        echo "</table>";
        
        echo "<form method='POST' onsubmit=\"return confirm('⚠️ ยืนยันลบ categories เก่าทั้งหมด?\\n\\nสินค้าจะถูก reset category_id เป็น NULL\\nแล้วค่อย assign ใหม่ในขั้นตอนถัดไป');\">";
        echo "<button type='submit' name='delete_all_categories' class='btn btn-red'>🗑️ ลบ Categories เก่าทั้งหมด ($catCount รายการ)</button>";
        echo "</form>";
    } else {
        echo "<p class='success'>✓ ไม่มี categories เก่า - พร้อมเพิ่มใหม่!</p>";
    }
}
echo "</div>";

echo "<div class='card'>";

// Check columns
$columns = [];
$stmt = $db->query("SHOW COLUMNS FROM $catTable");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

$hasCnyCode = in_array('cny_code', $columns);
$hasIcon = in_array('icon', $columns);
$hasDescription = in_array('description', $columns);

// Add cny_code column if not exists
if (!$hasCnyCode) {
    try {
        $db->exec("ALTER TABLE $catTable ADD COLUMN cny_code VARCHAR(10) NULL AFTER name");
        echo "<p class='success'>✓ เพิ่ม column cny_code สำเร็จ</p>";
        $hasCnyCode = true;
    } catch (Exception $e) {
        echo "<p class='warning'>⚠️ ไม่สามารถเพิ่ม column cny_code: " . $e->getMessage() . "</p>";
    }
}

// Add icon column if not exists
if (!$hasIcon) {
    try {
        $db->exec("ALTER TABLE $catTable ADD COLUMN icon VARCHAR(50) NULL");
        echo "<p class='success'>✓ เพิ่ม column icon สำเร็จ</p>";
        $hasIcon = true;
    } catch (Exception $e) {}
}
echo "</div>";

// ========== Process ==========
if (isset($_POST['setup_categories'])) {
    echo "<div class='card'>";
    echo "<h2>🔄 กำลังเพิ่ม Categories...</h2>";
    
    $added = 0;
    $updated = 0;
    $errors = 0;
    
    foreach ($categories as $cat) {
        try {
            // Check if exists
            $exists = false;
            if ($hasCnyCode) {
                $stmt = $db->prepare("SELECT id FROM $catTable WHERE cny_code = ? OR name LIKE ?");
                $stmt->execute([$cat['code'], '%' . $cat['code'] . '%']);
            } else {
                $stmt = $db->prepare("SELECT id FROM $catTable WHERE name LIKE ? OR name LIKE ?");
                $stmt->execute(['%' . $cat['code'] . '%', '%' . $cat['name'] . '%']);
            }
            $existingId = $stmt->fetchColumn();
            
            if ($existingId) {
                // Update existing
                if ($hasCnyCode && $hasIcon) {
                    $stmt = $db->prepare("UPDATE $catTable SET name = ?, cny_code = ?, icon = ?, is_active = 1 WHERE id = ?");
                    $stmt->execute([$cat['code'] . '-' . $cat['name'], $cat['code'], $cat['icon'], $existingId]);
                } elseif ($hasCnyCode) {
                    $stmt = $db->prepare("UPDATE $catTable SET name = ?, cny_code = ?, is_active = 1 WHERE id = ?");
                    $stmt->execute([$cat['code'] . '-' . $cat['name'], $cat['code'], $existingId]);
                } else {
                    $stmt = $db->prepare("UPDATE $catTable SET name = ?, is_active = 1 WHERE id = ?");
                    $stmt->execute([$cat['code'] . '-' . $cat['name'], $existingId]);
                }
                $updated++;
                echo "<p class='info'>🔄 อัพเดท: {$cat['icon']} {$cat['code']}-{$cat['name']}</p>";
            } else {
                // Insert new
                if ($hasCnyCode && $hasIcon) {
                    $stmt = $db->prepare("INSERT INTO $catTable (name, cny_code, icon, is_active) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$cat['code'] . '-' . $cat['name'], $cat['code'], $cat['icon']]);
                } elseif ($hasCnyCode) {
                    $stmt = $db->prepare("INSERT INTO $catTable (name, cny_code, is_active) VALUES (?, ?, 1)");
                    $stmt->execute([$cat['code'] . '-' . $cat['name'], $cat['code']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO $catTable (name, is_active) VALUES (?, 1)");
                    $stmt->execute([$cat['code'] . '-' . $cat['name']]);
                }
                $added++;
                echo "<p class='success'>✓ เพิ่ม: {$cat['icon']} {$cat['code']}-{$cat['name']}</p>";
            }
        } catch (Exception $e) {
            $errors++;
            echo "<p class='error'>✗ Error {$cat['code']}: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><strong>สรุป:</strong> เพิ่มใหม่ $added | อัพเดท $updated | Error $errors</p>";
    echo "</div>";
}

// ========== Auto-assign Products ==========
if (isset($_POST['auto_assign'])) {
    echo "<div class='card'>";
    echo "<h2>🔄 Auto-assign Category ให้สินค้า...</h2>";
    
    // Build category map
    $catMap = [];
    $stmt = $db->query("SELECT id, cny_code, name FROM $catTable WHERE cny_code IS NOT NULL");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $catMap[$row['cny_code']] = $row['id'];
    }
    
    // Get products without category
    $stmt = $db->query("SELECT id, sku, name FROM business_items WHERE category_id IS NULL OR category_id = 0");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $assigned = 0;
    $notFound = 0;
    
    foreach ($products as $p) {
        $categoryId = null;
        $sku = strtoupper($p['sku'] ?? '');
        
        // Extract category code from SKU (first 2-3 characters)
        foreach ($catMap as $code => $catId) {
            if (strpos($sku, $code) === 0) {
                $categoryId = $catId;
                break;
            }
        }
        
        if ($categoryId) {
            $stmt = $db->prepare("UPDATE business_items SET category_id = ? WHERE id = ?");
            $stmt->execute([$categoryId, $p['id']]);
            $assigned++;
        } else {
            $notFound++;
        }
    }
    
    echo "<p class='success'>✓ Assign สำเร็จ: <strong>$assigned</strong> รายการ</p>";
    if ($notFound > 0) {
        echo "<p class='warning'>⚠️ ไม่พบ category: $notFound รายการ (ต้อง set default)</p>";
    }
    echo "</div>";
}

// ========== Current Categories ==========
echo "<div class='card'>";
echo "<h2>📋 Categories ปัจจุบันในระบบ</h2>";

// Refresh current categories
$stmt = $db->query("SELECT * FROM $catTable ORDER BY id");
$currentCats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($currentCats) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Icon</th><th>Code</th><th>Name</th><th>Products</th></tr>";
    foreach ($currentCats as $c) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM business_items WHERE category_id = ?");
        $stmt->execute([$c['id']]);
        $productCount = $stmt->fetchColumn();
        
        echo "<tr>";
        echo "<td>{$c['id']}</td>";
        echo "<td>" . ($c['icon'] ?? '-') . "</td>";
        echo "<td><strong>" . ($c['cny_code'] ?? '-') . "</strong></td>";
        echo "<td>{$c['name']}</td>";
        echo "<td>$productCount</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>ยังไม่มี categories ในระบบ - กรุณาเพิ่มในขั้นตอนที่ 2</p>";
}
echo "</div>";

// ========== 22 Categories to Add ==========
echo "<div class='card'>";
echo "<h2><span class='step'>2</span> เพิ่ม 22 Categories มาตรฐาน CNY</h2>";

echo "<table>";
echo "<tr><th>#</th><th>Icon</th><th>Code</th><th>ชื่อหมวดหมู่</th></tr>";
$i = 1;
foreach ($categories as $cat) {
    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td>{$cat['icon']}</td>";
    echo "<td><strong>{$cat['code']}</strong></td>";
    echo "<td>{$cat['name']}</td>";
    echo "</tr>";
    $i++;
}
echo "</table>";

echo "<form method='POST' style='margin-top:20px;'>";
echo "<button type='submit' name='setup_categories' class='btn'>➕ เพิ่ม 22 Categories ใหม่</button>";
echo "</form>";
echo "</div>";

// ========== Products Status ==========
echo "<div class='card'>";
echo "<h2><span class='step'>3</span> Auto-assign Category ให้สินค้า</h2>";

$stmt = $db->query("SELECT COUNT(*) FROM business_items");
$totalProducts = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM business_items WHERE category_id IS NULL OR category_id = 0");
$noCategory = $stmt->fetchColumn();

$withCategory = $totalProducts - $noCategory;

echo "<p>สินค้าทั้งหมด: <strong>$totalProducts</strong></p>";
echo "<p class='success'>มี category แล้ว: <strong>$withCategory</strong></p>";
echo "<p class='" . ($noCategory > 0 ? 'warning' : 'success') . "'>ไม่มี category: <strong>$noCategory</strong></p>";

if ($noCategory > 0) {
    // Show sample products without category
    $stmt = $db->query("SELECT id, sku, name FROM business_items WHERE category_id IS NULL OR category_id = 0 LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($samples) > 0) {
        echo "<p style='margin-top:10px;'><strong>ตัวอย่างสินค้าที่ยังไม่มี category:</strong></p>";
        echo "<table><tr><th>SKU</th><th>Name</th></tr>";
        foreach ($samples as $s) {
            echo "<tr><td>{$s['sku']}</td><td>" . mb_substr($s['name'], 0, 50) . "</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<form method='POST' style='margin-top:15px;'>";
    echo "<p class='info'>ระบบจะ assign category โดยดูจาก SKU (เช่น VIT001 → วิตามิน)</p>";
    echo "<button type='submit' name='auto_assign' class='btn btn-blue'>🔄 Auto-assign Category ($noCategory รายการ)</button>";
    echo "</form>";
} else {
    echo "<p class='success' style='margin-top:15px;'>✓ สินค้าทุกรายการมี category แล้ว!</p>";
}
echo "</div>";

echo "<p style='text-align:center;color:#94A3B8;margin-top:20px;'>Generated at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
