<?php
/**
 * Template Library - คลังเทมเพลตข้อความ
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Template Library';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $data = [$_POST['name'], $_POST['category'], $_POST['message_type'], $_POST['content']];
        
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO templates (name, category, message_type, content) VALUES (?, ?, ?, ?)");
        } else {
            $data[] = $_POST['id'];
            $stmt = $db->prepare("UPDATE templates SET name=?, category=?, message_type=?, content=? WHERE id=?");
        }
        $stmt->execute($data);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    header('Location: templates.php');
    exit;
}

// Get all templates
$stmt = $db->query("SELECT * FROM templates ORDER BY category, name");
$templates = $stmt->fetchAll();

// Get categories
$categories = array_unique(array_column($templates, 'category'));

require_once 'includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <div class="flex space-x-2">
        <button onclick="filterCategory('')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 category-btn active">ทั้งหมด</button>
        <?php foreach ($categories as $cat): if($cat): ?>
        <button onclick="filterCategory('<?= htmlspecialchars($cat) ?>')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 category-btn"><?= htmlspecialchars($cat) ?></button>
        <?php endif; endforeach; ?>
    </div>
    <button onclick="openModal()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
        <i class="fas fa-plus mr-2"></i>เพิ่มเทมเพลต
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="templatesGrid">
    <?php foreach ($templates as $template): ?>
    <div class="bg-white rounded-xl shadow p-4 template-card" data-category="<?= htmlspecialchars($template['category']) ?>">
        <div class="flex justify-between items-start mb-2">
            <div>
                <h3 class="font-semibold"><?= htmlspecialchars($template['name']) ?></h3>
                <span class="text-xs text-gray-500"><?= $template['category'] ?: 'ไม่มีหมวดหมู่' ?></span>
            </div>
            <span class="px-2 py-1 text-xs rounded <?= $template['message_type'] === 'text' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' ?>">
                <?= $template['message_type'] ?>
            </span>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg mb-3 max-h-32 overflow-y-auto">
            <pre class="text-sm whitespace-pre-wrap"><?= htmlspecialchars($template['content']) ?></pre>
        </div>
        <div class="flex space-x-2">
            <button onclick="copyTemplate('<?= htmlspecialchars(addslashes($template['content'])) ?>')" class="flex-1 py-2 border rounded-lg hover:bg-gray-50 text-sm">
                <i class="fas fa-copy mr-1"></i>คัดลอก
            </button>
            <button onclick='editTemplate(<?= json_encode($template) ?>)' class="px-3 py-2 border rounded-lg hover:bg-gray-50"><i class="fas fa-edit"></i></button>
            <form method="POST" class="inline" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $template['id'] ?>">
                <button type="submit" class="px-3 py-2 border border-red-300 text-red-500 rounded-lg hover:bg-red-50"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($templates)): ?>
    <div class="col-span-full bg-white rounded-xl shadow p-8 text-center text-gray-500">
        <i class="fas fa-file-alt text-6xl mb-4"></i>
        <p>ยังไม่มีเทมเพลต</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-lg mx-4">
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold" id="modalTitle">เพิ่มเทมเพลต</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อเทมเพลต</label>
                    <input type="text" name="name" id="name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หมวดหมู่</label>
                    <input type="text" name="category" id="category" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="เช่น ทักทาย, โปรโมชั่น, FAQ">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ประเภท</label>
                    <select name="message_type" id="message_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="text">Text</option>
                        <option value="flex">Flex Message (JSON)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เนื้อหา</label>
                    <textarea name="content" id="content" rows="6" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
            </div>
            <div class="p-6 border-t flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'เพิ่มเทมเพลต';
    document.querySelector('#modal form').reset();
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editTemplate(template) {
    openModal();
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = template.id;
    document.getElementById('modalTitle').textContent = 'แก้ไขเทมเพลต';
    document.getElementById('name').value = template.name;
    document.getElementById('category').value = template.category || '';
    document.getElementById('message_type').value = template.message_type;
    document.getElementById('content').value = template.content;
}

function copyTemplate(content) {
    navigator.clipboard.writeText(content).then(() => showToast('คัดลอกแล้ว!'));
}

function filterCategory(category) {
    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active', 'bg-green-500', 'text-white'));
    event.target.classList.add('active', 'bg-green-500', 'text-white');
    
    document.querySelectorAll('.template-card').forEach(card => {
        card.style.display = (!category || card.dataset.category === category) ? '' : 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
