<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sample Rewards</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        button {
            background: #6366f1;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #4f46e5;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            color: #166534;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #dc2626;
            color: #991b1b;
        }
        .info {
            background: #e0f2fe;
            border: 1px solid #0284c7;
            color: #075985;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎁 Create Sample Rewards</h1>
        
        <form method="POST">
            <div class="form-group">
                <label>LINE Account ID:</label>
                <input type="number" name="line_account_id" value="3" required>
            </div>
            
            <div class="form-group">
                <label>Action:</label>
                <select name="action" required>
                    <option value="check">Check Existing Rewards</option>
                    <option value="create">Create Sample Rewards</option>
                    <option value="delete_all">Delete All Rewards (Dangerous!)</option>
                </select>
            </div>
            
            <button type="submit">Execute</button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../config/config.php';
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../classes/LoyaltyPoints.php';
            
            $db = Database::getInstance()->getConnection();
            $lineAccountId = (int)$_POST['line_account_id'];
            $action = $_POST['action'];
            
            echo '<div class="result">';
            
            try {
                $loyalty = new LoyaltyPoints($db, $lineAccountId);
                
                if ($action === 'check') {
                    echo '<div class="info">';
                    echo "=== Checking Rewards for Account ID: $lineAccountId ===\n\n";
                    
                    $rewards = $loyalty->getActiveRewards();
                    echo "Found " . count($rewards) . " active rewards:\n\n";
                    
                    if (count($rewards) > 0) {
                        foreach ($rewards as $reward) {
                            echo sprintf(
                                "ID: %3d | %-30s | %5d pts | Stock: %s\n",
                                $reward['id'],
                                mb_substr($reward['name'], 0, 30),
                                $reward['points_required'],
                                $reward['stock'] == -1 ? 'Unlimited' : $reward['stock']
                            );
                        }
                    } else {
                        echo "⚠️  No rewards found. Use 'Create Sample Rewards' to add some.\n";
                    }
                    echo '</div>';
                    
                } elseif ($action === 'create') {
                    echo '<div class="success">';
                    echo "=== Creating Sample Rewards for Account ID: $lineAccountId ===\n\n";
                    
                    $sampleRewards = [
                        [
                            'name' => 'ส่วนลด 50 บาท',
                            'description' => 'รับส่วนลด 50 บาท สำหรับการซื้อครั้งถัดไป',
                            'points_required' => 500,
                            'reward_type' => 'discount',
                            'reward_value' => 50,
                            'stock' => -1,
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
                    
                    $successCount = 0;
                    $failCount = 0;
                    
                    foreach ($sampleRewards as $reward) {
                        try {
                            $rewardId = $loyalty->createReward($reward);
                            echo "✅ Created: {$reward['name']} (ID: $rewardId)\n";
                            $successCount++;
                        } catch (Exception $e) {
                            echo "❌ Failed: {$reward['name']} - " . $e->getMessage() . "\n";
                            $failCount++;
                        }
                    }
                    
                    echo "\n=== Summary ===\n";
                    echo "✅ Success: $successCount\n";
                    echo "❌ Failed: $failCount\n";
                    
                    // Show all rewards
                    echo "\n=== All Active Rewards ===\n";
                    $allRewards = $loyalty->getActiveRewards();
                    echo "Total: " . count($allRewards) . "\n\n";
                    
                    foreach ($allRewards as $reward) {
                        echo sprintf(
                            "ID: %3d | %-30s | %5d pts | Stock: %s\n",
                            $reward['id'],
                            mb_substr($reward['name'], 0, 30),
                            $reward['points_required'],
                            $reward['stock'] == -1 ? 'Unlimited' : $reward['stock']
                        );
                    }
                    
                    echo "\n✅ Done! You can now test at:\n";
                    echo "https://cny.re-ya.com/liff/debug-rewards.html\n";
                    echo "or\n";
                    echo "https://cny.re-ya.com/liff/#/redeem\n";
                    echo '</div>';
                    
                } elseif ($action === 'delete_all') {
                    echo '<div class="error">';
                    echo "=== Deleting All Rewards for Account ID: $lineAccountId ===\n\n";
                    
                    $stmt = $db->prepare("DELETE FROM rewards WHERE line_account_id = ?");
                    $stmt->execute([$lineAccountId]);
                    $deleted = $stmt->rowCount();
                    
                    echo "🗑️  Deleted $deleted rewards\n";
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "\nStack trace:\n" . $e->getTraceAsString();
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
