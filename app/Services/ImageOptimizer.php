<?php
namespace App\Services;

/**
 * Image Optimizer Service
 * - Resize images
 * - Compress images
 * - Generate thumbnails
 * - WebP conversion
 */
class ImageOptimizer
{
    private string $uploadDir;
    private int $maxWidth = 1200;
    private int $maxHeight = 1200;
    private int $quality = 85;
    private int $thumbnailSize = 300;
    
    public function __construct(string $uploadDir = 'uploads/')
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
    }
    
    /**
     * Process uploaded image
     */
    public function process(string $sourcePath, array $options = []): array
    {
        $maxWidth = $options['max_width'] ?? $this->maxWidth;
        $maxHeight = $options['max_height'] ?? $this->maxHeight;
        $quality = $options['quality'] ?? $this->quality;
        $generateThumbnail = $options['thumbnail'] ?? true;
        $convertWebP = $options['webp'] ?? true;
        
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }
        
        $mimeType = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Create image resource
        $source = $this->createImageFromFile($sourcePath, $mimeType);
        if (!$source) {
            throw new \Exception('Failed to create image resource');
        }
        
        // Calculate new dimensions
        $newDimensions = $this->calculateDimensions($width, $height, $maxWidth, $maxHeight);
        
        // Resize if needed
        if ($newDimensions['width'] !== $width || $newDimensions['height'] !== $height) {
            $resized = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled(
                $resized, $source,
                0, 0, 0, 0,
                $newDimensions['width'], $newDimensions['height'],
                $width, $height
            );
            
            imagedestroy($source);
            $source = $resized;
        }
        
        // Generate filename
        $filename = $this->generateFilename();
        $results = [];
        
        // Save optimized image
        $optimizedPath = $this->uploadDir . $filename . '.jpg';
        imagejpeg($source, $optimizedPath, $quality);
        $results['optimized'] = $optimizedPath;
        $results['size'] = filesize($optimizedPath);
        
        // Generate WebP version
        if ($convertWebP && function_exists('imagewebp')) {
            $webpPath = $this->uploadDir . $filename . '.webp';
            imagewebp($source, $webpPath, $quality);
            $results['webp'] = $webpPath;
            $results['webp_size'] = filesize($webpPath);
        }
        
        // Generate thumbnail
        if ($generateThumbnail) {
            $thumbDimensions = $this->calculateDimensions(
                $newDimensions['width'], 
                $newDimensions['height'], 
                $this->thumbnailSize, 
                $this->thumbnailSize
            );
            
            $thumbnail = imagecreatetruecolor($thumbDimensions['width'], $thumbDimensions['height']);
            imagecopyresampled(
                $thumbnail, $source,
                0, 0, 0, 0,
                $thumbDimensions['width'], $thumbDimensions['height'],
                $newDimensions['width'], $newDimensions['height']
            );
            
            $thumbPath = $this->uploadDir . $filename . '_thumb.jpg';
            imagejpeg($thumbnail, $thumbPath, $quality);
            imagedestroy($thumbnail);
            
            $results['thumbnail'] = $thumbPath;
        }
        
        imagedestroy($source);
        
        $results['filename'] = $filename;
        $results['width'] = $newDimensions['width'];
        $results['height'] = $newDimensions['height'];
        
        return $results;
    }
    
    /**
     * Generate responsive image srcset
     */
    public function generateSrcset(string $imagePath): string
    {
        $pathInfo = pathinfo($imagePath);
        $baseName = $pathInfo['filename'];
        $dir = $pathInfo['dirname'];
        
        $sizes = [320, 640, 960, 1200];
        $srcset = [];
        
        foreach ($sizes as $size) {
            $resizedPath = "{$dir}/{$baseName}_{$size}w.jpg";
            if (file_exists($resizedPath)) {
                $srcset[] = "{$resizedPath} {$size}w";
            }
        }
        
        return implode(', ', $srcset);
    }
    
    /**
     * Get optimized image URL with WebP fallback
     */
    public function getOptimizedUrl(string $imagePath): array
    {
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        
        return [
            'original' => $imagePath,
            'webp' => file_exists($webpPath) ? $webpPath : null,
            'thumbnail' => $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.jpg'
        ];
    }
    
    /**
     * Create image resource from file
     */
    private function createImageFromFile(string $path, string $mimeType)
    {
        return match($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false
        };
    }
    
    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        if ($ratio >= 1) {
            return ['width' => $width, 'height' => $height];
        }
        
        return [
            'width' => (int)round($width * $ratio),
            'height' => (int)round($height * $ratio)
        ];
    }
    
    /**
     * Generate unique filename
     */
    private function generateFilename(): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    }
    
    /**
     * Clean up old/unused images
     */
    public function cleanup(int $daysOld = 30): int
    {
        $deleted = 0;
        $threshold = time() - ($daysOld * 86400);
        
        $files = glob($this->uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                // Check if file is referenced in database before deleting
                // This is a placeholder - implement actual check
                // unlink($file);
                // $deleted++;
            }
        }
        
        return $deleted;
    }
}
