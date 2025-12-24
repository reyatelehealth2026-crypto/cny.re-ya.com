<?php
/**
 * CNY Sync Dashboard
 * Dashboard สำหรับ monitor และจัดการ sync queue
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/SyncQueue.php';

$db = Database::getInstance()->getConnection();
$queue = new SyncQueue($db);

// Get stats
$stats = $queue->getStats();
$logs = $queue->getRecentLogs(30);

// Calculate progress
$total = $stats['total'] ?: 1;
$done = $stats['completed'] + $stats['failed'] + $stats['skipped'];
$progress = round(($done / $total) * 100);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 CNY Sync Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 { color: white; font-size: 28px; }
        h2 { color: #2d3748; font-size: 18px; margin-bottom: 16px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-number { font-size: 32px; font-weight: bold; margin-bottom: 8px; }
        .stat-label { font-size: 13px; opacity: 0.9; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            margin: 4px;
        }
        .btn:hover { opacity: 0.9; }
        .btn-success { background: #10B981; }
        .btn-warning { background: #F59E0B; }
        .btn-danger { background: #EF4444; }
        .btn-secondary { background: #718096; }
        .progress-container {
            background: #e2e8f0;
            border-radius: 10px;
            height: 24px;
            overflow: hidden;
            margin: 16px 0;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            transition: width 0.5s;
        }
        .log-container {
            background: #1a202c;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .log-line { margin-bottom: 4px; line-height: 1.5; }
        .log-success { color: #68d391; }
        .log-error { color: #fc8181; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; color: #4a5568; font-weight: 600; }
        input, select { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 CNY Sync Dashboard</h1>
            <button class="btn btn-secondary" onclick="location.reload()">🔄 Refresh</button>
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Total Jobs</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?= number_format($stats['pending']) ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= number_format($stats['processing']) ?></div>
                <div class="stat-label">Processing</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?= number_format($stats['completed']) ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                <div class="stat-number"><?= number_format($stats['failed']) ?></div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="card">
            <h2>📊 Overall Progress</h2>
            <div class="progress-container">
                <div class="progress-bar" style="width: <?= $progress ?>%"><?= $progress ?>%</div>
            </div>
            <p style="color: #718096; font-size: 14px;">
                <?= number_format($done) ?> of <?= number_format($stats['total']) ?> jobs processed
                (<?= number_format($stats['failed']) ?> failed, <?= number_format($stats['skipped']) ?> skipped)
            </p>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <div class="action-buttons">
                <button class="btn btn-success" onclick="createBatch()">➕ Create Batch from API</button>
                <a href="sync-worker-run.php?batch_size=5" class="btn" target="_blank">▶️ Run Worker (5)</a>
                <a href="sync-worker-run.php?batch_size=10" class="btn" target="_blank">▶️ Run Worker (10)</a>
                <a href="sync_categories_from_manufacturer.php" class="btn" style="background:#8b5cf6;color:white;">🏷️ สร้างหมวดหมู่จากผู้ผลิต</a>
                <button class="btn btn-warning" onclick="clearFailed()">🗑️ Clear Failed</button>
                <button class="btn btn-danger" onclick="clearAll()">🗑️ Clear All</button>
            </div>
            
            <h2 style="margin-top:20px;">📦 Direct Sync (ไม่ผ่าน Queue)</h2>
            <div class="action-buttons">
                <a href="sync_products_with_sku_id.php" class="btn" style="background:#059669;color:white;" target="_blank">🔄 Sync with SKU</a>
                <a href="sync_cny_with_id.php" class="btn" style="background:#0891b2;color:white;" target="_blank">🔄 Sync with CNY ID</a>
                <a href="sync_update_categories.php" class="btn" style="background:#7c3aed;color:white;" target="_blank">📁 Sync Categories</a>
            </div>
        </div>
        
        <!-- Create Batch Form -->
        <div class="card hidden" id="batchForm">
            <h2>➕ Create New Batch</h2>
            <form action="api/sync_queue.php" method="POST">
                <input type="hidden" name="action" value="create_batch">
                <div class="form-group">
                    <label>Batch Name</label>
                    <input type="text" name="batch_name" value="Sync <?= date('Y-m-d H:i') ?>" required>
                </div>
                <div class="form-group">
                    <label>Priority (1=highest, 10=lowest)</label>
                    <input type="number" name="priority" value="5" min="1" max="10">
                </div>
                <button type="submit" class="btn btn-success">Create Batch</button>
                <button type="button" class="btn btn-secondary" onclick="hideBatchForm()">Cancel</button>
            </form>
        </div>
        
        <!-- Recent Logs -->
        <div class="card">
            <h2>📝 Recent Activity</h2>
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <div class="log-line">No logs yet</div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-line <?= $log['action'] === 'failed' ? 'log-error' : ($log['action'] === 'created' ? 'log-success' : '') ?>">
                            [<?= $log['created_at'] ?>] 
                            <?= $log['action'] === 'created' ? '✓' : ($log['action'] === 'failed' ? '✗' : '○') ?>
                            <?= htmlspecialchars($log['sku']) ?> - <?= $log['action'] ?> 
                            (<?= $log['duration_ms'] ?>ms)
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function createBatch() {
            document.getElementById('batchForm').classList.remove('hidden');
        }
        
        function hideBatchForm() {
            document.getElementById('batchForm').classList.add('hidden');
        }
        
        function clearFailed() {
            if (confirm('Clear all failed jobs?')) {
                fetch('api/sync_queue.php?action=clear_failed', { method: 'POST' })
                    .then(r => r.json())
                    .then(d => { alert(d.message || 'Done'); location.reload(); });
            }
        }
        
        function clearAll() {
            if (confirm('Clear ALL jobs from queue? This cannot be undone!')) {
                fetch('api/sync_queue.php?action=clear_all', { method: 'POST' })
                    .then(r => r.json())
                    .then(d => { alert(d.message || 'Done'); location.reload(); });
            }
        }
        
        // Auto refresh every 10 seconds
        setTimeout(() => location.reload(), 10000);
    </script>
</body>
</html>
