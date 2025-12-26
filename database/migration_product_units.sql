-- Migration: Product Units (Multi-Unit per Product)
-- สินค้า 1 ตัวมีได้หลายหน่วย เช่น ขวด, โหล แต่ละหน่วยมีราคาต่างกัน

-- ตาราง product_units - หน่วยสินค้า
CREATE TABLE IF NOT EXISTS product_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    product_id INT NOT NULL,
    unit_name VARCHAR(50) NOT NULL COMMENT 'ชื่อหน่วย เช่น ขวด, โหล, กล่อง',
    unit_code VARCHAR(20) DEFAULT NULL COMMENT 'รหัสหน่วย เช่น BTL, DOZ, BOX',
    factor DECIMAL(10,4) NOT NULL DEFAULT 1 COMMENT 'ตัวคูณเทียบกับหน่วยหลัก เช่น โหล=12',
    cost_price DECIMAL(10,2) DEFAULT NULL COMMENT 'ราคาทุนต่อหน่วยนี้',
    sale_price DECIMAL(10,2) DEFAULT NULL COMMENT 'ราคาขายต่อหน่วยนี้',
    barcode VARCHAR(50) DEFAULT NULL COMMENT 'บาร์โค้ดของหน่วยนี้',
    is_base_unit TINYINT(1) DEFAULT 0 COMMENT 'เป็นหน่วยหลักหรือไม่',
    is_purchase_unit TINYINT(1) DEFAULT 1 COMMENT 'ใช้สำหรับสั่งซื้อ',
    is_sale_unit TINYINT(1) DEFAULT 1 COMMENT 'ใช้สำหรับขาย',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_line_account (line_account_id),
    INDEX idx_barcode (barcode),
    UNIQUE KEY unique_product_unit (product_id, unit_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่ม column unit_id ใน purchase_order_items
ALTER TABLE purchase_order_items 
ADD COLUMN IF NOT EXISTS unit_id INT DEFAULT NULL AFTER product_id,
ADD COLUMN IF NOT EXISTS unit_name VARCHAR(50) DEFAULT NULL AFTER unit_id,
ADD COLUMN IF NOT EXISTS unit_factor DECIMAL(10,4) DEFAULT 1 AFTER unit_name;

-- เพิ่ม column unit_id ใน goods_receive_items
ALTER TABLE goods_receive_items 
ADD COLUMN IF NOT EXISTS unit_id INT DEFAULT NULL AFTER product_id,
ADD COLUMN IF NOT EXISTS unit_name VARCHAR(50) DEFAULT NULL AFTER unit_id,
ADD COLUMN IF NOT EXISTS unit_factor DECIMAL(10,4) DEFAULT 1 AFTER unit_name;

-- เพิ่ม column unit_id ใน stock_movements
ALTER TABLE stock_movements 
ADD COLUMN IF NOT EXISTS unit_id INT DEFAULT NULL AFTER product_id,
ADD COLUMN IF NOT EXISTS unit_name VARCHAR(50) DEFAULT NULL AFTER unit_id,
ADD COLUMN IF NOT EXISTS unit_factor DECIMAL(10,4) DEFAULT 1 AFTER unit_name;
