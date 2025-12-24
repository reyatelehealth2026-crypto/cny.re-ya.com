<?php
/**
 * CRM Manager - User Tags, Behavior Tracking, Drip Campaigns
 * LINE OA Manager V2.5
 */

class CRMManager
{
    private $db;
    private $lineAccountId;

    public function __construct($db, $lineAccountId = null)
    {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }

    // =============================================
    // Tag Management
    // =============================================

    public function createTag($name, $color = '#3B82F6', $description = '', $autoRules = null)
    {
        $stmt = $this->db->prepare("INSERT INTO user_tags (line_account_id, name, color, description, auto_assign_rules) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$this->lineAccountId, $name, $color, $description, $autoRules ? json_encode($autoRules) : null]);
        return $this->db->lastInsertId();
    }

    public function getTags()
    {
        $stmt = $this->db->prepare("SELECT * FROM user_tags WHERE line_account_id = ? OR line_account_id IS NULL ORDER BY name");
        $stmt->execute([$this->lineAccountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersByTag($tagId)
    {
        $stmt = $this->db->prepare("SELECT u.* FROM users u 
                                    JOIN user_tag_assignments a ON u.id = a.user_id 
                                    WHERE a.tag_id = ?");
        $stmt->execute([$tagId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTagStats()
    {
        $stmt = $this->db->prepare("SELECT t.*, COUNT(a.user_id) as user_count 
                                    FROM user_tags t 
                                    LEFT JOIN user_tag_assignments a ON t.id = a.tag_id 
                                    WHERE t.line_account_id = ? OR t.line_account_id IS NULL 
                                    GROUP BY t.id 
                                    ORDER BY user_count DESC");
        $stmt->execute([$this->lineAccountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =============================================
    // Drip Campaign Management
    // =============================================

    public function createCampaign($name, $triggerType, $triggerConfig = null)
    {
        $stmt = $this->db->prepare("INSERT INTO drip_campaigns (line_account_id, name, trigger_type, trigger_config) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->lineAccountId, $name, $triggerType, $triggerConfig ? json_encode($triggerConfig) : null]);
        return $this->db->lastInsertId();
    }

    public function addCampaignStep($campaignId, $stepOrder, $delayMinutes, $messageType, $content, $conditions = null)
    {
        $stmt = $this->db->prepare("INSERT INTO drip_campaign_steps (campaign_id, step_order, delay_minutes, message_type, message_content, condition_rules) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$campaignId, $stepOrder, $delayMinutes, $messageType, $content, $conditions ? json_encode($conditions) : null]);
        return $this->db->lastInsertId();
    }

    public function getCampaigns()
    {
        $stmt = $this->db->prepare("SELECT c.*, 
                                    (SELECT COUNT(*) FROM drip_campaign_steps WHERE campaign_id = c.id) as step_count,
                                    (SELECT COUNT(*) FROM drip_campaign_progress WHERE campaign_id = c.id AND status = 'active') as active_users
                                    FROM drip_campaigns c 
                                    WHERE c.line_account_id = ? OR c.line_account_id IS NULL 
                                    ORDER BY c.created_at DESC");
        $stmt->execute([$this->lineAccountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCampaignSteps($campaignId)
    {
        $stmt = $this->db->prepare("SELECT * FROM drip_campaign_steps WHERE campaign_id = ? ORDER BY step_order ASC");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Enroll user in a drip campaign
     */
    public function enrollUserInCampaign($userId, $campaignId)
    {
        // Check if already enrolled
        $stmt = $this->db->prepare("SELECT id FROM drip_campaign_progress WHERE campaign_id = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$campaignId, $userId]);
        if ($stmt->fetch()) return false; // Already enrolled

        // Get first step delay
        $stmt = $this->db->prepare("SELECT delay_minutes FROM drip_campaign_steps WHERE campaign_id = ? ORDER BY step_order ASC LIMIT 1");
        $stmt->execute([$campaignId]);
        $firstStep = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $delayMinutes = $firstStep ? $firstStep['delay_minutes'] : 0;
        $nextSendAt = date('Y-m-d H:i:s', strtotime("+{$delayMinutes} minutes"));

        $stmt = $this->db->prepare("INSERT INTO drip_campaign_progress (campaign_id, user_id, current_step, next_send_at) VALUES (?, ?, 0, ?)");
        $stmt->execute([$campaignId, $userId, $nextSendAt]);
        
        return true;
    }

    /**
     * Process pending drip campaign messages (called by cron)
     */
    public function processDripCampaigns($line)
    {
        $stmt = $this->db->query("SELECT p.*, c.name as campaign_name, u.line_user_id 
                                  FROM drip_campaign_progress p 
                                  JOIN drip_campaigns c ON p.campaign_id = c.id 
                                  JOIN users u ON p.user_id = u.id 
                                  WHERE p.status = 'active' 
                                  AND p.next_send_at <= NOW() 
                                  AND c.is_active = 1 
                                  LIMIT 50");
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sent = 0;
        foreach ($pending as $progress) {
            $nextStep = $progress['current_step'] + 1;
            
            // Get step content
            $stmt = $this->db->prepare("SELECT * FROM drip_campaign_steps WHERE campaign_id = ? AND step_order = ?");
            $stmt->execute([$progress['campaign_id'], $nextStep]);
            $step = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$step) {
                // No more steps, mark as completed
                $stmt = $this->db->prepare("UPDATE drip_campaign_progress SET status = 'completed', completed_at = NOW() WHERE id = ?");
                $stmt->execute([$progress['id']]);
                continue;
            }

            // Check conditions if any
            if (!empty($step['condition_rules'])) {
                $conditions = json_decode($step['condition_rules'], true);
                if (!$this->checkStepConditions($progress['user_id'], $conditions)) {
                    // Skip this step
                    $this->advanceToNextStep($progress['id'], $progress['campaign_id'], $nextStep);
                    continue;
                }
            }

            // Send message
            $message = $this->buildDripMessage($step);
            $result = $line->pushMessage($progress['line_user_id'], $message);

            if ($result['code'] === 200) {
                $sent++;
                $this->advanceToNextStep($progress['id'], $progress['campaign_id'], $nextStep);
            }
        }

        return $sent;
    }

    private function advanceToNextStep($progressId, $campaignId, $currentStep)
    {
        // Get next step delay
        $stmt = $this->db->prepare("SELECT delay_minutes FROM drip_campaign_steps WHERE campaign_id = ? AND step_order = ?");
        $stmt->execute([$campaignId, $currentStep + 1]);
        $nextStep = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($nextStep) {
            $nextSendAt = date('Y-m-d H:i:s', strtotime("+{$nextStep['delay_minutes']} minutes"));
            $stmt = $this->db->prepare("UPDATE drip_campaign_progress SET current_step = ?, next_send_at = ? WHERE id = ?");
            $stmt->execute([$currentStep, $nextSendAt, $progressId]);
        } else {
            // No more steps
            $stmt = $this->db->prepare("UPDATE drip_campaign_progress SET current_step = ?, status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$currentStep, $progressId]);
        }
    }

    private function checkStepConditions($userId, $conditions)
    {
        // Example conditions: has_purchased, has_tag, etc.
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'has_purchased':
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_behaviors WHERE user_id = ? AND behavior_type = 'purchase'");
                    $stmt->execute([$userId]);
                    if (($stmt->fetchColumn() > 0) !== $value) return false;
                    break;
                    
                case 'has_tag':
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_tag_assignments a JOIN user_tags t ON a.tag_id = t.id WHERE a.user_id = ? AND t.name = ?");
                    $stmt->execute([$userId, $value]);
                    if ($stmt->fetchColumn() == 0) return false;
                    break;
            }
        }
        return true;
    }

    private function buildDripMessage($step)
    {
        if ($step['message_type'] === 'flex') {
            $content = json_decode($step['message_content'], true);
            return ['type' => 'flex', 'altText' => 'ข้อความ', 'contents' => $content];
        }
        return ['type' => 'text', 'text' => $step['message_content']];
    }

    // =============================================
    // Trigger Campaigns Based on Events
    // =============================================

    /**
     * Trigger campaigns when user follows
     */
    public function onUserFollow($userId)
    {
        // Auto-assign "New Customer" tag
        $stmt = $this->db->prepare("SELECT id FROM user_tags WHERE name = 'New Customer' AND (line_account_id = ? OR line_account_id IS NULL) LIMIT 1");
        $stmt->execute([$this->lineAccountId]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tag) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by) VALUES (?, ?, 'auto')");
            $stmt->execute([$userId, $tag['id']]);
        }

        // Enroll in "follow" trigger campaigns
        $stmt = $this->db->prepare("SELECT id FROM drip_campaigns WHERE trigger_type = 'follow' AND is_active = 1 AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($campaigns as $campaign) {
            $this->enrollUserInCampaign($userId, $campaign['id']);
        }
    }

    /**
     * Trigger campaigns when user makes a purchase
     */
    public function onUserPurchase($userId, $amount)
    {
        // Check for VIP upgrade
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_behaviors WHERE user_id = ? AND behavior_type = 'purchase'");
        $stmt->execute([$userId]);
        $purchaseCount = $stmt->fetchColumn();

        if ($purchaseCount >= 5) {
            $stmt = $this->db->prepare("SELECT id FROM user_tags WHERE name = 'VIP' AND (line_account_id = ? OR line_account_id IS NULL) LIMIT 1");
            $stmt->execute([$this->lineAccountId]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($tag) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by) VALUES (?, ?, 'auto')");
                $stmt->execute([$userId, $tag['id']]);
            }
        }

        // Enroll in "purchase" trigger campaigns
        $stmt = $this->db->prepare("SELECT id FROM drip_campaigns WHERE trigger_type = 'purchase' AND is_active = 1 AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($campaigns as $campaign) {
            $this->enrollUserInCampaign($userId, $campaign['id']);
        }
    }

    /**
     * Check for inactive users (called by cron)
     */
    public function checkInactiveUsers($inactiveDays = 30)
    {
        // Find users with no recent activity
        $stmt = $this->db->prepare("SELECT u.id FROM users u 
                                    WHERE u.line_account_id = ? 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM user_behaviors b 
                                        WHERE b.user_id = u.id 
                                        AND b.created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                                    )
                                    AND NOT EXISTS (
                                        SELECT 1 FROM messages m 
                                        WHERE m.user_id = u.id 
                                        AND m.created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                                    )");
        $stmt->execute([$this->lineAccountId, $inactiveDays, $inactiveDays]);
        $inactiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get "Inactive" tag
        $stmt = $this->db->prepare("SELECT id FROM user_tags WHERE name = 'Inactive' AND (line_account_id = ? OR line_account_id IS NULL) LIMIT 1");
        $stmt->execute([$this->lineAccountId]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tag) {
            foreach ($inactiveUsers as $user) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO user_tag_assignments (user_id, tag_id, assigned_by) VALUES (?, ?, 'auto')");
                $stmt->execute([$user['id'], $tag['id']]);
            }
        }

        // Enroll in "inactivity" campaigns
        $stmt = $this->db->prepare("SELECT id FROM drip_campaigns WHERE trigger_type = 'inactivity' AND is_active = 1 AND (line_account_id = ? OR line_account_id IS NULL)");
        $stmt->execute([$this->lineAccountId]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($campaigns as $campaign) {
            foreach ($inactiveUsers as $user) {
                $this->enrollUserInCampaign($user['id'], $campaign['id']);
            }
        }

        return count($inactiveUsers);
    }

    // =============================================
    // Analytics & Reports
    // =============================================

    public function getUserEngagementStats($days = 30)
    {
        $stats = [];

        // Active users
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) FROM user_behaviors WHERE line_account_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$this->lineAccountId, $days]);
        $stats['active_users'] = $stmt->fetchColumn();

        // New users
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE line_account_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$this->lineAccountId, $days]);
        $stats['new_users'] = $stmt->fetchColumn();

        // Purchases
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_behaviors WHERE line_account_id = ? AND behavior_type = 'purchase' AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$this->lineAccountId, $days]);
        $stats['purchases'] = $stmt->fetchColumn();

        // Top behaviors
        $stmt = $this->db->prepare("SELECT behavior_type, COUNT(*) as count FROM user_behaviors WHERE line_account_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY behavior_type ORDER BY count DESC LIMIT 10");
        $stmt->execute([$this->lineAccountId, $days]);
        $stats['top_behaviors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }
}
