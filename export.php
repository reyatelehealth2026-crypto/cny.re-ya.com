<?php
/**
 * Export Data - ส่งออกข้อมูลเป็น CSV
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? '';
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

if ($type === 'messages') {
    $stmt = $db->prepare("SELECT m.id, u.display_name, u.line_user_id, m.direction, m.message_type, m.content, m.created_at 
                          FROM messages m 
                          JOIN users u ON m.user_id = u.id 
                          WHERE DATE(m.created_at) BETWEEN ? AND ?
                          ORDER BY m.created_at DESC");
    $stmt->execute([$startDate, $endDate]);
    $data = $stmt->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="messages_' . $startDate . '_' . $endDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
    fputcsv($output, ['ID', 'Display Name', 'LINE User ID', 'Direction', 'Type', 'Content', 'Created At']);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    
} elseif ($type === 'users') {
    $stmt = $db->prepare("SELECT id, line_user_id, display_name, is_blocked, created_at 
                          FROM users 
                          WHERE DATE(created_at) BETWEEN ? AND ?
                          ORDER BY created_at DESC");
    $stmt->execute([$startDate, $endDate]);
    $data = $stmt->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_' . $startDate . '_' . $endDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'LINE User ID', 'Display Name', 'Is Blocked', 'Created At']);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    
} else {
    header('Location: analytics.php');
}
exit;
