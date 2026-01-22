<?php
/**
 * Cron Job: Process Drip Campaigns
 * รันทุก 5 นาที: * / 5 * * * * php /path/to/cron/process_drip_campaigns.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';
require_once __DIR__ . '/../classes/CRMManager.php';

$db = Database::getInstance()->getConnection();
$lineManager = new LineAccountManager($db);

// Get all active LINE accounts
$stmt = $db->query("SELECT * FROM line_accounts WHERE is_active = 1");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSent = 0;

foreach ($accounts as $account) {
    try {
        $line = $lineManager->getLineAPI($account['id']);
        $crm = new CRMManager($db, $account['id']);

        $sent = $crm->processDripCampaigns($line);
        $totalSent += $sent;

        if ($sent > 0) {
            echo "[{$account['name']}] Sent {$sent} drip messages\n";
        }
    } catch (Exception $e) {
        echo "[{$account['name']}] Error: " . $e->getMessage() . "\n";
    }
}

echo "Total drip messages sent: {$totalSent}\n";
