<?php
/**
 * Audit Logger Class
 * บันทึกการเข้าถึงข้อมูลสำคัญตาม PDPA
 */
class AuditLogger {
    private static $db = null;
    
    /**
     * Initialize database connection
     */
    private static function getDb() {
        if (self::$db === null) {
            self::$db = Database::getInstance()->getConnection();
        }
        return self::$db;
    }
    
    /**
     * Log data access
     * 
     * @param string $action Action performed (view_medical_info, export_data, etc.)
     * @param string $resourceType Type of resource (user, medical_info, transaction)
     * @param int|null $resourceId ID of the resource
     * @param int|null $targetUserId User whose data was accessed
     * @param array|null $details Additional details
     */
    public static function log($action, $resourceType, $resourceId = null, $targetUserId = null, $details = null) {
        try {
            $db = self::getDb();
            
            // Get admin user ID from session
            $adminUserId = $_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? null;
            
            // Get client info
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $db->prepare("
                INSERT INTO data_access_logs 
                (admin_user_id, user_id, action, resource_type, resource_id, ip_address, user_agent, details)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $adminUserId,
                $targetUserId,
                $action,
                $resourceType,
                $resourceId,
                $ipAddress,
                $userAgent,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('AuditLogger error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log viewing medical information
     */
    public static function logViewMedicalInfo($userId) {
        return self::log('view_medical_info', 'medical_info', null, $userId);
    }
    
    /**
     * Log viewing user profile
     */
    public static function logViewUserProfile($userId) {
        return self::log('view_user_profile', 'user', $userId, $userId);
    }
    
    /**
     * Log exporting data
     */
    public static function logExportData($resourceType, $details = null) {
        return self::log('export_data', $resourceType, null, null, $details);
    }
    
    /**
     * Log viewing transaction
     */
    public static function logViewTransaction($transactionId, $userId = null) {
        return self::log('view_transaction', 'transaction', $transactionId, $userId);
    }
    
    /**
     * Log dispensing medicine
     */
    public static function logDispenseMedicine($transactionId, $userId, $details = null) {
        return self::log('dispense_medicine', 'transaction', $transactionId, $userId, $details);
    }
    
    /**
     * Log updating medical info
     */
    public static function logUpdateMedicalInfo($userId, $field, $details = null) {
        return self::log('update_medical_info', 'medical_info', null, $userId, array_merge(
            ['field' => $field],
            $details ?? []
        ));
    }
    
    /**
     * Log video call
     */
    public static function logVideoCall($userId, $action = 'start') {
        return self::log('video_call_' . $action, 'video_call', null, $userId);
    }
    
    /**
     * Check if user has given required consents
     */
    public static function checkUserConsent($userId, $requiredConsents = ['privacy_policy', 'terms_of_service']) {
        try {
            $db = self::getDb();
            
            $placeholders = implode(',', array_fill(0, count($requiredConsents), '?'));
            $stmt = $db->prepare("
                SELECT consent_type 
                FROM user_consents 
                WHERE user_id = ? AND consent_type IN ({$placeholders}) AND is_accepted = 1
            ");
            
            $params = array_merge([$userId], $requiredConsents);
            $stmt->execute($params);
            $consented = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return count($consented) === count($requiredConsents);
        } catch (Exception $e) {
            error_log('checkUserConsent error: ' . $e->getMessage());
            return false;
        }
    }
}
