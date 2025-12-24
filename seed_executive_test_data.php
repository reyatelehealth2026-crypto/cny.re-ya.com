<?php
/**
 * Seed Test Data for Executive Dashboard
 * สร้างข้อมูลทดสอบสำหรับ Executive Dashboard
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🌱 Seeding Executive Dashboard Test Data</h2>";
echo "<pre>";

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// ==================== Check Messages Table Structure ====================
echo "\n📌 Checking messages table structure...\n";
try {
    $stmt = $db->query("DESCRIBE messages");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    echo "  Columns: " . implode(', ', array_keys($columns)) . "\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit;
}

// ==================== 1. Create Test Users ====================
echo "\n📌 Creating test users...\n";

$testUsers = [
    ['line_user_id' => 'U_test_exec_001', 'display_name' => 'คุณสมชาย ใจดี', 'picture_url' => 'https://profile.line-scdn.net/0h_'],
    ['line_user_id' => 'U_test_exec_002', 'display_name' => 'คุณสมหญิง รักสุขภาพ', 'picture_url' => 'https://profile.line-scdn.net/0h_'],
    ['line_user_id' => 'U_test_exec_003', 'display_name' => 'คุณวิชัย ปวดหัว', 'picture_url' => 'https://profile.line-scdn.net/0h_'],
    ['line_user_id' => 'U_test_exec_004', 'display_name' => 'คุณมาลี ไม่สบาย', 'picture_url' => 'https://profile.line-scdn.net/0h_'],
    ['line_user_id' => 'U_test_exec_005', 'display_name' => 'คุณประยุทธ์ สอบถาม', 'picture_url' => 'https://profile.line-scdn.net/0h_'],
];

$userIds = [];
foreach ($testUsers as $user) {
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE line_user_id = ?");
        $stmt->execute([$user['line_user_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $userIds[] = $existing['id'];
            echo "  ✓ User exists: {$user['display_name']} (ID: {$existing['id']})\n";
        } else {
            $stmt = $db->prepare("INSERT INTO users (line_user_id, display_name, picture_url, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['line_user_id'], $user['display_name'], $user['picture_url'], $now]);
            $userIds[] = $db->lastInsertId();
            echo "  ✓ Created: {$user['display_name']} (ID: {$db->lastInsertId()})\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error creating user: " . $e->getMessage() . "\n";
    }
}

// ==================== 2. Add sent_by column if not exists ====================
echo "\n📌 Checking sent_by column...\n";
if (!isset($columns['sent_by'])) {
    try {
        $db->exec("ALTER TABLE messages ADD COLUMN sent_by VARCHAR(100) NULL");
        echo "  ✓ Added sent_by column\n";
    } catch (Exception $e) {
        echo "  ✗ Could not add sent_by: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ✓ sent_by column exists\n";
}

// ==================== 3. Create Test Messages ====================
echo "\n📌 Creating test messages...\n";

$adminNames = ['Admin สมศักดิ์', 'Admin มานี', 'Admin วิภา', 'Bot'];
$problemMessages = [
    'มีปัญหาเรื่องสินค้าครับ ส่งผิดรุ่น',
    'ไม่พอใจมากเลย รอนานมาก',
    'ทำไมช้าจังครับ สั่งไป 3 วันแล้ว',
    'รอนานมาก ไม่มีใครตอบเลย',
    'ไม่ตอบเลยครับ ติดต่อไม่ได้',
    'สินค้าเสียครับ ขอคืนเงิน',
    'แย่มากเลย บริการไม่ดี',
];

$normalMessages = [
    'สอบถามราคายาพาราเซตามอลครับ',
    'มีสินค้าตัวนี้ไหมคะ',
    'ราคาเท่าไหร่คะ',
    'จัดส่งกี่วันถึงครับ',
    'แนะนำยาแก้ไอหน่อยค่ะ',
    'ขอบคุณมากครับ',
    'สั่งซื้อยังไงคะ',
    'มียาลดไข้ไหมครับ',
    'อยากได้วิตามินซี',
    'ส่งที่อยู่ให้แล้วนะคะ',
];

$replies = [
    'สวัสดีค่ะ ยินดีให้บริการค่ะ',
    'รับทราบค่ะ รอสักครู่นะคะ',
    'ขอบคุณที่ติดต่อมาค่ะ',
    'สินค้าพร้อมส่งค่ะ ราคา 150 บาท',
    'จัดส่งภายใน 1-2 วันค่ะ',
    'มีค่ะ รุ่นนี้ขายดีมากเลย',
    'แนะนำตัวนี้ค่ะ ดีมากๆ',
];

$messagesCreated = 0;
$errors = [];

// Generate messages for different hours today
for ($hour = 6; $hour <= min(23, (int)date('H') + 2); $hour++) {
    $msgCount = rand(8, 20);
    
    for ($i = 0; $i < $msgCount; $i++) {
        $userId = $userIds[array_rand($userIds)];
        $minute = rand(0, 59);
        $second = rand(0, 59);
        $msgTime = sprintf('%s %02d:%02d:%02d', $today, $hour, $minute, $second);
        
        // 25% chance of problem message
        $isProblem = rand(1, 100) <= 25;
        $message = $isProblem 
            ? $problemMessages[array_rand($problemMessages)]
            : $normalMessages[array_rand($normalMessages)];
        
        try {
            // Build insert based on available columns - use 'content' not 'message'
            $msgCol = isset($columns['content']) ? 'content' : 'message';
            $insertCols = ['user_id', $msgCol, 'direction', 'created_at'];
            $insertVals = [$userId, $message, 'incoming', $msgTime];
            $placeholders = ['?', '?', '?', '?'];
            
            if (isset($columns['is_read'])) {
                $insertCols[] = 'is_read';
                $insertVals[] = rand(0, 1);
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO messages (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($insertVals);
            $messagesCreated++;
            
            // 75% chance of reply
            if (rand(1, 100) <= 75) {
                $replyDelay = rand(1, 20);
                $replyTime = date('Y-m-d H:i:s', strtotime($msgTime) + ($replyDelay * 60));
                $reply = $replies[array_rand($replies)];
                $admin = $adminNames[array_rand($adminNames)];
                
                $insertCols2 = ['user_id', $msgCol, 'direction', 'created_at'];
                $insertVals2 = [$userId, $reply, 'outgoing', $replyTime];
                $placeholders2 = ['?', '?', '?', '?'];
                
                if (isset($columns['is_read'])) {
                    $insertCols2[] = 'is_read';
                    $insertVals2[] = 1;
                    $placeholders2[] = '?';
                }
                
                if (isset($columns['sent_by'])) {
                    $insertCols2[] = 'sent_by';
                    $insertVals2[] = $admin;
                    $placeholders2[] = '?';
                }
                
                $sql2 = "INSERT INTO messages (" . implode(', ', $insertCols2) . ") VALUES (" . implode(', ', $placeholders2) . ")";
                $stmt2 = $db->prepare($sql2);
                $stmt2->execute($insertVals2);
                $messagesCreated++;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

echo "  ✓ Created {$messagesCreated} messages\n";
if (!empty($errors)) {
    echo "  ⚠ Errors: " . count($errors) . " (first: " . $errors[0] . ")\n";
}

// ==================== 4. Create Test Orders ====================
echo "\n📌 Creating test orders...\n";

$ordersTable = 'transactions';
try {
    $db->query("SELECT 1 FROM transactions LIMIT 1");
} catch (Exception $e) {
    try {
        $db->query("SELECT 1 FROM orders LIMIT 1");
        $ordersTable = 'orders';
    } catch (Exception $e2) {
        echo "  ✗ No orders/transactions table found\n";
        $ordersTable = null;
    }
}

$ordersCreated = 0;
if ($ordersTable) {
    $statuses = ['pending', 'confirmed', 'paid', 'completed', 'delivered'];
    
    for ($i = 0; $i < 15; $i++) {
        $userId = $userIds[array_rand($userIds)];
        $status = $statuses[array_rand($statuses)];
        $total = rand(150, 3500);
        $hour = rand(6, min(23, (int)date('H') + 2));
        $orderTime = sprintf('%s %02d:%02d:%02d', $today, $hour, rand(0, 59), rand(0, 59));
        
        try {
            $stmt = $db->prepare("INSERT INTO {$ordersTable} (user_id, status, grand_total, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $status, $total, $orderTime]);
            $ordersCreated++;
        } catch (Exception $e) {
            // Skip
        }
    }
    echo "  ✓ Created {$ordersCreated} orders in {$ordersTable}\n";
}

// ==================== Summary ====================
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Test data seeding completed!\n";
echo str_repeat("=", 50) . "\n";

echo "\n📊 Summary:\n";
echo "  - Users: " . count($userIds) . "\n";
echo "  - Messages: {$messagesCreated}\n";
echo "  - Orders: {$ordersCreated}\n";

// Quick verify
echo "\n📌 Verification:\n";
$stmt = $db->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()");
echo "  - Messages today: " . $stmt->fetchColumn() . "\n";

$stmt = $db->query("SELECT COUNT(*) FROM messages WHERE direction = 'incoming' AND DATE(created_at) = CURDATE()");
echo "  - Incoming today: " . $stmt->fetchColumn() . "\n";

echo "\n🔗 <a href='/executive-dashboard'>View Executive Dashboard</a>\n";
echo "</pre>";
