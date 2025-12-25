<?php
/**
 * Fix Transactions Table
 * สร้างหรือแก้ไขตาราง transactions ให้ถูกต้อง
 */
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔧 Fix Transactions Table</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if transactions table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'transactions'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<h3>1. Current table structure:</h3>";
        $stmt = $db->query("DESCRIBE transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
        
        // Check for missing columns and add them
        echo "<h3>2. Adding missing columns...</h3>";
        
        $existingCols = array_column($columns, 'Field');
        
        $requiredColumns = [
            'line_account_id' => "INT NULL",
            'transaction_type' => "ENUM('purchase','booking','dispense') DEFAULT 'purchase'",
            'order_number' => "VARCHAR(50) NULL",
            'user_id' => "INT NULL",
            'total_amount' => "DECIMAL(10,2) DEFAULT 0",
            'shipping_fee' => "DECIMAL(10,2) DEFAULT 0",
            'grand_total' => "DECIMAL(10,2) DEFAULT 0",
            'delivery_info' => "TEXT NULL",
            'payment_method' => "VARCHAR(50) DEFAULT 'transfer'",
            'status' => "VARCHAR(20) DEFAULT 'pending'",
            'payment_status' => "VARCHAR(20) DEFAULT 'pending'",
            'notes' => "TEXT NULL",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($requiredColumns as $colName => $colDef) {
            if (!in_array($colName, $existingCols)) {
                try {
                    $db->exec("ALTER TABLE transactions ADD COLUMN `$colName` $colDef");
                    echo "<p>✅ Added: $colName</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ $colName: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p>⏭️ Exists: $colName</p>";
            }
        }
        
    } else {
        echo "<h3>Creating transactions table...</h3>";
        
        $db->exec("
            CREATE TABLE transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_account_id INT NULL,
                transaction_type ENUM('purchase','booking','dispense') DEFAULT 'purchase',
                order_number VARCHAR(50) NULL,
                user_id INT NULL,
                total_amount DECIMAL(10,2) DEFAULT 0,
                shipping_fee DECIMAL(10,2) DEFAULT 0,
                grand_total DECIMAL(10,2) DEFAULT 0,
                delivery_info TEXT NULL,
                payment_method VARCHAR(50) DEFAULT 'transfer',
                status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','completed') DEFAULT 'pending',
                payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_order (order_number),
                INDEX idx_account (line_account_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Created transactions table</p>";
    }
    
    // Also create transaction_items if not exists
    echo "<h3>3. Checking transaction_items table...</h3>";
    
    $itemsExists = $db->query("SHOW TABLES LIKE 'transaction_items'")->rowCount() > 0;
    
    if (!$itemsExists) {
        $db->exec("
            CREATE TABLE transaction_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id INT NOT NULL,
                product_id INT NULL,
                product_name VARCHAR(255) NOT NULL,
                product_sku VARCHAR(100) NULL,
                product_price DECIMAL(10,2) DEFAULT 0,
                quantity INT DEFAULT 1,
                subtotal DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transaction (transaction_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Created transaction_items table</p>";
    } else {
        echo "<p>⏭️ Exists: transaction_items table</p>";
        
        // Check and add missing columns
        $itemCols = ['product_price', 'subtotal', 'product_name', 'product_sku', 'quantity'];
        $stmt = $db->query("DESCRIBE transaction_items");
        $existingItemCols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        $itemColDefs = [
            'product_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'product_sku' => "VARCHAR(100) NULL",
            'product_price' => "DECIMAL(10,2) DEFAULT 0",
            'quantity' => "INT DEFAULT 1",
            'subtotal' => "DECIMAL(10,2) DEFAULT 0"
        ];
        
        foreach ($itemColDefs as $col => $def) {
            if (!in_array($col, $existingItemCols)) {
                try {
                    $db->exec("ALTER TABLE transaction_items ADD COLUMN `$col` $def");
                    echo "<p>✅ Added to transaction_items: $col</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ transaction_items.$col: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // 4. Check payment_slips table
    echo "<h3>4. Checking payment_slips table...</h3>";
    
    $slipsExists = $db->query("SHOW TABLES LIKE 'payment_slips'")->rowCount() > 0;
    
    if (!$slipsExists) {
        $db->exec("
            CREATE TABLE payment_slips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id INT NOT NULL,
                user_id INT NULL,
                image_url VARCHAR(500) NOT NULL,
                status ENUM('pending','approved','rejected') DEFAULT 'pending',
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transaction (transaction_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Created payment_slips table</p>";
    } else {
        echo "<p>⏭️ Exists: payment_slips table</p>";
    }
    
    // 5. Check cart_items table
    echo "<h3>5. Checking cart_items table...</h3>";
    
    $cartExists = $db->query("SHOW TABLES LIKE 'cart_items'")->rowCount() > 0;
    
    if (!$cartExists) {
        $db->exec("
            CREATE TABLE cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_cart (user_id, product_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Created cart_items table</p>";
    } else {
        echo "<p>⏭️ Exists: cart_items table</p>";
    }
    
    echo "<h3>✅ Done!</h3>";
    echo "<p><a href='liff-checkout.php'>ทดสอบ Checkout</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
