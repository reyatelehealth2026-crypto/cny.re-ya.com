<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Connection Singleton
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;
    
    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    /**
     * Execute query with caching support
     */
    public function cachedQuery(string $sql, array $params = [], int $ttl = 300): array
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        // Try to get from cache (if Redis/APCu available)
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success) {
                return $cached;
            }
        }
        
        // Execute query
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // Store in cache
        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $result, $ttl);
        }
        
        return $result;
    }
}
