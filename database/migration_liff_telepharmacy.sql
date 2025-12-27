-- =====================================================
-- Migration: LIFF Telepharmacy Redesign
-- Version: 1.0
-- Description: Tables required for LIFF Telepharmacy features
-- =====================================================

-- 1. Prescription Approvals Table
-- Requirements: 11.7, 11.9, 11.10
CREATE TABLE IF NOT EXISTS prescription_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pharmacist_id INT NULL,
    approved_items JSON NOT NULL COMMENT 'List of approved prescription items',
    status ENUM('pending', 'approved', 'rejected', 'expired', 'used') DEFAULT 'pending',
    video_call_id INT NULL COMMENT 'Link to video call consultation',
    notes TEXT NULL,
    line_account_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT '24-hour expiry from creation',
    used_at DATETIME NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_line_account_id (line_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Health Profiles Table
-- Requirements: 18.1, 18.2, 18.10
CREATE TABLE IF NOT EXISTS user_health_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(50) NOT NULL,
    line_account_id INT DEFAULT 0,
    age INT DEFAULT NULL,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL COMMENT 'Weight in kg',
    height DECIMAL(5,2) DEFAULT NULL COMMENT 'Height in cm',
    blood_type ENUM('A', 'B', 'AB', 'O', 'unknown') DEFAULT 'unknown',
    medical_conditions JSON DEFAULT NULL COMMENT 'Array of medical conditions',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (line_user_id, line_account_id),
    INDEX idx_line_user (line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Drug Allergies Table
-- Requirements: 18.4, 18.5
CREATE TABLE IF NOT EXISTS user_drug_allergies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(50) NOT NULL,
    line_account_id INT DEFAULT 0,
    drug_name VARCHAR(255) NOT NULL,
    drug_id INT DEFAULT NULL COMMENT 'Link to product if exists',
    reaction_type ENUM('rash', 'breathing', 'swelling', 'other') DEFAULT 'other',
    reaction_notes TEXT DEFAULT NULL,
    severity ENUM('mild', 'moderate', 'severe') DEFAULT 'moderate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_line_user (line_user_id),
    INDEX idx_drug (drug_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Current Medications Table
-- Requirements: 18.6, 18.7
CREATE TABLE IF NOT EXISTS user_current_medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(50) NOT NULL,
    line_account_id INT DEFAULT 0,
    medication_name VARCHAR(255) NOT NULL,
    product_id INT DEFAULT NULL COMMENT 'Link to product if exists',
    dosage VARCHAR(100) DEFAULT NULL,
    frequency VARCHAR(100) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_user (line_user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Medication Reminders Table
-- Requirements: 15.1, 15.2, 15.3
CREATE TABLE IF NOT EXISTS medication_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50),
    line_account_id INT,
    medication_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) COMMENT 'e.g., 1 tablet, 5ml',
    frequency VARCHAR(50) COMMENT 'daily, twice_daily, custom',
    reminder_times JSON COMMENT 'Array of times like ["08:00", "20:00"]',
    start_date DATE,
    end_date DATE,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    product_id INT COMMENT 'Link to product if from order',
    order_id INT COMMENT 'Link to order if from order history',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Medication Taken History Table
-- Requirements: 15.5, 15.6, 15.7
CREATE TABLE IF NOT EXISTS medication_taken_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_id INT NOT NULL,
    user_id INT NOT NULL,
    scheduled_time TIME,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('taken', 'skipped', 'missed') DEFAULT 'taken',
    notes TEXT,
    INDEX idx_reminder (reminder_id),
    INDEX idx_user (user_id),
    INDEX idx_date (taken_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Notification Preferences Table
-- Requirements: 14.1, 14.2, 14.3
CREATE TABLE IF NOT EXISTS user_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(50) NOT NULL,
    line_account_id INT DEFAULT 0,
    promotions TINYINT(1) DEFAULT 1 COMMENT 'Promotional messages',
    order_updates TINYINT(1) DEFAULT 1 COMMENT 'Order status updates',
    medication_reminders TINYINT(1) DEFAULT 1 COMMENT 'Medication reminders',
    health_tips TINYINT(1) DEFAULT 1 COMMENT 'Health tips and articles',
    appointment_reminders TINYINT(1) DEFAULT 1 COMMENT 'Appointment reminders',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (line_user_id, line_account_id),
    INDEX idx_line_user (line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Drug Interaction Acknowledgments Table
-- Requirements: 12.5
CREATE TABLE IF NOT EXISTS drug_interaction_acknowledgments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    line_user_id VARCHAR(50),
    drug1_id INT NOT NULL,
    drug2_id INT NOT NULL,
    drug1_name VARCHAR(255),
    drug2_name VARCHAR(255),
    severity ENUM('mild', 'moderate', 'severe') DEFAULT 'moderate',
    acknowledged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_id INT NULL COMMENT 'Link to order if acknowledged during checkout',
    INDEX idx_user (user_id),
    INDEX idx_drugs (drug1_id, drug2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Add is_prescription column to products if not exists
-- Requirements: 11.1, 11.2
ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS is_prescription TINYINT(1) DEFAULT 0 COMMENT 'Requires pharmacist approval',
    ADD COLUMN IF NOT EXISTS prescription_warning TEXT DEFAULT NULL COMMENT 'Warning text for Rx products';

-- Also add to business_items if exists
ALTER TABLE business_items 
    ADD COLUMN IF NOT EXISTS is_prescription TINYINT(1) DEFAULT 0 COMMENT 'Requires pharmacist approval',
    ADD COLUMN IF NOT EXISTS prescription_warning TEXT DEFAULT NULL COMMENT 'Warning text for Rx products';

-- 10. LIFF Message Bridge Logs
-- Requirements: 20.1, 20.2
CREATE TABLE IF NOT EXISTS liff_message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(50) NOT NULL,
    line_account_id INT,
    action_type VARCHAR(50) NOT NULL COMMENT 'order_placed, consultation_request, etc.',
    message_data JSON,
    sent_via ENUM('liff', 'api') DEFAULT 'liff',
    status ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (line_user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Ensure video_calls table has required columns
ALTER TABLE video_calls 
    ADD COLUMN IF NOT EXISTS pharmacist_id INT NULL COMMENT 'Assigned pharmacist',
    ADD COLUMN IF NOT EXISTS consultation_type ENUM('general', 'prescription', 'symptom', 'follow_up') DEFAULT 'general',
    ADD COLUMN IF NOT EXISTS prescription_approval_id INT NULL COMMENT 'Link to prescription approval if created';

-- 12. Ensure orders table has prescription-related columns
ALTER TABLE orders 
    ADD COLUMN IF NOT EXISTS has_prescription_items TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS prescription_approval_id INT NULL,
    ADD COLUMN IF NOT EXISTS pharmacist_approved_at DATETIME NULL;

-- Done
SELECT 'LIFF Telepharmacy migration completed successfully' as status;
