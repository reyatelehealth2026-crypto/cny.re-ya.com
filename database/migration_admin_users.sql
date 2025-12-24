-- =====================================================
-- Admin Users & Permissions Migration
-- ระบบจัดการผู้ดูแลและสิทธิ์การเข้าถึง LINE Bot
-- =====================================================

-- 1. Admin Users Table - ผู้ดูแลระบบ
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    display_name VARCHAR(100),
    avatar VARCHAR(500),
    role ENUM('super_admin', 'admin', 'staff') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admin Bot Access - กำหนดสิทธิ์เข้าถึง LINE Bot
CREATE TABLE IF NOT EXISTS admin_bot_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    line_account_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_edit TINYINT(1) DEFAULT 1,
    can_broadcast TINYINT(1) DEFAULT 1,
    can_manage_users TINYINT(1) DEFAULT 1,
    can_manage_shop TINYINT(1) DEFAULT 1,
    can_view_analytics TINYINT(1) DEFAULT 1,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_admin_bot (admin_id, line_account_id),
    INDEX idx_admin (admin_id),
    INDEX idx_bot (line_account_id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admin Activity Log - บันทึกกิจกรรม
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    line_account_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default super admin (password: admin123)
INSERT IGNORE INTO admin_users (id, username, password, display_name, role, is_active) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin', 1);

-- =====================================================
-- Show result
-- =====================================================
SELECT 'Admin users tables created successfully!' AS message;
