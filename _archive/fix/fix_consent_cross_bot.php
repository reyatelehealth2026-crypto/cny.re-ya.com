<?php
/**
 * Fix Consent Cross Bot
 * แก้ไขปัญหา consent ถามซ้ำเมื่อผู้ใช้ติดต่อบอทหลายตัว
 * 
 * วิธีใช้: เข้า https://yourdomain.com/v1/fix_consent_cross_bot.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔧 Fix Consent Cross Bot</h1>";
echo "<style>body{font-family:sans-serif;max-width:900px;margin:20px auto;padding:20px;} 
.success{color:green;} .error{color:red;} .warning{color:orange;}
pre{background:#f5f5f5;padding:15px;border-radius:8px;overflow-x:auto;}
table{width:100%;border-collapse:collapse;margin:15px 0;} th,td{padding:10px;border:1px solid #ddd;text-align:left;}
th{background:#f0f0f0;}</style>";

// ========== 1. ตรวจสอบ columns ที่จำเป็น ==========
echo "<h2>1. ตรวจสอบ Columns</h2>";

$requiredColumns = [
    'consent_privacy' => 'TINYINT(1) DEFAULT 0',
    'consent_terms' => 'TINYINT(1) DEFAULT 0',
    'consent_at' => 'DATETIME DEFAULT NULL',
];

foreach ($requiredColumns as $col => $type) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE users ADD COLUMN $col $type");
            echo "<p class='success'>✓ เพิ่ม column <code>$col</code> สำเร็จ</p>";
        } else {
            echo "<p class='success'>✓ Column <code>$col</code> มีอยู่แล้ว</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

// ========== 2. ดูผู้ใช้ที่มี consent แล้ว ==========
echo "<h2>2. ผู้ใช้ที่มี Consent แล้ว</h2>";

try {
    $stmt = $db->query("
        SELECT line_user_id, COUNT(*) as bot_count, 
               GROUP_CONCAT(DISTINCT line_account_id) as bot_ids,
               MAX(consent_privacy) as has_privacy,
               MAX(consent_terms) as has_terms
        FROM users 
        WHERE line_user_id IS NOT NULL
        GROUP BY line_user_id
        HAVING bot_count > 1
        ORDER BY bot_count DESC
        LIMIT 20
    ");
    $multiUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($multiUsers) > 0) {
        echo "<p>พบผู้ใช้ที่ติดต่อหลายบอท: " . count($multiUsers) . " คน</p>";
        echo "<table><tr><th>LINE User ID</th><th>จำนวนบอท</th><th>Bot IDs</th><th>Privacy</th><th>Terms</th></tr>";
        foreach ($multiUsers as $u) {
            $privacy = $u['has_privacy'] ? '✓' : '✗';
            $terms = $u['has_terms'] ? '✓' : '✗';
            echo "<tr><td>" . substr($u['line_user_id'], 0, 20) . "...</td><td>{$u['bot_count']}</td><td>{$u['bot_ids']}</td><td>$privacy</td><td>$terms</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>ไม่พบผู้ใช้ที่ติดต่อหลายบอท</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// ========== 3. Sync Consent ข้ามบอท ==========
echo "<h2>3. Sync Consent ข้ามบอท</h2>";

if (isset($_POST['sync_consent'])) {
    try {
        // หาผู้ใช้ที่เคย consent แล้ว
        $stmt = $db->query("
            SELECT DISTINCT line_user_id 
            FROM users 
            WHERE consent_privacy = 1 AND consent_terms = 1 AND line_user_id IS NOT NULL
        ");
        $consentedUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updated = 0;
        foreach ($consentedUsers as $lineUserId) {
            // Update ทุก record ของ user นี้
            $stmt = $db->prepare("
                UPDATE users 
                SET consent_privacy = 1, consent_terms = 1, consent_at = COALESCE(consent_at, NOW())
                WHERE line_user_id = ? AND (consent_privacy = 0 OR consent_terms = 0)
            ");
            $stmt->execute([$lineUserId]);
            $updated += $stmt->rowCount();
        }
        
        echo "<p class='success'>✓ Sync สำเร็จ! อัพเดท $updated records</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<form method='POST'>";
echo "<button type='submit' name='sync_consent' style='padding:10px 20px;background:#10B981;color:white;border:none;border-radius:8px;cursor:pointer;font-size:16px;'>🔄 Sync Consent ข้ามบอท</button>";
echo "</form>";

// ========== 4. ตรวจสอบ user_consents table ==========
echo "<h2>4. ตรวจสอบ user_consents Table</h2>";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'user_consents'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Table user_consents มีอยู่แล้ว</p>";
        
        // ดูข้อมูล
        $stmt = $db->query("SELECT COUNT(*) FROM user_consents");
        $count = $stmt->fetchColumn();
        echo "<p>จำนวน records: $count</p>";
    } else {
        echo "<p class='warning'>⚠️ Table user_consents ไม่มี (ใช้ columns ใน users table แทน)</p>";
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ " . $e->getMessage() . "</p>";
}

// ========== 5. สรุปสถานะ Consent ==========
echo "<h2>5. สรุปสถานะ Consent</h2>";

try {
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN consent_privacy = 1 AND consent_terms = 1 THEN 1 ELSE 0 END) as consented,
            SUM(CASE WHEN consent_privacy = 0 OR consent_terms = 0 THEN 1 ELSE 0 END) as not_consented
        FROM users
        WHERE line_user_id IS NOT NULL
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><td>ผู้ใช้ทั้งหมด</td><td><strong>{$stats['total_users']}</strong></td></tr>";
    echo "<tr><td>Consent แล้ว</td><td class='success'><strong>{$stats['consented']}</strong></td></tr>";
    echo "<tr><td>ยังไม่ Consent</td><td class='warning'><strong>{$stats['not_consented']}</strong></td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// ========== 6. Force Consent สำหรับผู้ใช้เฉพาะ ==========
echo "<h2>6. Force Consent สำหรับผู้ใช้เฉพาะ</h2>";

if (isset($_POST['force_consent'])) {
    $lineUserId = trim($_POST['line_user_id'] ?? '');
    if ($lineUserId) {
        try {
            $stmt = $db->prepare("UPDATE users SET consent_privacy = 1, consent_terms = 1, consent_at = NOW() WHERE line_user_id = ?");
            $stmt->execute([$lineUserId]);
            $affected = $stmt->rowCount();
            echo "<p class='success'>✓ อัพเดท $affected records สำหรับ $lineUserId</p>";
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<form method='POST'>";
echo "<input type='text' name='line_user_id' placeholder='LINE User ID (Uxxxxxxxxx)' style='padding:10px;width:300px;border:1px solid #ddd;border-radius:4px;'>";
echo "<button type='submit' name='force_consent' style='padding:10px 20px;background:#3B82F6;color:white;border:none;border-radius:8px;cursor:pointer;margin-left:10px;'>✓ Force Consent</button>";
echo "</form>";

echo "<hr><p style='color:#888;'>หลังจากแก้ไขแล้ว กรุณา upload <code>webhook.php</code> ไป server ด้วย!</p>";
