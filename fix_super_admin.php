<?php
/**
 * Fix Super Admin Role - Auto detect structure
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Fix Super Admin Role</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

try {
    // Step 1: Show current structure
    echo "<h2>1. Current Table Structure</h2>";
    $stmt = $db->query("DESCRIBE admin_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;font-size:12px;'>";
    echo "<tr style='background:#f5f5f5;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    $columnNames = [];
    $roleType = '';
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        if ($col['Field'] === 'role') {
            $roleType = $col['Type'];
        }
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // Step 2: Check if role column exists and has super_admin
    echo "<h2>2. Checking Role Column</h2>";
    
    if (!in_array('role', $columnNames)) {
        echo "<p class='error'>❌ role column not found! Adding it...</p>";
        $db->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'admin'");
        echo "<p class='ok'>✅ Added role column</p>";
    } else {
        echo "<p>Current role type: <code>{$roleType}</code></p>";
        
        if (strpos($roleType, 'super_admin') !== false) {
            echo "<p class='ok'>✅ super_admin already in enum</p>";
        } else {
            echo "<p>Need to update enum to include super_admin</p>";
            
            // Use VARCHAR instead of ENUM to avoid issues
            $db->exec("ALTER TABLE admin_users MODIFY COLUMN role VARCHAR(20) DEFAULT 'admin'");
            echo "<p class='ok'>✅ Changed role to VARCHAR(20)</p>";
        }
    }
    
    // Step 3: Update user 1 to super_admin
    echo "<h2>3. Setting Super Admin</h2>";
    $result = $db->exec("UPDATE admin_users SET role = 'super_admin' WHERE id = 1");
    echo "<p class='ok'>✅ Updated user ID 1 to super_admin (affected: {$result})</p>";
    
    // Step 4: Verify
    echo "<h2>4. Verification</h2>";
    $stmt = $db->query("SELECT id, username, display_name, role FROM admin_users ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f5f5f5;'><th>ID</th><th>Username</th><th>Display Name</th><th>Role</th></tr>";
    foreach ($admins as $admin) {
        $style = $admin['role'] === 'super_admin' ? 'background:#d4edda;font-weight:bold;' : '';
        echo "<tr style='{$style}'>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>{$admin['display_name']}</td>";
        echo "<td>{$admin['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if it worked
    $stmt = $db->query("SELECT role FROM admin_users WHERE id = 1");
    $role = $stmt->fetchColumn();
    
    if ($role === 'super_admin') {
        echo "<h2 class='ok'>🎉 Success!</h2>";
        echo "<p>User ID 1 is now <strong>super_admin</strong></p>";
        echo "<p><strong>Important:</strong> Please <a href='auth/logout.php'>Logout</a> and <a href='auth/login.php'>Login again</a> to refresh your session.</p>";
    } else {
        echo "<h2 class='error'>❌ Failed</h2>";
        echo "<p>Role is still: {$role}</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='auth/logout.php' style='padding:10px 20px;background:#dc3545;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Logout</a>";
    echo "<a href='auth/login.php' style='padding:10px 20px;background:#06C755;color:white;text-decoration:none;border-radius:5px;'>Login</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
