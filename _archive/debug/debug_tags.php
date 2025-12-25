<?php
/**
 * Debug Tags
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$userId = $_GET['user_id'] ?? 13;

echo "<h2>🔍 Debug Tags for User ID: {$userId}</h2>";

// 0. Show all tables with 'tag' in name
echo "<h3>0. ตารางที่มีคำว่า tag</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE '%tag%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables: " . implode(', ', $tables) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 1. Check tags table
echo "<h3>1. ตาราง tags</h3>";
try {
    $stmt = $db->query("DESCRIBE tags");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Columns:</p><pre>" . print_r($cols, true) . "</pre>";
    
    $stmt = $db->query("SELECT * FROM tags LIMIT 10");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Tags:</p><pre>" . print_r($tags, true) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 2. Check user_tags table
echo "<h3>2. ตาราง user_tags</h3>";
try {
    $stmt = $db->query("DESCRIBE user_tags");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Columns:</p><pre>" . print_r($cols, true) . "</pre>";
    
    $stmt = $db->query("SELECT * FROM user_tags LIMIT 10");
    $userTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Sample data:</p><pre>" . print_r($userTags, true) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 2b. Check user_tag_assignments table (alternative)
echo "<h3>2b. ตาราง user_tag_assignments</h3>";
try {
    $stmt = $db->query("DESCRIBE user_tag_assignments");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Columns:</p><pre>" . print_r($cols, true) . "</pre>";
    
    $stmt = $db->prepare("SELECT * FROM user_tag_assignments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $assigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Assignments for user {$userId}:</p><pre>" . print_r($assigns, true) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Table not found or error: " . $e->getMessage() . "</p>";
}

// 3. Join query
echo "<h3>3. Join Query</h3>";
try {
    $stmt = $db->prepare("SELECT t.* FROM tags t JOIN user_tags ut ON t.id = ut.tag_id WHERE ut.user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Result:</p><pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// 4. Check all user_tags
echo "<h3>4. All user_tags entries</h3>";
try {
    $stmt = $db->query("SELECT ut.*, t.name as tag_name, u.display_name FROM user_tags ut 
                        LEFT JOIN tags t ON ut.tag_id = t.id 
                        LEFT JOIN users u ON ut.user_id = u.id 
                        LIMIT 20");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($all, true) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
