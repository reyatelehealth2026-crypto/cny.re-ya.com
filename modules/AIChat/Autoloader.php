<?php
/**
 * AIChat Module Autoloader
 * โหลด Classes อัตโนมัติสำหรับ Module AIChat
 */

spl_autoload_register(function ($class) {
    // ตรวจสอบว่าเป็น class ใน Modules namespace หรือไม่
    if (strpos($class, 'Modules\\') !== 0) {
        return;
    }
    
    // แปลง namespace เป็น path
    $relativePath = str_replace('\\', '/', $class);
    $relativePath = str_replace('Modules/', '', $relativePath);
    
    $file = __DIR__ . '/../' . $relativePath . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Helper function สำหรับโหลด Module (ใช้ชื่อไฟล์ภาษาอังกฤษ)
 */
if (!function_exists('loadAIChatModule')) {
    function loadAIChatModule(): void
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        
        // โหลด Core ก่อน
        require_once __DIR__ . '/../Core/Database.php';
        
        // โหลด Models
        require_once __DIR__ . '/Models/AISettings.php';
        require_once __DIR__ . '/Models/ConversationHistory.php';
        if (file_exists(__DIR__ . '/Models/ConversationState.php')) {
            require_once __DIR__ . '/Models/ConversationState.php';
        }
        if (file_exists(__DIR__ . '/Models/MedicalHistory.php')) {
            require_once __DIR__ . '/Models/MedicalHistory.php';
        }
        
        // โหลด Services
        require_once __DIR__ . '/Services/ContextAnalyzer.php';
        require_once __DIR__ . '/Services/PromptBuilder.php';
        require_once __DIR__ . '/Services/GeminiAPI.php';
        if (file_exists(__DIR__ . '/Services/RedFlagDetector.php')) {
            require_once __DIR__ . '/Services/RedFlagDetector.php';
        }
        if (file_exists(__DIR__ . '/Services/TriageEngine.php')) {
            require_once __DIR__ . '/Services/TriageEngine.php';
        }
        if (file_exists(__DIR__ . '/Services/DrugInteractionChecker.php')) {
            require_once __DIR__ . '/Services/DrugInteractionChecker.php';
        }
        if (file_exists(__DIR__ . '/Services/PharmacyRAG.php')) {
            require_once __DIR__ . '/Services/PharmacyRAG.php';
        }
        if (file_exists(__DIR__ . '/Services/MIMSKnowledgeBase.php')) {
            require_once __DIR__ . '/Services/MIMSKnowledgeBase.php';
        }
        
        // โหลด Templates
        if (file_exists(__DIR__ . '/Templates/ProductFlexTemplates.php')) {
            require_once __DIR__ . '/Templates/ProductFlexTemplates.php';
        }
    }
}

/**
 * Helper function สำหรับโหลด Pharmacy Module
 */
if (!function_exists('loadPharmacyModule')) {
    function loadPharmacyModule(): void
    {
        loadAIChatModule();
        
        // โหลด Templates
        if (file_exists(__DIR__ . '/Templates/PharmacyFlexTemplates.php')) {
            require_once __DIR__ . '/Templates/PharmacyFlexTemplates.php';
        }
        
        // โหลด PharmacistNotifier
        if (file_exists(__DIR__ . '/Services/PharmacistNotifier.php')) {
            require_once __DIR__ . '/Services/PharmacistNotifier.php';
        }
    }
}
