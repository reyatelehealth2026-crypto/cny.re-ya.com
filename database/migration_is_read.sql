-- Migration: Add is_read column to messages table
-- สำหรับระบบ mark as read

-- เพิ่ม column is_read
ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER content;

-- เพิ่ม index สำหรับ query unread messages
ALTER TABLE messages ADD INDEX idx_is_read (is_read, direction);

-- Mark all existing messages as read
UPDATE messages SET is_read = 1 WHERE direction = 'incoming';
