-- =====================================================
-- Sample Products Seed
-- เพิ่มสินค้าตัวอย่างเพื่อทดสอบระบบ
-- =====================================================

-- ตรวจสอบและเพิ่มหมวดหมู่ถ้ายังไม่มี
INSERT IGNORE INTO product_categories (id, name, description, sort_order, is_active) VALUES
(1, 'สินค้าแนะนำ', 'สินค้าแนะนำพิเศษ', 1, 1),
(2, 'สินค้าใหม่', 'สินค้ามาใหม่', 2, 1),
(3, 'โปรโมชั่น', 'สินค้าลดราคา', 3, 1);

-- เพิ่มสินค้าตัวอย่าง
INSERT INTO products (category_id, name, description, price, sale_price, stock, image_url, is_active) VALUES
(1, 'สินค้าตัวอย่าง 1', 'รายละเอียดสินค้าตัวอย่าง 1', 299.00, NULL, 100, 'https://via.placeholder.com/400x400/06C755/FFFFFF?text=Product+1', 1),
(1, 'สินค้าตัวอย่าง 2', 'รายละเอียดสินค้าตัวอย่าง 2', 499.00, 399.00, 50, 'https://via.placeholder.com/400x400/3B82F6/FFFFFF?text=Product+2', 1),
(2, 'สินค้าใหม่ 1', 'สินค้ามาใหม่ล่าสุด', 199.00, NULL, 200, 'https://via.placeholder.com/400x400/8B5CF6/FFFFFF?text=New+1', 1),
(2, 'สินค้าใหม่ 2', 'สินค้ามาใหม่ยอดนิยม', 599.00, 499.00, 30, 'https://via.placeholder.com/400x400/EF4444/FFFFFF?text=New+2', 1),
(3, 'สินค้าลดราคา', 'ลดราคาพิเศษ!', 999.00, 599.00, 10, 'https://via.placeholder.com/400x400/F59E0B/FFFFFF?text=Sale', 1);

-- แสดงผลลัพธ์
SELECT 'Sample products added!' AS message;
SELECT COUNT(*) AS total_products FROM products WHERE is_active = 1;
