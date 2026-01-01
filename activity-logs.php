<?php
/**
 * Activity Logs - ดู Log กิจกรรมทั้งหมดในระบบ
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/classes/ActivityLogger.php';

$db = Database::getInstance()->getConnection();
$logger = ActivityLogger::getInstance($db);

// Filters
$filters = [];
$filterType = $_GET['type'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

if ($filterType) $filters['type'] = $filterType;
if ($filterAction) $filters['action'] = $filterAction;
if ($filterSearch) $filters['search'] = $filterSearch;
if ($filterDateFrom) $filters['date_from'] = $filterDateFrom . ' 00:00:00';
if ($filterDateTo) $filters['date_to'] = $filterDateTo . ' 23:59:59';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$totalLogs = $logger->countLogs($filters);
$totalPages = ceil($totalLogs / $perPage);
$logs = $logger->getLogs($filters, $perPage, $offset);

// Log types for filter
$logTypes = [
    'auth' => 'เข้าสู่ระบบ',
    'user' => 'ผู้ใช้',
    'admin' => 'แอดมิน',
    'data' => 'ข้อมูล',
    'consent' => 'ความยินยอม',
    'message' => 'ข้อความ',
    'order' => 'คำสั่งซื้อ',
    'pharmacy' => 'เภสัชกรรม',
    'ai' => 'AI',
    'api' => 'API',
    'system' => 'ระบบ'
];

$actions = [
    'create' => 'สร้าง',
    'read' => 'ดู',
    'update' => 'แก้ไข',
    'delete' => 'ลบ',
    'login' => 'เข้าสู่ระบบ',
    'logout' => 'ออกจากระบบ',
    'export' => 'ส่งออก',
    'send' => 'ส่ง',
    'approve' => 'อนุมัติ',
    'reject' => 'ปฏิเสธ'
];

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-history me-2"></i>Activity Logs
        </h4>
        <span class="badge bg-secondary"><?= number_format($totalLogs) ?> รายการ</span>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">ประเภท</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($logTypes as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterType === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">การกระทำ</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($actions as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterAction === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">จากวันที่</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">ถึงวันที่</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ค้นหา</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="คำอธิบาย, ชื่อผู้ใช้..." value="<?= htmlspecialchars($filterSearch) ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="140">เวลา</th>
                        <th width="100">ประเภท</th>
                        <th width="80">การกระทำ</th>
                        <th>รายละเอียด</th>
                        <th width="120">ผู้ดำเนินการ</th>
                        <th width="100">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูล</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <?php
                            $typeColors = [
                                'auth' => 'primary',
                                'user' => 'info',
                                'admin' => 'warning',
                                'data' => 'secondary',
                                'consent' => 'success',
                                'message' => 'info',
                                'order' => 'success',
                                'pharmacy' => 'danger',
                                'ai' => 'purple',
                                'api' => 'dark',
                                'system' => 'secondary'
                            ];
                            $color = $typeColors[$log['log_type']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= $logTypes[$log['log_type']] ?? $log['log_type'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-outline-secondary text-dark border"><?= $actions[$log['action']] ?? $log['action'] ?></span>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($log['description']) ?></div>
                            <?php if ($log['entity_type']): ?>
                            <small class="text-muted"><?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?></small>
                            <?php endif; ?>
                            <?php if ($log['user_name']): ?>
                            <small class="text-info d-block">ลูกค้า: <?= htmlspecialchars($log['user_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['admin_name']): ?>
                            <span class="text-primary"><?= htmlspecialchars($log['admin_name']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
