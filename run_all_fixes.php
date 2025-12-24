<?php
/**
 * Run All Fixes & Migrations - Complete Version
 * รันไฟล์นี้เพื่อแก้ไขปัญหาทั้งหมดในระบบ
 */
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Run All Fixes & Migrations</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:1200px;margin:auto}
.success{color:#059669;background:#D1FAE5;padding:10px;border-radius:8px;margin:5px 0}
.error{color:#DC2626;background:#FEE2E2;padding:10px;border-radius:8px;margin:5px 0}
.info{color:#2563EB;background:#DBEAFE;padding:10px;border-radius:8px;margin:5px 0}
h2{margin-top:30px;border-bottom:2px solid #E5E7EB;padding-bottom:10px}</style>";

$results = [];

// ==========================================
// 1. TAGS SYSTEM
// ==========================================
echo "<h2>1. 🏷️ Tags System</h2>";

// Create tags table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `tags` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `color` varchar(20) DEFAULT 'gray',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง tags พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ tags: " . $e->getMessage() . "</div>";
}

// Create user_tag_assignments table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `tag_id` int(11) NOT NULL,
        `assigned_by` varchar(50) DEFAULT 'manual',
        `assigned_reason` text,
        `score` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `expires_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_tag` (`user_id`, `tag_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_tag_id` (`tag_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง user_tag_assignments พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ user_tag_assignments: " . $e->getMessage() . "</div>";
}

// ==========================================
// 2. LOYALTY POINTS SYSTEM
// ==========================================
echo "<h2>2. 💎 Loyalty Points System</h2>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `user_points` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `line_account_id` int(11) DEFAULT NULL,
        `total_points` int(11) DEFAULT 0,
        `available_points` int(11) DEFAULT 0,
        `used_points` int(11) DEFAULT 0,
        `tier` varchar(20) DEFAULT 'member',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_account` (`user_id`, `line_account_id`),
        KEY `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง user_points พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ user_points: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `points_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `line_account_id` int(11) DEFAULT NULL,
        `points` int(11) NOT NULL,
        `type` enum('earn','use','expire','adjust') NOT NULL,
        `source` varchar(50) DEFAULT NULL,
        `source_id` int(11) DEFAULT NULL,
        `description` varchar(255) DEFAULT NULL,
        `balance_after` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง points_history พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ points_history: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `rewards` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `description` text,
        `points_required` int(11) NOT NULL,
        `stock` int(11) DEFAULT -1,
        `image_url` varchar(500) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง rewards พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ rewards: " . $e->getMessage() . "</div>";
}

// ==========================================
// 3. SHOP SYSTEM
// ==========================================
echo "<h2>3. 🛒 Shop System</h2>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `business_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `description` text,
        `image_url` varchar(500) DEFAULT NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง business_categories พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ business_categories: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `business_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `category_id` int(11) DEFAULT NULL,
        `name` varchar(200) NOT NULL,
        `description` text,
        `price` decimal(10,2) NOT NULL DEFAULT 0,
        `sale_price` decimal(10,2) DEFAULT NULL,
        `image_url` varchar(500) DEFAULT NULL,
        `stock` int(11) DEFAULT -1,
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_category` (`category_id`),
        KEY `idx_line_account` (`line_account_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง business_items พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ business_items: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `cart_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_product` (`user_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง cart_items พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ cart_items: " . $e->getMessage() . "</div>";
}

// ==========================================
// 4. TRANSACTIONS SYSTEM
// ==========================================
echo "<h2>4. 📦 Transactions System</h2>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `user_id` int(11) NOT NULL,
        `order_number` varchar(50) NOT NULL,
        `status` enum('pending','confirmed','paid','shipping','delivered','cancelled') DEFAULT 'pending',
        `subtotal` decimal(10,2) DEFAULT 0,
        `discount` decimal(10,2) DEFAULT 0,
        `shipping_fee` decimal(10,2) DEFAULT 0,
        `grand_total` decimal(10,2) DEFAULT 0,
        `shipping_name` varchar(100) DEFAULT NULL,
        `shipping_phone` varchar(20) DEFAULT NULL,
        `shipping_address` text,
        `tracking_number` varchar(100) DEFAULT NULL,
        `note` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_order_number` (`order_number`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง transactions พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ transactions: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `transaction_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` int(11) NOT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(200) DEFAULT NULL,
        `price` decimal(10,2) DEFAULT 0,
        `quantity` int(11) DEFAULT 1,
        `total` decimal(10,2) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_transaction_id` (`transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง transaction_items พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ transaction_items: " . $e->getMessage() . "</div>";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `payment_slips` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `slip_url` varchar(500) DEFAULT NULL,
        `amount` decimal(10,2) DEFAULT NULL,
        `status` enum('pending','approved','rejected') DEFAULT 'pending',
        `admin_note` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_transaction_id` (`transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง payment_slips พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ payment_slips: " . $e->getMessage() . "</div>";
}

// ==========================================
// 5. RICH MENU SYSTEM
// ==========================================
echo "<h2>5. 📱 Rich Menu System</h2>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `rich_menus` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `line_rich_menu_id` varchar(100) DEFAULT NULL,
        `name` varchar(255) NOT NULL,
        `chat_bar_text` varchar(50) DEFAULT NULL,
        `size_width` int(11) DEFAULT 2500,
        `size_height` int(11) DEFAULT 1686,
        `areas` json DEFAULT NULL,
        `image_path` varchar(255) DEFAULT NULL,
        `is_default` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='success'>✅ ตาราง rich_menus พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ rich_menus: " . $e->getMessage() . "</div>";
}

// ==========================================
// 6. SHOP SETTINGS
// ==========================================
echo "<h2>6. ⚙️ Shop Settings</h2>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `shop_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `line_account_id` int(11) DEFAULT NULL,
        `shop_name` varchar(100) DEFAULT 'LINE Shop',
        `shop_description` text,
        `shop_logo` varchar(500) DEFAULT NULL,
        `is_open` tinyint(1) DEFAULT 1,
        `shipping_fee` decimal(10,2) DEFAULT 0,
        `free_shipping_min` decimal(10,2) DEFAULT 0,
        `payment_methods` json DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insert default if not exists
    $stmt = $db->query("SELECT COUNT(*) FROM shop_settings");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO shop_settings (shop_name, is_open) VALUES ('LINE Shop', 1)");
    }
    echo "<div class='success'>✅ ตาราง shop_settings พร้อมใช้งาน</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ shop_settings: " . $e->getMessage() . "</div>";
}

// ==========================================
// 7. FIX FOREIGN KEYS
// ==========================================
echo "<h2>7. 🔗 Fix Foreign Keys</h2>";

// Drop cart_items FK to products (should be business_items)
try {
    $db->exec("ALTER TABLE cart_items DROP FOREIGN KEY cart_items_ibfk_2");
    echo "<div class='success'>✅ ลบ FK cart_items_ibfk_2 สำเร็จ</div>";
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ FK cart_items_ibfk_2 ไม่มีหรือลบแล้ว</div>";
}

// ==========================================
// 8. ADD MISSING COLUMNS
// ==========================================
echo "<h2>8. 📝 Add Missing Columns</h2>";

// Users table columns
$userColumns = [
    'real_name' => "ALTER TABLE users ADD COLUMN real_name VARCHAR(100) DEFAULT NULL",
    'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
    'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL",
    'birthday' => "ALTER TABLE users ADD COLUMN birthday DATE DEFAULT NULL",
    'address' => "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL",
    'province' => "ALTER TABLE users ADD COLUMN province VARCHAR(50) DEFAULT NULL",
    'postal_code' => "ALTER TABLE users ADD COLUMN postal_code VARCHAR(10) DEFAULT NULL",
    'note' => "ALTER TABLE users ADD COLUMN note TEXT DEFAULT NULL"
];

foreach ($userColumns as $col => $sql) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec($sql);
            echo "<div class='success'>✅ เพิ่ม column users.$col</div>";
        }
    } catch (Exception $e) {}
}

// business_items columns
$itemColumns = [
    'sort_order' => "ALTER TABLE business_items ADD COLUMN sort_order INT DEFAULT 0",
    'stock' => "ALTER TABLE business_items ADD COLUMN stock INT DEFAULT -1",
    'sale_price' => "ALTER TABLE business_items ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL"
];

foreach ($itemColumns as $col => $sql) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM business_items LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec($sql);
            echo "<div class='success'>✅ เพิ่ม column business_items.$col</div>";
        }
    } catch (Exception $e) {}
}

