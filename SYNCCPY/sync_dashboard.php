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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
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
        
        h1 {
            color: white;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        h2 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .btn-success { background: #38ef7d; }
        .btn-warning { background: #f5576c; }
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
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-error { background: #fed7d7; color: #742a2a; }
        .alert-info { background: #bee3f8; color: #2c5282; }
        
        .log-container {
            background: #1a202c;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .log-line {
            margin-bottom: 4px;
            line-height: 1.6;
        }
        
        .log-success { color: #68d391; }
        .log-error { color: #fc8181; }
        .log-warning { color: #f6ad55; }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: #fef5e7; color: #f59e0b; }
        .status-processing { background: #dbeafe; color: #3b82f6; }
        .status-completed { background: #d1fae5; color: #10b981; }
        .status-failed { background: #fee2e2; color: #ef4444; }
        
        #auto-refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 14px;
            display: none;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>🔄</span>
                CNY Sync Dashboard
            </h1>
            <button class="btn btn-secondary" onclick="location.reload()">🔄 Refresh</button>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats" id="stats-container">
            <div class="stat-card">
                <div class="stat-number" id="stat-total">-</div>
                <div class="stat-label">Total Jobs</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number" id="stat-pending">-</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number" id="stat-processing">-</div>
                <div class="stat-label">Processing</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number" id="stat-completed">-</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="card">
            <h2>📊 Overall Progress</h2>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar" style="width: 0%">0%</div>
            </div>
            <div id="progress-details" style="color: #718096; font-size: 14px; margin-top: 8px;">
                Calculating...
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <div class="action-buttons">
                <button class="btn btn-success" onclick="startSync()">▶️ Start Worker</button>
                <button class="btn btn-warning" onclick="stopSync()">⏸️ Stop Worker</button>
                <button class="btn" onclick="showCreateBatch()">➕ Create Batch</button>
                <button class="btn btn-secondary" onclick="clearQueue()">🗑️ Clear Queue</button>
                <button class="btn btn-secondary" onclick="viewLogs()">📋 View Logs</button>
            </div>
        </div>
        
        <!-- Create Batch Form -->
        <div class="card" id="create-batch-form" style="display: none;">
            <h2>➕ Create New Batch</h2>
            <form onsubmit="createBatch(event)">
                <div class="form-group">
                    <label>Batch Name</label>
                    <input type="text" id="batch-name" placeholder="e.g., Full Sync 2024-01-15" required>
                </div>
                <div class="form-group">
                    <label>SKU Source</label>
                    <select id="sku-source" onchange="toggleSkuInput()">
                        <option value="api">Fetch from API</option>
                        <option value="manual">Manual Input</option>
                    </select>
                </div>
                <div class="form-group" id="manual-sku-input" style="display: none;">
                    <label>SKUs (one per line)</label>
                    <textarea id="sku-list" rows="10" placeholder="0001&#10;0002&#10;0003"></textarea>
                </div>
                <div class="form-group">
                    <label>Priority (1 = Highest, 10 = Lowest)</label>
                    <input type="number" id="batch-priority" min="1" max="10" value="5">
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Create Batch</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateBatch()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Recent Logs -->
        <div class="card">
            <h2>📝 Recent Activity</h2>
            <div class="log-container" id="log-container">
                <div class="log-line">Loading...</div>
            </div>
        </div>
    </div>
    
    <div id="auto-refresh-indicator">
        <div class="spinner"></div>
        <span style="margin-left: 8px;">Auto-refreshing...</span>
    </div>
    
    <script>
        // API endpoint (ปรับตาม path จริง)
        const API_URL = 'sync_api.php';
        
        // Auto-refresh every 5 seconds
        let autoRefreshInterval = null;
        let isRefreshing = false;
        
        function startAutoRefresh() {
            if (autoRefreshInterval) return;
            
            autoRefreshInterval = setInterval(() => {
                refreshStats();
                refreshLogs();
            }, 5000);
        }
        
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }
        
        // Fetch stats
        async function refreshStats() {
            if (isRefreshing) return;
            isRefreshing = true;
            
            try {
                const response = await fetch(`${API_URL}?action=stats`);
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.stats;
                    
                    // Update stat cards
                    document.getElementById('stat-total').textContent = stats.total || 0;
                    document.getElementById('stat-pending').textContent = stats.pending || 0;
                    document.getElementById('stat-processing').textContent = stats.processing || 0;
                    document.getElementById('stat-completed').textContent = stats.completed || 0;
                    
                    // Calculate progress
                    const total = stats.total || 0;
                    const done = (stats.completed || 0) + (stats.failed || 0) + (stats.skipped || 0);
                    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
                    
                    const progressBar = document.getElementById('progress-bar');
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    
                    document.getElementById('progress-details').textContent = 
                        `${done} of ${total} jobs processed (${stats.failed || 0} failed, ${stats.skipped || 0} skipped)`;
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            } finally {
                isRefreshing = false;
            }
        }
        
        // Fetch logs
        async function refreshLogs() {
            try {
                const response = await fetch(`${API_URL}?action=recent_logs&limit=50`);
                const data = await response.json();
                
                if (data.success && data.logs) {
                    const logContainer = document.getElementById('log-container');
                    logContainer.innerHTML = data.logs.map(log => {
                        const className = log.action === 'failed' ? 'log-error' : 
                                        log.action === 'created' ? 'log-success' : '';
                        const icon = log.action === 'created' ? '✓' : 
                                   log.action === 'updated' ? '⟳' : 
                                   log.action === 'failed' ? '✗' : '○';
                        
                        return `<div class="log-line ${className}">[${log.created_at}] ${icon} ${log.sku} - ${log.action} (${log.duration_ms}ms)</div>`;
                    }).join('');
                }
            } catch (error) {
                console.error('Error fetching logs:', error);
            }
        }
        
        // Start sync worker
        async function startSync() {
            if (!confirm('Start sync worker? This will begin processing pending jobs.')) return;
            
            try {
                const response = await fetch(`${API_URL}?action=start_worker`, {
                    method: 'POST'
                });
                const data = await response.json();
                
                alert(data.message || 'Worker started');
                refreshStats();
            } catch (error) {
                alert('Error starting worker: ' + error.message);
            }
        }
        
        // Stop sync worker
        async function stopSync() {
            alert('Worker will stop after completing current job');
        }
        
        // Show create batch form
        function showCreateBatch() {
            document.getElementById('create-batch-form').style.display = 'block';
        }
        
        // Hide create batch form
        function hideCreateBatch() {
            document.getElementById('create-batch-form').style.display = 'none';
        }
        
        // Toggle SKU input
        function toggleSkuInput() {
            const source = document.getElementById('sku-source').value;
            document.getElementById('manual-sku-input').style.display = 
                source === 'manual' ? 'block' : 'none';
        }
        
        // Create batch
        async function createBatch(event) {
            event.preventDefault();
            
            const name = document.getElementById('batch-name').value;
            const source = document.getElementById('sku-source').value;
            const priority = document.getElementById('batch-priority').value;
            
            let skus = [];
            if (source === 'manual') {
                const skuText = document.getElementById('sku-list').value;
                skus = skuText.split('\n').map(s => s.trim()).filter(s => s);
            }
            
            try {
                const response = await fetch(`${API_URL}?action=create_batch`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, source, priority, skus })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Batch created with ${data.jobs_added} jobs`);
                    hideCreateBatch();
                    refreshStats();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error creating batch: ' + error.message);
            }
        }
        
        // Clear queue
        async function clearQueue() {
            if (!confirm('Clear all failed jobs from queue?')) return;
            
            try {
                const response = await fetch(`${API_URL}?action=clear_queue`, {
                    method: 'POST'
                });
                const data = await response.json();
                
                alert(data.message || 'Queue cleared');
                refreshStats();
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        // View logs
        function viewLogs() {
            window.open('sync_logs.php', '_blank');
        }
        
        // Initialize
        refreshStats();
        refreshLogs();
        startAutoRefresh();
    </script>
</body>
</html>
