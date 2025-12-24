-- =====================================================
-- Migration: Pharmacist System Enhancement
-- Version: 1.0
-- Description: เพิ่ม columns สำหรับระบบเภสัชกร
-- =====================================================

-- เพิ่ม line_user_id ใน admin_users สำหรับรับ notification
ALTER TABLE admin_users 
    ADD COLUMN IF NOT EXISTS line_user_id VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS notification_enabled TINYINT(1) DEFAULT 1;

-- เพิ่ม index
ALTER TABLE admin_users ADD INDEX IF NOT EXISTS idx_line_user (line_user_id);
ALTER TABLE admin_users ADD INDEX IF NOT EXISTS idx_role_active (role, is_active);

-- เพิ่ม handled_by ใน pharmacist_notifications
ALTER TABLE pharmacist_notifications 
    ADD COLUMN IF NOT EXISTS handled_by INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pharmacist_note TEXT DEFAULT NULL;

-- สร้างตาราง triage_analytics สำหรับเก็บสถิติ
CREATE TABLE IF NOT EXISTS triage_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    line_account_id INT DEFAULT NULL,
    total_sessions INT DEFAULT 0,
    completed_sessions INT DEFAULT 0,
    escalated_sessions INT DEFAULT 0,
    urgent_sessions INT DEFAULT 0,
    avg_completion_time_minutes DECIMAL(10,2) DEFAULT 0,
    top_symptoms JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_date_account (date, line_account_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่ม completed_at ใน triage_sessions ถ้ายังไม่มี
ALTER TABLE triage_sessions 
    ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL;

-- เพิ่ม pharmacist_id ใน triage_sessions
ALTER TABLE triage_sessions 
    ADD COLUMN IF NOT EXISTS pharmacist_id INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pharmacist_note TEXT DEFAULT NULL;

-- สร้าง view สำหรับ dashboard
CREATE OR REPLACE VIEW v_pharmacist_dashboard AS
SELECT 
    pn.id,
    pn.user_id,
    pn.line_account_id,
    pn.priority,
    pn.status,
    pn.created_at,
    pn.handled_at,
    u.display_name,
    u.picture_url,
    u.phone,
    u.drug_allergies,
    ts.current_state,
    ts.triage_data
FROM pharmacist_notifications pn
LEFT JOIN users u ON pn.user_id = u.id
LEFT JOIN triage_sessions ts ON pn.triage_session_id = ts.id
ORDER BY 
    CASE pn.priority WHEN 'urgent' THEN 0 ELSE 1 END,
    pn.created_at DESC;

-- Insert sample pharmacist role if not exists
INSERT IGNORE INTO admin_users (username, password, role, is_active) 
VALUES ('pharmacist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', 1);
