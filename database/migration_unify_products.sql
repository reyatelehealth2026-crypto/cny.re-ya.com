-- =============================================
-- Migration: Unify products and business_items
-- ยึด products เป็นตารางหลัก
-- Date: 2025-12-23
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- STEP 1: เพิ่ม columns ที่ขาดใน products (จาก business_items)
-- =============================================

-- เพิ่ม sort_order (สำหรับเรียงลำดับแสดงผล)
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0 AFTER `is_featured`;

-- เพิ่ม validity_days (สำหรับ digital/service products)
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `validity_days` INT DEFAULT NULL 
COMMENT 'อายุการใช้งาน (วัน) สำหรับ digital/service' AFTER `sort_order`;

-- เพิ่ม old_business_item_id สำหรับ track ว่า migrate มาจาก business_items id ไหน
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `old_business_item_id` INT DEFAULT NULL 
COMMENT 'ID เดิมจาก business_items (สำหรับ migration)' AFTER `validity_days`;

-- เพิ่ม Index
ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_sort_order` (`sort_order`);
ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_is_featured` (`is_featured`);

-- =============================================
-- STEP 2: Migrate data จาก business_items → products
-- =============================================

-- Insert ข้อมูลจาก business_items ที่ยังไม่มีใน products
-- ใช้ SKU หรือ name+line_account_id เป็นตัวเช็คว่าซ้ำหรือไม่

INSERT INTO `products` (
    `line_account_id`,
    `category_id`,
    `name`,
    `description`,
    `price`,
    `sale_price`,
    `image_url`,
    `item_type`,
    `delivery_method`,
    `action_data`,
    `stock`,
    `max_quantity`,
    `sku`,
    `is_active`,
    `is_featured`,
    `sort_order`,
    `validity_days`,
    `barcode`,
    `manufacturer`,
    `generic_name`,
    `usage_instructions`,
    `unit`,
    `extra_data`,
    `old_business_item_id`,
    `created_at`,
    `updated_at`
)
SELECT 
    bi.`line_account_id`,
    bi.`category_id`,
    bi.`name`,
    bi.`description`,
    bi.`price`,
    bi.`sale_price`,
    bi.`image_url`,
    bi.`item_type`,
    bi.`delivery_method`,
    bi.`action_data`,
    bi.`stock`,
    bi.`max_quantity`,
    bi.`sku`,
    bi.`is_active`,
    0 AS `is_featured`,  -- default
    bi.`sort_order`,
    bi.`validity_days`,
    bi.`barcode`,
    bi.`manufacturer`,
    bi.`generic_name`,
    bi.`usage_instructions`,
    bi.`unit`,
    bi.`extra_data`,
    bi.`id` AS `old_business_item_id`,
    bi.`created_at`,
    bi.`updated_at`
FROM `business_items` bi
WHERE NOT EXISTS (
    -- เช็คว่าไม่มี product ที่ SKU เหมือนกัน (ถ้ามี SKU)
    SELECT 1 FROM `products` p 
    WHERE (bi.`sku` IS NOT NULL AND bi.`sku` != '' AND p.`sku` = bi.`sku`)
    OR (
        -- หรือ name + line_account_id เหมือนกัน
        p.`name` = bi.`name` 
        AND COALESCE(p.`line_account_id`, 0) = COALESCE(bi.`line_account_id`, 0)
    )
);

-- =============================================
-- STEP 3: สร้าง mapping table สำหรับ update references
-- =============================================

