-- Migration: Quick Access Preferences
-- ให้ผู้ใช้เลือก Quick Access Menu ได้เอง

CREATE TABLE IF NOT EXISTS admin_quick_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    menu_key VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_menu (admin_user_id, menu_key),
    INDEX idx_admin_user (admin_user_id),
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default quick access items for reference
-- menu_key values: messages, orders, products, broadcast, users, auto-reply, 
--                  analytics, rich-menu, appointments, pharmacist, sync, ai-settings
