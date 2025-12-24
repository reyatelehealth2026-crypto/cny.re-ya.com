<?php
namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    protected Database $db;
    protected ?int $lineAccountId;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->lineAccountId = $_SESSION['current_bot_id'] ?? null;
    }
    
    /**
     * Render view with data
     */
    protected function view(string $view, array $data = []): string
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    
    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    
    /**
     * Generate CSRF token
     */
    protected function generateCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
