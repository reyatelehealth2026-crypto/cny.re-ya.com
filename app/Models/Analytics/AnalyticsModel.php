<?php
namespace App\Models\Analytics;

use App\Core\Database;
use PDO;

/**
 * Analytics Model - ดึงข้อมูลสถิติต่างๆ
 */
class AnalyticsModel
{
    private PDO $db;
    private ?int $lineAccountId;
    
    public function __construct(?int $lineAccountId = null)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Get dashboard overview stats
     */
    public function getDashboardStats(string $period = '7d'): array
    {
        $dateRange = $this->getDateRange($period);
        
        return [
            'users' => $this->getUserStats($dateRange),
            'messages' => $this->getMessageStats($dateRange),
            'orders' => $this->getOrderStats($dateRange),
            'revenue' => $this->getRevenueStats($dateRange),
            'engagement' => $this->getEngagementStats($dateRange),
        ];
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = $this->lineAccountId ? [$dateRange['start'], $dateRange['end'], $this->lineAccountId] : [$dateRange['start'], $dateRange['end']];
        
        // Total users
        $sql = "SELECT COUNT(*) FROM users WHERE 1=1 " . ($this->lineAccountId ? "AND line_account_id = ?" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->lineAccountId ? [$this->lineAccountId] : []);
        $total = $stmt->fetchColumn();
        
        // New users in period
        $sql = "SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $newUsers = $stmt->fetchColumn();
        
        // Active users (sent message in period)
        $sql = "SELECT COUNT(DISTINCT user_id) FROM messages 
                WHERE direction = 'incoming' AND created_at BETWEEN ? AND ? 
                " . ($this->lineAccountId ? "AND line_account_id = ?" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $activeUsers = $stmt->fetchColumn();
        
        // Daily breakdown
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM users WHERE created_at BETWEEN ? AND ? {$accountFilter}
                GROUP BY DATE(created_at) ORDER BY date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $daily = $stmt->fetchAll();
        
        return [
            'total' => (int)$total,
            'new' => (int)$newUsers,
            'active' => (int)$activeUsers,
            'growth_rate' => $total > 0 ? round(($newUsers / $total) * 100, 1) : 0,
            'daily' => $daily
        ];
    }
    
    /**
     * Get message statistics
     */
    public function getMessageStats(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($this->lineAccountId) $params[] = $this->lineAccountId;
        
        // Total messages
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as incoming,
                    SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as outgoing
                FROM messages WHERE created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch();
        
        // Messages by type
        $sql = "SELECT message_type, COUNT(*) as count 
                FROM messages WHERE created_at BETWEEN ? AND ? {$accountFilter}
                GROUP BY message_type ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $byType = $stmt->fetchAll();
        
        // Hourly distribution
        $sql = "SELECT HOUR(created_at) as hour, COUNT(*) as count 
                FROM messages WHERE direction = 'incoming' AND created_at BETWEEN ? AND ? {$accountFilter}
                GROUP BY HOUR(created_at) ORDER BY hour";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $hourly = $stmt->fetchAll();
        
        // Response rate (messages replied within 5 minutes)
        $sql = "SELECT COUNT(DISTINCT m1.user_id) as responded
                FROM messages m1
                INNER JOIN messages m2 ON m1.user_id = m2.user_id 
                    AND m2.direction = 'outgoing' 
                    AND m2.created_at BETWEEN m1.created_at AND DATE_ADD(m1.created_at, INTERVAL 5 MINUTE)
                WHERE m1.direction = 'incoming' AND m1.created_at BETWEEN ? AND ? 
                " . ($this->lineAccountId ? "AND m1.line_account_id = ?" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $responded = $stmt->fetchColumn();
        
        return [
            'total' => (int)($stats['total'] ?? 0),
            'incoming' => (int)($stats['incoming'] ?? 0),
            'outgoing' => (int)($stats['outgoing'] ?? 0),
            'by_type' => $byType,
            'hourly' => $hourly,
            'response_rate' => $stats['incoming'] > 0 ? round(($responded / $stats['incoming']) * 100, 1) : 0
        ];
    }
    
    /**
     * Get order statistics
     */
    public function getOrderStats(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($this->lineAccountId) $params[] = $this->lineAccountId;
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(grand_total) as total_revenue,
                    AVG(grand_total) as avg_order_value
                FROM orders WHERE created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch();
        
        // Daily orders
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(grand_total) as revenue
                FROM orders WHERE created_at BETWEEN ? AND ? {$accountFilter}
                GROUP BY DATE(created_at) ORDER BY date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $daily = $stmt->fetchAll();
        
        return [
            'total' => (int)($stats['total'] ?? 0),
            'pending' => (int)($stats['pending'] ?? 0),
            'paid' => (int)($stats['paid'] ?? 0),
            'delivered' => (int)($stats['delivered'] ?? 0),
            'cancelled' => (int)($stats['cancelled'] ?? 0),
            'conversion_rate' => $stats['total'] > 0 ? round((($stats['paid'] + $stats['delivered']) / $stats['total']) * 100, 1) : 0,
            'avg_order_value' => round($stats['avg_order_value'] ?? 0, 2),
            'daily' => $daily
        ];
    }
    
