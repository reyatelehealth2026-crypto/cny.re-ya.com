-- Add quick_reply column to quick_reply_templates table
-- Version: 1.0
-- Date: 2026-01-14
-- Description: Adds quick_reply column for LINE Quick Reply buttons

ALTER TABLE quick_reply_templates 
ADD COLUMN IF NOT EXISTS quick_reply TEXT NULL COMMENT 'JSON array of LINE Quick Reply items' 
AFTER category;
