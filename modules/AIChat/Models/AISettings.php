<?php
/**
 * Model: AI Settings
 * จัดการข้อมูลการตั้งค่า AI Chat ในฐานข้อมูล
 */

namespace Modules\AIChat\Models;

use Modules\Core\Database;

class AISettings
{
    private Database $db;
    private ?int $lineAccountId;
    private array $settings = [];
    
    // ค่าเริ่มต้น
    private const DEFAULT_SETTINGS = [
        'is_enabled' => false,
        'model' => 'gemini-2.0-flash',
        'temperature' => 0.5,
        'max_tokens' => 300,
        'response_style' => 'friendly',
        'fallback_message' => 'ขออภัยค่ะ ไม่เข้าใจคำถาม กรุณาติดต่อเจ้าหน้าที่',
        'system_prompt' => '',
        'business_info' => '',
        'product_knowledge' => '',
        'sender_name' => '',
        'sender_icon' => '',
        'quick_reply_buttons' => ''
    ];
    
    public function __construct(?int $lineAccountId = null)
    {
        $this->db = Database::getInstance();
        $this->lineAccountId = $lineAccountId;
        $this->loadSettings();
    }
    
    /**
     * โหลดการตั้งค่าจากฐานข้อมูล
     */
    private function loadSettings(): void
    {
        $this->settings = self::DEFAULT_SETTINGS;
        
        if (!$this->lineAccountId) {
            return;
        }
        
        $result = $this->db->fetchOne(
            "SELECT * FROM ai_chat_settings WHERE line_account_id = ?",
            [$this->lineAccountId]
        );
        
        if ($result) {
            $this->settings = array_merge($this->settings, $result);
        }
    }
    
    /**
     * ตรวจสอบว่า AI เปิดใช้งานหรือไม่
     */
    public function isEnabled(): bool
    {
        return (bool) $this->settings['is_enabled'] && !empty($this->settings['gemini_api_key']);
    }
    
    public function getApiKey(): string
    {
        return $this->settings['gemini_api_key'] ?? '';
    }
    
    public function getModel(): string
    {
        return $this->settings['model'] ?? 'gemini-2.0-flash';
    }
    
    public function getSystemPrompt(): string
    {
        return $this->settings['system_prompt'] ?? '';
    }
    
    public function getResponseStyle(): string
    {
        return $this->settings['response_style'] ?? 'friendly';
    }
    
    public function getBusinessInfo(): string
    {
        return $this->settings['business_info'] ?? '';
    }
    
    public function getProductKnowledge(): string
    {
        return $this->settings['product_knowledge'] ?? '';
    }
    
    public function getFallbackMessage(): string
    {
        return $this->settings['fallback_message'] ?? self::DEFAULT_SETTINGS['fallback_message'];
    }
    
    public function getTemperature(): float
    {
        return (float) ($this->settings['temperature'] ?? 0.5);
    }
    
    public function getMaxTokens(): int
    {
        return (int) ($this->settings['max_tokens'] ?? 300);
    }
    
    public function getSenderName(): string
    {
        return $this->settings['sender_name'] ?? '';
    }
    
    public function getSenderIcon(): string
    {
        return $this->settings['sender_icon'] ?? '';
    }
    
    public function getQuickReplyButtons(): array
    {
        $buttons = $this->settings['quick_reply_buttons'] ?? '';
        if (empty($buttons)) {
            return [];
        }
        return json_decode($buttons, true) ?: [];
    }
    
    public function getAll(): array
    {
        return $this->settings;
    }
    
    public function getLineAccountId(): ?int
    {
        return $this->lineAccountId;
    }
}
