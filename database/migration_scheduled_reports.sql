-- Scheduled Reports Migration
-- ระบบรายงานอัตโนมัติส่งทาง LINE

CREATE TABLE IF NOT EXISTS scheduled_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    report_type ENUM('daily_sales', 'weekly_summary', 'low_stock_alert', 'pending_orders', 'custom') NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    schedule_time TIME NOT NULL DEFAULT '08:00:00',
    schedule_day TINYINT DEFAULT NULL COMMENT 'Day of week (0=Sun) or day of month',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_schedule (is_active, schedule_type, schedule_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scheduled_report_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    admin_user_id INT NOT NULL,
    line_user_id VARCHAR(50) DEFAULT NULL COMMENT 'LINE User ID for push message',
    notify_method ENUM('line', 'email', 'both') NOT NULL DEFAULT 'line',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES scheduled_reports(id) ON DELETE CASCADE,
    UNIQUE KEY unique_report_recipient (report_id, admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scheduled_report_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    sent_at DATETIME NOT NULL,
    recipients_count INT NOT NULL DEFAULT 0,
    status ENUM('success', 'partial', 'failed') NOT NULL,
    report_data JSON DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES scheduled_reports(id) ON DELETE CASCADE,
    INDEX idx_report_sent (report_id, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add LINE User ID to admin_users if not exists
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS line_user_id VARCHAR(50) DEFAULT NULL;
