<?php
/**
 * Run Complete Migration - Final Version
 * รันไฟล์นี้เพื่อติดตั้ง/อัพเดทตารางทั้งหมด
 */
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Complete Migration</title>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:1200px;margin:auto;background:#F9FAFB}
.box{background:#fff;border-radius:12px;padding:20px;margin:15px 0;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
.success{color:#059669;background:#D1FAE5;padding:10px;border-radius:8px;margin:5px 0}
.error{color:#DC2626;background:#FEE2E2;padding:10px;border-radius:8px;margin:5px 0}
.info{color:#2563EB;background:#DBEAFE;padding:10px;border-radius:8px;margin:5px 0}
h1{color:#1F2937}h2{color:#374151;border-bottom:2px solid #10B981;padding-bottom:8px}
table{width:100%;border-collapse:collapse}th,td{padding:10px;border:1px solid #E5E7EB;text-align:left}
th{background:#F3F4F6}.btn{display:inline-block;padding:12px 24px;background:#10B981;color:white;
text-decoration:none;border-radius:8px;margin:5px}</style></head><body>";

echo "<h1>🚀 LINE CRM - Complete Migration</h1>";

$results = ['success' => 0, 'error' => 0, 'skip' => 0];

function runSQL($db, $sql, $name) {
    global $results;
    try {
        $db->exec($sql);
        echo "<div class='success'>✅ $name</div>";
        $results['success']++;
        return true;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<div class='info'>ℹ️ $name (มีอยู่แล้ว)</div>";
            $results['skip']++;
        } else {
            echo "<div class='error'>❌ $name: " . $e->getMessage() . "</div>";
            $results['error']++;
        }
        return false;
    }
}

function addColumn($db, $table, $column, $definition) {
    global $results;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "<div class='success'>✅ เพิ่ม $table.$column</div>";
            $results['success']++;
        }
    } catch (Exception $e) {}
}

// ==========================================
// 1. TAGS SYSTEM
// ==========================================
echo "<div class='box'><h2>1. 🏷️ Tags System</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `color` varchar(20) DEFAULT 'gray',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง tags");

runSQL($db, "CREATE TABLE IF NOT EXISTS `user_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(50) NOT NULL,
    `color` varchar(20) DEFAULT 'gray',
    `description` text,
    `auto_assign` tinyint(1) DEFAULT 0,
    `conditions` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง user_tags");

runSQL($db, "CREATE TABLE IF NOT EXISTS `user_tag_assignments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง user_tag_assignments");

echo "</div>";

// ==========================================
// 2. LOYALTY POINTS
// ==========================================
echo "<div class='box'><h2>2. 💎 Loyalty Points</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `user_points` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง user_points");

runSQL($db, "CREATE TABLE IF NOT EXISTS `points_history` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง points_history");

runSQL($db, "CREATE TABLE IF NOT EXISTS `rewards` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง rewards");

echo "</div>";

// ==========================================
// 3. SHOP SYSTEM
// ==========================================
echo "<div class='box'><h2>3. 🛒 Shop System</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `business_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `image_url` varchar(500) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง business_categories");

runSQL($db, "CREATE TABLE IF NOT EXISTS `business_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `name` varchar(200) NOT NULL,
    `description` text,
    `price` decimal(10,2) NOT NULL DEFAULT 0,
    `sale_price` decimal(10,2) DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `stock` int(11) DEFAULT -1,
    `sku` varchar(50) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_line_account` (`line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง business_items");

runSQL($db, "CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง cart_items");

runSQL($db, "CREATE TABLE IF NOT EXISTS `shop_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `shop_name` varchar(100) DEFAULT 'LINE Shop',
    `shop_description` text,
    `shop_logo` varchar(500) DEFAULT NULL,
    `is_open` tinyint(1) DEFAULT 1,
    `shipping_fee` decimal(10,2) DEFAULT 0,
    `free_shipping_min` decimal(10,2) DEFAULT 0,
    `payment_methods` json DEFAULT NULL,
    `bank_accounts` json DEFAULT NULL,
    `checkout_fields` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง shop_settings");

echo "</div>";

// ==========================================
// 4. TRANSACTIONS
// ==========================================
echo "<div class='box'><h2>4. 📦 Transactions</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `user_id` int(11) NOT NULL,
    `order_number` varchar(50) NOT NULL,
    `status` enum('pending','confirmed','paid','shipping','delivered','cancelled') DEFAULT 'pending',
    `subtotal` decimal(10,2) DEFAULT 0,
    `discount` decimal(10,2) DEFAULT 0,
    `shipping_fee` decimal(10,2) DEFAULT 0,
    `grand_total` decimal(10,2) DEFAULT 0,
    `points_earned` int(11) DEFAULT 0,
    `points_used` int(11) DEFAULT 0,
    `shipping_name` varchar(100) DEFAULT NULL,
    `shipping_phone` varchar(20) DEFAULT NULL,
    `shipping_address` text,
    `shipping_province` varchar(50) DEFAULT NULL,
    `shipping_postal_code` varchar(10) DEFAULT NULL,
    `tracking_number` varchar(100) DEFAULT NULL,
    `note` text,
    `admin_note` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_order_number` (`order_number`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง transactions");

runSQL($db, "CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `product_name` varchar(200) DEFAULT NULL,
    `product_image` varchar(500) DEFAULT NULL,
    `price` decimal(10,2) DEFAULT 0,
    `quantity` int(11) DEFAULT 1,
    `total` decimal(10,2) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง transaction_items");

runSQL($db, "CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `slip_url` varchar(500) DEFAULT NULL,
    `amount` decimal(10,2) DEFAULT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `admin_note` text,
    `verified_at` timestamp NULL DEFAULT NULL,
    `verified_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง payment_slips");

echo "</div>";

// ==========================================
// 5. RICH MENU & BROADCAST
// ==========================================
echo "<div class='box'><h2>5. 📱 Rich Menu & Broadcast</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `rich_menus` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `line_rich_menu_id` varchar(100) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `chat_bar_text` varchar(50) DEFAULT 'เมนู',
    `size_width` int(11) DEFAULT 2500,
    `size_height` int(11) DEFAULT 1686,
    `areas` json DEFAULT NULL,
    `image_path` varchar(255) DEFAULT NULL,
    `is_default` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง rich_menus");

runSQL($db, "CREATE TABLE IF NOT EXISTS `broadcasts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `message_type` varchar(50) DEFAULT 'text',
    `message_content` text,
    `flex_content` json DEFAULT NULL,
    `target_type` enum('all','segment','tags') DEFAULT 'all',
    `target_tags` json DEFAULT NULL,
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `status` enum('draft','scheduled','sending','sent','failed') DEFAULT 'draft',
    `total_recipients` int(11) DEFAULT 0,
    `success_count` int(11) DEFAULT 0,
    `fail_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง broadcasts");

runSQL($db, "CREATE TABLE IF NOT EXISTS `broadcast_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `broadcast_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `action_type` varchar(50) DEFAULT NULL,
    `action_data` text,
    `clicked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_broadcast_id` (`broadcast_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง broadcast_clicks");

echo "</div>";

// ==========================================
// 6. DRIP CAMPAIGNS & AUTO REPLY
// ==========================================
echo "<div class='box'><h2>6. 🤖 Drip Campaigns & Auto Reply</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `drip_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `trigger_type` enum('new_follower','tag_added','purchase','manual') DEFAULT 'new_follower',
    `trigger_tag_id` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง drip_campaigns");

runSQL($db, "CREATE TABLE IF NOT EXISTS `drip_campaign_steps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `step_order` int(11) DEFAULT 1,
    `delay_minutes` int(11) DEFAULT 0,
    `message_type` varchar(50) DEFAULT 'text',
    `message_content` text,
    `flex_content` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง drip_campaign_steps");

runSQL($db, "CREATE TABLE IF NOT EXISTS `drip_campaign_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `step_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `scheduled_at` timestamp NOT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `status` enum('pending','sent','failed','cancelled') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_scheduled` (`scheduled_at`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง drip_campaign_queue");

runSQL($db, "CREATE TABLE IF NOT EXISTS `auto_replies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `keyword` varchar(255) NOT NULL,
    `match_type` enum('exact','contains','starts','regex') DEFAULT 'contains',
    `reply_type` varchar(50) DEFAULT 'text',
    `reply_content` text,
    `flex_content` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `priority` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง auto_replies");

runSQL($db, "CREATE TABLE IF NOT EXISTS `user_states` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `line_account_id` int(11) DEFAULT NULL,
    `state` varchar(50) DEFAULT 'idle',
    `state_data` json DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_account` (`user_id`, `line_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง user_states");

echo "</div>";

// ==========================================
// 7. LINK TRACKING
// ==========================================
echo "<div class='box'><h2>7. 🔗 Link Tracking</h2>";

runSQL($db, "CREATE TABLE IF NOT EXISTS `tracked_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `line_account_id` int(11) DEFAULT NULL,
    `short_code` varchar(20) NOT NULL,
    `original_url` text NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `click_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_short_code` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง tracked_links");

runSQL($db, "CREATE TABLE IF NOT EXISTS `link_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `link_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `clicked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_link_id` (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "ตาราง link_clicks");

echo "</div>";

// ==========================================
// 8. ADD MISSING COLUMNS
// ==========================================
echo "<div class='box'><h2>8. 📝 Add Missing Columns</h2>";

// Users table
addColumn($db, 'users', 'real_name', "VARCHAR(100) DEFAULT NULL");
addColumn($db, 'users', 'phone', "VARCHAR(20) DEFAULT NULL");
addColumn($db, 'users', 'email', "VARCHAR(100) DEFAULT NULL");
addColumn($db, 'users', 'birthday', "DATE DEFAULT NULL");
addColumn($db, 'users', 'address', "TEXT DEFAULT NULL");
addColumn($db, 'users', 'province', "VARCHAR(50) DEFAULT NULL");
addColumn($db, 'users', 'postal_code', "VARCHAR(10) DEFAULT NULL");
addColumn($db, 'users', 'note', "TEXT DEFAULT NULL");
addColumn($db, 'users', 'total_spent', "DECIMAL(12,2) DEFAULT 0");
addColumn($db, 'users', 'order_count', "INT DEFAULT 0");

// business_items
addColumn($db, 'business_items', 'sort_order', "INT DEFAULT 0");
addColumn($db, 'business_items', 'stock', "INT DEFAULT -1");
addColumn($db, 'business_items', 'sale_price', "DECIMAL(10,2) DEFAULT NULL");
addColumn($db, 'business_items', 'sku', "VARCHAR(50) DEFAULT NULL");

// transactions
addColumn($db, 'transactions', 'points_earned', "INT DEFAULT 0");
addColumn($db, 'transactions', 'points_used', "INT DEFAULT 0");
addColumn($db, 'transactions', 'admin_note', "TEXT DEFAULT NULL");

echo "</div>";

// ==========================================
// 9. FIX FOREIGN KEYS
// ==========================================
echo "<div class='box'><h2>9. 🔗 Fix Foreign Keys</h2>";

try {
    $db->exec("ALTER TABLE cart_items DROP FOREIGN KEY cart_items_ibfk_2");
    echo "<div class='success'>✅ ลบ FK cart_items_ibfk_2</div>";
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ FK cart_items_ibfk_2 ไม่มีหรือลบแล้ว</div>";
}

try {
    $db->exec("ALTER TABLE payment_slips DROP FOREIGN KEY payment_slips_ibfk_1");
    echo "<div class='success'>✅ ลบ FK payment_slips_ibfk_1</div>";
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ FK payment_slips_ibfk_1 ไม่มีหรือลบแล้ว</div>";
}

echo "</div>";

// ==========================================
// 10. DEFAULT DATA
// ==========================================
echo "<div class='box'><h2>10. 📊 Default Data</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM tags");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO tags (name, color) VALUES 
            ('ลูกค้าใหม่', 'green'),
            ('รอชำระเงิน', 'yellow'),
            ('VIP', 'red'),
            ('ส่งแล้ว', 'blue'),
            ('ลูกค้าประจำ', 'purple')");
        echo "<div class='success'>✅ เพิ่ม default tags</div>";
    } else {
        echo "<div class='info'>ℹ️ tags มีข้อมูลแล้ว</div>";
    }
} catch (Exception $e) {}

try {
    $stmt = $db->query("SELECT COUNT(*) FROM shop_settings");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO shop_settings (shop_name, is_open, shipping_fee, free_shipping_min) 
                   VALUES ('LINE Shop', 1, 50, 500)");
        echo "<div class='success'>✅ เพิ่ม default shop settings</div>";
    } else {
        echo "<div class='info'>ℹ️ shop_settings มีข้อมูลแล้ว</div>";
    }
} catch (Exception $e) {}

echo "</div>";

// ==========================================
// 11. SUMMARY
// ==========================================
echo "<div class='box'><h2>11. 📋 Summary</h2>";

$allTables = [
    'users', 'messages', 'line_accounts', 'tags', 'user_tags', 'user_tag_assignments',
    'user_points', 'points_history', 'rewards', 'business_categories', 'business_items',
    'cart_items', 'shop_settings', 'transactions', 'transaction_items', 'payment_slips',
    'rich_menus', 'broadcasts', 'broadcast_clicks', 'drip_campaigns', 'drip_campaign_steps',
    'drip_campaign_queue', 'auto_replies', 'user_states', 'tracked_links', 'link_clicks'
];

echo "<table><tr><th>Table</th><th>Status</th><th>Rows</th></tr>";
$okCount = 0;
$failCount = 0;

foreach ($allTables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<tr><td>$table</td><td style='color:#059669'>✅ OK</td><td>$count</td></tr>";
        $okCount++;
    } catch (Exception $e) {
        echo "<tr><td>$table</td><td style='color:#DC2626'>❌ Missing</td><td>-</td></tr>";
        $failCount++;
    }
}
echo "</table>";

echo "<div style='margin-top:20px;padding:20px;background:#F3F4F6;border-radius:12px;text-align:center'>";
echo "<div style='display:inline-block;margin:0 20px'><span style='font-size:32px;color:#059669'>{$results['success']}</span><br>Success</div>";
echo "<div style='display:inline-block;margin:0 20px'><span style='font-size:32px;color:#2563EB'>{$results['skip']}</span><br>Skipped</div>";
echo "<div style='display:inline-block;margin:0 20px'><span style='font-size:32px;color:#DC2626'>{$results['error']}</span><br>Errors</div>";
echo "<div style='display:inline-block;margin:0 20px'><span style='font-size:32px;color:#059669'>$okCount</span><br>Tables OK</div>";
echo "</div>";

if ($failCount == 0 && $results['error'] == 0) {
    echo "<div class='success' style='margin-top:20px;font-size:18px;text-align:center'>🎉 Migration สำเร็จ! ระบบพร้อมใช้งาน</div>";
} else {
    echo "<div class='error' style='margin-top:20px;text-align:center'>⚠️ มีบางรายการที่ต้องตรวจสอบ</div>";
}

echo "</div>";

// Quick Links
echo "<div class='box'><h2>🔗 Quick Links</h2>";
echo "<a href='index.php' class='btn'>🏠 หน้าหลัก</a>";
echo "<a href='debug_system.php' class='btn'>🔍 Debug System</a>";
echo "<a href='users.php' class='btn'>👥 Users</a>";
echo "<a href='messages.php' class='btn'>💬 Messages</a>";
echo "<a href='shop/index.php' class='btn'>🛒 Shop</a>";
echo "<a href='loyalty-points.php' class='btn'>💎 Loyalty</a>";
echo "<a href='rich-menu.php' class='btn'>📱 Rich Menu</a>";
echo "</div>";

echo "</body></html>";
