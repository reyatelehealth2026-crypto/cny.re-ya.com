<?php
/**
 * Debug Polling - ตรวจสอบว่า polling ทำงานหรือไม่
 */
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$currentBotId = $_SESSION['current_bot_id'] ?? 1;

// Get first user with messages
$stmt = $db->prepare("SELECT u.id, u.display_name, 
    (SELECT MAX(id) FROM messages WHERE user_id = u.id) as last_msg_id,
    (SELECT COUNT(*) FROM messages WHERE user_id = u.id) as msg_count
    FROM users u WHERE u.line_account_id = ? 
    HAVING msg_count > 0
    ORDER BY last_msg_id DESC LIMIT 1");
$stmt->execute([$currentBotId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userId = $user['id'] ?? 0;
$lastMsgId = $user['last_msg_id'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Debug Polling</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 900px; margin: 0 auto; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .success { border-left: 4px solid #10B981; }
        .error { border-left: 4px solid #EF4444; }
        .warning { border-left: 4px solid #F59E0B; }
        .info { border-left: 4px solid #3B82F6; }
        #logs { max-height: 400px; overflow-y: auto; background: #1E293B; color: #E2E8F0; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; }
        .log-entry { margin: 5px 0; padding: 5px; border-radius: 4px; }
        .log-success { background: rgba(16, 185, 129, 0.2); }
        .log-error { background: rgba(239, 68, 68, 0.2); }
        .log-info { background: rgba(59, 130, 246, 0.2); }
        button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-primary { background: #10B981; color: white; }
        .btn-danger { background: #EF4444; color: white; }
        .btn-secondary { background: #64748B; color: white; }
        #status { padding: 15px; border-radius: 8px; font-weight: bold; text-align: center; }
        .status-polling { background: #FEF3C7; color: #92400E; }
        .status-connected { background: #D1FAE5; color: #065F46; }
        .status-error { background: #FEE2E2; color: #991B1B; }
        .status-stopped { background: #E2E8F0; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #F8FAFC; }
    </style>
</head>
<body>
    <h1>🔄 Debug Polling System</h1>
    
    <div class="card info">
        <h3>📊 ข้อมูลระบบ</h3>
        <table>
            <tr><td>Bot ID</td><td><strong><?= $currentBotId ?></strong></td></tr>
            <tr><td>User</td><td><strong><?= htmlspecialchars($user['display_name'] ?? 'N/A') ?></strong> (ID: <?= $userId ?>)</td></tr>
            <tr><td>Last Message ID</td><td><strong><?= $lastMsgId ?></strong></td></tr>
            <tr><td>Total Messages</td><td><strong><?= $user['msg_count'] ?? 0 ?></strong></td></tr>
            <tr><td>Session ID</td><td><code><?= session_id() ?></code></td></tr>
            <tr><td>Timezone</td><td><?= date_default_timezone_get() ?> (<?= date('Y-m-d H:i:s') ?>)</td></tr>
        </table>
    </div>
    
    <div class="card">
        <h3>🎮 Controls</h3>
        <button class="btn-primary" onclick="startPolling()">▶️ Start Polling</button>
        <button class="btn-danger" onclick="stopPolling()">⏹️ Stop</button>
        <button class="btn-secondary" onclick="testOnce()">🔍 Test Once</button>
        <button class="btn-secondary" onclick="clearLogs()">🗑️ Clear Logs</button>
        <button class="btn-secondary" onclick="insertTestMessage()">📝 Insert Test Message</button>
    </div>
    
    <div id="status" class="status-stopped">⏸️ Stopped</div>
    
    <div class="card">
        <h3>📋 Logs</h3>
        <div id="logs"></div>
    </div>
    
    <div class="card">
        <h3>🔗 Links</h3>
        <p>
            <a href="inbox">← Inbox</a> | 
            <a href="fix_sent_by_now.php">🔧 Fix sent_by</a> | 
            <a href="api/messages.php?action=poll&user_id=<?= $userId ?>&last_id=<?= $lastMsgId ?>" target="_blank">📡 Raw API</a>
        </p>
    </div>

<script>
const userId = <?= $userId ?>;
let lastId = <?= $lastMsgId ?>;
let interval = null;
let pollCount = 0;

function log(msg, type = 'info') {
    const logs = document.getElementById('logs');
    const entry = document.createElement('div');
    entry.className = 'log-entry log-' + type;
    const time = new Date().toLocaleTimeString('th-TH');
    entry.innerHTML = `<span style="color:#94A3B8">[${time}]</span> ${msg}`;
    logs.insertBefore(entry, logs.firstChild);
    
    // Keep only last 100 logs
    while (logs.children.length > 100) {
        logs.removeChild(logs.lastChild);
    }
}

function setStatus(text, cls) {
    const el = document.getElementById('status');
    el.textContent = text;
    el.className = 'status-' + cls;
}

async function poll() {
    pollCount++;
    setStatus(`🔄 Polling #${pollCount}...`, 'polling');
    
    const url = `api/messages.php?action=poll&user_id=${userId}&last_id=${lastId}&_t=${Date.now()}`;
    log(`📡 Fetching: <code>${url}</code>`, 'info');
    
    try {
        const startTime = Date.now();
        const res = await fetch(url);
        const elapsed = Date.now() - startTime;
        
        log(`⏱️ Response: HTTP ${res.status} (${elapsed}ms)`, res.ok ? 'success' : 'error');
        
        const text = await res.text();
        
        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            log(`❌ Invalid JSON: ${text.substring(0, 200)}`, 'error');
            setStatus('❌ Invalid Response', 'error');
            return;
        }
        
        if (data.success) {
            setStatus(`✅ Connected - Poll #${pollCount}`, 'connected');
            
            if (data.messages && data.messages.length > 0) {
                log(`📨 <strong>NEW MESSAGES: ${data.messages.length}</strong>`, 'success');
                data.messages.forEach(m => {
                    log(`&nbsp;&nbsp;→ [${m.id}] ${m.direction}: ${(m.content || '').substring(0, 50)}...`, 'success');
                    if (parseInt(m.id) > lastId) {
                        lastId = parseInt(m.id);
                    }
                });
            } else {
                log(`✓ No new messages`, 'info');
            }
            
            if (data.unread_users && data.unread_users.length > 0) {
                log(`📬 Unread users: ${data.unread_users.map(u => u.id).join(', ')}`, 'info');
            }
        } else {
            log(`❌ API Error: ${data.error}`, 'error');
            setStatus('❌ API Error', 'error');
        }
    } catch (err) {
        log(`❌ Network Error: ${err.message}`, 'error');
        setStatus('❌ Network Error', 'error');
    }
}

function startPolling() {
    if (interval) return;
    log('🟢 Starting polling (every 2s)...', 'success');
    poll();
    interval = setInterval(poll, 2000);
}

function stopPolling() {
    if (interval) {
        clearInterval(interval);
        interval = null;
        setStatus('⏸️ Stopped', 'stopped');
        log('🔴 Polling stopped', 'info');
    }
}

function testOnce() {
    log('🔍 Testing single poll...', 'info');
    poll();
}

function clearLogs() {
    document.getElementById('logs').innerHTML = '';
}

async function insertTestMessage() {
    log('📝 Inserting test message...', 'info');
    
    try {
        const res = await fetch('api/messages.php?action=poll&user_id=<?= $userId ?>&last_id=0&_t=' + Date.now());
        const data = await res.json();
        
        if (data.success) {
            log('✅ API is working! Messages count: ' + (data.messages?.length || 0), 'success');
        } else {
            log('❌ API error: ' + data.error, 'error');
        }
    } catch (err) {
        log('❌ Error: ' + err.message, 'error');
    }
}

// Auto-start on load
log('📋 Page loaded. Click "Start Polling" to begin.', 'info');
log(`📊 User ID: ${userId}, Last Message ID: ${lastId}`, 'info');
</script>
</body>
</html>
