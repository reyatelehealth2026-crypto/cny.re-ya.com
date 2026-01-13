-- Migration: Vibe Selling OS v2 (Pharmacy Edition)
-- Description: Creates tables for AI-Powered Pharmacy Assistant
-- Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6

-- =============================================
-- DRUG INTERACTIONS TABLE (Requirements: 10.5)
-- =============================================
-- Stores drug-drug interaction data for pharmacy safety checks
CREATE TABLE IF NOT EXISTS drug_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drug1_name VARCHAR(255) NOT NULL COMMENT 'First drug brand name',
    drug1_generic VARCHAR(255) NULL COMMENT 'First drug generic name',
    drug2_name VARCHAR(255) NOT NULL COMMENT 'Second drug brand name',
    drug2_generic VARCHAR(255) NULL COMMENT 'Second drug generic name',
    severity ENUM('mild', 'moderate', 'severe', 'contraindicated') NOT NULL DEFAULT 'moderate' COMMENT 'Interaction severity level',
    description TEXT NULL COMMENT 'Description of the interaction effect',
    recommendation TEXT NULL COMMENT 'Clinical recommendation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_drug1 (drug1_name),
    INDEX idx_drug2 (drug2_name),
    INDEX idx_drug1_generic (drug1_generic),
    INDEX idx_drug2_generic (drug2_generic),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample drug interactions for common pharmacy scenarios
