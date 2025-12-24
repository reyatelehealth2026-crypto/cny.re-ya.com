<?php
/**
 * LIFF Bottom Navigation - Include in all LIFF pages
 * Usage: include 'includes/liff-nav.php';
 */

// Get current page name
$currentLiffPage = basename($_SERVER['PHP_SELF'], '.php');

// Get line_account_id from various sources
$navLineAccountId = $lineAccountId ?? $_GET['account'] ?? 1;
?>

<!-- Bottom Navigation -->
<nav class="liff-bottom-nav">
    <div class="liff-nav-container">
        <a href="liff-app.php?page=home&account=<?= $navLineAccountId ?>" class="liff-nav-item <?= $currentLiffPage === 'liff-app' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>หน้าหลัก</span>
        </a>
        <a href="liff-shop.php?account=<?= $navLineAccountId ?>" class="liff-nav-item <?= $currentLiffPage === 'liff-shop' ? 'active' : '' ?>">
            <i class="fas fa-store"></i>
            <span>ร้านค้า</span>
        </a>
        <a href="liff-checkout.php?account=<?= $navLineAccountId ?>" class="liff-nav-item <?= $currentLiffPage === 'liff-checkout' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>ตะกร้า</span>
        </a>
        <a href="liff-my-orders.php?account=<?= $navLineAccountId ?>" class="liff-nav-item <?= $currentLiffPage === 'liff-my-orders' ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>ออเดอร์</span>
        </a>
        <a href="liff-member-card.php?account=<?= $navLineAccountId ?>" class="liff-nav-item <?= in_array($currentLiffPage, ['liff-member-card', 'liff-points-history', 'liff-redeem-points']) ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>โปรไฟล์</span>
        </a>
    </div>
</nav>

<style>
.liff-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    padding-bottom: env(safe-area-inset-bottom);
}
.liff-nav-container {
    display: flex;
    max-width: 500px;
    margin: 0 auto;
}
.liff-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 0 8px;
    color: #9ca3af;
    font-size: 10px;
    text-decoration: none;
    transition: color 0.2s;
}
.liff-nav-item i {
    font-size: 20px;
    margin-bottom: 3px;
}
.liff-nav-item.active {
    color: #11B0A6;
}
.liff-nav-item:hover {
    color: #11B0A6;
}
/* Add padding to body for nav */
body {
    padding-bottom: 70px !important;
}
</style>
