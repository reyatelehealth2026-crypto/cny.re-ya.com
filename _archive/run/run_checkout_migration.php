<?php
/**
 * Run checkout options migration
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Checkout Options Migration</h2>";

// Add payment_method to transactions
echo "<h3>1. Add payment_method to transactions</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE transactions ADD COLUMN payment_method VARCHAR(50) DEFAULT 'transfer' AFTER payment_status");
        echo "✅ Added payment_method column<br>";
    } else {
        echo "✅ payment_method column already exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Add cod_enabled to shop_settings
echo "<h3>2. Add cod_enabled to shop_settings</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE 'cod_enabled'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE shop_settings ADD COLUMN cod_enabled TINYINT(1) DEFAULT 1");
        echo "✅ Added cod_enabled column<br>";
    } else {
        echo "✅ cod_enabled column already exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Add cod_fee to shop_settings
echo "<h3>3. Add cod_fee to shop_settings</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE 'cod_fee'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE shop_settings ADD COLUMN cod_fee DECIMAL(10,2) DEFAULT 0");
        echo "✅ Added cod_fee column<br>";
    } else {
        echo "✅ cod_fee column already exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Add liff_id to line_accounts
echo "<h3>4. Add liff_id to line_accounts</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN liff_id VARCHAR(50) DEFAULT NULL");
        echo "✅ Added liff_id column<br>";
    } else {
        echo "✅ liff_id column already exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Add liff_checkout_url to shop_settings
echo "<h3>5. Add liff_checkout_url to shop_settings</h3>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM shop_settings LIKE 'liff_checkout_url'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE shop_settings ADD COLUMN liff_checkout_url VARCHAR(255) DEFAULT NULL");
        echo "✅ Added liff_checkout_url column<br>";
    } else {
        echo "✅ liff_checkout_url column already exists<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><br>✅ Migration completed!";
echo "<br><br><a href='shop/products.php'>← Back to Products</a>";
