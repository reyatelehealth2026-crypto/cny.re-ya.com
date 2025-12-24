<?php
/**
 * Create Placeholder Icons
 * Run this to create basic placeholder icons
 */

$sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];

foreach ($sizes as $size) {
    // Create image
    $img = imagecreatetruecolor($size, $size);
    
    // Colors
    $green = imagecolorallocate($img, 6, 199, 85);
    $white = imagecolorallocate($img, 255, 255, 255);
    
    // Fill background
    imagefill($img, 0, 0, $green);
    
    // Draw rounded corners (simple version)
    $radius = $size * 0.2;
    
    // Draw "L" text in center
    $fontSize = $size * 0.4;
    $text = "L";
    
    // Calculate position
    $bbox = imagettfbbox($fontSize, 0, __DIR__ . '/arial.ttf', $text);
    if ($bbox) {
        $x = ($size - ($bbox[2] - $bbox[0])) / 2;
        $y = ($size + ($bbox[1] - $bbox[7])) / 2;
        imagettftext($img, $fontSize, 0, $x, $y, $white, __DIR__ . '/arial.ttf', $text);
    } else {
        // Fallback without font
        $fontBuiltin = $size > 100 ? 5 : ($size > 50 ? 4 : 2);
        $textWidth = imagefontwidth($fontBuiltin) * strlen($text);
        $textHeight = imagefontheight($fontBuiltin);
        $x = ($size - $textWidth) / 2;
        $y = ($size - $textHeight) / 2;
        imagestring($img, $fontBuiltin, $x, $y, $text, $white);
    }
    
    // Save
    $filename = __DIR__ . "/icon-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);
    
    echo "Created: icon-{$size}x{$size}.png\n";
}

echo "\nDone! Placeholder icons created.\n";
echo "For production, please use proper icons from PWA Builder.\n";
