<?php
/**
 * Activity Logger - บันทึก log ทุกกระบวนการในระบบ
 * รองรับ PDPA Compliance
 */

class ActivityLogger
{
    private $db;
    private static $instance = null;
    
    // ประเภท Log
    const TYPE_AUTH = 'auth';           // Login, Logout
    const TYPE_USER = 'user';           // User actions
    const TYPE_ADMIN = 'admin';         // Admin actions
    const TYPE_DATA = 'data';           // Data access/modify
    const TYPE_CONSENT = 'consent';     // Consent actions
    const TYPE_MESSAGE = 'message';     // Messages sent/received
    const TYPE_ORDER = 'order';         // Orders/Transactions
    const TYPE_PHARMACY = 'pharmacy';   // Pharmacy/Dispense actions
    const TYPE_AI = 'ai';               // AI interactions
    const TYPE_API = 'api';             // API calls
    const TYPE_SYSTEM = 'system';       // System events
    
    // Actions
    const ACTION_CREATE = 'create';
    const ACTION_READ = 'read';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_EXPORT = 'export';
    const ACTION_SEND = 'send';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    
    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $this->db = Database::getInstance()->getConnection();
        }
        $this->ensureTable();
    }
    
    public static function getInstance($db = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }
    
    /**
     * สร้างตาราง activity_logs ถ้ายังไม่มี
     */
    private function ensureTable(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    log_type VARCHAR(50) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    description TEXT,
                    
                    -- Who
                    user_id INT NULL,
                    user_name VARCHAR(255) NULL,
                    admin_id INT NULL,
                    admin_name VARCHAR(255) NULL,
                    
                    -- What
                    entity_type VARCHAR(100) NULL,
                    entity_id INT NULL,
                    old_value JSON NULL,
                    new_value JSON NULL,
                    
                    -- Where
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    request_url VARCHAR(500) NULL,
                    
                    -- Context
                    line_account_id INT NULL,
                    session_id VARCHAR(100) NULL,
                    extra_data JSON NULL,
                    
                    -- When
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    -- Indexes
                    INDEX idx_log_type (log_type),
                    INDEX idx_action (action),
                    INDEX idx_user_id (user_id),
                    INDEX idx_admin_id (admin_id),
                    INDEX idx_entity (entity_type, entity_id),
                    INDEX idx_created_at (created_at),
                    INDEX idx_line_account (line_account_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("ActivityLogger table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * บันทึก Log
     */
    public function log(
        string $type,
        string $action,
        string $description,
        array $options = []
    ): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs 
                (log_type, action, description, user_id, user_name, admin_id, admin_name,
                 entity_type, entity_id, old_value, new_value, ip_address, user_agent,
                 request_url, line_account_id, session_id, extra_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $type,
                $action,
                $description,
                $options['user_id'] ?? null,
                $options['user_name'] ?? null,
                $options['admin_id'] ?? ($_SESSION['admin_id'] ?? null),
                $options['admin_name'] ?? ($_SESSION['admin_user']['username'] ?? $_SESSION['username'] ?? null),
                $options['entity_type'] ?? null,
                $options['entity_id'] ?? null,
                isset($options['old_value']) ? json_encode($options['old_value'], JSON_UNESCAPED_UNICODE) : null,
                isset($options['new_value']) ? json_encode($options['new_value'], JSON_UNESCAPED_UNICODE) : null,
                $options['ip_address'] ?? $this->getClientIP(),
                $options['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                $options['request_url'] ?? ($_SERVER['REQUEST_URI'] ?? null),
                $options['line_account_id'] ?? ($_SESSION['current_bot_id'] ?? null),
                $options['session_id'] ?? (session_id() ?: null),
                isset($options['extra_data']) ? json_encode($options['extra_data'], JSON_UNESCAPED_UNICODE) : null
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("ActivityLogger error: " . $e->getMessage());
            return null;
        }
    }
    
    // ===== Shortcut Methods =====
    
    public function logAuth(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_AUTH, $action, $description, $options);
    }
    
    public function logUser(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_USER, $action, $description, $options);
    }
    
    public function logAdmin(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_ADMIN, $action, $description, $options);
    }
    
    public function logData(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_DATA, $action, $description, $options);
    }
    
    public function logConsent(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_CONSENT, $action, $description, $options);
    }
    
    public function logMessage(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_MESSAGE, $action, $description, $options);
    }
    
    public function logOrder(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_ORDER, $action, $description, $options);
    }
    
    public function logPharmacy(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_PHARMACY, $action, $description, $options);
    }
    
    public function logAI(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_AI, $action, $description, $options);
    }
    
    public function logAPI(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_API, $action, $description, $options);
    }
    
    public function logSystem(string $action, string $description, array $options = []): ?int
    {
        return $this->log(self::TYPE_SYSTEM, $action, $description, $options);
    }
    
    // ===== Query Methods =====
    
    /**
     * ดึง logs ตาม filters
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = 'log_type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['admin_id'])) {
            $where[] = 'admin_id = ?';
            $params[] = $filters['admin_id'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = ?';
            $params[] = $filters['entity_id'];
        }
        if (!empty($filters['line_account_id'])) {
            $where[] = 'line_account_id = ?';
            $params[] = $filters['line_account_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(description LIKE ? OR user_name LIKE ? OR admin_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM activity_logs WHERE " . implode(' AND ', $where) . 
               " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * นับจำนวน logs
     */
    public function countLogs(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = 'log_type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['line_account_id'])) {
            $where[] = 'line_account_id = ?';
            $params[] = $filters['line_account_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }
        
        $sql = "SELECT COUNT(*) FROM activity_logs WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * ดึง logs ของ user คนเดียว
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        return $this->getLogs(['user_id' => $userId], $limit);
    }
    
    /**
     * ดึง logs ของ entity
     */
    public function getEntityLogs(string $entityType, int $entityId, int $limit = 50): array
    {
        return $this->getLogs(['entity_type' => $entityType, 'entity_id' => $entityId], $limit);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Get log type label (Thai)
     */
    public static function getTypeLabel(string $type): string
    {
        $labels = [
            self::TYPE_AUTH => 'เข้าสู่ระบบ',
            self::TYPE_USER => 'ผู้ใช้',
            self::TYPE_ADMIN => 'แอดมิน',
            self::TYPE_DATA => 'ข้อมูล',
            self::TYPE_CONSENT => 'ความยินยอม',
            self::TYPE_MESSAGE => 'ข้อความ',
            self::TYPE_ORDER => 'คำสั่งซื้อ',
            self::TYPE_PHARMACY => 'เภสัชกรรม',
            self::TYPE_AI => 'AI',
            self::TYPE_API => 'API',
            self::TYPE_SYSTEM => 'ระบบ'
        ];
        return $labels[$type] ?? $type;
    }
    
    /**
     * Get action label (Thai)
     */
    public static function getActionLabel(string $action): string
    {
        $labels = [
            self::ACTION_CREATE => 'สร้าง',
            self::ACTION_READ => 'ดู',
            self::ACTION_UPDATE => 'แก้ไข',
            self::ACTION_DELETE => 'ลบ',
            self::ACTION_LOGIN => 'เข้าสู่ระบบ',
            self::ACTION_LOGOUT => 'ออกจากระบบ',
            self::ACTION_EXPORT => 'ส่งออก',
            self::ACTION_SEND => 'ส่ง',
            self::ACTION_APPROVE => 'อนุมัติ',
            self::ACTION_REJECT => 'ปฏิเสธ'
        ];
        return $labels[$action] ?? $action;
    }
}
