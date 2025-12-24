<?php
/**
 * Cron Job: Check Inactive Users
 * รันทุกวัน: 0 2 * * * php /path/to/cron/check_inactive_users.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/CRMManager.php';

$db = Database::getInstance()->getConnection();

// Get all active LINE accounts
$stmt = $db->query("SELECT * FROM line_accounts WHERE is_active = 1");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalInactive = 0;

foreach ($accounts as $account) {
    try {
        $crm = new CRMManager($db, $account['id']);
        
        // Check for users inactive for 30 days
        $inactive = $crm->checkInactiveUsers(30);
        $totalInactive += $inactive;
        
        if ($inactive > 0) {
            echo "[{$account['name']}] Found {$inactive} inactive users\n";
        }
    } catch (Exception $e) {
        echo "[{$account['name']}] Error: " . $e->getMessage() . "\n";
    }
}

echo "Total inactive users tagged: {$totalInactive}\n";
