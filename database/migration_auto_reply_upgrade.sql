-- Migration: Upgrade Auto Reply with Sender, Quick Reply, Alt Text
-- เพิ่มฟีเจอร์ใหม่สำหรับ Auto Reply

ALTER TABLE auto_replies ADD COLUMN alt_text VARCHAR(400) DEFAULT NULL COMMENT 'Alt text for Flex Message' AFTER reply_content;
ALTER TABLE auto_replies ADD COLUMN sender_name VARCHAR(100) DEFAULT NULL COMMENT 'Custom sender name' AFTER alt_text;
ALTER TABLE auto_replies ADD COLUMN sender_icon VARCHAR(500) DEFAULT NULL COMMENT 'Custom sender icon URL' AFTER sender_name;
ALTER TABLE auto_replies ADD COLUMN quick_reply JSON DEFAULT NULL COMMENT 'Quick reply buttons JSON' AFTER sender_icon;
ALTER TABLE auto_replies ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT 'Rule description' AFTER keyword;
ALTER TABLE auto_replies ADD COLUMN tags VARCHAR(255) DEFAULT NULL COMMENT 'Tags for categorization' AFTER description;
ALTER TABLE auto_replies ADD COLUMN use_count INT DEFAULT 0 COMMENT 'Number of times used' AFTER priority;
ALTER TABLE auto_replies ADD COLUMN last_used_at TIMESTAMP NULL COMMENT 'Last time this rule was triggered' AFTER use_count;