// ==========================================
// 9. DEFAULT DATA
// ==========================================
echo "<h2>9. 📊 Default Data</h2>";

// Default tags
try {
    $stmt = $db->query("SELECT COUNT(*) FROM tags");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO tags (name, color) VALUES 
            ('ลูกค้าใหม่', 'green'),
            ('รอชำระเงิน', 'yellow'),
            ('VIP', 'red'),
            ('ส่งแล้ว', 'blue')");
        echo "<div class='success'>✅ เพิ่ม default tags</div>";
    } else {
        echo "<div class='info'>ℹ️ tags มีข้อมูลแล้ว</div>";
    }
} catch (Exception $e) {}

// ==========================================
// 10. SUMMARY
// ==========================================
echo "<h2>10. 📋 Summary</h2>";

$tables = ['tags', 'user_tag_assignments', 'user_points', 'points_history', 'rewards', 
           'business_categories', 'business_items', 'cart_items', 'transactions', 
           'transaction_items', 'payment_slips', 'rich_menus', 'shop_settings'];

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%'>";
echo "<tr style='background:#F3F4F6'><th>Table</th><th>Status</th><th>Rows</th></tr>";

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<tr><td>$table</td><td style='color:green'>✅ OK</td><td>$count</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>$table</td><td style='color:red'>❌ Missing</td><td>-</td></tr>";
    }
}
echo "</table>";

echo "<br><div class='success' style='font-size:18px;text-align:center'>🎉 การติดตั้งเสร็จสมบูรณ์!</div>";
echo "<p style='text-align:center'><a href='index.php' style='color:#059669'>← กลับหน้าหลัก</a></p>";
