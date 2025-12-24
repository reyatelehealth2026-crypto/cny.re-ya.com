<?php
declare(strict_types=1);

/**
 * CNY Sync System Configuration
 * 
 * ไฟล์นี้เก็บค่า config ทั้งหมดของระบบ sync
 * แยกออกจาก database config เพื่อความยืดหยุ่น
 */

namespace CnySync\Config;

final class SyncConfig
{
    // ==================== Queue Settings ====================
    public const BATCH_SIZE = 10; // จำนวน jobs ที่ประมวลผลต่อรอบ
    public const DELAY_BETWEEN_JOBS_MS = 500; // หน่วงเวลาระหว่าง jobs (ป้องกัน API overload)
    public const MAX_RETRY_ATTEMPTS = 3; // จำนวนครั้งสูงสุดที่ลองใหม่
    public const JOB_TIMEOUT_SECONDS = 30; // Timeout สำหรับแต่ละ job
    
    // ==================== API Settings ====================
    public const API_TIMEOUT = 30; // Timeout สำหรับ API requests
    public const API_CONNECT_TIMEOUT = 10; // Timeout สำหรับการ connect
    public const ENABLE_RATE_LIMITING = true;
    public const MAX_REQUESTS_PER_MINUTE = 20; // จำกัด request ไม่ให้เกิน 20 ต่อนาที
    
    // ==================== Worker Settings ====================
    public const MAX_CONCURRENT_WORKERS = 1; // รัน worker ครั้งละ 1 ตัวเพื่อความปลอดภัย
    public const WORKER_MEMORY_LIMIT = '256M';
    public const WORKER_TIME_LIMIT = 300; // 5 นาที
    
    // ==================== Cache Settings ====================
    public const CACHE_API_RESPONSE = true; // Cache response จาก API
    public const CACHE_TTL_SECONDS = 3600; // 1 ชั่วโมง
    
    // ==================== Logging ====================
    public const ENABLE_DETAILED_LOGGING = true;
    public const LOG_SLOW_QUERIES_MS = 1000; // Log queries ที่ช้ากว่า 1 วินาที
    
    // ==================== Priority Levels ====================
    public const PRIORITY_CRITICAL = 1;
    public const PRIORITY_HIGH = 3;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LOW = 7;
    public const PRIORITY_VERY_LOW = 10;
    
    /**
     * ดึง config จาก database (แบบ dynamic)
     * 
     * @param \PDO $db
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(\PDO $db, string $key, mixed $default = null): mixed
    {
        static $cache = [];
        
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        
        $stmt = $db->prepare("SELECT config_value FROM sync_config WHERE config_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        
        $value = $result !== false ? $result : $default;
        $cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * บันทึก config ลง database
     * 
     * @param \PDO $db
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return bool
     */
    public static function set(\PDO $db, string $key, mixed $value, ?string $description = null): bool
    {
        $stmt = $db->prepare(
            "INSERT INTO sync_config (config_key, config_value, description) 
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                config_value = VALUES(config_value),
                description = COALESCE(VALUES(description), description)"
        );
        
        return $stmt->execute([
            $key,
            is_array($value) || is_object($value) ? json_encode($value) : (string)$value,
            $description
        ]);
    }
}
