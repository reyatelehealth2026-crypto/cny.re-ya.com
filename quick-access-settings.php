<?php
/**
 * Quick Access Settings
 * ให้ผู้ใช้เลือก Quick Access Menu ได้เอง
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'ตั้งค่า Quick Access';

require_once 'includes/header.php';
?>

<style>
.menu-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.menu-card:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}
.menu-card.selected {
    border-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}
.menu-card.selected .menu-card-icon {
    transform: scale(1.1);
}
.menu-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    margin: 0 auto 8px;
    transition: transform 0.2s;
}
.menu-card-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #10b981;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}
.menu-card.selected .menu-card-check {
    display: flex;
}
.sortable-ghost {
    opacity: 0.4;
}
.drag-handle {
    cursor: grab;
}
.drag-handle:active {
    cursor: grabbing;
}
</style>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">⚡ ตั้งค่า Quick Access</h1>
            <p class="text-gray-500 mt-1">เลือกเมนูที่ใช้บ่อยเพื่อเข้าถึงได้รวดเร็ว (สูงสุด 8 รายการ)</p>
        </div>
        <div class="flex gap-2">
            <button onclick="resetToDefault()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                <i class="fas fa-undo mr-1"></i> รีเซ็ต
            </button>
            <button onclick="saveQuickAccess()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                <i class="fas fa-save mr-1"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<!-- Selected Items (Sortable) -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">
            <i class="fas fa-star text-yellow-500 mr-2"></i>เมนูที่เลือก
            <span id="selectedCount" class="text-sm font-normal text-gray-500">(0/8)</span>
        </h2>
        <p class="text-sm text-gray-500">ลากเพื่อเรียงลำดับ</p>
    </div>
    
    <div id="selectedMenus" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 min-h-[100px] p-4 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
        <div class="col-span-full text-center text-gray-400 py-8" id="emptyMessage">
            <i class="fas fa-hand-pointer text-4xl mb-2"></i>
            <p>คลิกเลือกเมนูด้านล่าง</p>
        </div>
    </div>
</div>

<!-- Available Menus -->
<div class="bg-white rounded-xl shadow p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">
        <i class="fas fa-th-large text-blue-500 mr-2"></i>เมนูทั้งหมด
    </h2>
    
    <div id="availableMenus" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <!-- Will be populated by JS -->
    </div>
</div>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
const BASE_URL = '<?= rtrim(BASE_URL, '/') ?>';
let selectedMenus = [];
let availableMenus = [];

// Color mapping
const colorClasses = {
    'green': 'bg-green-500',
    'orange': 'bg-orange-500',
    'blue': 'bg-blue-500',
    'purple': 'bg-purple-500',
    'cyan': 'bg-cyan-500',
    'pink': 'bg-pink-500',
    'indigo': 'bg-indigo-500',
    'teal': 'bg-teal-500',
    'amber': 'bg-amber-500',
    'emerald': 'bg-emerald-500',
    'sky': 'bg-sky-500',
    'violet': 'bg-violet-500',
    'rose': 'bg-rose-500',
    'fuchsia': 'bg-fuchsia-500',
    'lime': 'bg-lime-500',
    'slate': 'bg-slate-500',
};

document.addEventListener('DOMContentLoaded', async () => {
    await loadAvailableMenus();
    await loadUserMenus();
    initSortable();
});

async function loadAvailableMenus() {
    try {
        const res = await fetch(`${BASE_URL}/api/quick-access.php?action=get_available`);
        const data = await res.json();
        if (data.success) {
            availableMenus = data.data;
            renderAvailableMenus();
        }
    } catch (e) {
        console.error('Load available menus error:', e);
    }
}

async function loadUserMenus() {
    try {
        const res = await fetch(`${BASE_URL}/api/quick-access.php?action=get`);
        const data = await res.json();
        if (data.success) {
            selectedMenus = data.data.map(m => m.key);
            renderSelectedMenus();
            updateAvailableMenusState();
        }
    } catch (e) {
        console.error('Load user menus error:', e);
    }
}

function renderAvailableMenus() {
    const container = document.getElementById('availableMenus');
    container.innerHTML = availableMenus.map(menu => `
        <div class="menu-card relative" data-key="${menu.key}" onclick="toggleMenu('${menu.key}')">
            <div class="menu-card-check"><i class="fas fa-check"></i></div>
            <div class="menu-card-icon ${colorClasses[menu.color] || 'bg-gray-500'}">
                <i class="fas ${menu.icon}"></i>
            </div>
            <p class="text-center font-medium text-gray-700 text-sm">${menu.label}</p>
        </div>
    `).join('');
}

function renderSelectedMenus() {
    const container = document.getElementById('selectedMenus');
    const emptyMsg = document.getElementById('emptyMessage');
    
    if (selectedMenus.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center text-gray-400 py-8" id="emptyMessage">
                <i class="fas fa-hand-pointer text-4xl mb-2"></i>
                <p>คลิกเลือกเมนูด้านล่าง</p>
            </div>
        `;
        document.getElementById('selectedCount').textContent = '(0/8)';
        return;
    }
    
    container.innerHTML = selectedMenus.map(key => {
        const menu = availableMenus.find(m => m.key === key);
        if (!menu) return '';
        return `
            <div class="menu-card selected drag-handle" data-key="${key}">
                <div class="menu-card-icon ${colorClasses[menu.color] || 'bg-gray-500'}">
                    <i class="fas ${menu.icon}"></i>
                </div>
                <p class="text-center font-medium text-gray-700 text-xs">${menu.label}</p>
                <button onclick="event.stopPropagation(); removeMenu('${key}')" 
                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs hover:bg-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }).join('');
    
    document.getElementById('selectedCount').textContent = `(${selectedMenus.length}/8)`;
}

function updateAvailableMenusState() {
    document.querySelectorAll('#availableMenus .menu-card').forEach(card => {
        const key = card.dataset.key;
        if (selectedMenus.includes(key)) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}

function toggleMenu(key) {
    const index = selectedMenus.indexOf(key);
    if (index > -1) {
        selectedMenus.splice(index, 1);
    } else {
        if (selectedMenus.length >= 8) {
            alert('เลือกได้สูงสุด 8 รายการ');
            return;
        }
        selectedMenus.push(key);
    }
    renderSelectedMenus();
    updateAvailableMenusState();
    initSortable();
}

function removeMenu(key) {
    const index = selectedMenus.indexOf(key);
    if (index > -1) {
        selectedMenus.splice(index, 1);
        renderSelectedMenus();
        updateAvailableMenusState();
        initSortable();
    }
}

function initSortable() {
    const container = document.getElementById('selectedMenus');
    if (container._sortable) {
        container._sortable.destroy();
    }
    
    container._sortable = new Sortable(container, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        handle: '.drag-handle',
        onEnd: function(evt) {
            // Update order
            const newOrder = [];
            container.querySelectorAll('.menu-card').forEach(card => {
                if (card.dataset.key) {
                    newOrder.push(card.dataset.key);
                }
            });
            selectedMenus = newOrder;
        }
    });
}

async function saveQuickAccess() {
    try {
        const res = await fetch(`${BASE_URL}/api/quick-access.php?action=save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menus: selectedMenus })
        });
        const data = await res.json();
        if (data.success) {
            alert('บันทึกสำเร็จ! กรุณารีเฟรชหน้าเพื่อดูการเปลี่ยนแปลง');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.error || 'Unknown'));
        }
    } catch (e) {
        alert('เกิดข้อผิดพลาด: ' + e.message);
    }
}

async function resetToDefault() {
    if (!confirm('รีเซ็ตเป็นค่าเริ่มต้น?')) return;
    
    try {
        const res = await fetch(`${BASE_URL}/api/quick-access.php?action=reset`);
        const data = await res.json();
        if (data.success) {
            selectedMenus = ['messages', 'orders', 'products', 'broadcast'];
            renderSelectedMenus();
            updateAvailableMenusState();
            initSortable();
            alert('รีเซ็ตสำเร็จ!');
        }
    } catch (e) {
        alert('เกิดข้อผิดพลาด: ' + e.message);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
