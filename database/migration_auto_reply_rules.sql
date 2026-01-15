-- Auto Reply Rules Migration
-- ตาราง auto_reply_rules สำหรับกำหนดกฎการตอบกลับอัตโนมัติ

CREATE TABLE IF NOT EXISTS `auto_reply_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `line_account_id` int(11) DEFAULT NULL,
  `keyword` varchar(255) NOT NULL COMMENT 'คำสำคัญที่ต้องการตรวจจับ',
  `match_type` enum('exact','contains','starts_with','ends_with','regex') DEFAULT 'contains' COMMENT 'ประเภทการจับคู่',
  `response_type` enum('text','flex','image','video','audio') DEFAULT 'text' COMMENT 'ประเภทการตอบกลับ',
  `response_content` text NOT NULL COMMENT 'เนื้อหาการตอบกลับ (text หรือ JSON สำหรับ flex)',
  `priority` int(11) DEFAULT 0 COMMENT 'ลำดับความสำคัญ (เลขมากทำก่อน)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'เปิดใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_line_account` (`line_account_id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_active` (`is_active`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='กฎการตอบกลับอัตโนมัติ';

-- เพิ่มข้อมูลตัวอย่าง (optional)
INSERT INTO `auto_reply_rules` (`line_account_id`, `keyword`, `match_type`, `response_type`, `response_content`, `priority`, `is_active`) VALUES
(NULL, 'สวัสดี', 'contains', 'text', 'สวัสดีครับ! ยินดีต้อนรับสู่ร้านยาของเรา 😊\nพิมพ์ "เมนู" เพื่อดูเมนูหลัก', 10, 1),
(NULL, 'hello', 'contains', 'text', 'Hello! Welcome to our pharmacy 😊\nType "menu" to see main menu', 10, 1),
(NULL, 'ขอบคุณ', 'contains', 'text', 'ยินดีครับ! หากมีคำถามเพิ่มเติมสามารถสอบถามได้ตลอดเวลานะครับ 🙏', 5, 1),
(NULL, 'thank', 'contains', 'text', 'You\'re welcome! Feel free to ask if you have any questions 🙏', 5, 1);
