-- Fix cart_items foreign key constraint
-- ลบ foreign key ที่ทำให้เพิ่มสินค้าลงตะกร้าไม่ได้

-- ลบ foreign key ถ้ามี
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'cart_items' 
                  AND CONSTRAINT_NAME = 'cart_items_ibfk_2');

-- Drop foreign key if exists
ALTER TABLE cart_items DROP FOREIGN KEY IF EXISTS cart_items_ibfk_2;
ALTER TABLE cart_items DROP FOREIGN KEY IF EXISTS cart_items_ibfk_1;
ALTER TABLE cart_items DROP FOREIGN KEY IF EXISTS fk_cart_product;
ALTER TABLE cart_items DROP FOREIGN KEY IF EXISTS fk_cart_user;

-- เพิ่ม index แทน (ไม่ใช้ foreign key เพราะ product อาจอยู่ใน business_items หรือ products)
ALTER TABLE cart_items ADD INDEX IF NOT EXISTS idx_product_id (product_id);
ALTER TABLE cart_items ADD INDEX IF NOT EXISTS idx_user_id (user_id);
