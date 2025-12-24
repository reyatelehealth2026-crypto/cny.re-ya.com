<?php
/**
 * Cron Job: Process Broadcast Queue
 * รันทุกนาที: * * * * * php /path/to/cron/process_broadcast_queue.php
 * 
 * สำหรับ Broadcast หาคนจำนวนมาก (10,000+) ทยอยส่งเพื่อป้องกัน Rate Limit
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LineAPI.php';
require_once __DIR__ . '/../classes/LineAccountManager.php';

$db = Database::getInstance()->getConnection();
$lineManager = new LineAccountManager($db);

// Process up to 100 messages per run (LINE rate limit friendly)
$batchSize = 100;

$stmt = $db->prepare("SELECT q.*, b.content, b.message_type, b.line_account_id, u.line_user_id 
                      FROM broadcast_queue q 
                      JOIN broadcasts b ON q.broadcast_id = b.id 
                      JOIN users u ON q.user_id = u.id 
                      WHERE q.status = 'pending' 
                      ORDER BY q.created_at ASC 
                      LIMIT ?");
$stmt->execute([$batchSize]);
$queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($queue)) {
    exit("No pending broadcasts\n");
}

$sent = 0;
$failed = 0;
$currentAccountId = null;
$line = null;

foreach ($queue as $item) {
    // Get LINE API for this account
    if ($currentAccountId !== $item['line_account_id']) {
        $currentAccountId = $item['line_account_id'];
        $line = $lineManager->getLineAPI($currentAccountId);
    }
    
    try {
        // Build message
        if ($item['message_type'] === 'flex') {
            $content = json_decode($item['content'], true);
            $message = ['type' => 'flex', 'altText' => 'ข้อความ', 'contents' => $content];
        } else {
            $message = ['type' => 'text', 'text' => $item['content']];
        }
        
        // Send
        $result = $line->pushMessage($item['line_user_id'], $message);
        
        if ($result['code'] === 200) {
            $stmt = $db->prepare("UPDATE broadcast_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $stmt->execute([$item['id']]);
            $sent++;
        } else {
            $stmt = $db->prepare("UPDATE broadcast_queue SET status = 'failed', error_message = ? WHERE id = ?");
            $stmt->execute([$result['body'] ?? 'Unknown error', $item['id']]);
            $failed++;
        }
        
        // Small delay to respect rate limits
        usleep(50000); // 50ms
        
    } catch (Exception $e) {
        $stmt = $db->prepare("UPDATE broadcast_queue SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute([$e->getMessage(), $item['id']]);
        $failed++;
    }
}

// Update broadcast sent_count
$stmt = $db->query("UPDATE broadcasts b SET sent_count = (SELECT COUNT(*) FROM broadcast_queue WHERE broadcast_id = b.id AND status = 'sent')");

echo "Processed: {$sent} sent, {$failed} failed\n";
