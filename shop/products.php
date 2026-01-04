<?php
/**
 * Shop Products - Redirect to Inventory Products Tab
 * จัดการสินค้าใน inventory/index.php?tab=products
 */

// Preserve query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? '';

header('Location: /inventory?tab=products' . ($queryString ? '&' . $queryString : ''));
exit;
