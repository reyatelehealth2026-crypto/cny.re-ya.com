<?php
/**
 * Check notifications and sessions in database
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Database Check</h2>";

try {
    // Check triage_sessions
    echo "<h3>Triage Sessions</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM triage_sessions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total sessions: <strong>{$result['total']}</strong></p>";
    
    // Check date range
    $stmt = $db->query("SELECT MIN(created_at) as min_date, MAX(created_at) as max_date FROM triage_sessions");
    $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Date range: {$dateRange['min_date']} to {$dateRange['max_date']}</p>";
    
    // Test the exact query from triage-analytics
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
    echo "<p>Query date range: {$startDate} to {$endDate}</p>";
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status IS NULL THEN 1 ELSE 0 END) as null_status
        FROM triage_sessions 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Query result: total={$stats['total']}, completed={$stats['completed']}, active={$stats['active']}, null_status={$stats['null_status']}</p>";
    
    $stmt = $db->query("SELECT id, user_id, line_account_id, current_state, status, created_at FROM triage_sessions ORDER BY created_at DESC LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($sessions, true) . "</pre>";
    
    // Check pharmacist_notifications
    echo "<h3>Pharmacist Notifications</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM pharmacist_notifications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total notifications: <strong>{$result['total']}</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as pending FROM pharmacist_notifications WHERE status = 'pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Pending notifications: <strong>{$result['pending']}</strong></p>";
    
    // Test exact query from pharmacist-dashboard
    echo "<h3>Test Pharmacist Dashboard Query</h3>";
    $stmt = $db->query("
        SELECT pn.*, u.display_name, u.picture_url, u.phone,
               ts.current_state, ts.triage_data
        FROM pharmacist_notifications pn
        LEFT JOIN users u ON pn.user_id = u.id
        LEFT JOIN triage_sessions ts ON pn.triage_session_id = ts.id
        WHERE pn.status = 'pending'
        ORDER BY 
            CASE WHEN pn.priority = 'urgent' THEN 0 ELSE 1 END,
            pn.created_at DESC
        LIMIT 5
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found " . count($notifications) . " notifications</p>";
    echo "<pre>" . print_r($notifications, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
