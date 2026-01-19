<?php
/**
 * Create sample rewards for line_account_id = 3
 * Run this directly on the server: php install/create_rewards_account3.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/LoyaltyPoints.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = 3;

echo "=== Creating Sample Rewards for Account ID: $lineAccountId ===\n\n";

// Check existing rewards
$stmt = $db->prepare("SELECT COUNT(*) FROM rewards WHERE line_account_id = ?");
$stmt->execute([$lineAccountId]);
$existingCount = $stmt->fetchColumn();

echo "Existing rewards: $existingCount\n\n";

if ($existingCount > 0) {
    echo "⚠️  Rewards already exist. Do you want to add more? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) != 'y') {
        echo "Cancelled.\n";
        exit(0);
    }
    fclose($handle);
}

// Create LoyaltyPoints instance
$loyalty = new LoyaltyPoints($db, $lineAccountId);

// Sample rewards
$sampleRewards = [
    [
        'name' => 'ส่วนลด 50 บาท',
        'description' => 'รับส่วนลด 50 บาท สำหรับการซื้อครั้งถัดไป',
        'points_required' => 500,
        'reward_type' => 'discount',
        'reward_value' => 50,
        'stock' => -1, // Unlimited
        'max_per_user' => 0,
        'is_active' => 1
    ],
    [
        'name' => 'ส่วนลด 100 บาท',
        'description' => 'รับส่วนลด 100 บาท สำหรับการซื้อครั้งถัดไป',
        'points_required' => 1000,
        'reward_type' => 'discount',
        'reward_value' => 100,
        'stock' => -1,
        'max_per_user' => 0,
        'is_active' => 1
    ],
    [
        'name' => 'ส่วนลด 200 บาท',
        'description' => 'รับส่วนลด 200 บาท สำหรับการซื้อครั้งถัดไป (จำกัด 5 สิทธิ์)',
        'points_required' => 2000,
        'reward_type' => 'discount',
        'reward_value' => 200,
        'stock' => 5,
        'max_per_user' => 1,
        'is_active' => 1
    ],
    [
        'name' => 'จัดส่งฟรี',
        'description' => 'รับบริการจัดส่งฟรีสำหรับคำสั่งซื้อครั้งถัดไป',
        'points_required' => 300,
        'reward_type' => 'free_shipping',
        'reward_value' => 0,
        'stock' => -1,
        'max_per_user' => 0,
        'is_active' => 1
    ],
    [
        'name' => 'ของขวัญพิเศษ',
        'description' => 'รับของขวัญพิเศษจากร้าน (จำนวนจำกัด)',
        'points_required' => 1500,
        'reward_type' => 'gift',
        'reward_value' => 0,
        'stock' => 10,
        'max_per_user' => 1,
        'is_active' => 1
    ]
];

echo "Creating " . count($sampleRewards) . " sample rewards...\n\n";

$successCount = 0;
$failCount = 0;

foreach ($sampleRewards as $reward) {
    try {
        $rewardId = $loyalty->createReward($reward);
        echo "✅ Created: {$reward['name']} (ID: $rewardId, Points: {$reward['points_required']}, Stock: {$reward['stock']})\n";
        $successCount++;
    } catch (Exception $e) {
        echo "❌ Failed to create {$reward['name']}: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n=== Summary ===\n";
echo "✅ Success: $successCount\n";
echo "❌ Failed: $failCount\n";

// Show all rewards for this account
echo "\n=== All Rewards for Account $lineAccountId ===\n";
$allRewards = $loyalty->getActiveRewards();
echo "Total active rewards: " . count($allRewards) . "\n\n";

foreach ($allRewards as $reward) {
    echo sprintf(
        "ID: %3d | %-30s | %5d pts | Stock: %s | Active: %s\n",
        $reward['id'],
        mb_substr($reward['name'], 0, 30),
        $reward['points_required'],
        $reward['stock'] == -1 ? 'Unlimited' : str_pad($reward['stock'], 3, ' ', STR_PAD_LEFT),
        $reward['is_active'] ? 'Yes' : 'No'
    );
}

echo "\n=== Done ===\n";
echo "\nYou can now test at:\n";
echo "https://cny.re-ya.com/liff/debug-rewards.html\n";
echo "or\n";
echo "https://cny.re-ya.com/liff/#/redeem\n";
