<?php
/**
 * OpenAI API Class
 */

class OpenAI {
    private $apiKey;
    private $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
    }

    /**
     * Get AI response
     */
    public function chat($message, $systemPrompt = null, $model = 'gpt-3.5-turbo', $maxTokens = 500, $temperature = 0.7) {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $message];

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => (float)$temperature
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'message' => trim($result['choices'][0]['message']['content'])
            ];
        }

        return [
            'success' => false,
            'message' => 'ขออภัย ไม่สามารถประมวลผลได้ในขณะนี้'
        ];
    }
}
