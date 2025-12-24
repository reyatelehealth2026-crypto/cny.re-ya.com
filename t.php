<?php
/**
 * Link Tracking Redirect Handler
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$shortCode = $_GET['c'] ?? '';

if (empty($shortCode)) {
    http_response_code(404);
    echo 'Link not found';
    exit;
}

// Get link
$stmt = $db->prepare("SELECT * FROM tracked_links WHERE short_code = ? AND is_active = 1");
$stmt->execute([$shortCode]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    http_response_code(404);
    echo 'Link not found or expired';
    exit;
}

// Update click count
$stmt = $db->prepare("UPDATE tracked_links SET click_count = click_count + 1 WHERE id = ?");
$stmt->execute([$link['id']]);

// Record click
try {
    $stmt = $db->prepare("INSERT INTO link_clicks (link_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$link['id'], $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
} catch (Exception $e) {}

// Redirect
header('Location: ' . $link['original_url'], true, 302);
exit;
