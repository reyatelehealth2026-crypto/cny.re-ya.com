<?php
/**
 * Fix is_registered column
 * แก้ไขปัญหา users ที่มี member_id แต่ is_registered = 0 หรือ NULL
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix is_registered Column</h2>";

// 1. Check if column exists
echo "<h3>1. Check Column</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_registered'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$col) {
        echo "<p>Adding is_registered column...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN is_registered TINYINT(1) DEFAULT 0 AFTER member_tier");
        echo "<p style='color:green'>✅ Column added!</p>";
    } else {
        echo "<p style='color:green'>✅ Column exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Check if registered_at column exists
echo "<h3>2. Check registered_at Column</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'registered_at'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$col) {
        echo "<p>Adding registered_at column...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN registered_at DATETIME DEFAULT NULL AFTER is_registered");
        echo "<p style='color:green'>✅ Column added!</p>";
    } else {
        echo "<p style='color:green'>✅ Column exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. Fix users with member_id but is_registered = 0 or NULL
echo "<h3>3. Fix Users</h3>";
try {
    // Count affected users
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users WHERE member_id IS NOT NULL AND member_id != '' AND (is_registered = 0 OR is_registered IS NULL)");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($count > 0) {
        echo "<p>Found {$count} users with member_id but is_registered = 0 or NULL</p>";
        
        // Fix them
        $stmt = $db->exec("UPDATE users SET is_registered = 1, registered_at = COALESCE(registered_at, created_at, NOW()) WHERE member_id IS NOT NULL AND member_id != '' AND (is_registered = 0 OR is_registered IS NULL)");
        
        echo "<p style='color:green'>✅ Fixed {$count} users!</p>";
    } else {
        echo "<p style='color:green'>✅ No users need fixing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 4. Also fix users with first_name but is_registered = 0
echo "<h3>4. Fix Users with first_name</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users WHERE first_name IS NOT NULL AND first_name != '' AND (is_registered = 0 OR is_registered IS NULL)");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($count > 0) {
        echo "<p>Found {$count} users with first_name but is_registered = 0 or NULL</p>";
        
        // Generate member_id for them if missing
        $stmt = $db->query("SELECT id FROM users WHERE first_name IS NOT NULL AND first_name != '' AND (member_id IS NULL OR member_id = '')");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $prefix = 'M' . date('y');
        $lastNum = 0;
        
        // Get last member number
        $stmt = $db->query("SELECT member_id FROM users WHERE member_id LIKE '{$prefix}%' ORDER BY member_id DESC LIMIT 1");
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last && preg_match('/^M\d{2}(\d{5})$/', $last['member_id'], $matches)) {
            $lastNum = intval($matches[1]);
        }
        
        foreach ($users as $u) {
            $lastNum++;
            $memberId = $prefix . str_pad($lastNum, 5, '0', STR_PAD_LEFT);
            $db->prepare("UPDATE users SET member_id = ? WHERE id = ?")->execute([$memberId, $u['id']]);
        }
        
        // Now fix is_registered
        $db->exec("UPDATE users SET is_registered = 1, registered_at = COALESCE(registered_at, created_at, NOW()) WHERE first_name IS NOT NULL AND first_name != '' AND (is_registered = 0 OR is_registered IS NULL)");
        
        echo "<p style='color:green'>✅ Fixed!</p>";
    } else {
        echo "<p style='color:green'>✅ No users need fixing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 5. Show current status
echo "<h3>5. Current Status</h3>";
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_registered = 1 THEN 1 ELSE 0 END) as registered,
        SUM(CASE WHEN member_id IS NOT NULL AND member_id != '' THEN 1 ELSE 0 END) as has_member_id
    FROM users");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li>Total users: {$stats['total']}</li>";
    echo "<li>Registered (is_registered=1): {$stats['registered']}</li>";
    echo "<li>Has member_id: {$stats['has_member_id']}</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p style='color:green; font-weight:bold;'>✅ Done! กลับไปทดสอบหน้า member card ได้เลย</p>";
