-- Migration: Create dev_logs table for Developer Dashboard
-- Run this SQL to create the dev_logs table

CREATE TABLE IF NOT EXISTS dev_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('error', 'warning', 'info', 'debug', 'webhook') DEFAULT 'info',
    source VARCHAR(100) COMMENT 'Source of log (e.g., webhook, BusinessBot, LineAPI)',
    message TEXT COMMENT 'Log message',
    data JSON COMMENT 'Additional data in JSON format',
    user_id VARCHAR(100) COMMENT 'LINE user ID if applicable',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (log_type),
    INDEX idx_created (created_at),
    INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index on created_at for faster date queries
-- Note: MariaDB doesn't support functional indexes, use created_at directly
-- The idx_created index above already covers date-based queries

-- Auto-cleanup old logs (optional - run as scheduled event)
-- DELETE FROM dev_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
