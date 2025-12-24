<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Analytics\AnalyticsModel;

/**
 * Analytics Controller
 */
class AnalyticsController extends Controller
{
    private AnalyticsModel $analytics;
    
    public function __construct()
    {
        parent::__construct();
        $this->analytics = new AnalyticsModel($this->lineAccountId);
    }
    
    /**
     * Dashboard overview
     */
    public function dashboard(): void
    {
        $period = $_GET['period'] ?? '7d';
        
        $data = [
            'stats' => $this->analytics->getDashboardStats($period),
            'realtime' => $this->analytics->getRealTimeStats(),
            'period' => $period,
            'csrf_token' => $this->generateCsrf()
        ];
        
        // Output view content (header/footer handled by entry point)
        echo $this->view('analytics.dashboard', $data);
    }
    
    /**
     * Render dashboard as standalone (with header/footer)
     */
    public function renderFull(): string
    {
        $period = $_GET['period'] ?? '7d';
        
        $data = [
            'stats' => $this->analytics->getDashboardStats($period),
            'realtime' => $this->analytics->getRealTimeStats(),
            'period' => $period,
            'csrf_token' => $this->generateCsrf()
        ];
        
        return $this->view('analytics.dashboard', $data);
    }
    
    /**
     * API: Get dashboard stats (for AJAX refresh)
     */
    public function apiStats(): void
    {
        $period = $_GET['period'] ?? '7d';
        
        $this->json([
            'success' => true,
            'data' => $this->analytics->getDashboardStats($period)
        ]);
    }
    
    /**
     * API: Get real-time stats
     */
    public function apiRealtime(): void
    {
        $this->json([
            'success' => true,
            'data' => $this->analytics->getRealTimeStats()
        ]);
    }
    
    /**
     * API: Get funnel data
     */
    public function apiFunnel(): void
    {
        $period = $_GET['period'] ?? '7d';
        $dateRange = $this->getDateRange($period);
        
        $this->json([
            'success' => true,
            'data' => $this->analytics->getCustomerFunnel($dateRange)
        ]);
    }
    
    /**
     * Export analytics to CSV
     */
    public function export(): void
    {
        $period = $_GET['period'] ?? '7d';
        $type = $_GET['type'] ?? 'overview';
        
        $stats = $this->analytics->getDashboardStats($period);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, ['Metric', 'Value', 'Period']);
        
        // Data
        fputcsv($output, ['Total Users', $stats['users']['total'], $period]);
        fputcsv($output, ['New Users', $stats['users']['new'], $period]);
        fputcsv($output, ['Active Users', $stats['users']['active'], $period]);
        fputcsv($output, ['Total Messages', $stats['messages']['total'], $period]);
        fputcsv($output, ['Total Orders', $stats['orders']['total'], $period]);
        fputcsv($output, ['Revenue', $stats['revenue']['total'], $period]);
        
        fclose($output);
        exit;
    }
    
    private function getDateRange(string $period): array
    {
        $end = date('Y-m-d 23:59:59');
        $days = match($period) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7
        };
        $start = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        return ['start' => $start, 'end' => $end];
    }
}
