<?php
/**
 * Core Database Wrapper
 * ตัวจัดการฐานข้อมูลกลาง - ใช้ร่วมกันทุก Module
 */

namespace Modules\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection = null;
    
    private function __construct()
    {
        $this->connect();
    }
    
    /**
     * Singleton Pattern - ใช้ connection เดียวกันทั้งระบบ
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * เชื่อมต่อฐานข้อมูล
     */
    private function connect()
    {
        // ตรวจสอบว่า Database class ของระบบเก่าถูก load แล้วหรือยัง
        if (!class_exists('\Database', false)) {
            // ใช้ config เดิมของระบบ - ต้อง require config.php ก่อน
            // รองรับทั้ง config.php และ confFig.php
            $configPaths = [
                __DIR__ . '/../../config/config.php',
                __DIR__ . '/../../config/confFig.php'
            ];
            $dbPath = __DIR__ . '/../../config/database.php';
            
            foreach ($configPaths as $configPath) {
                if (file_exists($configPath)) {
                    require_once $configPath;
                    break;
                }
            }
            if (file_exists($dbPath)) {
                require_once $dbPath;
            }
        }
        
        try {
            $this->connection = \Database::getInstance()->getConnection();
        } catch (PDOException $e) {
            throw new \Exception("Cannot connect to database: " . $e->getMessage());
        }
    }
    
    /**
     * ดึง PDO Connection
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Query แบบ Prepared Statement
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * ดึงข้อมูลแถวเดียว
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * ดึงข้อมูลทั้งหมด
     * @return array
     */
    public function fetchAll(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert และ return ID
     * @return int
     */
    public function insert(string $table, array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Update
     * @return int
     */
    public function update(string $table, array $data, string $where, array $whereParams = [])
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $stmt = $this->query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }
    
    /**
     * Execute SQL (INSERT, UPDATE, DELETE)
     * @return bool
     */
    public function execute(string $sql, array $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     * @return int
     */
    public function lastInsertId()
    {
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Execute raw SQL (for DDL statements)
     * @return int
     */
    public function exec(string $sql)
    {
        return $this->connection->exec($sql);
    }
}
