<?php
/**
 * Create dev_logs table for debugging
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Create dev_logs Table</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS dev_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level VARCHAR(20) DEFAULT 'info',
        source VARCHAR(100),
        action VARCHAR(100),
        message TEXT,
        data JSON,
        user_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_level (level),
        INDEX idx_source (source),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color:green'>✅ dev_logs table created!</p>";
    
    // Show recent logs
    $logs = $db->query("SELECT * FROM dev_logs ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($logs) > 0) {
        echo "<h3>Recent Logs</h3>";
        echo "<table border='1' cellpadding='5'><tr><th>Time</th><th>Level</th><th>Source</th><th>Action</th><th>Message</th></tr>";
        foreach ($logs as $log) {
            $color = $log['level'] === 'error' ? 'red' : ($log['level'] === 'warning' ? 'orange' : 'black');
            echo "<tr style='color:{$color}'>
                <td>" . ($log['created_at'] ?? '') . "</td>
                <td>{$log['level']}</td>
                <td>{$log['source']}</td>
                <td>{$log['action']}</td>
                <td>" . htmlspecialchars(mb_substr($log['message'] ?? '', 0, 100)) . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No logs yet. Send a message to LINE bot to generate logs.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
