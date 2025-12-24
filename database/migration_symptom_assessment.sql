-- Migration: Symptom Assessment System
-- สร้างตารางสำหรับบันทึกผลการประเมินอาการ

-- ตาราง symptom_assessments
CREATE TABLE IF NOT EXISTS symptom_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    line_user_id VARCHAR(100) NULL,
    
    -- ข้อมูลผู้ป่วย
    gender VARCHAR(10) NULL,
    age INT NULL,
    weight DECIMAL(5,2) NULL,
    
    -- อาการ
    symptoms JSON NOT NULL COMMENT 'อาการหลักที่เลือก',
    other_symptoms TEXT NULL COMMENT 'อาการอื่นๆ',
    severity INT DEFAULT 5 COMMENT 'ความรุนแรง 1-10',
    duration VARCHAR(50) NULL COMMENT 'ระยะเวลาที่เป็น',
    
    -- ประวัติสุขภาพ
    conditions JSON NULL COMMENT 'โรคประจำตัว',
    allergies TEXT NULL COMMENT 'แพ้ยา',
    current_medications TEXT NULL COMMENT 'ยาที่ใช้อยู่',
    
    -- ผลการวิเคราะห์
    risk_level ENUM('low', 'medium', 'high') DEFAULT 'low',
    analysis TEXT NULL COMMENT 'การวิเคราะห์จาก AI',
    recommendation TEXT NULL COMMENT 'คำแนะนำ',
    warning TEXT NULL COMMENT 'คำเตือน',
    
    -- แหล่งอ้างอิง
    references_json JSON NULL COMMENT 'แหล่งอ้างอิงทางการแพทย์',
    
    -- ยาที่แนะนำ
    recommended_medications JSON NULL COMMENT 'ยาที่แนะนำ',
    
    -- Metadata
    assessment_id VARCHAR(50) NULL COMMENT 'รหัสการประเมิน',
    line_account_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_line_user_id (line_user_id),
    INDEX idx_risk_level (risk_level),
    INDEX idx_created_at (created_at),
    INDEX idx_line_account_id (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง symptom_assessment_followups (ติดตามผล)
CREATE TABLE IF NOT EXISTS symptom_assessment_followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    followup_date DATE NOT NULL,
    status ENUM('pending', 'improved', 'same', 'worse', 'consulted_doctor') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assessment_id) REFERENCES symptom_assessments(id) ON DELETE CASCADE,
    INDEX idx_assessment_id (assessment_id),
    INDEX idx_followup_date (followup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


