-- Migration: Add mark_as_read_token column to messages table
-- For LINE Messaging API Mark as Read feature

-- Add mark_as_read_token column
ALTER TABLE messages ADD COLUMN IF NOT EXISTS mark_as_read_token VARCHAR(255) NULL AFTER reply_token;

-- Add index for faster lookup
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_mark_as_read_token (mark_as_read_token);

-- Add is_read_on_line column to track if message was marked as read on LINE
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_read_on_line TINYINT(1) DEFAULT 0 AFTER is_read;
