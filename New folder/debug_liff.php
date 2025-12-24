<?php
/**
 * Debug LIFF ID column
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Handle actions first
if (isset($_POST['add_column'])) {
    try {
        $db->exec("ALTER TABLE line_accounts ADD COLUMN liff_id VARCHAR(50) DEFAULT NULL");
        header("Location: debug_liff.php?msg=column_added");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_POST['update_liff'])) {
    $accountId = $_POST['account_id'];
    $newLiffId = trim($_POST['new_liff_id']);
    try {
        $stmt = $db->prepare("UPDATE line_accounts SET liff_id = ? WHERE id = ?");
        $stmt->execute([$newLiffId ?: null, $accountId]);
        header("Location: debug_liff.php?msg=updated");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug LIFF ID</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        h2 { color: #06C755; }
        h3 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #06C755; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px; }
        .btn:hover { background: #05a648; }
        .btn-secondary { background: #6c757d; }
        input[type="text"] { padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        .highlight { background: #ffffcc !important; }
        .info-box { background: #e7f3ff; border: 1px solid #b6d4fe; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h2>🔧 Debug LIFF ID</h2>
    
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'updated'): ?>
            <div class="success">✅ LIFF ID อัพเดทเรียบร้อย!</div>
        <?php elseif ($_GET['msg'] === 'column_added'): ?>
            <div class="success">✅ เพิ่ม column liff_id เรียบร้อย!</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error">❌ Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="info-box">
        <strong>📌 วิธีสร้าง LIFF ID:</strong><br>
        1. ไปที่ <a href="https://developers.line.biz/console/" target="_blank">LINE Developers Console</a><br>
        2. เลือก Provider และ Channel ของคุณ<br>
        3. ไปที่ LIFF tab แล้วกด "Add"<br>
        4. ตั้งค่า Endpoint URL เป็น: <code><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/liff-checkout.php</code><br>
        5. เลือก Size: Full<br>
        6. Copy LIFF ID (เช่น 1234567890-abcdefgh) มาใส่ด้านล่าง
    </div>

    <h3>1. ตรวจสอบ Column liff_id</h3>
    <?php
    try {
        $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_id'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            echo '<div class="success">✅ Column liff_id มีอยู่แล้ว</div>';
        } else {
            echo '<div class="warning">⚠️ Column liff_id ยังไม่มี</div>';
            echo '<form method="post"><button type="submit" name="add_column" class="btn">เพิ่ม Column ตอนนี้</button></form>';
        }
    } catch (Exception $e) {
        echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <h3>2. LINE Accounts และ LIFF ID</h3>
    <?php
    try {
        // Check if liff_id column exists first
        $stmt = $db->query("SHOW COLUMNS FROM line_accounts LIKE 'liff_id'");
        $hasLiffId = $stmt->fetch() ? true : false;
        
        if ($hasLiffId) {
            $stmt = $db->query("SELECT id, name, liff_id, bot_mode FROM line_accounts ORDER BY id");
        } else {
            $stmt = $db->query("SELECT id, name, bot_mode FROM line_accounts ORDER BY id");
        }
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($accounts)) {
            echo '<div class="warning">ยังไม่มี LINE Account</div>';
        } else {
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>LIFF ID</th><th>Bot Mode</th><th>Action</th></tr>';
            foreach ($accounts as $a) {
                $liffId = $a['liff_id'] ?? '';
                $isEmpty = empty($liffId);
                $rowClass = $isEmpty ? '' : 'highlight';
                echo "<tr class='{$rowClass}'>";
                echo "<td>{$a['id']}</td>";
                echo "<td>{$a['name']}</td>";
                echo "<td>" . ($liffId ?: '<em style="color:#999">ยังไม่ได้ตั้งค่า</em>') . "</td>";
                echo "<td>{$a['bot_mode']}</td>";
                echo "<td>";
                if ($hasLiffId) {
                    echo "<form method='post' style='display:flex;gap:5px'>";
                    echo "<input type='hidden' name='account_id' value='{$a['id']}'>";
                    echo "<input type='text' name='new_liff_id' value='" . htmlspecialchars($liffId) . "' placeholder='เช่น 1234567890-abc' size='25'>";
                    echo "<button type='submit' name='update_liff' class='btn'>บันทึก</button>";
                    echo "</form>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <h3>3. ทดสอบ LIFF URL</h3>
    <?php
    try {
        if ($hasLiffId) {
            $stmt = $db->query("SELECT id, name, liff_id FROM line_accounts WHERE liff_id IS NOT NULL AND liff_id != '' LIMIT 1");
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($account) {
                $liffUrl = "https://liff.line.me/{$account['liff_id']}";
                echo "<p>✅ LIFF URL สำหรับ <strong>{$account['name']}</strong>:</p>";
                echo "<p><a href='{$liffUrl}' target='_blank' class='btn'>{$liffUrl}</a></p>";
                echo "<p><small>กดเพื่อทดสอบ (ต้องเปิดใน LINE App)</small></p>";
            } else {
                echo '<div class="warning">⚠️ ยังไม่มี LINE Account ที่ตั้งค่า LIFF ID</div>';
            }
        }
    } catch (Exception $e) {
        echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <br><br>
    <a href="line-accounts.php" class="btn btn-secondary">← กลับไปหน้า LINE Accounts</a>
</body>
</html>
