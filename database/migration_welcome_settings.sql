-- สร้างตาราง welcome_settings สำหรับข้อความต้อนรับ

CREATE TABLE IF NOT EXISTS welcome_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL COMMENT 'รหัสบัญชี LINE (null = ใช้กับทุกบัญชี)',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT 'เปิด/ปิดข้อความต้อนรับ',
    message_type ENUM('text', 'flex') DEFAULT 'text' COMMENT 'ประเภทข้อความ',
    text_content TEXT COMMENT 'ข้อความธรรมดา',
    flex_content JSON COMMENT 'Flex Message JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_welcome_line_account (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ใส่ค่าเริ่มต้น
INSERT INTO welcome_settings (is_enabled, message_type, text_content) VALUES 
(1, 'text', 'สวัสดีค่ะ ยินดีต้อนรับ! 🎉\n\nขอบคุณที่เพิ่มเราเป็นเพื่อน\nหากต้องการความช่วยเหลือ สามารถพิมพ์ข้อความมาได้เลยค่ะ');
