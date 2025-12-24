<?php
/**
 * API - Save Template
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['name']) || empty($input['content'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO templates (name, category, message_type, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $input['name'],
        $input['category'] ?? 'Flex Message',
        $input['message_type'] ?? 'flex',
        $input['content']
    ]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
