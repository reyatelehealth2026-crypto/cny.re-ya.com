<?php
/**
 * Fix payment_slips foreign key constraint
 * Remove FK to orders table so we can use transaction_id instead
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Fix Payment Slips Foreign Key</h2>";

// 1. Show current foreign keys
echo "<h3>1. Current Foreign Keys on payment_slips</h3>";
try {
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'payment_slips'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fks)) {
        echo "<p style='color:green'>✅ No foreign keys found</p>";
    } else {
        echo "<table border='1' cellpadding='5'><tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
        foreach ($fks as $fk) {
            echo "<tr><td>{$fk['CONSTRAINT_NAME']}</td><td>{$fk['COLUMN_NAME']}</td><td>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Drop foreign key if exists
echo "<h3>2. Drop Foreign Key</h3>";
if (isset($_GET['fix'])) {
    try {
        // Try to drop the FK
        $db->exec("ALTER TABLE payment_slips DROP FOREIGN KEY payment_slips_ibfk_1");
        echo "<p style='color:green'>✅ Dropped payment_slips_ibfk_1</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ " . $e->getMessage() . "</p>";
    }
    
    // Try other possible FK names
    $possibleFKs = ['fk_payment_slips_order', 'payment_slips_order_id_foreign', 'fk_order_id'];
    foreach ($possibleFKs as $fkName) {
        try {
            $db->exec("ALTER TABLE payment_slips DROP FOREIGN KEY {$fkName}");
            echo "<p style='color:green'>✅ Dropped {$fkName}</p>";
        } catch (Exception $e) {
            // Ignore if not exists
        }
    }
    
    // Make order_id nullable
    try {
        $db->exec("ALTER TABLE payment_slips MODIFY COLUMN order_id INT DEFAULT NULL");
        echo "<p style='color:green'>✅ Made order_id nullable</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='fix_payment_slips_fk.php'>Refresh to verify</a></p>";
    
    // Test insert
    echo "<h3>3. Test Insert After Fix</h3>";
    try {
        $stmt = $db->query("SELECT id, user_id FROM transactions ORDER BY id DESC LIMIT 1");
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $testUrl = 'https://test.com/test_' . time() . '.jpg';
        $stmt = $db->prepare("INSERT INTO payment_slips (transaction_id, user_id, image_url, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$txn['id'], $txn['user_id'], $testUrl]);
        $insertId = $db->lastInsertId();
        echo "<p style='color:green'>✅ Test insert SUCCESS! ID: {$insertId}</p>";
        
        // Clean up
        $db->exec("DELETE FROM payment_slips WHERE id = {$insertId}");
        echo "<p>Test record deleted</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Test insert failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p><a href='?fix=1' onclick=\"return confirm('Drop foreign key constraint?')\">🔧 Click to fix (drop FK)</a></p>";
}

// 3. Show slip files not in DB
echo "<h3>4. Slip Files NOT in Database</h3>";
$slipDir = __DIR__ . '/uploads/slips/';
if (is_dir($slipDir)) {
    $files = array_diff(scandir($slipDir), ['.', '..', '.htaccess']);
    $notInDb = [];
    foreach ($files as $f) {
        $stmt = $db->prepare("SELECT id FROM payment_slips WHERE image_url LIKE ?");
        $stmt->execute(['%' . $f]);
        if (!$stmt->fetch()) {
            $notInDb[] = $f;
        }
    }
    
    if (empty($notInDb)) {
        echo "<p style='color:green'>✅ All files are in database</p>";
    } else {
        echo "<p style='color:orange'>⚠️ " . count($notInDb) . " file(s) not in database:</p>";
        echo "<ul>";
        foreach (array_slice($notInDb, 0, 10) as $f) {
            echo "<li>{$f}</li>";
        }
        echo "</ul>";
        
        if (isset($_GET['import'])) {
            echo "<h3>5. Importing missing slips...</h3>";
            foreach ($notInDb as $f) {
                // Extract order number from filename
                if (preg_match('/slip_(TXN\d+)_/', $f, $matches)) {
                    $orderNum = $matches[1];
                    $stmt = $db->prepare("SELECT id, user_id FROM transactions WHERE order_number = ?");
                    $stmt->execute([$orderNum]);
                    $txn = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($txn) {
                        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ("https://" . $_SERVER['HTTP_HOST']);
                        $imageUrl = $baseUrl . "/uploads/slips/" . $f;
                        try {
                            $stmt = $db->prepare("INSERT INTO payment_slips (transaction_id, user_id, image_url, status) VALUES (?, ?, ?, 'pending')");
                            $stmt->execute([$txn['id'], $txn['user_id'], $imageUrl]);
                            echo "<p style='color:green'>✅ Imported: {$f} → transaction #{$txn['id']}</p>";
                        } catch (Exception $e) {
                            echo "<p style='color:red'>❌ Failed {$f}: " . $e->getMessage() . "</p>";
                        }
                    } else {
                        echo "<p style='color:orange'>⚠️ No transaction found for {$orderNum}</p>";
                    }
                }
            }
        } else {
            echo "<p><a href='?fix=1&import=1'>🔧 Fix FK and Import missing slips</a></p>";
        }
    }
}
