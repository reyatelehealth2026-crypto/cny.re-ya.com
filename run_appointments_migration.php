<?php
/**
 * Run Appointments Migration
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Running Appointments Migration</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/database/migration_appointments.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $db->exec($statement);
            $success++;
            echo "<p style='color:green'>✅ Executed successfully</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "<p style='color:orange'>⚠️ Already exists (skipped)</p>";
            } else {
                $errors++;
                echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p>Success: {$success}</p>";
    echo "<p>Errors: {$errors}</p>";
    
    // Verify tables
    echo "<h3>Verify Tables</h3>";
    $tables = ['pharmacists', 'pharmacist_schedules', 'pharmacist_holidays', 'appointments', 'consultation_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "<p>✅ {$table}: {$count} rows</p>";
        } catch (Exception $e) {
            echo "<p>❌ {$table}: Not found</p>";
        }
    }
    
    echo "<hr><p><a href='liff-appointment.php'>Test Appointment Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
