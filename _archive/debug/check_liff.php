<?php
/**
 * Quick check LIFF ID in database
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Check LIFF ID</h2>";

// 1. Check if column exists
echo "<h3>1. Column Check</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_id'");
    $col = $stmt->fetch();
    if ($col) {
        echo "✅ Column liff_id EXISTS<br>";
    } else {
        echo "❌ Column liff_id NOT FOUND<br>";
        echo "<form method='post'><button name='add_col'>Add Column</button></form>";
        if (isset($_POST['add_col'])) {
            $db->exec("ALTER TABLE line_accounts ADD COLUMN liff_id VARCHAR(100) DEFAULT NULL");
            echo "✅ Added! Refresh page.";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 2. Show all data
echo "<h3>2. Current Data</h3>";
try {
    $stmt = $db->query("SELECT * FROM line_accounts LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "No accounts found";
    } else {
        echo "<table border='1' cellpadding='5'><tr>";
        foreach (array_keys($rows[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $key => $val) {
                $style = ($key === 'liff_id') ? 'background:yellow;font-weight:bold' : '';
                $display = is_null($val) ? '<em style="color:red">NULL</em>' : (strlen($val) > 50 ? substr($val, 0, 50) . '...' : htmlspecialchars($val));
                echo "<td style='$style'>$display</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 3. Direct update form
echo "<h3>3. Update LIFF ID</h3>";
if (isset($_POST['save_liff'])) {
    $id = $_POST['account_id'];
    $liff = trim($_POST['liff_id']);
    try {
        $stmt = $db->prepare("UPDATE line_accounts SET liff_id = ? WHERE id = ?");
        $stmt->execute([$liff ?: null, $id]);
        echo "<p style='color:green'>✅ Saved! Refresh to verify.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}

try {
    $stmt = $db->query("SELECT id, name, liff_id FROM line_accounts");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($accounts as $a) {
        echo "<form method='post' style='margin:10px 0;padding:10px;border:1px solid #ddd'>";
        echo "<strong>Account #{$a['id']}: {$a['name']}</strong><br>";
        echo "<input type='hidden' name='account_id' value='{$a['id']}'>";
        echo "LIFF ID: <input type='text' name='liff_id' value='" . htmlspecialchars($a['liff_id'] ?? '') . "' size='40' placeholder='e.g. 1234567890-abcdefgh'>";
        echo " <button name='save_liff'>Save</button>";
        echo "</form>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 4. Test what liff-checkout.php sees (NEW QUERY)
echo "<h3>4. What liff-checkout.php sees</h3>";
try {
    // New query - get first account with liff_id set
    $stmt = $db->query("SELECT id, liff_id FROM line_accounts WHERE liff_id IS NOT NULL AND liff_id != '' ORDER BY is_default DESC, id ASC LIMIT 1");
    $row = $stmt->fetch();
    $liffId = $row['liff_id'] ?? '';
    $accountId = $row['id'] ?? 'N/A';
    
    echo "Query: <code>SELECT id, liff_id FROM line_accounts WHERE liff_id IS NOT NULL AND liff_id != '' ...</code><br>";
    echo "Account ID: <strong>{$accountId}</strong><br>";
    echo "LIFF ID from DB: <strong style='color:" . ($liffId ? 'green' : 'red') . "'>" . ($liffId ?: 'EMPTY') . "</strong><br>";
    
    if ($liffId) {
        echo "<br>✅ LIFF URL would be: <a href='https://liff.line.me/{$liffId}' target='_blank'>https://liff.line.me/{$liffId}</a>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
