-- Migration: Add HTML content fields to business_items
-- These fields store rich HTML content from CNY products

-- Add description field (LONGTEXT for full HTML)
ALTER TABLE business_items 
ADD COLUMN IF NOT EXISTS description LONGTEXT NULL AFTER name_en;

-- Add usage_instructions field (LONGTEXT for full HTML)
ALTER TABLE business_items 
ADD COLUMN IF NOT EXISTS usage_instructions LONGTEXT NULL AFTER description;

-- Modify properties_other to LONGTEXT if exists
ALTER TABLE business_items 
MODIFY COLUMN properties_other LONGTEXT NULL;
