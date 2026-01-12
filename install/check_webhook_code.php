<?php
/**
 * Check if webhook.php has the new AI mode code
 */
header('Content-Type: text/plain; charset=utf-8');

$webhookPath = __DIR__ . '/../webhook.php';
$webhookContent = file_get_contents($webhookPath);

echo "=== Webhook Code Check ===\n\n";

// Check for new debug logs
$checks = [
    'AI_entry' => strpos($webhookContent, 'AI_entry') !== false,
    'AI_flow' => strpos($webhookContent, 'AI_flow') !== false,
    'AI_mode_check' => strpos($webhookContent, 'AI_mode_check') !== false,
    'AI_sales' => strpos($webhookContent, 'AI_sales') !== false,
    'AI_before_pharmacy' => strpos($webhookContent, 'AI_before_pharmacy') !== false,
    'currentAIMode === sales' => strpos($webhookContent, "currentAIMode === 'sales'") !== false,
    'GeminiChat for sales' => strpos($webhookContent, 'พนักงานขาย AI') !== false,
    'Sales mode return null' => strpos($webhookContent, "// Sales mode แต่ GeminiChat") !== false,
];

echo "Debug Logs Present:\n";
foreach ($checks as $name => $found) {
    echo "  - {$name}: " . ($found ? "✅ YES" : "❌ NO") . "\n";
}

// Check checkAIChatbot function - search for sales mode logic
echo "\n=== Sales Mode Logic ===\n";
$hasSalesCheck = strpos($webhookContent, "in_array(\$commandMode, ['sales', 'support'])") !== false;
$hasGeminiChat = strpos($webhookContent, "new GeminiChat(\$db, \$lineAccountId)") !== false;
$hasReturnNull = strpos($webhookContent, "// Sales mode แต่ GeminiChat return null") !== false;

echo "  - Has sales/support command check: " . ($hasSalesCheck ? "✅ YES" : "❌ NO") . "\n";
echo "  - Has GeminiChat instantiation: " . ($hasGeminiChat ? "✅ YES" : "❌ NO") . "\n";
echo "  - Has return null for sales mode: " . ($hasReturnNull ? "✅ YES" : "❌ NO") . "\n";

// Check ai_settings query
echo "\n=== AI Settings Query ===\n";
if (preg_match('/SELECT ai_mode FROM ai_settings/', $webhookContent)) {
    echo "✅ Found ai_settings query\n";
} else {
    echo "❌ NOT found ai_settings query\n";
}

// Check file modification time
echo "\n=== File Info ===\n";
echo "File: {$webhookPath}\n";
echo "Size: " . filesize($webhookPath) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($webhookPath)) . "\n";

// Show git status
echo "\n=== Git Status ===\n";
$gitLog = shell_exec('cd ' . dirname($webhookPath) . ' && git log --oneline -3 2>&1');
echo $gitLog;
