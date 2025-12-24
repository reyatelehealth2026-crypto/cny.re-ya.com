<?php
/**
 * Debug Messages Table Structure
 */
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug Messages Table</h1>";

// 1. Show table structure
echo "<h2>1. Table Structure</h2>";
$stmt = $db->query("DESCRIBE messages");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    $highlight = ($col['Field'] === 'sent_by') ? 'background:yellow;' : '';
    echo "<tr style='{$highlight}'>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check triggers
echo "<h2>2. Triggers on messages table</h2>";
try {
    $stmt = $db->query("SHOW TRIGGERS LIKE 'messages'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($triggers)) {
        echo "<p style='color:green;'>✅ No triggers found</p>";
    } else {
        echo "<pre>";
        print_r($triggers);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// 3. Test different INSERT methods
echo "<h2>3. Test INSERT Methods</h2>";

// Method A: Named columns
echo "<h3>Method A: Named columns with sent_by</h3>";
try {
    $testId = 'TEST_A_' . time();
    $sql = "INSERT INTO messages (line_account_id, user_id, direction, message_type, content, sent_by, created_at, is_read) 
            VALUES (1, 1, 'outgoing', 'text', ?, 'admin:test_user', NOW(), 0)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$testId]);
    $newId = $db->lastInsertId();
    
    // Check what was saved
    $stmt = $db->prepare("SELECT id, content, sent_by FROM messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Inserted ID: {$newId}</p>";
    echo "<p>Content: {$row['content']}</p>";
    echo "<p>Sent By: <strong>" . ($row['sent_by'] ?: 'NULL/EMPTY') . "</strong></p>";
    
    if ($row['sent_by'] === 'admin:test_user') {
        echo "<p style='color:green;'>✅ Method A works!</p>";
    } else {
        echo "<p style='color:red;'>❌ Method A failed - sent_by not saved</p>";
    }
    
    // Cleanup
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$newId]);
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Method B: Direct UPDATE after INSERT
echo "<h3>Method B: INSERT then UPDATE</h3>";
try {
    $testId = 'TEST_B_' . time();
    
    // Insert without sent_by
    $sql = "INSERT INTO messages (line_account_id, user_id, direction, message_type, content, created_at, is_read) 
            VALUES (1, 1, 'outgoing', 'text', ?, NOW(), 0)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$testId]);
    $newId = $db->lastInsertId();
    
    // Update sent_by
    $stmt = $db->prepare("UPDATE messages SET sent_by = ? WHERE id = ?");
    $stmt->execute(['admin:test_user_b', $newId]);
    
    // Check what was saved
    $stmt = $db->prepare("SELECT id, content, sent_by FROM messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Inserted ID: {$newId}</p>";
    echo "<p>Sent By after UPDATE: <strong>" . ($row['sent_by'] ?: 'NULL/EMPTY') . "</strong></p>";
    
    if ($row['sent_by'] === 'admin:test_user_b') {
        echo "<p style='color:green;'>✅ Method B works!</p>";
    } else {
        echo "<p style='color:red;'>❌ Method B failed</p>";
    }
    
    // Cleanup
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$newId]);
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// 4. Check column position
echo "<h2>4. Column Order Check</h2>";
$colNames = array_column($columns, 'Field');
$sentByPos = array_search('sent_by', $colNames);
echo "<p>sent_by is at position: <strong>{$sentByPos}</strong></p>";
echo "<p>Column order: " . implode(', ', $colNames) . "</p>";

// 5. Raw SQL test
echo "<h2>5. Raw SQL Test</h2>";
try {
    $rawSql = "INSERT INTO messages (line_account_id, user_id, direction, message_type, content, sent_by, created_at, is_read) VALUES (1, 1, 'outgoing', 'text', 'RAW_TEST', 'admin:raw_test', NOW(), 0)";
    $db->exec($rawSql);
    $newId = $db->lastInsertId();
    
    $stmt = $db->prepare("SELECT sent_by FROM messages WHERE id = ?");
    $stmt->execute([$newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Raw SQL Result - sent_by: <strong>" . ($row['sent_by'] ?: 'NULL/EMPTY') . "</strong></p>";
    
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$newId]);
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='fix_sent_by_now.php'>← Back to Fix Tool</a></p>";