INSERT INTO drug_interactions (drug1_name, drug1_generic, drug2_name, drug2_generic, severity, description, recommendation) VALUES
('Warfarin', 'warfarin', 'Aspirin', 'aspirin', 'severe', 'เพิ่มความเสี่ยงเลือดออก', 'หลีกเลี่ยงการใช้ร่วมกัน หรือติดตามค่า INR อย่างใกล้ชิด'),
('Warfarin', 'warfarin', 'Ibuprofen', 'ibuprofen', 'severe', 'เพิ่มความเสี่ยงเลือดออกในทางเดินอาหาร', 'หลีกเลี่ยงการใช้ร่วมกัน พิจารณาใช้ Paracetamol แทน'),
('Metformin', 'metformin', 'Alcohol', 'alcohol', 'moderate', 'เพิ่มความเสี่ยง Lactic acidosis', 'แนะนำให้หลีกเลี่ยงการดื่มแอลกอฮอล์'),
('Simvastatin', 'simvastatin', 'Grapefruit', 'grapefruit', 'moderate', 'เพิ่มระดับยาในเลือด เสี่ยงต่อผลข้างเคียง', 'หลีกเลี่ยงการรับประทานเกรปฟรุต'),
('Ciprofloxacin', 'ciprofloxacin', 'Antacid', 'antacid', 'moderate', 'ลดการดูดซึมยา Ciprofloxacin', 'รับประทานยาห่างกันอย่างน้อย 2 ชั่วโมง'),
('Omeprazole', 'omeprazole', 'Clopidogrel', 'clopidogrel', 'moderate', 'ลดประสิทธิภาพของ Clopidogrel', 'พิจารณาใช้ Pantoprazole แทน'),
('Fluoxetine', 'fluoxetine', 'Tramadol', 'tramadol', 'severe', 'เสี่ยงต่อ Serotonin syndrome', 'หลีกเลี่ยงการใช้ร่วมกัน'),
('Lisinopril', 'lisinopril', 'Potassium', 'potassium', 'moderate', 'เพิ่มระดับโพแทสเซียมในเลือด', 'ติดตามระดับโพแทสเซียมอย่างสม่ำเสมอ'),
('Amlodipine', 'amlodipine', 'Simvastatin', 'simvastatin', 'moderate', 'เพิ่มระดับ Simvastatin ในเลือด', 'จำกัดขนาด Simvastatin ไม่เกิน 20 mg/day'),
('Methotrexate', 'methotrexate', 'NSAIDs', 'nsaids', 'severe', 'เพิ่มความเป็นพิษของ Methotrexate', 'หลีกเลี่ยงการใช้ร่วมกัน'),
('Digoxin', 'digoxin', 'Amiodarone', 'amiodarone', 'severe', 'เพิ่มระดับ Digoxin ในเลือด', 'ลดขนาด Digoxin ลง 50% และติดตามระดับยา'),
('Theophylline', 'theophylline', 'Ciprofloxacin', 'ciprofloxacin', 'severe', 'เพิ่มระดับ Theophylline ในเลือด', 'ลดขนาด Theophylline และติดตามระดับยา'),
('Paracetamol', 'paracetamol', 'Alcohol', 'alcohol', 'moderate', 'เพิ่มความเสี่ยงต่อตับ', 'หลีกเลี่ยงการดื่มแอลกอฮอล์ขณะใช้ยา'),
('Diphenhydramine', 'diphenhydramine', 'Alcohol', 'alcohol', 'moderate', 'เพิ่มฤทธิ์กดประสาท ง่วงซึมมากขึ้น', 'หลีกเลี่ยงการขับรถหรือทำงานกับเครื่องจักร'),
('Loratadine', 'loratadine', 'Ketoconazole', 'ketoconazole', 'mild', 'เพิ่มระดับ Loratadine ในเลือดเล็กน้อย', 'ไม่จำเป็นต้องปรับขนาดยา')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Vibe Selling Settings
-- Stores v2 toggle and configuration settings (Requirements: 10.6)
CREATE TABLE IF NOT EXISTS vibe_selling_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_vibe_setting (line_account_id, setting_key),
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO vibe_selling_settings (line_account_id, setting_key, setting_value) VALUES
(NULL, 'v2_enabled', '0'),
(NULL, 'auto_switch_on_error', '1'),
(NULL, 'show_v2_badge', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Customer Health Profiles
-- Stores customer communication style classification and health summary
CREATE TABLE IF NOT EXISTS customer_health_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    communication_type ENUM('A', 'B', 'C') COMMENT 'A=Direct, B=Concerned, C=Detailed',
    confidence DECIMAL(3,2) DEFAULT 0.00,
    chronic_conditions JSON COMMENT 'List of chronic conditions',
    communication_tips TEXT,
    last_analyzed_at DATETIME,
    message_count_analyzed INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (communication_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Symptom Analysis Cache
-- Caches AI analysis results for symptom images
CREATE TABLE IF NOT EXISTS symptom_analysis_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_hash VARCHAR(64) NOT NULL UNIQUE,
    image_url TEXT,
    analysis_result JSON COMMENT 'Condition, severity, recommendations',
    is_urgent TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX idx_hash (image_hash),
    INDEX idx_urgent (is_urgent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drug Recognition Cache
-- Caches AI drug identification results from photos
CREATE TABLE IF NOT EXISTS drug_recognition_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_hash VARCHAR(64) NOT NULL UNIQUE,
    image_url TEXT,
    drug_name VARCHAR(255),
    generic_name VARCHAR(255),
    matched_product_id INT,
    recognition_result JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX idx_hash (image_hash),
    INDEX idx_product (matched_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Prescription OCR Results
-- Stores extracted data from prescription images
CREATE TABLE IF NOT EXISTS prescription_ocr_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_hash VARCHAR(64) NOT NULL,
    image_url TEXT,
    extracted_drugs JSON COMMENT 'List of drugs from prescription',
    doctor_name VARCHAR(255),
    hospital_name VARCHAR(255),
    prescription_date DATE,
    ocr_confidence DECIMAL(3,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_hash (image_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacy Ghost Draft Learning
-- Stores pharmacist edits to AI drafts for learning
CREATE TABLE IF NOT EXISTS pharmacy_ghost_learning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_message TEXT NOT NULL,
    ai_draft TEXT NOT NULL,
    pharmacist_final TEXT NOT NULL,
    edit_distance INT COMMENT 'Levenshtein distance',
    was_accepted TINYINT(1) DEFAULT 0,
    context JSON COMMENT 'Stage, health profile, symptoms, etc.',
    mentioned_drugs JSON COMMENT 'Drugs mentioned in conversation',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_accepted (was_accepted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consultation Stage Tracking
-- Tracks current consultation stage per customer
CREATE TABLE IF NOT EXISTS consultation_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage ENUM('symptom_assessment', 'drug_recommendation', 'purchase', 'follow_up') NOT NULL,
    confidence DECIMAL(3,2) DEFAULT 0.00,
    signals JSON COMMENT 'Detected signals',
    has_urgent_symptoms TINYINT(1) DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user (user_id),
    INDEX idx_stage (stage),
    INDEX idx_urgent (has_urgent_symptoms)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Pharmacy Context Keywords
-- Maps keywords to HUD widgets for context-aware display
CREATE TABLE IF NOT EXISTS pharmacy_context_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    keyword_type ENUM('symptom', 'drug', 'condition', 'action') NOT NULL,
    widget_type ENUM('drug_info', 'interaction', 'symptom', 'allergy', 'pricing', 'pregnancy') NOT NULL,
    related_data JSON COMMENT 'Related drug IDs, condition info, etc.',
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_keyword (keyword),
    INDEX idx_widget (widget_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consultation Analytics
-- Stores analytics data for pharmacy consultations
CREATE TABLE IF NOT EXISTS consultation_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pharmacist_id INT,
    communication_type ENUM('A', 'B', 'C'),
    stage_at_close VARCHAR(50),
    response_time_avg INT COMMENT 'Average response time in seconds',
    message_count INT,
    ai_suggestions_shown INT DEFAULT 0,
    ai_suggestions_accepted INT DEFAULT 0,
    resulted_in_purchase TINYINT(1) DEFAULT 0,
    purchase_amount DECIMAL(12,2),
    symptom_categories JSON COMMENT 'Categories of symptoms discussed',
    drugs_recommended JSON COMMENT 'Drugs recommended in consultation',
    successful_patterns JSON COMMENT 'Patterns that led to purchase',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_pharmacist (pharmacist_id),
    INDEX idx_purchase (resulted_in_purchase),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default pharmacy context keywords for common symptoms
INSERT INTO pharmacy_context_keywords (keyword, keyword_type, widget_type, related_data, priority) VALUES
('ปวดหัว', 'symptom', 'symptom', '{"category": "pain", "severity": "mild"}', 10),
('headache', 'symptom', 'symptom', '{"category": "pain", "severity": "mild"}', 10),
('ไข้', 'symptom', 'symptom', '{"category": "fever", "severity": "moderate"}', 15),
('fever', 'symptom', 'symptom', '{"category": "fever", "severity": "moderate"}', 15),
('ไอ', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10),
('cough', 'symptom', 'symptom', '{"category": "respiratory", "severity": "mild"}', 10),
('ท้องเสีย', 'symptom', 'symptom', '{"category": "digestive", "severity": "moderate"}', 12),
('diarrhea', 'symptom', 'symptom', '{"category": "digestive", "severity": "moderate"}', 12),
('ผื่น', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10),
('rash', 'symptom', 'symptom', '{"category": "skin", "severity": "mild"}', 10),
('แพ้ยา', 'condition', 'allergy', '{"alert": true}', 20),
('drug allergy', 'condition', 'allergy', '{"alert": true}', 20),
('ตั้งครรภ์', 'condition', 'pregnancy', '{"alert": true}', 25),
('pregnancy', 'condition', 'pregnancy', '{"alert": true}', 25),
('ให้นมบุตร', 'condition', 'pregnancy', '{"alert": true}', 25),
('breastfeeding', 'condition', 'pregnancy', '{"alert": true}', 25),
('ยาตีกัน', 'action', 'interaction', '{"check_required": true}', 20),
('drug interaction', 'action', 'interaction', '{"check_required": true}', 20)
ON DUPLICATE KEY UPDATE priority = VALUES(priority);
