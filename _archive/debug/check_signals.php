<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $callId = $_GET['call_id'] ?? null;
    
    if (!$callId) {
        // Get latest call
        $stmt = $db->query("SELECT id FROM video_calls ORDER BY id DESC LIMIT 1");
        $call = $stmt->fetch();
        $callId = $call['id'] ?? 0;
    }
    
    // Get ALL columns to see what's stored
    $stmt = $db->prepare("SELECT * FROM video_call_signals WHERE call_id = ? ORDER BY id DESC LIMIT 20");
    $stmt->execute([$callId]);
    $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Simplify signal_data for display
    foreach ($signals as &$sig) {
        if (isset($sig['signal_data'])) {
            $data = json_decode($sig['signal_data'], true);
            $sig['signal_data_preview'] = is_array($data) ? array_keys($data) : 'invalid';
            unset($sig['signal_data']); // Remove full data for readability
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'call_id' => $callId,
        'total' => count($signals),
        'signals' => $signals
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
