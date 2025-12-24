<?php
/**
 * LIFF Helper Functions
 * ใช้สำหรับดึง Unified LIFF ID และข้อมูลที่จำเป็น
 */

/**
 * Get Unified LIFF ID from line_accounts
 * ใช้ liff_id เดียวสำหรับทุกหน้า
 */
function getUnifiedLiffId($db, $lineAccountId = null) {
    try {
        if ($lineAccountId) {
            $stmt = $db->prepare("SELECT id, liff_id, name FROM line_accounts WHERE id = ?");
            $stmt->execute([$lineAccountId]);
        } else {
            $stmt = $db->query("SELECT id, liff_id, name FROM line_accounts WHERE is_active = 1 ORDER BY is_default DESC LIMIT 1");
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'liff_id' => $row['liff_id'] ?? '',
                'line_account_id' => $row['id'],
                'account_name' => $row['name']
            ];
        }
    } catch (Exception $e) {
        error_log("getUnifiedLiffId error: " . $e->getMessage());
    }
    return ['liff_id' => '', 'line_account_id' => 1, 'account_name' => ''];
}

/**
 * Get shop settings
 */
function getShopSettings($db, $lineAccountId = null) {
    try {
        if ($lineAccountId) {
            $stmt = $db->prepare("SELECT * FROM shop_settings WHERE line_account_id = ? LIMIT 1");
            $stmt->execute([$lineAccountId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (empty($settings)) {
            $stmt = $db->query("SELECT * FROM shop_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $settings ?: [
            'shop_name' => 'LINE Shop',
            'shipping_fee' => 50,
            'free_shipping_min' => 500
        ];
    } catch (Exception $e) {
        return ['shop_name' => 'LINE Shop', 'shipping_fee' => 50, 'free_shipping_min' => 500];
    }
}

/**
 * Get line_account_id from user's line_user_id
 */
function getLineAccountIdFromUser($db, $lineUserId) {
    if (!$lineUserId) return null;
    try {
        $stmt = $db->prepare("SELECT line_account_id FROM users WHERE line_user_id = ?");
        $stmt->execute([$lineUserId]);
        return $stmt->fetchColumn() ?: null;
    } catch (Exception $e) {
        return null;
    }
}
