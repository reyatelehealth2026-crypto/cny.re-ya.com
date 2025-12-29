<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Delete pharmacist ID 2
$stmt = $db->prepare("DELETE FROM pharmacists WHERE id = ?");
$stmt->execute([2]);

echo "Deleted pharmacist ID 2<br>";
echo "Rows affected: " . $stmt->rowCount() . "<br><br>";

// Show remaining
$stmt = $db->query("SELECT id, name, title FROM pharmacists ORDER BY id");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Remaining pharmacists: " . count($results) . "<br>";
foreach ($results as $r) {
    echo "- ID {$r['id']}: {$r['title']}{$r['name']}<br>";
}
