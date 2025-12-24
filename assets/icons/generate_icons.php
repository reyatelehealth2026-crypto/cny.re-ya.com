<?php
/**
 * Icon Generator for PWA
 * Run this script to generate PNG icons from SVG
 * Requires: GD library or ImageMagick
 * 
 * Usage: php generate_icons.php
 */

$sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
$svgFile = __DIR__ . '/icon.svg';

// Check if ImageMagick is available
if (class_exists('Imagick')) {
    echo "Using ImageMagick...\n";
    
    foreach ($sizes as $size) {
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->readImageBlob(file_get_contents($svgFile));
        $imagick->setImageFormat('png32');
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        $imagick->writeImage(__DIR__ . "/icon-{$size}x{$size}.png");
        echo "Generated: icon-{$size}x{$size}.png\n";
    }
    
    echo "Done!\n";
} else {
    echo "ImageMagick not available.\n";
    echo "Please generate icons manually using an online tool like:\n";
    echo "https://realfavicongenerator.net/\n";
    echo "or\n";
    echo "https://www.pwabuilder.com/imageGenerator\n";
    echo "\nRequired sizes: " . implode(', ', $sizes) . "\n";
}
