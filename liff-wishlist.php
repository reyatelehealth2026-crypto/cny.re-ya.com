<?php
/**
 * LIFF Wishlist - หน้ารายการโปรดของลูกค้า
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$userId = $_GET['user'] ?? null;
$lineAccountId = $_GET['account'] ?? null;

require_once 'includes/liff-helper.php';
$liffData = getUnifiedLiffId($db, $lineAccountId);
$liffId = $liffData['liff_id'];

$shopSettings = [];
try {
    $stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ? LIMIT 1");
    $stmt->execute([$lineAccountId]);
    $shopSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {}
$shopName = $shopSettings['shop_name'] ?? 'ร้านยา';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>รายการโปรด - <?= htmlspecialchars($shopName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #11B0A6; }
        body { font-family: 'Sarabun', sans-serif; background: #F8FAFC; }
    </style>
</head>
<body class="pb-20">
    <!-- Header -->
    <div class="bg-white px-4 py-3 sticky top-0 z-40 shadow-sm flex items-center gap-3">
        <button onclick="goBack()" class="p-2 -ml-2">
            <i class="fas fa-arrow-left text-gray-600"></i>
        </button>
        <h1 class="font-bold text-gray-800 flex-1">❤️ รายการโปรด</h1>
        <span id="itemCount" class="text-sm text-gray-500">0 รายการ</span>
    </div>

    <!-- Info Banner -->
    <div class="mx-4 mt-4 p-3 bg-pink-50 border border-pink-200 rounded-xl text-sm text-pink-700">
        <i class="fas fa-bell mr-2"></i>
        เราจะแจ้งเตือนคุณเมื่อสินค้าในรายการโปรดลดราคา!
    </div>

    <!-- Wishlist Items -->
    <div id="wishlistContainer" class="px-4 py-4">
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-spinner fa-spin text-3xl"></i>
            <p class="mt-2">กำลังโหลด...</p>
        </div>
    </div>

    <!-- Empty State Template -->
    <template id="emptyTemplate">
        <div class="text-center py-16 text-gray-400">
            <i class="far fa-heart text-6xl mb-4"></i>
            <p class="text-lg font-medium">ยังไม่มีรายการโปรด</p>
            <p class="text-sm mt-2">กดปุ่ม ❤️ ที่สินค้าเพื่อเพิ่มรายการโปรด</p>
            <a href="liff-shop.php?user=<?= $userId ?>&account=<?= $lineAccountId ?>" 
               class="inline-block mt-4 px-6 py-2 bg-teal-500 text-white rounded-full">
                <i class="fas fa-shopping-bag mr-2"></i>ไปช้อปปิ้ง
            </a>
        </div>
    </template>

    <script>
    const userId = '<?= $userId ?>';
    const lineAccountId = '<?= $lineAccountId ?>';
    const BASE_URL = '<?= rtrim(BASE_URL, '/') ?>';
    
    function goBack() {
        window.history.back();
    }
    
    async function loadWishlist() {
        if (!userId) {
            showEmpty();
            return;
        }
        
        try {
            const res = await fetch(`${BASE_URL}/api/wishlist.php?action=list&line_user_id=${userId}`);
            const data = await res.json();
            
            if (data.success && data.items.length > 0) {
                renderWishlist(data.items);
                document.getElementById('itemCount').textContent = data.count + ' รายการ';
            } else {
                showEmpty();
            }
        } catch (e) {
            console.error(e);
            showEmpty();
        }
    }
    
    function renderWishlist(items) {
        const container = document.getElementById('wishlistContainer');
        container.innerHTML = items.map(item => {
            const price = item.sale_price || item.price;
            const isOnSale = item.is_on_sale == 1;
            const discount = item.discount_percent || 0;
            
            return `
            <div class="bg-white rounded-xl shadow mb-3 overflow-hidden" data-id="${item.product_id}">
                <div class="flex">
                    <div class="w-24 h-24 bg-gray-100 flex-shrink-0" onclick="viewProduct(${item.product_id})">
                        ${item.image_url 
                            ? `<img src="${item.image_url}" class="w-full h-full object-cover">` 
                            : '<div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fas fa-image text-2xl"></i></div>'}
                    </div>
                    <div class="flex-1 p-3">
                        <div class="flex justify-between items-start">
                            <h3 class="font-medium text-gray-800 text-sm line-clamp-2 flex-1 pr-2" onclick="viewProduct(${item.product_id})">${item.name}</h3>
                            <button onclick="removeFromWishlist(${item.product_id})" class="text-gray-400 hover:text-red-500 p-1">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="${isOnSale ? 'text-red-500' : 'text-teal-600'} font-bold">฿${Number(price).toLocaleString()}</span>
                            ${isOnSale ? `
                                <span class="text-gray-400 text-xs line-through">฿${Number(item.price_when_added).toLocaleString()}</span>
                                <span class="px-1.5 py-0.5 bg-red-100 text-red-600 text-[10px] rounded font-bold">-${discount}%</span>
                            ` : ''}
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button onclick="addToCart(${item.product_id})" class="flex-1 py-1.5 bg-teal-500 text-white text-xs rounded-lg ${item.stock <= 0 ? 'opacity-50 cursor-not-allowed' : ''}">
                                <i class="fas fa-cart-plus mr-1"></i>${item.stock <= 0 ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า'}
                            </button>
                        </div>
                    </div>
                </div>
                ${isOnSale ? `
                <div class="px-3 py-2 bg-red-50 border-t border-red-100 text-xs text-red-600">
                    <i class="fas fa-fire mr-1"></i>ลดราคาจากที่คุณเพิ่มไว้ ${discount}%!
                </div>
                ` : ''}
            </div>
            `;
        }).join('');
    }
    
    function showEmpty() {
        const container = document.getElementById('wishlistContainer');
        const template = document.getElementById('emptyTemplate');
        container.innerHTML = template.innerHTML;
        document.getElementById('itemCount').textContent = '0 รายการ';
    }
    
    function viewProduct(productId) {
        window.location.href = `liff-product-detail.php?id=${productId}&user=${userId}&account=${lineAccountId}`;
    }
    
    async function removeFromWishlist(productId) {
        if (!confirm('ลบออกจากรายการโปรด?')) return;
        
        try {
            const res = await fetch(`${BASE_URL}/api/wishlist.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'remove',
                    line_user_id: userId,
                    product_id: productId
                })
            });
            const data = await res.json();
            
            if (data.success) {
                // Remove from DOM
                const item = document.querySelector(`[data-id="${productId}"]`);
                if (item) item.remove();
                
                // Check if empty
                const remaining = document.querySelectorAll('[data-id]').length;
                if (remaining === 0) showEmpty();
                document.getElementById('itemCount').textContent = remaining + ' รายการ';
                
                showToast('ลบออกจากรายการโปรดแล้ว');
            }
        } catch (e) {
            showToast('เกิดข้อผิดพลาด', 'error');
        }
    }
    
    function addToCart(productId) {
        let cart = JSON.parse(localStorage.getItem('cart_' + lineAccountId) || '{}');
        cart[productId] = (cart[productId] || 0) + 1;
        localStorage.setItem('cart_' + lineAccountId, JSON.stringify(cart));
        showToast('เพิ่มลงตะกร้าแล้ว');
    }
    
    function showToast(message, type = 'success') {
        const colors = { success: 'bg-green-500', error: 'bg-red-500' };
        const toast = document.createElement('div');
        toast.className = `fixed top-20 left-1/2 -translate-x-1/2 ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
        toast.innerHTML = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }
    
    // Init
    loadWishlist();
    </script>
</body>
</html>
