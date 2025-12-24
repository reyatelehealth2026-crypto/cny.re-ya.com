<?php
/**
 * Debug Member Flow - ทดสอบ flow การสมัครสมาชิก
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$lineAccountId = $_GET['account'] ?? 1;

echo "<h2>Debug Member Flow</h2>";

// Get test user
$lineUserId = $_GET['line_user_id'] ?? '';

if (empty($lineUserId)) {
    echo "<h3>Select a user to test:</h3>";
    $stmt = $db->query("SELECT id, line_user_id, display_name, first_name, is_registered, member_id FROM users ORDER BY id DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Display Name</th><th>is_registered</th><th>member_id</th><th>Action</th></tr>";
    foreach ($users as $u) {
        $lineId = htmlspecialchars($u['line_user_id']);
        $regStatus = $u['is_registered'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['display_name']}</td>";
        echo "<td>{$regStatus}</td>";
        echo "<td>{$u['member_id']}</td>";
        echo "<td><a href='?line_user_id={$lineId}&account={$lineAccountId}'>Test Flow</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

echo "<h3>Testing flow for: $lineUserId</h3>";

// 1. Test consent check
echo "<h4>1. Consent Check API</h4>";
$consentUrl = BASE_URL . "/api/consent.php?action=check&line_user_id=$lineUserId";
echo "<p>URL: <code>$consentUrl</code></p>";

$ch = curl_init($consentUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$consentData = json_decode($response, true);
echo "<pre>" . json_encode($consentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if ($consentData['all_consented'] ?? false) {
    echo "<p style='color:green'>✅ Consent OK - จะไปเช็ค member</p>";
} else {
    echo "<p style='color:orange'>⚠️ Consent NOT OK - จะ redirect ไป consent page</p>";
}

// 2. Test member check
echo "<h4>2. Member Check API</h4>";
$memberCheckUrl = BASE_URL . "/api/member.php?action=check&line_user_id=$lineUserId&line_account_id=$lineAccountId";
echo "<p>URL: <code>$memberCheckUrl</code></p>";

$ch = curl_init($memberCheckUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$memberCheckData = json_decode($response, true);
echo "<pre>" . json_encode($memberCheckData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if ($memberCheckData['is_registered'] ?? false) {
    echo "<p style='color:green'>✅ is_registered = true - จะไป member-card</p>";
} else {
    echo "<p style='color:orange'>⚠️ is_registered = false - จะ redirect ไป register page</p>";
}

// 3. Test member get_card
echo "<h4>3. Member Get Card API</h4>";
$memberCardUrl = BASE_URL . "/api/member.php?action=get_card&line_user_id=$lineUserId&line_account_id=$lineAccountId";
echo "<p>URL: <code>$memberCardUrl</code></p>";

$ch = curl_init($memberCardUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$memberCardData = json_decode($response, true);
echo "<pre>" . json_encode($memberCardData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if ($memberCardData['success'] ?? false) {
    echo "<p style='color:green'>✅ Get Card OK - จะแสดงบัตรสมาชิก</p>";
} else {
    echo "<p style='color:red'>❌ Get Card FAILED - จะแสดง 'ยังไม่ได้ลงทะเบียน'</p>";
}

// 4. Summary
echo "<h4>4. Summary - Expected Flow</h4>";
$consentOk = $consentData['all_consented'] ?? false;
$isRegistered = $memberCheckData['is_registered'] ?? false;
$cardOk = $memberCardData['success'] ?? false;

echo "<ol>";
echo "<li>เปิด member-card.php</li>";
if (!$consentOk) {
    echo "<li style='color:orange'>→ Consent ไม่ผ่าน → redirect ไป consent.php</li>";
    echo "<li>→ หลัง consent → เช็ค is_registered</li>";
}
if (!$isRegistered) {
    echo "<li style='color:orange'>→ is_registered = false → redirect ไป register.php</li>";
} else {
    echo "<li style='color:green'>→ is_registered = true → แสดงบัตรสมาชิก</li>";
}
echo "</ol>";

// 5. Quick fix
if (!$isRegistered && ($memberCheckData['exists'] ?? false)) {
    echo "<h4>5. Quick Fix</h4>";
    echo "<p>User exists but is_registered = 0. <a href='?line_user_id=$lineUserId&account=$lineAccountId&fix=1' style='color:blue'>Click to fix</a></p>";
    
    if (isset($_GET['fix'])) {
        $stmt = $db->prepare("UPDATE users SET is_registered = 1, registered_at = NOW() WHERE line_user_id = ?");
        $stmt->execute([$lineUserId]);
        echo "<p style='color:green'>✅ Fixed! Refresh to verify.</p>";
    }
}

echo "<hr><p><a href='?'>Back to user list</a></p>";
