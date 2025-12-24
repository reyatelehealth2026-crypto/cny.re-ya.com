<?php
namespace App\Core;

/**
 * PSR-4 Autoloader
 * โหลด class อัตโนมัติตาม namespace
 */
class Autoloader
{
    private array $namespaces = [];
    
    /**
     * Register autoloader
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Add namespace mapping
     */
    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        $this->namespaces[$prefix] = $baseDir;
    }
    
    /**
     * Load class file
     */
    public function loadClass(string $class): bool
    {
        // Try registered namespaces first
        foreach ($this->namespaces as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) === 0) {
                $relativeClass = substr($class, $len);
                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }
        
        // Fallback: App namespace from default location
        $prefix = 'App\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = __DIR__ . '/../' . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require $file;
                return true;
            }
        }
        
        return false;
    }
}
