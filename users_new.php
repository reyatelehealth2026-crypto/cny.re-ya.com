<?php
/**
 * Users Management - รายชื่อผู้ใช้/ลูกค้า
 * แสดงทุก follow = 1 customer
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Users';

require_once 'includes/header.php';

// Get filter parameters
$tagFilter = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get tag info if filtering
$currentTag = null;
if ($tagFilter) {
    try {
        $stmt = $db->prepare("SELECT * FROM user_tags WHERE id = ?");
        $stmt->execute([$tagFilter]);
        $currentTag = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Build query
$whereConditions = ["1=1"];
$params = [];

if ($tagFilter) {
    $whereConditions[] = "EXISTS (SELECT 1 FROM user_tag_assignments uta WHERE uta.user_id = u.id AND uta.tag_id = ?)";
    $params[] = $tagFilter;
}

if ($search) {
    $whereConditions[] = "(u.display_name LIKE ? OR u.line_user_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countSql = "SELECT COUNT(*) FROM users u WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
try {
    // Check if extra columns exist
    $hasExtraCols = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM users LIKE 'real_name'");
        $hasExtraCols = $checkStmt->fetch() !== false;
    } catch (Exception $e) {
        $hasExtraCols = false;
    }
    
    // Build SELECT clause based on available columns
    $selectCols = "u.id, u.line_user_id, u.display_name, u.picture_url, u.status_message, u.is_blocked, u.created_at, u.updated_at, u.line_account_id";
    if ($hasExtraCols) {
        $selectCols .= ", u.real_name, u.phone, u.email, u.birthday";
    }
    
    $sql = "SELECT {$selectCols},
            (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM user_tags t 
             JOIN user_tag_assignments uta ON t.id = uta.tag_id 
             WHERE uta.user_id = u.id) as tags,
            (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.id) as message_count,
            (SELECT MAX(created_at) FROM messages m WHERE m.user_id = u.id) as last_message_at
            FROM users u 
            WHERE {$whereClause}
            ORDER BY u.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add default values for missing columns
    if (!$hasExtraCols) {
        foreach ($users as &$user) {
            $user['real_name'] = null;
            $user['phone'] = null;
            $user['email'] = null;
            $user['birthday'] = null;
        }
        unset($user);
    }
} catch (Exception $e) {
    // Fallback query
    $sql = "SELECT u.* FROM users u WHERE {$whereClause} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as &$user) {
        $user['tags'] = null;
        $user['message_count'] = 0;
        $user['last_message_at'] = null;
        $user['real_name'] = null;
        $user['phone'] = null;
        $user['email'] = null;
        $user['birthday'] = null;
    }
    unset($user);
}

// Get all tags
$allTags = [];
try {
    $stmt = $db->prepare("SELECT * FROM user_tags WHERE line_account_id = ? OR line_account_id IS NULL ORDER BY name");
    $stmt->execute([$currentBotId]);
    $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign_tag') {
        $userId = (int)$_POST['user_id'];
        $tagId = (int)$_POST['tag_id'];
        try {
            $stmt = $db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by) VALUES (?, ?, 'manual')");
            $stmt->execute([$userId, $tagId]);
        } catch (Exception $e) {}
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if ($action === 'remove_tag') {
        $userId = (int)$_POST['user_id'];
        $tagId = (int)$_POST['tag_id'];
        try {
            $stmt = $db->prepare("DELETE FROM user_tag_assignments WHERE user_id = ? AND tag_id = ?");
            $stmt->execute([$userId, $tagId]);
        } catch (Exception $e) {}
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">👥 Customers</h2>
        <p class="text-gray-600">ทั้งหมด <?php echo number_format($totalUsers); ?> คน</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium mb-1">ค้นหา</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ชื่อ หรือ LINE ID..." class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
        </div>
        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
            <i class="fas fa-search mr-1"></i>ค้นหา
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tags</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ข้อความ</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <img src="<?php echo $user['picture_url'] ?: 'https://via.placeholder.com/40'; ?>" class="w-10 h-10 rounded-full object-cover mr-3">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($user['display_name'] ?: 'Unknown'); ?></p>
                            <p class="text-xs text-gray-500"><?php echo substr($user['line_user_id'], 0, 15); ?>...</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <?php if ($user['tags']): ?>
                        <?php foreach (explode(', ', $user['tags']) as $tagName): ?>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs"><?php echo htmlspecialchars($tagName); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray-400 text-xs">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="font-medium"><?php echo number_format($user['message_count'] ?? 0); ?></span>
                </td>
                <td class="px-6 py-4 text-center">
                    <?php if ($user['is_blocked']): ?>
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Blocked</span>
                    <?php else: ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Active</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <a href="user-detail.php?id=<?php echo $user['id']; ?>" class="text-green-500 hover:text-green-700" title="ดูรายละเอียด">
                            <i class="fas fa-user"></i>
                        </a>
                        <a href="messages.php?user=<?php echo $user['id']; ?>" class="text-blue-500 hover:text-blue-700" title="ดูแชท">
                            <i class="fas fa-comments"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-users text-4xl text-gray-300 mb-3 block"></i>
                    <p>ไม่พบผู้ใช้</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="mt-4 flex justify-center gap-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
       class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-green-500 text-white' : 'bg-white hover:bg-gray-100'; ?>">
        <?php echo $i; ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
