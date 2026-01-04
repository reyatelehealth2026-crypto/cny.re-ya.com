<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$pageTitle = "System Testing Checklist";
include 'includes/header.php';
?>

<style>
.testing-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.test-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.test-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(255,255,255,0.2);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    display: block;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.category-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.category-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.category-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    width: 150px;
    height: 8px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    transition: width 0.3s ease;
}

.test-case {
    background: #f9f9f9;
    border-left: 4px solid #ddd;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.test-case:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.test-case.status-passed {
    border-left-color: #4CAF50;
    background: #f1f8f4;
}

.test-case.status-failed {
    border-left-color: #f44336;
    background: #fef1f0;
}

.test-case.status-skipped {
    border-left-color: #FF9800;
    background: #fff8f0;
}

.test-case-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.test-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    flex: 1;
}

.test-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-pass {
    background: #4CAF50;
    color: white;
}

.btn-fail {
    background: #f44336;
    color: white;
}

.btn-skip {
    background: #FF9800;
    color: white;
}

.btn-reset {
    background: #9E9E9E;
    color: white;
}

.test-details {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
}

.test-steps {
    margin: 10px 0;
    padding-left: 20px;
}

.test-steps li {
    margin: 5px 0;
}

.test-expected {
    background: #e3f2fd;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.test-files {
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

.notes-section {
    margin-top: 15px;
}

.notes-textarea {
    width: 100%;
    min-height: 60px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    justify-content: flex-end;
}

.btn-primary {
    background: #667eea;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.collapse-btn {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    font-size: 14px;
    padding: 5px 10px;
}

.test-case-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.test-case-body.expanded {
    max-height: 1000px;
}
</style>

<div class="testing-container">
    <div class="test-header">
        <h1>🧪 System Testing Checklist</h1>
        <p>Comprehensive testing for Telepharmacy & E-commerce Platform</p>
        <div class="test-stats">
            <div class="stat-card">
                <span class="stat-number" id="total-tests">127</span>
                <span class="stat-label">Total Tests</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="passed-tests">0</span>
                <span class="stat-label">✅ Passed</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="failed-tests">0</span>
                <span class="stat-label">❌ Failed</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="skipped-tests">0</span>
                <span class="stat-label">⏭️ Skipped</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="completion-rate">0%</span>
                <span class="stat-label">Completion</span>
            </div>
        </div>
    </div>

    <div class="filter-section">
        <strong>Filter:</strong>
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="pending">Pending</button>
        <button class="filter-btn" data-filter="passed">Passed</button>
        <button class="filter-btn" data-filter="failed">Failed</button>
        <button class="filter-btn" data-filter="skipped">Skipped</button>
        <div style="flex: 1"></div>
        <button class="btn-primary" onclick="saveResults()">💾 Save Results</button>
        <button class="btn-primary" onclick="exportResults()">📥 Export</button>
    </div>


    <!-- Category 1: Authentication & Authorization -->
    <div class="category-section" data-category="auth">
        <div class="category-header">
            <div>
                <div class="category-title">🔐 1. Authentication & Authorization</div>
                <small style="color: #666;">5 test cases</small>
            </div>
            <div class="category-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <span class="progress-text">0/5</span>
            </div>
        </div>

        <div class="test-case" data-test-id="1.1">
            <div class="test-case-header">
                <div class="test-title">1.1: Admin Login - Valid Credentials</div>
                <div class="test-controls">
                    <button class="status-btn btn-pass" onclick="setStatus('1.1', 'passed')">✓ Pass</button>
                    <button class="status-btn btn-fail" onclick="setStatus('1.1', 'failed')">✗ Fail</button>
                    <button class="status-btn btn-skip" onclick="setStatus('1.1', 'skipped')">⏭ Skip</button>
                    <button class="collapse-btn" onclick="toggleDetails(this)">▼ Details</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="test-details">
                    <strong>Prerequisites:</strong> Valid admin account exists
                    <div class="test-steps">
                        <strong>Steps:</strong>
                        <ol>
                            <li>Navigate to /auth/login.php</li>
                            <li>Enter valid username and password</li>
                            <li>Click "Login" button</li>
                        </ol>
                    </div>
                    <div class="test-expected">
                        <strong>Expected Result:</strong> Redirect to dashboard, session created
                    </div>
                    <div class="test-files">
                        📁 Related Files: auth/login.php, classes/AdminAuth.php
                    </div>
                </div>
                <div class="notes-section">
                    <textarea class="notes-textarea" placeholder="Add notes or observations..."></textarea>
                </div>
            </div>
        </div>

        <div class="test-case" data-test-id="1.2">
            <div class="test-case-header">
                <div class="test-title">1.2: Admin Login - Invalid Credentials</div>
                <div class="test-controls">
                    <button class="status-btn btn-pass" onclick="setStatus('1.2', 'passed')">✓ Pass</button>
                    <button class="status-btn btn-fail" onclick="setStatus('1.2', 'failed')">✗ Fail</button>
                    <button class="status-btn btn-skip" onclick="setStatus('1.2', 'skipped')">⏭ Skip</button>
                    <button class="collapse-btn" onclick="toggleDetails(this)">▼ Details</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="test-details">
                    <strong>Prerequisites:</strong> None
                    <div class="test-steps">
                        <strong>Steps:</strong>
                        <ol>
                            <li>Navigate to /auth/login.php</li>
                            <li>Enter invalid credentials</li>
                            <li>Click "Login" button</li>
                        </ol>
                    </div>
                    <div class="test-expected">
                        <strong>Expected Result:</strong> Error message displayed, no session created
                    </div>
                    <div class="test-files">
                        📁 Related Files: auth/login.php
                    </div>
                </div>
                <div class="notes-section">
                    <textarea class="notes-textarea" placeholder="Add notes or observations..."></textarea>
                </div>
            </div>
        </div>

        <div class="test-case" data-test-id="1.3">
            <div class="test-case-header">
                <div class="test-title">1.3: LINE LIFF Authentication</div>
                <div class="test-controls">
                    <button class="status-btn btn-pass" onclick="setStatus('1.3', 'passed')">✓ Pass</button>
                    <button class="status-btn btn-fail" onclick="setStatus('1.3', 'failed')">✗ Fail</button>
                    <button class="status-btn btn-skip" onclick="setStatus('1.3', 'skipped')">⏭ Skip</button>
                    <button class="collapse-btn" onclick="toggleDetails(this)">▼ Details</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="test-details">
                    <strong>Prerequisites:</strong> LINE test account
                    <div class="test-steps">
                        <strong>Steps:</strong>
                        <ol>
                            <li>Open any LIFF app URL</li>
                            <li>Verify LINE login prompt</li>
                            <li>Complete LINE authentication</li>
                        </ol>
                    </div>
                    <div class="test-expected">
                        <strong>Expected Result:</strong> LIFF initialized, user profile loaded
                    </div>
                    <div class="test-files">
                        📁 Related Files: liff/index.php, assets/js/liff-init.js
                    </div>
                </div>
                <div class="notes-section">
                    <textarea class="notes-textarea" placeholder="Add notes or observations..."></textarea>
                </div>
            </div>
        </div>

        <div class="test-case" data-test-id="1.4">
            <div class="test-case-header">
                <div class="test-title">1.4: Unauthorized Access Prevention</div>
                <div class="test-controls">
                    <button class="status-btn btn-pass" onclick="setStatus('1.4', 'passed')">✓ Pass</button>
                    <button class="status-btn btn-fail" onclick="setStatus('1.4', 'failed')">✗ Fail</button>
                    <button class="status-btn btn-skip" onclick="setStatus('1.4', 'skipped')">⏭ Skip</button>
                    <button class="collapse-btn" onclick="toggleDetails(this)">▼ Details</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="test-details">
                    <strong>Prerequisites:</strong> Not logged in
                    <div class="test-steps">
                        <strong>Steps:</strong>
                        <ol>
                            <li>Navigate directly to /dashboard.php</li>
                            <li>Observe behavior</li>
                        </ol>
                    </div>
                    <div class="test-expected">
                        <strong>Expected Result:</strong> Redirect to login page
                    </div>
                    <div class="test-files">
                        📁 Related Files: includes/auth_check.php
                    </div>
                </div>
                <div class="notes-section">
                    <textarea class="notes-textarea" placeholder="Add notes or observations..."></textarea>
                </div>
            </div>
        </div>

        <div class="test-case" data-test-id="1.5">
            <div class="test-case-header">
                <div class="test-title">1.5: Pharmacist Role Access</div>
                <div class="test-controls">
                    <button class="status-btn btn-pass" onclick="setStatus('1.5', 'passed')">✓ Pass</button>
                    <button class="status-btn btn-fail" onclick="setStatus('1.5', 'failed')">✗ Fail</button>
                    <button class="status-btn btn-skip" onclick="setStatus('1.5', 'skipped')">⏭ Skip</button>
                    <button class="collapse-btn" onclick="toggleDetails(this)">▼ Details</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="test-details">
                    <strong>Prerequisites:</strong> Pharmacist account
                    <div class="test-steps">
                        <strong>Steps:</strong>
                        <ol>
                            <li>Login as pharmacist</li>
                            <li>Attempt to access admin-only pages</li>
                            <li>Verify pharmacist dashboard access</li>
                        </ol>
                    </div>
                    <div class="test-expected">
                        <strong>Expected Result:</strong> Admin pages blocked, pharmacist features accessible
                    </div>
                    <div class="test-files">
                        📁 Related Files: pharmacist-dashboard.php, classes/AdminAuth.php
                    </div>
                </div>
                <div class="notes-section">
                    <textarea class="notes-textarea" placeholder="Add notes or observations..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Add more categories here - I'll create a template structure -->
    <div id="additional-categories"></div>

</div>

<script>
// Test data structure
const testData = {
    '1.1': { status: 'pending', notes: '' },
    '1.2': { status: 'pending', notes: '' },
    '1.3': { status: 'pending', notes: '' },
    '1.4': { status: 'pending', notes: '' },
    '1.5': { status: 'pending', notes: '' }
};

// Load saved data from localStorage
function loadSavedData() {
    const saved = localStorage.getItem('testingChecklistData');
    if (saved) {
        Object.assign(testData, JSON.parse(saved));
        applyLoadedData();
    }
}

function applyLoadedData() {
    Object.keys(testData).forEach(testId => {
        const data = testData[testId];
        if (data.status !== 'pending') {
            const testCase = document.querySelector(`[data-test-id="${testId}"]`);
            if (testCase) {
                testCase.classList.add(`status-${data.status}`);
            }
        }
        if (data.notes) {
            const testCase = document.querySelector(`[data-test-id="${testId}"]`);
            if (testCase) {
                const textarea = testCase.querySelector('.notes-textarea');
                if (textarea) textarea.value = data.notes;
            }
        }
    });
    updateStats();
}

// Set test status
function setStatus(testId, status) {
    testData[testId].status = status;
    
    const testCase = document.querySelector(`[data-test-id="${testId}"]`);
    testCase.classList.remove('status-passed', 'status-failed', 'status-skipped');
    
    if (status !== 'pending') {
        testCase.classList.add(`status-${status}`);
    }
    
    updateStats();
    updateCategoryProgress(testCase.closest('.category-section'));
    saveToLocalStorage();
}

// Toggle test details
function toggleDetails(btn) {
    const testCase = btn.closest('.test-case');
    const body = testCase.querySelector('.test-case-body');
    
    if (body.classList.contains('expanded')) {
        body.classList.remove('expanded');
        btn.textContent = '▼ Details';
    } else {
        body.classList.add('expanded');
        btn.textContent = '▲ Hide';
    }
}

// Update statistics
function updateStats() {
    const total = Object.keys(testData).length;
    let passed = 0, failed = 0, skipped = 0;
    
    Object.values(testData).forEach(test => {
        if (test.status === 'passed') passed++;
        else if (test.status === 'failed') failed++;
        else if (test.status === 'skipped') skipped++;
    });
    
    const completed = passed + failed + skipped;
    const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    document.getElementById('total-tests').textContent = total;
    document.getElementById('passed-tests').textContent = passed;
    document.getElementById('failed-tests').textContent = failed;
    document.getElementById('skipped-tests').textContent = skipped;
    document.getElementById('completion-rate').textContent = completionRate + '%';
}

// Update category progress
function updateCategoryProgress(category) {
    const testCases = category.querySelectorAll('.test-case');
    const total = testCases.length;
    let completed = 0;
    
    testCases.forEach(testCase => {
        if (testCase.classList.contains('status-passed') || 
            testCase.classList.contains('status-failed') || 
            testCase.classList.contains('status-skipped')) {
            completed++;
        }
    });
    
    const percentage = total > 0 ? (completed / total) * 100 : 0;
    const progressFill = category.querySelector('.progress-fill');
    const progressText = category.querySelector('.progress-text');
    
    progressFill.style.width = percentage + '%';
    progressText.textContent = `${completed}/${total}`;
}

// Save to localStorage
function saveToLocalStorage() {
    // Save notes from textareas
    document.querySelectorAll('.test-case').forEach(testCase => {
        const testId = testCase.dataset.testId;
        const textarea = testCase.querySelector('.notes-textarea');
        if (textarea && testData[testId]) {
            testData[testId].notes = textarea.value;
        }
    });
    
    localStorage.setItem('testingChecklistData', JSON.stringify(testData));
}

// Save results to server
function saveResults() {
    saveToLocalStorage();
    
    // TODO: Send to server via AJAX
    fetch('api/testing-results.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            testData: testData,
            timestamp: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        alert('✅ Results saved successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('⚠️ Saved locally. Server save failed.');
    });
}

// Export results
function exportResults() {
    const results = {
        exportDate: new Date().toISOString(),
        testData: testData,
        summary: {
            total: Object.keys(testData).length,
            passed: Object.values(testData).filter(t => t.status === 'passed').length,
            failed: Object.values(testData).filter(t => t.status === 'failed').length,
            skipped: Object.values(testData).filter(t => t.status === 'skipped').length
        }
    };
    
    const blob = new Blob([JSON.stringify(results, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `testing-results-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
}

// Filter tests
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        document.querySelectorAll('.test-case').forEach(testCase => {
            if (filter === 'all') {
                testCase.style.display = 'block';
            } else if (filter === 'pending') {
                const hasStatus = testCase.classList.contains('status-passed') || 
                                testCase.classList.contains('status-failed') || 
                                testCase.classList.contains('status-skipped');
                testCase.style.display = hasStatus ? 'none' : 'block';
            } else {
                testCase.style.display = testCase.classList.contains(`status-${filter}`) ? 'block' : 'none';
            }
        });
    });
});

// Auto-save notes
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('notes-textarea')) {
        saveToLocalStorage();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadSavedData();
    updateStats();
    
    // Update all category progress bars
    document.querySelectorAll('.category-section').forEach(updateCategoryProgress);
});
</script>

<?php include 'includes/footer.php'; ?>
