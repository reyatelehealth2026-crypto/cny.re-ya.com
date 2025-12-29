<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h3>Latest Triage Sessions (Last 10)</h3>";

$stmt = $db->query("
    SELECT ts.id, ts.user_id, ts.status, ts.current_state, ts.triage_data, ts.created_at,
           u.display_name
    FROM triage_sessions ts
    LEFT JOIN users u ON ts.user_id = u.id
    ORDER BY ts.created_at DESC
    LIMIT 10
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>User</th><th>Status</th><th>State</th><th>Symptoms</th><th>Created</th></tr>";
foreach ($sessions as $s) {
    $data = json_decode($s['triage_data'] ?? '{}', true);
    $symptoms = $data['symptoms'] ?? [];
    $symptomStr = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;
    echo "<tr>";
    echo "<td>{$s['id']}</td>";
    echo "<td>{$s['display_name']} (ID:{$s['user_id']})</td>";
    echo "<td>{$s['status']}</td>";
    echo "<td>{$s['current_state']}</td>";
    echo "<td>" . htmlspecialchars(substr($symptomStr, 0, 50)) . "</td>";
    echo "<td>{$s['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Dashboard Query Check</h3>";
$dateFrom = date('Y-m-d', strtotime('-30 days'));
$dateTo = date('Y-m-d');

$stmt = $db->prepare("
    SELECT ts.*, u.display_name, u.picture_url
    FROM triage_sessions ts
    LEFT JOIN users u ON ts.user_id = u.id
    WHERE DATE(ts.created_at) BETWEEN ? AND ?
    AND (ts.status IS NULL OR ts.status IN ('', 'active', 'pending'))
    ORDER BY ts.created_at DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p>Pending sessions (for dashboard): " . count($pending) . "</p>";
