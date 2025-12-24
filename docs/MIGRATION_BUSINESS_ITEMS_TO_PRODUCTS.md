# Migration Guide: business_items → products

## สรุปไฟล์ที่ต้องแก้ไข

หลังจากรัน `run_unify_products_migration.php` แล้ว ต้องแก้ไขไฟล์ต่อไปนี้ให้ใช้ `products` แทน `business_items`

---

## ไฟล์หลักที่ต้องแก้ (Priority: HIGH)

### 1. liff-shop.php
```php
// เปลี่ยนจาก
FROM business_items WHERE ...
// เป็น
FROM products WHERE ...
```

### 2. liff-checkout.php
```php
// เปลี่ยนจาก
JOIN business_items p ON c.product_id = p.id
// เป็น
JOIN products p ON c.product_id = p.id
```

### 3. liff-order-detail.php
```php
// เปลี่ยนจาก
LEFT JOIN business_items bi ON ti.product_id = bi.id
// เป็น
LEFT JOIN products bi ON ti.product_id = bi.id
```

### 4. shop/promotions.php
```php
// เปลี่ยนทุก business_items เป็น products
UPDATE products SET is_featured = ...
SELECT ... FROM products WHERE ...
```

### 5. shop/products-grid.php
```php
// เปลี่ยนจาก
FROM business_items bi
// เป็น
FROM products bi
```

### 6. shop/product-detail.php
```php
// เปลี่ยนจาก
$db->query("SELECT 1 FROM business_items LIMIT 1");
$productsTable = 'business_items';
// เป็น
$productsTable = 'products';
```

### 7. user-detail.php
```php
// เปลี่ยนจาก
LEFT JOIN business_items bi ON ti.product_id = bi.id
// เป็น
LEFT JOIN products bi ON ti.product_id = bi.id
```

---

## ไฟล์ Sync ที่ต้องแก้ (Priority: HIGH)

### 8. sync_categories_from_cny.php
```php
// เปลี่ยนทุก business_items เป็น products
UPDATE products SET category_id = ?
SELECT COUNT(*) FROM products WHERE category_id IS NULL
```

### 9. sync_products_with_sku_id.php
```php
// เปลี่ยน function getItemsTable()
function getItemsTable($db) {
    return 'products';  // ใช้ products เสมอ
}
```

### 10. sync_cny_with_id.php
```php
// เปลี่ยน function getItemsTable()
function getItemsTable($db) {
    return 'products';
}
```

### 11. sync_categories_from_manufacturer.php
```php
// เปลี่ยน function getItemsTable()
function getItemsTable($db) {
    return 'products';
}
```

---

## ไฟล์ Test/Debug (Priority: MEDIUM)

### 12. test_ai_product_flex.php
```php
FROM products WHERE is_active = 1
```

### 13. test_checkout.php
```php
SELECT 1 FROM products LIMIT 1
SELECT COUNT(*) FROM products WHERE is_active = 1
```

### 14. test_search.php
```php
$table = 'products';
```

### 15. fix_missing_products.php
```php
SELECT COUNT(*) FROM products
SELECT sku FROM products
```

---

## ไฟล์ Fix/Migration (Priority: LOW - อาจลบได้)

### 16. fix_cart_fk.php
```php
SELECT id FROM products WHERE is_active = 1 LIMIT 1
```

### 17. fix_all_columns.php
- ลบส่วนที่สร้าง business_items table
- ลบส่วนที่ add columns to business_items

### 18. fix_encoding.php
```php
// ลบ 'business_items' ออกจาก array
$tables = ['line_accounts', 'users', 'messages', 'admin_users', 'products', 'orders'];
```

### 19. run_all_fixes.php
- ลบส่วนที่สร้าง business_items
- เปลี่ยน business_items เป็น products

### 20. run_all_migrations.php
- ลบ migration ที่เกี่ยวกับ business_items

---

## ไฟล์ที่ใช้ Dynamic Table Detection (ต้องแก้)

### 21. index.php
```php
// ลบ dynamic detection
// เปลี่ยนจาก
$productsTable = 'products';
try {
    $db->query("SELECT 1 FROM business_items LIMIT 1");
    $productsTable = 'business_items';
} catch (Exception $e) {}

// เป็น
$productsTable = 'products';
```

---

## Search & Replace Commands

ใช้คำสั่งนี้ใน IDE หรือ terminal:

```bash
# Find all files with business_items
grep -r "business_items" --include="*.php" .

# Replace in specific file (ตัวอย่าง)
sed -i 's/business_items/products/g' liff-shop.php
```

---

## Checklist

- [x] รัน `run_unify_products_migration.php`
- [x] แก้ไข liff-shop.php ✅
- [x] แก้ไข liff-checkout.php ✅
- [x] แก้ไข liff-order-detail.php ✅
- [x] แก้ไข shop/promotions.php ✅
- [x] แก้ไข shop/products-grid.php ✅
- [x] แก้ไข shop/product-detail.php ✅
- [x] แก้ไข user-detail.php ✅
- [x] แก้ไข sync_categories_from_cny.php ✅
- [x] แก้ไข sync_products_with_sku_id.php ✅
- [x] แก้ไข sync_cny_with_id.php ✅
- [x] แก้ไข sync_categories_from_manufacturer.php ✅
- [x] แก้ไข messages.php ✅
- [x] แก้ไข scheduled.php ✅
- [x] แก้ไข shop/liff-shop-settings.php ✅
- [x] แก้ไข shop/import-products.php ✅
- [ ] แก้ไข test files (optional)
- [ ] ลบ/แก้ไข fix files (optional)
- [ ] ทดสอบระบบ

---

## หมายเหตุ

1. View `v_business_items` ถูกสร้างไว้สำหรับ backward compatibility
2. หลังจากแก้ไขทุกไฟล์แล้ว สามารถลบ View ได้
3. Backup table `business_items_backup_20251223` สามารถลบได้หลังทดสอบเรียบร้อย
