<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "Adding sample products...\n\n";

// Add categories
$db->exec("INSERT IGNORE INTO product_categories (id, name, description, sort_order, is_active) VALUES
(1, 'สินค้าแนะนำ', 'สินค้าแนะนำพิเศษ', 1, 1),
(2, 'สินค้าใหม่', 'สินค้ามาใหม่', 2, 1),
(3, 'โปรโมชั่น', 'สินค้าลดราคา', 3, 1)");
echo "✅ Categories added\n";

// Add products
$products = [
    [1, 'สินค้าตัวอย่าง 1', 'รายละเอียดสินค้าตัวอย่าง 1', 299.00, null, 100],
    [1, 'สินค้าตัวอย่าง 2', 'รายละเอียดสินค้าตัวอย่าง 2', 499.00, 399.00, 50],
    [2, 'สินค้าใหม่ 1', 'สินค้ามาใหม่ล่าสุด', 199.00, null, 200],
    [2, 'สินค้าใหม่ 2', 'สินค้ามาใหม่ยอดนิยม', 599.00, 499.00, 30],
    [3, 'สินค้าลดราคา', 'ลดราคาพิเศษ!', 999.00, 599.00, 10],
];

$stmt = $db->prepare("INSERT INTO products (category_id, name, description, price, sale_price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");

foreach ($products as $p) {
    try {
        $stmt->execute($p);
        echo "✅ Added: {$p[1]}\n";
    } catch (Exception $e) {
        echo "⚠️ Skip: {$p[1]} - " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Current products:\n";
$result = $db->query("SELECT id, name, price, stock FROM products WHERE is_active = 1");
foreach ($result as $p) {
    echo "  [{$p['id']}] {$p['name']} - ฿{$p['price']} (stock: {$p['stock']})\n";
}

echo "\n✅ Done!\n";
