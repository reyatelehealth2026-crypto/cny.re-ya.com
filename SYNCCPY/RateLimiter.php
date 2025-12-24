<?php
declare(strict_types=1);

/**
 * RateLimiter - ควบคุมอัตราการเรียก API
 * 
 * ใช้ Token Bucket Algorithm เพื่อจำกัดจำนวน requests
 * ป้องกันไม่ให้ระบบยิง API เยอะเกินไปทำให้ล้น
 * 
 * Design Pattern: Token Bucket Algorithm
 * Performance: ใช้ APCu หรือ file-based cache
 */

namespace CnySync\Utils;

final class RateLimiter
{
    private string $identifier;
    private int $maxRequests;
    private int $windowSeconds;
    private string $storageMethod;
    
    /**
     * @param string $identifier ชื่อ identifier (เช่น 'cny_api')
     * @param int $maxRequests จำนวน requests สูงสุด
     * @param int $windowSeconds ช่วงเวลาที่จำกัด (วินาที)
     */
    public function __construct(
        string $identifier,
        int $maxRequests = 20,
        int $windowSeconds = 60
    ) {
        $this->identifier = $identifier;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        
        // เลือกวิธี storage (APCu > File)
        $this->storageMethod = function_exists('apcu_fetch') ? 'apcu' : 'file';
    }
    
    /**
     * ตรวจสอบว่าสามารถทำ request ได้หรือไม่
     * 
     * @return bool
     */
    public function attempt(): bool
    {
        $key = $this->getKey();
        $now = time();
        
        // ดึงข้อมูลปัจจุบัน
        $data = $this->get($key);
        
        if ($data === null) {
            // ครั้งแรก - สร้างใหม่
            $data = [
                'count' => 1,
                'reset_at' => $now + $this->windowSeconds
            ];
            $this->set($key, $data, $this->windowSeconds);
            return true;
        }
        
        // ตรวจสอบว่าหมดเวลาหรือยัง
        if ($now >= $data['reset_at']) {
            // Reset counter
            $data = [
                'count' => 1,
                'reset_at' => $now + $this->windowSeconds
            ];
            $this->set($key, $data, $this->windowSeconds);
            return true;
        }
        
        // เช็คว่าเกิน limit หรือไม่
        if ($data['count'] >= $this->maxRequests) {
            return false;
        }
        
        // เพิ่ม counter
        $data['count']++;
        $ttl = $data['reset_at'] - $now;
        $this->set($key, $data, max(1, $ttl));
        
        return true;
    }
    
    /**
     * รอจนกว่าจะสามารถทำ request ได้
     * 
     * @param int $maxWaitSeconds เวลาสูงสุดที่จะรอ (0 = ไม่จำกัด)
     * @return bool สำเร็จหรือไม่
     */
    public function wait(int $maxWaitSeconds = 0): bool
    {
        $startTime = time();
        
        while (!$this->attempt()) {
            if ($maxWaitSeconds > 0 && (time() - $startTime) >= $maxWaitSeconds) {
                return false;
            }
            
            // รอ 100ms แล้วลองใหม่
            usleep(100000);
        }
        
        return true;
    }
    
    /**
     * ดึงข้อมูลสถานะปัจจุบัน
     * 
     * @return array
     */
    public function getStatus(): array
    {
        $key = $this->getKey();
        $data = $this->get($key);
        $now = time();
        
        if ($data === null) {
            return [
                'remaining' => $this->maxRequests,
                'limit' => $this->maxRequests,
                'reset_in' => 0,
                'available' => true
            ];
        }
        
        $remaining = max(0, $this->maxRequests - $data['count']);
        $resetIn = max(0, $data['reset_at'] - $now);
        
        return [
            'remaining' => $remaining,
            'limit' => $this->maxRequests,
            'reset_in' => $resetIn,
            'available' => $remaining > 0
        ];
    }
    
    /**
     * Reset rate limiter
     */
    public function reset(): void
    {
        $key = $this->getKey();
        $this->delete($key);
    }
    
    /**
     * สร้าง cache key
     * 
     * @return string
     */
    private function getKey(): string
    {
        return "rate_limiter:{$this->identifier}";
    }
    
    /**
     * ดึงข้อมูลจาก storage
     * 
     * @param string $key
     * @return array|null
     */
    private function get(string $key): ?array
    {
        if ($this->storageMethod === 'apcu') {
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }
        
        // File-based fallback
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        
        // ตรวจสอบว่าหมดอายุหรือยัง
        if (isset($data['expires_at']) && time() >= $data['expires_at']) {
            @unlink($file);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    /**
     * บันทึกข้อมูลลง storage
     * 
     * @param string $key
     * @param array $value
     * @param int $ttl
     */
    private function set(string $key, array $value, int $ttl): void
    {
        if ($this->storageMethod === 'apcu') {
            apcu_store($key, $value, $ttl);
            return;
        }
        
        // File-based fallback
        $file = $this->getCacheFile($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * ลบข้อมูลจาก storage
     * 
     * @param string $key
     */
    private function delete(string $key): void
    {
        if ($this->storageMethod === 'apcu') {
            apcu_delete($key);
            return;
        }
        
        $file = $this->getCacheFile($key);
        @unlink($file);
    }
    
    /**
     * ดึง path ของ cache file
     * 
     * @param string $key
     * @return string
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        $dir = sys_get_temp_dir() . '/cny_sync_cache';
        return "{$dir}/{$hash}.cache";
    }
}
