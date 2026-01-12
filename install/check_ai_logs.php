<?php
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$name = defined('DB_NAME') ? DB_NAME : '';
$user = defined('DB_USER') ? DB_USER : '';
$pass = defined('DB_PASS') ? DB_PASS : '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Recent Dev Logs ===\n\n";
    
    $stmt = $pdo->query("SELECT * FROM dev_logs ORDER BY id DESC LIMIT 30");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        echo "ID: {$row['id']}\n";
        echo "Type: " . ($row['log_type'] ?? $row['category'] ?? 'N/A') . "\n";
        echo "Source: " . ($row['source'] ?? $row['action'] ?? 'N/A') . "\n";
        echo "Message: {$row['message']}\n";
        echo "Data: " . substr($row['data'] ?? '', 0, 300) . "\n";
        echo "Time: {$row['created_at']}\n";
        echo "---\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
