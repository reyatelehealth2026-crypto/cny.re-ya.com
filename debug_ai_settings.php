<?php
/**
 * Debug AI Settings Table
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Debug AI Settings</h2>";

// Check table structure
echo "<h3>1. Table Structure</h3>";
try {
    $stmt = $db->query("DESCRIBE ai_settings");
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check indexes
echo "<h3>2. Indexes</h3>";
try {
    $stmt = $db->query("SHOW INDEX FROM ai_settings");
    echo "<table border='1' cellpadding='5'><tr><th>Key_name</th><th>Column_name</th><th>Non_unique</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Key_name']}</td><td>{$row['Column_name']}</td><td>{$row['Non_unique']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check current data
echo "<h3>3. Current Data</h3>";
try {
    $stmt = $db->query("SELECT * FROM ai_settings ORDER BY id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "<p>No data in table</p>";
    } else {
        echo "<table border='1' cellpadding='5'><tr>";
        foreach (array_keys($rows[0]) as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $key => $val) {
                $display = $key === 'setting_value' && strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val;
                echo "<td>" . htmlspecialchars($display ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test insert/update
echo "<h3>4. Test Insert/Update</h3>";
try {
    // Check if gemini_api_key column exists
    $stmt = $db->query("SHOW COLUMNS FROM ai_settings LIKE 'gemini_api_key'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE ai_settings ADD COLUMN gemini_api_key VARCHAR(255) DEFAULT NULL");
        echo "<p style='color:green'>✅ Added gemini_api_key column</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ gemini_api_key column exists</p>";
    }
    
    // Test update existing record
    $stmt = $db->query("SELECT id FROM ai_settings LIMIT 1");
    $existing = $stmt->fetch();
    if ($existing) {
        echo "<p style='color:green'>✅ Found existing record (id: {$existing['id']}), can update</p>";
    } else {
        echo "<p style='color:yellow'>⚠️ No existing records, will insert new</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Session info
echo "<h3>5. Session Info</h3>";
echo "<p>current_bot_id: " . ($_SESSION['current_bot_id'] ?? 'NULL') . "</p>";
