-- Migration: Add product detail columns for CNY Pharmacy API data
-- Version: 1.0
-- Date: 2025-12-21

-- Add new columns to products table (if not exist)
ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS barcode VARCHAR(100) NULL AFTER sku,
    ADD COLUMN IF NOT EXISTS manufacturer VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS generic_name VARCHAR(255) NULL AFTER manufacturer,
    ADD COLUMN IF NOT EXISTS usage_instructions TEXT NULL AFTER generic_name,
    ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT 'ชิ้น' AFTER usage_instructions,
    ADD COLUMN IF NOT EXISTS extra_data JSON NULL AFTER unit;

-- Add index for barcode
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_barcode (barcode);

-- Add new columns to business_items table (if exist)
ALTER TABLE business_items 
    ADD COLUMN IF NOT EXISTS barcode VARCHAR(100) NULL AFTER sku,
    ADD COLUMN IF NOT EXISTS manufacturer VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS generic_name VARCHAR(255) NULL AFTER manufacturer,
    ADD COLUMN IF NOT EXISTS usage_instructions TEXT NULL AFTER generic_name,
    ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT 'ชิ้น' AFTER usage_instructions,
    ADD COLUMN IF NOT EXISTS extra_data JSON NULL AFTER unit;

-- Add index for barcode in business_items
ALTER TABLE business_items ADD INDEX IF NOT EXISTS idx_barcode (barcode);