    /**
     * Get revenue statistics
     */
    public function getRevenueStats(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($this->lineAccountId) $params[] = $this->lineAccountId;
        
        // Total revenue (paid orders only)
        $sql = "SELECT SUM(grand_total) as total FROM orders 
                WHERE payment_status = 'paid' AND created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn() ?: 0;
        
        // Previous period for comparison
        $prevRange = $this->getPreviousDateRange($dateRange);
        $prevParams = [$prevRange['start'], $prevRange['end']];
        if ($this->lineAccountId) $prevParams[] = $this->lineAccountId;
        
        $stmt->execute($prevParams);
        $prevTotal = $stmt->fetchColumn() ?: 0;
        
        // Growth
        $growth = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : 0;
        
        // Top products
        $sql = "SELECT oi.product_name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as revenue
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.payment_status = 'paid' AND o.created_at BETWEEN ? AND ? 
                " . ($this->lineAccountId ? "AND o.line_account_id = ?" : "") . "
                GROUP BY oi.product_name ORDER BY revenue DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $topProducts = $stmt->fetchAll();
        
        return [
            'total' => round($total, 2),
            'previous' => round($prevTotal, 2),
            'growth' => $growth,
            'top_products' => $topProducts
        ];
    }
    
    /**
     * Get engagement statistics
     */
    public function getEngagementStats(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($this->lineAccountId) $params[] = $this->lineAccountId;
        
        // Click-through rate from broadcasts
        $sql = "SELECT 
                    bc.name,
                    bc.sent_count,
                    COUNT(DISTINCT bcl.user_id) as unique_clicks,
                    ROUND(COUNT(DISTINCT bcl.user_id) / NULLIF(bc.sent_count, 0) * 100, 1) as ctr
                FROM broadcast_campaigns bc
                LEFT JOIN broadcast_clicks bcl ON bc.id = bcl.broadcast_id
                WHERE bc.sent_at BETWEEN ? AND ? 
                " . ($this->lineAccountId ? "AND bc.line_account_id = ?" : "") . "
                GROUP BY bc.id ORDER BY bc.sent_at DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $broadcasts = $stmt->fetchAll();
        
        // Link clicks
        $sql = "SELECT tl.title, tl.click_count, tl.unique_clicks
                FROM tracked_links tl
                WHERE tl.created_at BETWEEN ? AND ? 
                " . ($this->lineAccountId ? "AND tl.line_account_id = ?" : "") . "
                ORDER BY tl.click_count DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $links = $stmt->fetchAll();
        
        return [
            'broadcasts' => $broadcasts,
            'links' => $links
        ];
    }
    
    /**
     * Get real-time stats (last 24 hours)
     */
    public function getRealTimeStats(): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = $this->lineAccountId ? [$this->lineAccountId] : [];
        
        // Active users in last hour
        $sql = "SELECT COUNT(DISTINCT user_id) FROM messages 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $activeNow = $stmt->fetchColumn();
        
        // Messages in last hour
        $sql = "SELECT COUNT(*) FROM messages 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $messagesNow = $stmt->fetchColumn();
        
        // Orders today
        $sql = "SELECT COUNT(*), SUM(grand_total) FROM orders 
                WHERE DATE(created_at) = CURDATE() {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ordersToday = $stmt->fetch(PDO::FETCH_NUM);
        
        return [
            'active_users' => (int)$activeNow,
            'messages_per_hour' => (int)$messagesNow,
            'orders_today' => (int)($ordersToday[0] ?? 0),
            'revenue_today' => round($ordersToday[1] ?? 0, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get customer funnel data
     */
    public function getCustomerFunnel(array $dateRange): array
    {
        $accountFilter = $this->lineAccountId ? "AND line_account_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($this->lineAccountId) $params[] = $this->lineAccountId;
        
        // Visitors (new followers)
        $sql = "SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $visitors = $stmt->fetchColumn();
        
        // Engaged (sent at least 1 message)
        $sql = "SELECT COUNT(DISTINCT u.id) FROM users u
                INNER JOIN messages m ON u.id = m.user_id AND m.direction = 'incoming'
                WHERE u.created_at BETWEEN ? AND ? " . ($this->lineAccountId ? "AND u.line_account_id = ?" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $engaged = $stmt->fetchColumn();
        
        // Added to cart
        $sql = "SELECT COUNT(DISTINCT user_id) FROM cart_items 
                WHERE created_at BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateRange['start'], $dateRange['end']]);
        $addedToCart = $stmt->fetchColumn();
        
        // Purchased
        $sql = "SELECT COUNT(DISTINCT user_id) FROM orders 
                WHERE created_at BETWEEN ? AND ? {$accountFilter}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $purchased = $stmt->fetchColumn();
        
        return [
            ['stage' => 'ผู้ติดตามใหม่', 'count' => (int)$visitors, 'rate' => 100],
            ['stage' => 'มีการสนทนา', 'count' => (int)$engaged, 'rate' => $visitors > 0 ? round(($engaged / $visitors) * 100, 1) : 0],
            ['stage' => 'เพิ่มตะกร้า', 'count' => (int)$addedToCart, 'rate' => $visitors > 0 ? round(($addedToCart / $visitors) * 100, 1) : 0],
            ['stage' => 'สั่งซื้อ', 'count' => (int)$purchased, 'rate' => $visitors > 0 ? round(($purchased / $visitors) * 100, 1) : 0],
        ];
    }
    
    /**
     * Helper: Get date range from period string
     */
    private function getDateRange(string $period): array
    {
        $end = date('Y-m-d 23:59:59');
        
        switch ($period) {
            case '24h':
                $start = date('Y-m-d H:i:s', strtotime('-24 hours'));
                break;
            case '7d':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case '30d':
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case '90d':
                $start = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
            case 'year':
                $start = date('Y-01-01 00:00:00');
                break;
            default:
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
        }
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Helper: Get previous period for comparison
     */
    private function getPreviousDateRange(array $currentRange): array
    {
        $diff = strtotime($currentRange['end']) - strtotime($currentRange['start']);
        
        return [
            'start' => date('Y-m-d H:i:s', strtotime($currentRange['start']) - $diff),
            'end' => date('Y-m-d H:i:s', strtotime($currentRange['start']) - 1)
        ];
    }
}
