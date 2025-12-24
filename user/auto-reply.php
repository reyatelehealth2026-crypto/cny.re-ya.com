<?php
/**
 * User Auto Reply - ตั้งค่าตอบกลับอัตโนมัติ (AJAX Version)
 */
$pageTitle = 'ตอบกลับอัตโนมัติ';
require_once '../includes/user_header.php';

// Get auto replies
$stmt = $db->prepare("SELECT * FROM auto_replies WHERE line_account_id = ? ORDER BY priority DESC, id DESC");
$stmt->execute([$currentBotId]);
$autoReplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="toast-message">สำเร็จ</span>
    </div>
</div>

<div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
        <span class="text-sm text-blue-700">
            กฎตอบกลับด้านล่างใช้เฉพาะกับ <strong><?= htmlspecialchars($lineAccount['name']) ?></strong> เท่านั้น
        </span>
    </div>
</div>

<div class="mb-4 flex justify-between items-center">
    <div>
        <span class="text-gray-600">กฎตอบกลับทั้งหมด <span id="rule-count"><?= count($autoReplies) ?></span> รายการ</span>
    </div>
    <button onclick="openModal()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
        <i class="fas fa-plus mr-2"></i>เพิ่มกฎใหม่
    </button>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">คีย์เวิร์ด</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ข้อความตอบกลับ</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">จัดการ</th>
            </tr>
        </thead>
        <tbody id="rules-tbody" class="divide-y divide-gray-100">
            <?php if (empty($autoReplies)): ?>
            <tr id="empty-row">
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">ยังไม่มีกฎตอบกลับ</td>
            </tr>
            <?php else: ?>
            <?php 
            $types = ['exact' => 'ตรงทั้งหมด', 'contains' => 'มีคำนี้', 'starts_with' => 'ขึ้นต้นด้วย', 'regex' => 'Regex'];
            foreach ($autoReplies as $rule): 
            ?>
            <tr class="hover:bg-gray-50 rule-row" data-id="<?= $rule['id'] ?>">
                <td class="px-4 py-3 font-medium rule-keyword"><?= htmlspecialchars($rule['keyword']) ?></td>
                <td class="px-4 py-3 text-sm text-gray-500 rule-type"><?= $types[$rule['match_type']] ?? $rule['match_type'] ?></td>
                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate rule-content"><?= htmlspecialchars(mb_substr($rule['reply_content'], 0, 50)) ?></td>
                <td class="px-4 py-3 text-center">
                    <button onclick="toggleRule(<?= $rule['id'] ?>)" class="status-btn px-2 py-1 text-xs rounded <?= $rule['is_active'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600' ?>">
                        <?= $rule['is_active'] ? 'เปิด' : 'ปิด' ?>
                    </button>
                </td>
                <td class="px-4 py-3 text-center">
                    <button onclick='editRule(<?= json_encode($rule) ?>)' class="text-blue-500 hover:text-blue-700 mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteRule(<?= $rule['id'] ?>)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-lg mx-4">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold" id="modalTitle">เพิ่มกฎตอบกลับ</h3>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="formAction" value="create">
            <input type="hidden" id="formId">
            <div>
                <label class="block text-sm font-medium mb-1">คีย์เวิร์ด <span class="text-red-500">*</span></label>
                <input type="text" id="keyword" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ประเภทการจับคู่</label>
                <select id="match_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="contains">มีคำนี้</option>
                    <option value="exact">ตรงทั้งหมด</option>
                    <option value="starts_with">ขึ้นต้นด้วย</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ข้อความตอบกลับ <span class="text-red-500">*</span></label>
                <textarea id="reply_content" rows="4" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" id="is_active" checked class="mr-2">
                    <span class="text-sm">เปิดใช้งาน</span>
                </label>
            </div>
        </div>
        <div class="p-6 border-t flex justify-end space-x-2">
            <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">ยกเลิก</button>
            <button type="button" onclick="saveRule()" id="btn-save" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 disabled:opacity-50">บันทึก</button>
        </div>
    </div>
</div>

<script>
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-message');
    toastMsg.textContent = message;
    
    const toastDiv = toast.querySelector('div');
    toastDiv.className = isError 
        ? 'bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center'
        : 'bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center';
    
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'เพิ่มกฎตอบกลับ';
    resetForm();
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function resetForm() {
    document.getElementById('formId').value = '';
    document.getElementById('keyword').value = '';
    document.getElementById('match_type').value = 'contains';
    document.getElementById('reply_content').value = '';
    document.getElementById('is_active').checked = true;
}

function editRule(rule) {
    openModal();
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = rule.id;
    document.getElementById('modalTitle').textContent = 'แก้ไขกฎตอบกลับ';
    document.getElementById('keyword').value = rule.keyword;
    document.getElementById('match_type').value = rule.match_type;
    document.getElementById('reply_content').value = rule.reply_content;
    document.getElementById('is_active').checked = rule.is_active == 1;
}

function saveRule() {
    const btn = document.getElementById('btn-save');
    const action = document.getElementById('formAction').value;
    const keyword = document.getElementById('keyword').value.trim();
    const replyContent = document.getElementById('reply_content').value.trim();
    
    if (!keyword || !replyContent) {
        showToast('กรุณากรอกข้อมูลให้ครบ', true);
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';
    
    const formData = new FormData();
    formData.append('action', action === 'create' ? 'create_auto_reply' : 'update_auto_reply');
    formData.append('keyword', keyword);
    formData.append('match_type', document.getElementById('match_type').value);
    formData.append('reply_content', replyContent);
    if (document.getElementById('is_active').checked) {
        formData.append('is_active', '1');
    }
    if (action === 'update') {
        formData.append('id', document.getElementById('formId').value);
    }
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            closeModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'บันทึก';
    });
}

function deleteRule(id) {
    if (!confirm('ยืนยันลบกฎนี้?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_auto_reply');
    formData.append('id', id);
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            const row = document.querySelector(`.rule-row[data-id="${id}"]`);
            if (row) {
                row.remove();
                const countEl = document.getElementById('rule-count');
                countEl.textContent = parseInt(countEl.textContent) - 1;
            }
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    });
}

function toggleRule(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_auto_reply');
    formData.append('id', id);
    
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            const row = document.querySelector(`.rule-row[data-id="${id}"]`);
            const btn = row.querySelector('.status-btn');
            if (data.is_active) {
                btn.className = 'status-btn px-2 py-1 text-xs rounded bg-green-100 text-green-600';
                btn.textContent = 'เปิด';
            } else {
                btn.className = 'status-btn px-2 py-1 text-xs rounded bg-gray-100 text-gray-600';
                btn.textContent = 'ปิด';
            }
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', true);
        }
    })
    .catch(err => {
        showToast('เกิดข้อผิดพลาด: ' + err.message, true);
    });
}
</script>

<?php require_once '../includes/user_footer.php'; ?>
