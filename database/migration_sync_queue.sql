-- CNY Sync Queue System - Database Schema
-- สำหรับระบบ sync แบบ queue-based

-- ตาราง sync_queue - เก็บ jobs ที่รอ sync
CREATE TABLE IF NOT EXISTS sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    priority TINYINT DEFAULT 5 COMMENT '1=highest, 10=lowest',
    attempts TINYINT DEFAULT 0,
    max_attempts TINYINT DEFAULT 3,
    api_data JSON NULL COMMENT 'Cached API response',
    result JSON NULL COMMENT 'Sync result',
    error_message TEXT NULL,
    processing_started_at DATETIME NULL,
    processing_completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY idx_sku (sku),
    INDEX idx_status (status),
    INDEX idx_priority_status (priority, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง sync_batches - เก็บข้อมูล batch
CREATE TABLE IF NOT EXISTS sync_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(255) NOT NULL,
    total_jobs INT DEFAULT 0,
    completed_jobs INT DEFAULT 0,
    failed_jobs INT DEFAULT 0,
    skipped_jobs INT DEFAULT 0,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง sync_logs - เก็บ log การ sync
CREATE TABLE IF NOT EXISTS sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NULL,
    sku VARCHAR(50) NULL,
    action VARCHAR(50) NOT NULL COMMENT 'created, updated, skipped, failed',
    duration_ms INT NULL COMMENT 'Processing time in milliseconds',
    details JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_queue_id (queue_id),
    INDEX idx_sku (sku),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง sync_config - เก็บ config แบบ dynamic
CREATE TABLE IF NOT EXISTS sync_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT NULL,
    description VARCHAR(255) NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default config
INSERT IGNORE INTO sync_config (config_key, config_value, description) VALUES
('batch_size', '10', 'จำนวน jobs ต่อ batch'),
('delay_between_jobs', '500', 'หน่วงเวลาระหว่าง jobs (ms)'),
('max_requests_per_minute', '20', 'จำกัด API requests ต่อนาที'),
('max_retry_attempts', '3', 'จำนวนครั้งสูงสุดที่ลองใหม่'),
('enable_rate_limiting', '1', 'เปิดใช้ rate limiting');

-- View สำหรับดูสรุป queue
CREATE OR REPLACE VIEW v_queue_summary AS
SELECT 
    status,
    COUNT(*) as count,
    AVG(attempts) as avg_attempts
FROM sync_queue
GROUP BY status;

-- View สำหรับดู batch progress
CREATE OR REPLACE VIEW v_batch_progress AS
SELECT 
    b.*,
    ROUND((b.completed_jobs + b.failed_jobs + b.skipped_jobs) / NULLIF(b.total_jobs, 0) * 100, 2) as progress_percent,
    TIMESTAMPDIFF(SECOND, b.started_at, COALESCE(b.completed_at, NOW())) as duration_seconds
FROM sync_batches b;
