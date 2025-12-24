-- =====================================================
-- Migration: Triage System for Pharmacy AI
-- Version: 2.0
-- Description: Tables for intelligent pharmacy triage
-- =====================================================

-- Triage Sessions - เก็บ session การซักประวัติ
CREATE TABLE IF NOT EXISTS triage_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_account_id INT DEFAULT NULL,
    current_state VARCHAR(50) DEFAULT 'greeting',
    triage_data JSON,
    status ENUM('active', 'completed', 'cancelled', 'escalated') DEFAULT 'active',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_pharmacist_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_line_account (line_account_id),
    INDEX idx_priority (priority, status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversation States - เก็บ state การสนทนา
CREATE TABLE IF NOT EXISTS conversation_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    current_state VARCHAR(50) NOT NULL,
    state_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacist Notifications - แจ้งเตือนเภสัชกร
CREATE TABLE IF NOT EXISTS pharmacist_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_account_id INT DEFAULT NULL,
    triage_session_id INT DEFAULT NULL,
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    notification_data JSON,
    status ENUM('pending', 'read', 'handled', 'dismissed') DEFAULT 'pending',
    handled_by INT DEFAULT NULL,
    handled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_priority (status, priority),
    INDEX idx_line_account (line_account_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (triage_session_id) REFERENCES triage_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drug Interactions - ฐานข้อมูลยาตีกัน
CREATE TABLE IF NOT EXISTS drug_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drug1_name VARCHAR(100) NOT NULL,
    drug1_generic VARCHAR(100) DEFAULT NULL,
    drug2_name VARCHAR(100) NOT NULL,
    drug2_generic VARCHAR(100) DEFAULT NULL,
    severity ENUM('mild', 'moderate', 'severe', 'contraindicated') DEFAULT 'moderate',
    description TEXT,
    recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_drug1 (drug1_name),
    INDEX idx_drug2 (drug2_name),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical History - ประวัติการรักษา
CREATE TABLE IF NOT EXISTS medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    triage_session_id INT DEFAULT NULL,
    symptoms JSON,
    diagnosis TEXT,
    medications_prescribed JSON,
    pharmacist_notes TEXT,
    follow_up_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (triage_session_id) REFERENCES triage_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns to users table if not exist
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS drug_allergies TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS medical_conditions TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS current_medications TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS blood_type VARCHAR(5) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL;

-- Add columns to business_items for pharmacy
ALTER TABLE business_items
    ADD COLUMN IF NOT EXISTS generic_name VARCHAR(200) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS dosage_form VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS strength VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS usage_instructions TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS warnings TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS contraindications TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS side_effects TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS storage_conditions VARCHAR(200) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS requires_prescription TINYINT(1) DEFAULT 0;

-- Insert sample drug interactions
INSERT IGNORE INTO drug_interactions (drug1_name, drug1_generic, drug2_name, drug2_generic, severity, description, recommendation) VALUES
('Warfarin', 'warfarin', 'Aspirin', 'aspirin', 'severe', 'เพิ่มความเสี่ยงเลือดออก', 'หลีกเลี่ยงการใช้ร่วมกัน หรือใช้ภายใต้การดูแลของแพทย์'),
('Warfarin', 'warfarin', 'Ibuprofen', 'ibuprofen', 'severe', 'เพิ่มความเสี่ยงเลือดออก', 'หลีกเลี่ยงการใช้ร่วมกัน'),
('Metformin', 'metformin', 'Alcohol', 'alcohol', 'moderate', 'เพิ่มความเสี่ยง lactic acidosis', 'หลีกเลี่ยงการดื่มแอลกอฮอล์'),
('Simvastatin', 'simvastatin', 'Grapefruit', 'grapefruit', 'moderate', 'เพิ่มระดับยาในเลือด', 'หลีกเลี่ยงการทานเกรปฟรุต'),
('Omeprazole', 'omeprazole', 'Clopidogrel', 'clopidogrel', 'moderate', 'ลดประสิทธิภาพของ clopidogrel', 'พิจารณาใช้ยาลดกรดตัวอื่น'),
('Ciprofloxacin', 'ciprofloxacin', 'Antacid', 'antacid', 'moderate', 'ลดการดูดซึมยา', 'ทานห่างกันอย่างน้อย 2 ชั่วโมง');
