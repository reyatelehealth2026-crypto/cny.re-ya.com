<?php
/**
 * Loyalty Points System
 */

class LoyaltyPoints
{
    private $db;
    private $lineAccountId;
    private $settings;

    public function __construct($db, $lineAccountId = null)
    {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
        $this->loadSettings();
    }

    private function loadSettings()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM points_settings WHERE line_account_id = ? OR line_account_id IS NULL ORDER BY line_account_id DESC LIMIT 1");
            $stmt->execute([$this->lineAccountId]);
            $this->settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['points_per_baht' => 1, 'min_order_for_points' => 0, 'points_expiry_days' => 365, 'is_active' => 1];
        } catch (Exception $e) {
            $this->settings = ['points_per_baht' => 1, 'min_order_for_points' => 0, 'points_expiry_days' => 365, 'is_active' => 1];
        }
    }

    public function getSettings() { return $this->settings; }

    public function updateSettings($data)
    {
        $stmt = $this->db->prepare("INSERT INTO points_settings (line_account_id, points_per_baht, min_order_for_points, points_expiry_days, is_active) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE points_per_baht = VALUES(points_per_baht), min_order_for_points = VALUES(min_order_for_points), points_expiry_days = VALUES(points_expiry_days), is_active = VALUES(is_active)");
        return $stmt->execute([$this->lineAccountId, $data['points_per_baht'] ?? 1, $data['min_order_for_points'] ?? 0, $data['points_expiry_days'] ?? 365, $data['is_active'] ?? 1]);
    }

    public function calculatePoints($amount)
    {
        if (!$this->settings['is_active']) return 0;
        if ($amount < $this->settings['min_order_for_points']) return 0;
        return (int)floor($amount * $this->settings['points_per_baht']);
    }

    public function getUserPoints($userId)
    {
        $stmt = $this->db->prepare("SELECT total_points, available_points, used_points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_points' => 0, 'available_points' => 0, 'used_points' => 0];
    }

    public function addPoints($userId, $points, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($points <= 0) return false;
        $current = $this->getUserPoints($userId);
        $newBalance = $current['available_points'] + $points;
        $expiresAt = $this->settings['points_expiry_days'] > 0 ? date('Y-m-d H:i:s', strtotime("+{$this->settings['points_expiry_days']} days")) : null;

        $stmt = $this->db->prepare("UPDATE users SET total_points = total_points + ?, available_points = available_points + ? WHERE id = ?");
        $stmt->execute([$points, $points, $userId]);

        $stmt = $this->db->prepare("INSERT INTO points_transactions (user_id, line_account_id, type, points, balance_after, reference_type, reference_id, description, expires_at) VALUES (?, ?, 'earn', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $this->lineAccountId, $points, $newBalance, $referenceType, $referenceId, $description ?? "Earned {$points} points", $expiresAt]);
        return true;
    }

    public function deductPoints($userId, $points, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($points <= 0) return false;
        $current = $this->getUserPoints($userId);
        if ($current['available_points'] < $points) return false;
        $newBalance = $current['available_points'] - $points;

        $stmt = $this->db->prepare("UPDATE users SET available_points = available_points - ?, used_points = used_points + ? WHERE id = ?");
        $stmt->execute([$points, $points, $userId]);

        $stmt = $this->db->prepare("INSERT INTO points_transactions (user_id, line_account_id, type, points, balance_after, reference_type, reference_id, description) VALUES (?, ?, 'redeem', ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $this->lineAccountId, -$points, $newBalance, $referenceType, $referenceId, $description ?? "Used {$points} points"]);
        return true;
    }

    public function awardPointsForOrder($userId, $orderId, $orderAmount)
    {
        $points = $this->calculatePoints($orderAmount);
        if ($points > 0) return $this->addPoints($userId, $points, 'order', $orderId, "Points from order #{$orderId}");
        return false;
    }

    public function getPointsHistory($userId, $limit = 20)
    {
        $stmt = $this->db->prepare("SELECT * FROM points_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRewards($activeOnly = true)
    {
        $sql = "SELECT * FROM rewards WHERE (line_account_id = ? OR line_account_id IS NULL)";
        if ($activeOnly) $sql .= " AND is_active = 1";
        $sql .= " ORDER BY points_required ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->lineAccountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReward($rewardId)
    {
        $stmt = $this->db->prepare("SELECT * FROM rewards WHERE id = ?");
        $stmt->execute([$rewardId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createReward($data)
    {
        $stmt = $this->db->prepare("INSERT INTO rewards (line_account_id, name, description, image_url, points_required, reward_type, reward_value, stock, max_per_user, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$this->lineAccountId, $data['name'], $data['description'] ?? null, $data['image_url'] ?? null, $data['points_required'], $data['reward_type'] ?? 'gift', $data['reward_value'] ?? null, $data['stock'] ?? -1, $data['max_per_user'] ?? 0, $data['is_active'] ?? 1]);
        return $this->db->lastInsertId();
    }

    public function updateReward($rewardId, $data)
    {
        $fields = [];
        $values = [];
        foreach (['name', 'description', 'image_url', 'points_required', 'stock', 'max_per_user', 'is_active'] as $field) {
            if (isset($data[$field])) { $fields[] = "{$field} = ?"; $values[] = $data[$field]; }
        }
        if (empty($fields)) return false;
        $values[] = $rewardId;
        $stmt = $this->db->prepare("UPDATE rewards SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function deleteReward($rewardId)
    {
        $stmt = $this->db->prepare("DELETE FROM rewards WHERE id = ?");
        return $stmt->execute([$rewardId]);
    }

    public function redeemReward($userId, $rewardId)
    {
        $reward = $this->getReward($rewardId);
        if (!$reward || !$reward['is_active']) return ['success' => false, 'message' => 'Reward not found'];
        if ($reward['stock'] == 0) return ['success' => false, 'message' => 'Out of stock'];

        $userPoints = $this->getUserPoints($userId);
        if ($userPoints['available_points'] < $reward['points_required']) return ['success' => false, 'message' => 'Not enough points'];

        if (!$this->deductPoints($userId, $reward['points_required'], 'reward', $rewardId, "Redeemed: {$reward['name']}")) return ['success' => false, 'message' => 'Failed to deduct points'];

        if ($reward['stock'] > 0) {
            $stmt = $this->db->prepare("UPDATE rewards SET stock = stock - 1 WHERE id = ? AND stock > 0");
            $stmt->execute([$rewardId]);
        }

        $code = 'RW' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt = $this->db->prepare("INSERT INTO reward_redemptions (user_id, reward_id, line_account_id, points_used, redemption_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $rewardId, $this->lineAccountId, $reward['points_required'], $code]);

        return ['success' => true, 'message' => 'Success!', 'redemption_code' => $code, 'reward' => $reward];
    }

    public function getUserRedemptions($userId, $limit = 20)
    {
        $stmt = $this->db->prepare("SELECT rr.*, r.name as reward_name, r.image_url as reward_image FROM reward_redemptions rr JOIN rewards r ON rr.reward_id = r.id WHERE rr.user_id = ? ORDER BY rr.created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRedemptions($status = null, $limit = 50)
    {
        $sql = "SELECT rr.*, r.name as reward_name, r.image_url as reward_image, u.display_name, u.picture_url FROM reward_redemptions rr JOIN rewards r ON rr.reward_id = r.id JOIN users u ON rr.user_id = u.id WHERE (rr.line_account_id = ? OR rr.line_account_id IS NULL)";
        $params = [$this->lineAccountId];
        if ($status) { $sql .= " AND rr.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY rr.created_at DESC LIMIT ?";
        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRedemptionStatus($redemptionId, $status, $adminId = null, $notes = null)
    {
        $updates = ['status = ?'];
        $params = [$status];
        if ($status === 'approved') { $updates[] = 'approved_by = ?'; $updates[] = 'approved_at = NOW()'; $params[] = $adminId; }
        elseif ($status === 'delivered') { $updates[] = 'delivered_at = NOW()'; }
        if ($notes) { $updates[] = 'notes = ?'; $params[] = $notes; }
        $params[] = $redemptionId;
        $stmt = $this->db->prepare("UPDATE reward_redemptions SET " . implode(', ', $updates) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function getPointsSummary()
    {
        $summary = [];
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(points), 0) FROM points_transactions WHERE type = 'earn' AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $summary['total_issued'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(ABS(points)), 0) FROM points_transactions WHERE type = 'redeem' AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $summary['total_redeemed'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rewards WHERE is_active = 1 AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $summary['active_rewards'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reward_redemptions WHERE status = 'pending' AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $summary['pending_redemptions'] = $stmt->fetchColumn();

        return $summary;
    }
}
