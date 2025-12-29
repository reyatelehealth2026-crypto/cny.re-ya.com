<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h3>Pharmacists in Database</h3>";

$stmt = $db->query("SELECT id, name, title, specialty, is_active, is_available, line_account_id FROM pharmacists ORDER BY id");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total: " . count($results) . "</p>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Title</th><th>Specialty</th><th>Active</th><th>Available</th><th>Line Account</th></tr>";
foreach ($results as $r) {
    $active = $r['is_active'] ? '✅' : '❌';
    $avail = $r['is_available'] ? '✅' : '❌';
    echo "<tr><td>{$r['id']}</td><td>{$r['name']}</td><td>{$r['title']}</td><td>{$r['specialty']}</td><td>{$active}</td><td>{$avail}</td><td>{$r['line_account_id']}</td></tr>";
}
echo "</table>";
