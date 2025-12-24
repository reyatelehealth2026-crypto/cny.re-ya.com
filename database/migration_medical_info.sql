-- =====================================================
-- Migration: Medical Info for Pharmacy
-- เพิ่มข้อมูลทางการแพทย์สำหรับร้านขายยา
-- =====================================================

-- เพิ่ม columns ในตาราง users
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS medical_conditions TEXT DEFAULT NULL COMMENT 'โรคประจำตัว',
    ADD COLUMN IF NOT EXISTS drug_allergies TEXT DEFAULT NULL COMMENT 'แพ้ยา',
    ADD COLUMN IF NOT EXISTS current_medications TEXT DEFAULT NULL COMMENT 'ยาที่ใช้อยู่';

-- สร้างตาราง dispensing_records สำหรับบันทึกการจ่ายยา
CREATE TABLE IF NOT EXISTS dispensing_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    pharmacist_id INT DEFAULT NULL COMMENT 'ID ของเภสัชกรที่จ่ายยา',
    order_number VARCHAR(50) DEFAULT NULL,
    items JSON NOT NULL COMMENT 'รายการยาที่จ่าย',
    total_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'paid',
    notes TEXT DEFAULT NULL COMMENT 'หมายเหตุการจ่ายยา',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_pharmacist (pharmacist_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
