<?php
/**
 * Reward Notification Functions
 * Separated from rewards.php to avoid execution order issues
 */

/**
 * Send LINE notification for redemption status change
 */
function sendRedemptionNotification($db, $lineAccountId, $redemption, $status) {
    try {
        error_log("sendRedemptionNotification called:");
        error_log("  - lineAccountId: $lineAccountId");
        error_log("  - status: $status");
        error_log("  - redemption: " . json_encode($redemption));

        // Get LINE account credentials
        $stmt = $db->prepare("SELECT channel_access_token FROM line_accounts WHERE id = ?");
        $stmt->execute([$lineAccountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("  - account found: " . ($account ? 'YES' : 'NO'));
        error_log("  - has token: " . (!empty($account['channel_access_token']) ? 'YES' : 'NO'));

        if (!$account || empty($account['channel_access_token'])) {
            error_log("  - FAILED: No account or token");
            return false;
        }

        $messages = [
            'approved' => [
                'title' => '✅ รางวัลได้รับการอนุมัติ',
                'body' => "รางวัล: {$redemption['reward_name']}\nรหัส: {$redemption['redemption_code']}\n\nกรุณาติดต่อรับรางวัลที่ร้าน"
            ],
            'delivered' => [
                'title' => '🎁 ส่งมอบรางวัลแล้ว',
                'body' => "รางวัล: {$redemption['reward_name']}\nรหัส: {$redemption['redemption_code']}\n\nขอบคุณที่ใช้บริการ"
            ],
            'cancelled' => [
                'title' => '❌ ยกเลิกการแลกรางวัล',
                'body' => "รางวัล: {$redemption['reward_name']}\n\nแต้มได้ถูกคืนเข้าบัญชีของคุณแล้ว"
            ]
        ];

        $msg = $messages[$status] ?? null;
        if (!$msg) {
            error_log("  - FAILED: Invalid status '$status'");
            return false;
        }

        // Send push message via LINE API
        $data = [
            'to' => $redemption['line_user_id'],
            'messages' => [[
                'type' => 'text',
                'text' => "{$msg['title']}\n\n{$msg['body']}"
            ]]
        ];

        error_log("  - Sending to user: " . $redemption['line_user_id']);
        error_log("  - Message: " . $msg['title']);

        $ch = curl_init('https://api.line.me/v2/bot/message/push');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $account['channel_access_token']
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("  - HTTP Code: $httpCode");
        error_log("  - Response: " . $response);

        if ($httpCode == 200) {
            error_log("  - SUCCESS: Notification sent");
            return true;
        } else {
            error_log("  - FAILED: HTTP $httpCode - $response");
            return false;
        }
    } catch (Exception $e) {
        error_log("Failed to send redemption notification: " . $e->getMessage());
        return false;
    }
}
