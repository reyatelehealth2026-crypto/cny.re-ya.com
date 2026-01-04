<?php
/**
 * Shop Products - Redirect to CNY Products
 * ใช้ข้อมูลสินค้าจาก CNY Pharmacy API
 */

// Preserve query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? '';

header('Location: /shop/products-cny.php' . ($queryString ? '?' . $queryString : ''));
exit;
