-- Migration: Video Calls V2 - Add signaling columns
-- เพิ่ม columns สำหรับ WebRTC signaling

-- Add from_who column if not exists
ALTER TABLE video_call_signals 
ADD COLUMN IF NOT EXISTS from_who VARCHAR(20) DEFAULT 'customer' AFTER signal_data;

-- Add processed column if not exists  
ALTER TABLE video_call_signals
ADD COLUMN IF NOT EXISTS processed TINYINT(1) DEFAULT 0 AFTER from_who;

-- Add index for faster signal polling
ALTER TABLE video_call_signals
ADD INDEX IF NOT EXISTS idx_signal_poll (call_id, from_who, processed);
