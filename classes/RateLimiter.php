<?php
/**
 * RateLimiter - ควบคุมอัตราการเรียก API
 * ใช้ Token Bucket Algorithm
 */

class RateLimiter
{
    private $identifier;
    private $maxRequests;
    private $windowSeconds;
    private $storageMethod;
    
    public function __construct($identifier, $maxRequests = 20, $windowSeconds = 60)
    {
        $this->identifier = $identifier;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storageMethod = function_exists('apcu_fetch') ? 'apcu' : 'file';
    }
    
    /**
     * ตรวจสอบว่าสามารถทำ request ได้หรือไม่
     */
    public function attempt()
    {
        $key = $this->getKey();
        $now = time();
        
        $data = $this->get($key);
        
        if ($data === null) {
            $data = [
                'count' => 1,
                'reset_at' => $now + $this->windowSeconds
            ];
            $this->set($key, $data, $this->windowSeconds);
            return true;
        }
        
        if ($now >= $data['reset_at']) {
            $data = [
                'count' => 1,
                'reset_at' => $now + $this->windowSeconds
            ];
            $this->set($key, $data, $this->windowSeconds);
            return true;
        }
        
        if ($data['count'] >= $this->maxRequests) {
            return false;
        }
        
        $data['count']++;
        $ttl = $data['reset_at'] - $now;
        $this->set($key, $data, max(1, $ttl));
        
        return true;
    }
    
    /**
     * รอจนกว่าจะสามารถทำ request ได้
     */
    public function wait($maxWaitSeconds = 0)
    {
        $startTime = time();
        
        while (!$this->attempt()) {
            if ($maxWaitSeconds > 0 && (time() - $startTime) >= $maxWaitSeconds) {
                return false;
            }
            usleep(100000); // 100ms
        }
        
        return true;
    }
    
    /**
     * ดึงข้อมูลสถานะปัจจุบัน
     */
    public function getStatus()
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
    public function reset()
    {
        $key = $this->getKey();
        $this->delete($key);
    }
    
    private function getKey()
    {
        return "rate_limiter:{$this->identifier}";
    }
    
    private function get($key)
    {
        if ($this->storageMethod === 'apcu') {
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }
        
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        
        if (isset($data['expires_at']) && time() >= $data['expires_at']) {
            @unlink($file);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    private function set($key, $value, $ttl)
    {
        if ($this->storageMethod === 'apcu') {
            apcu_store($key, $value, $ttl);
            return;
        }
        
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
    
    private function delete($key)
    {
        if ($this->storageMethod === 'apcu') {
            apcu_delete($key);
            return;
        }
        
        $file = $this->getCacheFile($key);
        @unlink($file);
    }
    
    private function getCacheFile($key)
    {
        $hash = md5($key);
        $dir = sys_get_temp_dir() . '/cny_sync_cache';
        return "{$dir}/{$hash}.cache";
    }
}
