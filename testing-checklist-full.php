<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$pageTitle = "System Testing Checklist";
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/testing-checklist.css">

<div class="testing-container">
    <div class="test-header">
        <h1>🧪 System Testing Checklist</h1>
        <p>Comprehensive testing for Telepharmacy & E-commerce Platform</p>
        <div class="test-stats">
            <div class="stat-card">
                <span class="stat-number" id="total-tests">0</span>
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
        <button class="btn-primary" onclick="exportResults()">📥 Export JSON</button>
        <button class="btn-primary" onclick="printReport()">🖨️ Print Report</button>
    </div>

    <div id="categories-container"></div>
</div>

<script src="assets/js/testing-checklist-data.js"></script>
<script src="assets/js/testing-checklist.js"></script>

<?php include 'includes/footer.php'; ?>
