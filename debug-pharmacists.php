<?php
/**
 * Debug Pharmacists - ตรวจสอบข้อมูลเภสัชกรในฐานข้อมูล
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Pharmacists Table Data</h2>";
echo "<pre>";

$stmt = $db->query("SELECT * FROM pharmacists ORDER BY id");
$pharmacists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total records: " . count($pharmacists) . "\n\n";

foreach ($pharmacists as $p) {
    echo "ID: {$p['id']}\n";
    echo "Name: {$p['name']}\n";
    echo "Title: {$p['title']}\n";
    echo "License: {$p['license_no']}\n";
    echo "---\n";
}

echo "</pre>";

echo "<h2>Appointments for each pharmacist</h2>";
echo "<pre>";

foreach ($pharmacists as $p) {
    $stmt = $db->prepare("SELECT COUNT(*) as total, 
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status IN ('pending','confirmed') AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
        FROM appointments WHERE pharmacist_id = ?");
    $stmt->execute([$p['id']]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Pharmacist ID {$p['id']} ({$p['name']}): Total={$counts['total']}, Completed={$counts['completed']}, Upcoming={$counts['upcoming']}\n";
}

echo "</pre>";
