<?php
/**
 * Fix transactions with NULL line_account_id
 * แก้ไขปัญหา orders ไม่แสดงในหลังบ้าน
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>🔧 Fix Transaction Bot ID</h2>";
echo "<style>
    body { font-family: sans-serif; padding: 20px; }
    .ok { color: green; }
    .warn { color: orange; }
    .box { background: #f0f9ff; padding: 15px; margin: 10px 0; border-radius: 8px; border: 1px solid #0ea5e9; }
</style>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get default line_account_id
    $stmt = $db->query("SELECT id FROM line_accounts WHERE is_default = 1 LIMIT 1");
    $defaultBot = $stmt->fetch();
    
    if (!$defaultBot) {
        $stmt = $db->query("SELECT id FROM line_accounts WHERE is_active = 1 ORDER BY id LIMIT 1");
        $defaultBot = $stmt->fetch();
    }
    
    $defaultBotId = $defaultBot['id'] ?? 1;
    echo "<p>Default Bot ID: <strong>{$defaultBotId}</strong></p>";
    
    // Count transactions with NULL line_account_id
    $stmt = $db->query("SELECT COUNT(*) FROM transactions WHERE line_account_id IS NULL");
    $nullCount = $stmt->fetchColumn();
    
    echo "<p>Transactions with NULL line_account_id: <strong>{$nullCount}</strong></p>";
    
    if ($nullCount > 0) {
        // Show affected transactions
        echo "<h3>Affected Transactions:</h3>";
        $stmt = $db->query("SELECT id, order_number, user_id, status, grand_total, created_at 
                           FROM transactions WHERE line_account_id IS NULL ORDER BY id DESC");
        $txns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Order#</th><th>User</th><th>Status</th><th>Total</th><th>Created</th></tr>";
        foreach ($txns as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['order_number']}</td>";
            echo "<td>{$t['user_id']}</td>";
            echo "<td>{$t['status']}</td>";
            echo "<td>฿" . number_format($t['grand_total'], 2) . "</td>";
            echo "<td>{$t['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Apply fix
        if (isset($_GET['fix'])) {
            echo "<h3>🔧 Applying Fix...</h3>";
            
            $stmt = $db->prepare("UPDATE transactions SET line_account_id = ? WHERE line_account_id IS NULL");
            $stmt->execute([$defaultBotId]);
            $affected = $stmt->rowCount();
            
            echo "<p class='ok'>✅ Fixed {$affected} transactions - set line_account_id to {$defaultBotId}</p>";
            echo "<div class='box'>";
            echo "<p><strong>✅ เสร็จสิ้น!</strong> ตอนนี้ orders ควรจะแสดงในหลังบ้านแล้ว</p>";
            echo "<p><a href='shop/orders.php'>→ ไปหน้า Orders</a></p>";
            echo "</div>";
        } else {
            echo "<div class='box'>";
            echo "<p>คลิกปุ่มด้านล่างเพื่อแก้ไข:</p>";
            echo "<a href='?fix=1' style='display:inline-block;padding:12px 24px;background:#10b981;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>✅ Fix Now</a>";
            echo "</div>";
        }
    } else {
        echo "<p class='ok'>✅ ไม่มี transactions ที่ต้องแก้ไข</p>";
        echo "<p><a href='shop/orders.php'>→ ไปหน้า Orders</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
