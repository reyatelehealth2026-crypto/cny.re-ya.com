<?php
/**
 * Fix cart_items Foreign Key
 * ลบ FK ที่ชี้ไป products และเปลี่ยนเป็น business_items
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Fix Cart Items Foreign Key</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;}</style>";

$db = Database::getInstance()->getConnection();

// 1. Show current FK constraints
echo "<h2>1. FK Constraints ปัจจุบัน</h2>";
try {
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'cart_items'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($fks) {
        echo "<table border='1' cellpadding='5'><tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
        foreach ($fks as $fk) {
            $isProducts = $fk['REFERENCED_TABLE_NAME'] === 'products';
            echo "<tr" . ($isProducts ? " style='background:#fee'" : "") . ">
                <td>{$fk['CONSTRAINT_NAME']}</td>
                <td>{$fk['COLUMN_NAME']}</td>
                <td>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}" . ($isProducts ? " ⚠️" : "") . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ไม่มี FK constraints</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// 2. Drop FK to products table
echo "<h2>2. ลบ FK ที่ชี้ไป products</h2>";
if (isset($_GET['fix'])) {
    try {
        // Find and drop FK constraints pointing to products
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'cart_items'
            AND REFERENCED_TABLE_NAME = 'products'
        ");
        $fksToRemove = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($fksToRemove as $fkName) {
            $db->exec("ALTER TABLE cart_items DROP FOREIGN KEY `{$fkName}`");
            echo "<p class='ok'>✅ ลบ FK: {$fkName}</p>";
        }
        
        if (empty($fksToRemove)) {
            echo "<p class='warn'>⚠️ ไม่พบ FK ที่ชี้ไป products</p>";
        }
        
        // Also try to drop any FK pointing to wrong table
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'cart_items'
            AND COLUMN_NAME = 'product_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND REFERENCED_TABLE_NAME != 'business_items'
        ");
        $otherFks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($otherFks as $fk) {
            $db->exec("ALTER TABLE cart_items DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            echo "<p class='ok'>✅ ลบ FK: {$fk['CONSTRAINT_NAME']} (ชี้ไป {$fk['REFERENCED_TABLE_NAME']})</p>";
        }
        
        echo "<p class='ok'>✅ เสร็จสิ้น!</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?fix=1' style='padding:10px 20px;background:#dc3545;color:white;text-decoration:none;border-radius:5px;'>🔧 ลบ FK Constraints</a></p>";
    echo "<p class='warn'>⚠️ คลิกปุ่มด้านบนเพื่อลบ FK ที่ชี้ไป products</p>";
}

// 3. Verify after fix
echo "<h2>3. ตรวจสอบหลังแก้ไข</h2>";
try {
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'cart_items'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $remainingFks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasProductsFk = false;
    foreach ($remainingFks as $fk) {
        if ($fk['REFERENCED_TABLE_NAME'] === 'products') {
            $hasProductsFk = true;
        }
    }
    
    if ($hasProductsFk) {
        echo "<p class='error'>❌ ยังมี FK ชี้ไป products อยู่</p>";
    } else {
        echo "<p class='ok'>✅ ไม่มี FK ชี้ไป products แล้ว</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// 4. Test insert
echo "<h2>4. ทดสอบ Insert</h2>";
if (isset($_GET['fix'])) {
    try {
        // Get a test product
        $stmt = $db->query("SELECT id FROM business_items WHERE is_active = 1 LIMIT 1");
        $productId = $stmt->fetchColumn();
        
        if ($productId) {
            // Try insert
            $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (1, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity");
            $stmt->execute([$productId]);
            echo "<p class='ok'>✅ Insert สำเร็จ!</p>";
            
            // Clean up test
            $db->exec("DELETE FROM cart_items WHERE user_id = 1 AND quantity = 1");
        } else {
            echo "<p class='warn'>ไม่มีสินค้าสำหรับทดสอบ</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Insert Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p>Links:</p>";
echo "<ul>";
echo "<li><a href='debug_cart.php?user=U123'>Debug Cart</a></li>";
echo "<li><a href='liff-shop.php?debug=1'>LIFF Shop</a></li>";
echo "</ul>";
