<?php
/**
 * Telegram Bot API Class - รองรับการตอบกลับ LINE ผ่าน Telegram
 */

class TelegramAPI {
    private $botToken;
    private $chatId;
    private $apiEndpoint = 'https://api.telegram.org/bot';

    public function __construct() {
        $this->botToken = TELEGRAM_BOT_TOKEN;
        $this->chatId = TELEGRAM_CHAT_ID;
    }

    /**
     * Send request to Telegram API
     */
    private function sendRequest($method, $data) {
        $url = $this->apiEndpoint . $this->botToken . '/' . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Send message to Telegram
     */
    public function sendMessage($text, $chatId = null, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId ?? $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }

        return $this->sendRequest('sendMessage', $data);
    }

    /**
     * Send notification for new follower
     */
    public function notifyNewFollower($displayName, $lineUserId = null) {
        $text = "🎉 <b>ผู้ติดตามใหม่!</b>\n\n";
        $text .= "👤 ชื่อ: {$displayName}\n";
        $text .= "📅 เวลา: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($text);
    }

    /**
     * Send notification for new message with reply button
     */
    public function notifyNewMessage($displayName, $message, $lineUserId = null, $dbUserId = null) {
        $text = "💬 <b>ข้อความใหม่!</b>\n\n";
        $text .= "👤 จาก: {$displayName}\n";
        $text .= "📝 ข้อความ: {$message}\n";
        $text .= "📅 เวลา: " . date('Y-m-d H:i:s');
        
        // Add reply instruction if user ID available
        if ($dbUserId) {
            $text .= "\n\n💡 <i>ตอบกลับ: พิมพ์</i> <code>/reply {$dbUserId} ข้อความ</code>";
        }
        
        // Inline keyboard for quick actions
        $replyMarkup = null;
        if ($dbUserId) {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '📝 ตอบกลับ', 'callback_data' => "reply_{$dbUserId}"],
                        ['text' => '👤 ดูโปรไฟล์', 'callback_data' => "profile_{$dbUserId}"]
                    ]
                ]
            ];
        }
        
        return $this->sendMessage($text, null, $replyMarkup);
    }

    /**
     * Send notification for unfollow
     */
    public function notifyUnfollow($displayName) {
        $text = "😢 <b>ผู้ติดตามยกเลิก</b>\n\n";
        $text .= "👤 ชื่อ: {$displayName}\n";
        $text .= "📅 เวลา: " . date('Y-m-d H:i:s');
        return $this->sendMessage($text);
    }

    /**
     * Send reply confirmation
     */
    public function sendReplyConfirmation($displayName, $message) {
        $text = "✅ <b>ส่งข้อความสำเร็จ!</b>\n\n";
        $text .= "👤 ถึง: {$displayName}\n";
        $text .= "📝 ข้อความ: {$message}";
        return $this->sendMessage($text);
    }

    /**
     * Send error message
     */
    public function sendError($error) {
        $text = "❌ <b>เกิดข้อผิดพลาด</b>\n\n";
        $text .= $error;
        return $this->sendMessage($text);
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery($callbackQueryId, $text = null) {
        return $this->sendRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text
        ]);
    }

    /**
     * Set webhook
     */
    public function setWebhook($url) {
        return $this->sendRequest('setWebhook', ['url' => $url]);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook() {
        return $this->sendRequest('deleteWebhook', []);
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo() {
        $url = $this->apiEndpoint . $this->botToken . '/getWebhookInfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Send photo from binary data
     */
    public function sendPhoto($imageData, $caption = '', $dbUserId = null) {
        $url = $this->apiEndpoint . $this->botToken . '/sendPhoto';
        
        // Save temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'line_img_');
        file_put_contents($tempFile, $imageData);
        
        $data = [
            'chat_id' => $this->chatId,
            'photo' => new CURLFile($tempFile, 'image/jpeg', 'photo.jpg'),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        if ($dbUserId) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📝 ตอบกลับ', 'callback_data' => "reply_{$dbUserId}"],
                        ['text' => '👤 ดูโปรไฟล์', 'callback_data' => "profile_{$dbUserId}"]
                    ]
                ]
            ]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Delete temp file
        unlink($tempFile);

        return json_decode($response, true);
    }

    /**
     * Send photo from URL
     */
    public function sendPhotoUrl($photoUrl, $caption = '', $dbUserId = null) {
        $data = [
            'chat_id' => $this->chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        if ($dbUserId) {
            $data['reply_markup'] = [
                'inline_keyboard' => [
                    [
                        ['text' => '📝 ตอบกลับ', 'callback_data' => "reply_{$dbUserId}"],
                        ['text' => '👤 ดูโปรไฟล์', 'callback_data' => "profile_{$dbUserId}"]
                    ]
                ]
            ];
        }

        return $this->sendRequest('sendPhoto', $data);
    }

    /**
     * Send location
     */
    public function sendLocation($latitude, $longitude, $caption = '', $dbUserId = null) {
        // Send location
        $this->sendRequest('sendLocation', [
            'chat_id' => $this->chatId,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
        
        // Send caption with reply button
        if ($caption) {
            $replyMarkup = null;
            if ($dbUserId) {
                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📝 ตอบกลับ', 'callback_data' => "reply_{$dbUserId}"],
                            ['text' => '🗺 เปิดแผนที่', 'url' => "https://www.google.com/maps?q={$latitude},{$longitude}"]
                        ]
                    ]
                ];
            }
            $this->sendMessage($caption, null, $replyMarkup);
        }
    }

    /**
     * Send video from URL
     */
    public function sendVideo($videoUrl, $caption = '', $dbUserId = null) {
        $data = [
            'chat_id' => $this->chatId,
            'video' => $videoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        return $this->sendRequest('sendVideo', $data);
    }

    /**
     * Send document/file
     */
    public function sendDocument($fileData, $filename, $caption = '') {
        $url = $this->apiEndpoint . $this->botToken . '/sendDocument';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'line_file_');
        file_put_contents($tempFile, $fileData);
        
        $data = [
            'chat_id' => $this->chatId,
            'document' => new CURLFile($tempFile, 'application/octet-stream', $filename),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        unlink($tempFile);

        return json_decode($response, true);
    }
}