CREATE TABLE IF NOT EXISTS `business_items_to_products_map` (
    `old_business_item_id` INT NOT NULL,
    `new_product_id` INT NOT NULL,
    `migrated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`old_business_item_id`),
    INDEX `idx_new_product` (`new_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert mapping
INSERT INTO `business_items_to_products_map` (`old_business_item_id`, `new_product_id`)
SELECT `old_business_item_id`, `id` 
FROM `products` 
WHERE `old_business_item_id` IS NOT NULL
ON DUPLICATE KEY UPDATE `new_product_id` = VALUES(`new_product_id`);

-- =============================================
-- STEP 4: Update cart_items ที่อ้างอิง business_items
-- =============================================

-- Update cart_items ให้ชี้ไป products แทน business_items
-- (ถ้า cart_items.product_id อ้างอิง business_items)
UPDATE `cart_items` ci
INNER JOIN `business_items_to_products_map` m ON ci.`product_id` = m.`old_business_item_id`
SET ci.`product_id` = m.`new_product_id`
WHERE EXISTS (
    SELECT 1 FROM `business_items` bi WHERE bi.`id` = ci.`product_id`
);

-- =============================================
-- STEP 5: Update order_items ที่อ้างอิง business_items
-- =============================================

-- Update order_items ให้ชี้ไป products แทน business_items
UPDATE `order_items` oi
INNER JOIN `business_items_to_products_map` m ON oi.`product_id` = m.`old_business_item_id`
SET oi.`product_id` = m.`new_product_id`
WHERE oi.`product_id` IN (SELECT `old_business_item_id` FROM `business_items_to_products_map`);

-- =============================================
-- STEP 6: Update broadcast_items ที่อ้างอิง business_items
-- =============================================

UPDATE `broadcast_items` bi_ref
INNER JOIN `business_items_to_products_map` m ON bi_ref.`product_id` = m.`old_business_item_id`
SET bi_ref.`product_id` = m.`new_product_id`
WHERE bi_ref.`product_id` IN (SELECT `old_business_item_id` FROM `business_items_to_products_map`);

-- =============================================
-- STEP 7: สร้าง View สำหรับ backward compatibility
-- =============================================

-- สร้าง view ชื่อ business_items ที่ชี้ไป products
-- เพื่อให้ code เก่าที่ยังใช้ business_items ยังทำงานได้
DROP VIEW IF EXISTS `v_business_items`;
CREATE VIEW `v_business_items` AS
SELECT 
    `id`,
    `line_account_id`,
    `item_type`,
    `category_id`,
    `name`,
    `description`,
    `price`,
    `sale_price`,
    `image_url`,
    `action_data`,
    `delivery_method`,
    `validity_days`,
    `max_quantity`,
    `stock`,
    `sku`,
    `is_active`,
    `sort_order`,
    `created_at`,
    `updated_at`,
    `barcode`,
    `manufacturer`,
    `generic_name`,
    `usage_instructions`,
    `unit`,
    `extra_data`
FROM `products`;

-- =============================================
-- STEP 8: Backup และ Rename tables
-- =============================================

-- Backup business_items ก่อนลบ
RENAME TABLE `business_items` TO `business_items_backup_20251223`;

-- หมายเหตุ: หลังจากทดสอบแล้วว่าทุกอย่างทำงานปกติ
-- สามารถลบ backup table ได้ด้วยคำสั่ง:
-- DROP TABLE IF EXISTS `business_items_backup_20251223`;
-- DROP TABLE IF EXISTS `business_items_to_products_map`;

-- =============================================
-- STEP 9: Cleanup - ลบ column ที่ไม่จำเป็น (Optional)
-- =============================================

-- หลังจากทดสอบเรียบร้อยแล้ว สามารถลบ old_business_item_id ได้
-- ALTER TABLE `products` DROP COLUMN `old_business_item_id`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- สรุปการเปลี่ยนแปลง:
-- =============================================
-- 1. เพิ่ม columns: sort_order, validity_days, old_business_item_id ใน products
-- 2. Migrate ข้อมูลจาก business_items → products
-- 3. Update references ใน cart_items, order_items, broadcast_items
-- 4. สร้าง view v_business_items สำหรับ backward compatibility
-- 5. Backup business_items เป็น business_items_backup_20251223
-- =============================================
