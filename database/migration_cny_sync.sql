-- =====================================================
-- Migration: CNY Pharmacy Sync Support
-- Version: 1.0
-- Date: 2025-12-17
-- Description: เพิ่ม columns สำหรับ sync สินค้าจาก CNY Pharmacy API
-- =====================================================

-- เพิ่ม extra_data column สำหรับเก็บข้อมูลเพิ่มเติมจาก API
-- สำหรับ business_items table
ALTER TABLE business_items
    ADD COLUMN IF NOT EXISTS extra_data JSON DEFAULT NULL COMMENT 'ข้อมูลเพิ่มเติมจาก API (JSON)';

-- สำหรับ products table (ถ้ามี)
-- ALTER TABLE products ADD COLUMN IF NOT EXISTS extra_data JSON DEFAULT NULL;

-- =====================================================
-- Manual SQL (สำหรับ MySQL ที่ไม่รองรับ IF NOT EXISTS):
-- =====================================================
-- ALTER TABLE business_items ADD COLUMN extra_data JSON DEFAULT NULL;
-- ALTER TABLE products ADD COLUMN extra_data JSON DEFAULT NULL;

