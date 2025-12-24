<?php
/**
 * Debug Appointment Reminder System
 * ตรวจสอบปัญหาระบบแจ้งเตือนนัดหมาย
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 Debug Appointment Reminder System</h1>";
echo "<p><strong>Current Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";

// 1. Check if appointments table exists
echo "<h2>1. ตรวจสอบตาราง appointments</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'appointments'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ ตาราง appointments มีอยู่</p>";
        
        // Check columns
        $cols = $db->query("DESCRIBE appointments")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columns: " . implode(', ', $cols) . "</p>";
        
        $requiredCols = ['reminder_10min_sent', 'reminder_now_sent', 'line_account_id', 'user_id', 'pharmacist_id'];
        foreach ($requiredCols as $col) {
            if (in_array($col, $cols)) {
                echo "<span style='color:green'>✅ {$col}</span> ";
            } else {
                echo "<span style='color:red'>❌ {$col} (MISSING!)</span> ";
            }
        }
        echo "<br>";
    } else {
        echo "<p style='color:red'>❌ ตาราง appointments ไม่มี!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Check line_accounts table
echo "<h2>2. ตรวจสอบ LINE Accounts</h2>";
try {
    $stmt = $db->query("SELECT id, COALESCE(bot_name, CONCAT('Account #', id)) as account_name, channel_access_token IS NOT NULL AND channel_access_token != '' as has_token FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Has Token</th></tr>";
    foreach ($accounts as $acc) {
        $tokenStatus = $acc['has_token'] ? "<span style='color:green'>✅ Yes</span>" : "<span style='color:red'>❌ No</span>";
        echo "<tr><td>{$acc['id']}</td><td>{$acc['account_name']}</td><td>{$tokenStatus}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 3. Check pharmacists table
echo "<h2>3. ตรวจสอบ Pharmacists</h2>";
try {
    $stmt = $db->query("SELECT id, name, is_active FROM pharmacists LIMIT 10");
    $pharmacists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pharmacists) > 0) {
        echo "<p style='color:green'>✅ พบ " . count($pharmacists) . " เภสัชกร</p>";
        echo "<ul>";
        foreach ($pharmacists as $p) {
            $status = $p['is_active'] ? '🟢' : '🔴';
            echo "<li>{$status} {$p['name']} (ID: {$p['id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>❌ ไม่พบเภสัชกร</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 4. Check appointments
echo "<h2>4. ตรวจสอบ Appointments</h2>";
try {
    // Count all
    $total = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    echo "<p>Total appointments: {$total}</p>";
    
    // Count by status
    $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM appointments GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>By status: ";
    foreach ($statuses as $s) {
        echo "{$s['status']}: {$s['cnt']}, ";
    }
    echo "</p>";
    
    // Get upcoming confirmed appointments
    $stmt = $db->query("
        SELECT a.*, 
               u.line_user_id, u.display_name,
               p.name as pharmacist_name,
               la.id as la_id, la.channel_access_token IS NOT NULL AND la.channel_access_token != '' as has_token
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN pharmacists p ON a.pharmacist_id = p.id
        LEFT JOIN line_accounts la ON a.line_account_id = la.id
        WHERE a.status = 'confirmed'
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($appointments) > 0) {
        echo "<h3>Confirmed Appointments (Latest 10)</h3>";
        echo "<table border='1' cellpadding='5' style='font-size:12px'>";
        echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>User</th><th>LINE User ID</th><th>Pharmacist</th><th>LA ID</th><th>Token</th><th>10min</th><th>Now</th><th>Mins Until</th></tr>";
        
        foreach ($appointments as $apt) {
            $aptDateTime = $apt['appointment_date'] . ' ' . $apt['appointment_time'];
            $minsUntil = round((strtotime($aptDateTime) - time()) / 60);
            
            $tokenStatus = $apt['has_token'] ? '✅' : '❌';
            $r10 = isset($apt['reminder_10min_sent']) ? ($apt['reminder_10min_sent'] ? '✅' : '❌') : 'N/A';
            $rNow = isset($apt['reminder_now_sent']) ? ($apt['reminder_now_sent'] ? '✅' : '❌') : 'N/A';
            $lineUserId = $apt['line_user_id'] ?: '<span style="color:red">NULL</span>';
            
            $minsColor = 'gray';
            if ($minsUntil <= 0) $minsColor = 'red';
            elseif ($minsUntil <= 15) $minsColor = 'orange';
            elseif ($minsUntil <= 30) $minsColor = 'green';
            
            echo "<tr>";
            echo "<td>{$apt['appointment_id']}</td>";
            echo "<td>{$apt['appointment_date']}</td>";
            echo "<td>{$apt['appointment_time']}</td>";
            echo "<td>{$apt['display_name']}</td>";
            echo "<td>{$lineUserId}</td>";
            echo "<td>{$apt['pharmacist_name']}</td>";
            echo "<td>{$apt['la_id']}</td>";
            echo "<td>{$tokenStatus}</td>";
            echo "<td>{$r10}</td>";
            echo "<td>{$rNow}</td>";
            echo "<td style='color:{$minsColor}'>{$minsUntil} mins</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ ไม่พบ confirmed appointments</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 5. Test SQL queries
echo "<h2>5. ทดสอบ SQL Queries</h2>";
try {
    // Test 10-min query
    $sql10 = "
        SELECT COUNT(*) FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN pharmacists p ON a.pharmacist_id = p.id
        JOIN line_accounts la ON a.line_account_id = la.id
        WHERE a.status = 'confirmed'
          AND (a.reminder_10min_sent = 0 OR a.reminder_10min_sent IS NULL)
          AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.appointment_date, ' ', a.appointment_time)) BETWEEN 8 AND 12
          AND la.channel_access_token IS NOT NULL
          AND la.channel_access_token != ''
    ";
    $count10 = $db->query($sql10)->fetchColumn();
    echo "<p>10-min reminder query: {$count10} appointments</p>";
    
    // Test NOW query
    $sqlNow = "
        SELECT COUNT(*) FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN pharmacists p ON a.pharmacist_id = p.id
        JOIN line_accounts la ON a.line_account_id = la.id
        WHERE a.status = 'confirmed'
          AND (a.reminder_now_sent = 0 OR a.reminder_now_sent IS NULL)
          AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.appointment_date, ' ', a.appointment_time)) BETWEEN -2 AND 2
          AND la.channel_access_token IS NOT NULL
          AND la.channel_access_token != ''
    ";
    $countNow = $db->query($sqlNow)->fetchColumn();
    echo "<p>NOW reminder query: {$countNow} appointments</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>SQL Error: " . $e->getMessage() . "</p>";
}

// 6. Check JOIN issues
echo "<h2>6. ตรวจสอบ JOIN Issues</h2>";
try {
    // Check appointments without valid user
    $noUser = $db->query("SELECT COUNT(*) FROM appointments a LEFT JOIN users u ON a.user_id = u.id WHERE u.id IS NULL")->fetchColumn();
    echo "<p>Appointments without valid user: {$noUser}</p>";
    
    // Check appointments without valid pharmacist
    $noPharm = $db->query("SELECT COUNT(*) FROM appointments a LEFT JOIN pharmacists p ON a.pharmacist_id = p.id WHERE p.id IS NULL")->fetchColumn();
    echo "<p>Appointments without valid pharmacist: {$noPharm}</p>";
    
    // Check appointments without valid line_account
    $noLA = $db->query("SELECT COUNT(*) FROM appointments a LEFT JOIN line_accounts la ON a.line_account_id = la.id WHERE la.id IS NULL")->fetchColumn();
    echo "<p>Appointments without valid line_account: {$noLA}</p>";
    
    // Check users without line_user_id
    $noLineUserId = $db->query("SELECT COUNT(*) FROM appointments a JOIN users u ON a.user_id = u.id WHERE u.line_user_id IS NULL OR u.line_user_id = ''")->fetchColumn();
    echo "<p>Appointments where user has no line_user_id: {$noLineUserId}</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 7. Quick fix suggestions
echo "<h2>7. 🔧 Quick Fixes</h2>";
echo "<ul>";
echo "<li><a href='cron/appointment_reminder.php?key=appointment_cron_2025&debug=1'>Run Cron with Debug</a></li>";
echo "<li><a href='run_appointments_migration.php'>Run Appointments Migration</a></li>";
echo "</ul>";

// 8. Create test appointment
echo "<h2>8. สร้าง Test Appointment</h2>";
if (isset($_GET['create_test'])) {
    try {
        // Get first user with line_user_id
        $user = $db->query("SELECT id, line_user_id FROM users WHERE line_user_id IS NOT NULL AND line_user_id != '' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        // Get first pharmacist
        $pharm = $db->query("SELECT id FROM pharmacists WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        // Get first line_account with token
        $la = $db->query("SELECT id FROM line_accounts WHERE channel_access_token IS NOT NULL AND channel_access_token != '' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $pharm && $la) {
            $aptId = 'TEST' . date('ymdHis');
            $aptDate = date('Y-m-d');
            $aptTime = date('H:i:s', strtotime('+12 minutes')); // 12 minutes from now
            
            $stmt = $db->prepare("
                INSERT INTO appointments (appointment_id, user_id, pharmacist_id, line_account_id, appointment_date, appointment_time, status, reminder_10min_sent, reminder_now_sent)
                VALUES (?, ?, ?, ?, ?, ?, 'confirmed', 0, 0)
            ");
            $stmt->execute([$aptId, $user['id'], $pharm['id'], $la['id'], $aptDate, $aptTime]);
            
            echo "<p style='color:green'>✅ Created test appointment: {$aptId} at {$aptDate} {$aptTime}</p>";
            echo "<p>This appointment should trigger 10-min reminder in ~2 minutes</p>";
        } else {
            echo "<p style='color:red'>Missing required data (user/pharmacist/line_account)</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?create_test=1'>Click to create test appointment (12 mins from now)</a></p>";
}
