<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Dev Logs (Last 20)</h2>";

try {
    $stmt = $db->query("SELECT * FROM dev_logs ORDER BY id DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='font-size:12px;'>";
    echo "<tr><th>ID</th><th>Time</th><th>Type</th><th>Source</th><th>Message</th><th>Data</th></tr>";
    foreach ($logs as $log) {
        $data = json_decode($log['data'] ?? '{}', true);
        $dataStr = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
        echo "<tr>";
        echo "<td>{$log['id']}</td>";
        echo "<td>" . substr($log['created_at'], 11) . "</td>";
        echo "<td>{$log['log_type']}</td>";
        echo "<td>{$log['source']}</td>";
        echo "<td>" . htmlspecialchars(substr($log['message'], 0, 100)) . "</td>";
        echo "<td><pre style='max-width:300px;overflow:auto;font-size:10px;'>" . htmlspecialchars($dataStr) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><a href='debug_dev_logs.php'>Refresh</a>";
