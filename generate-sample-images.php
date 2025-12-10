<?php

/**
 * Generate Sample Product Images for Testing
 * 
 * This script creates sample product images that can be used
 * to test the chunked image upload functionality.
 * 
 * Updated to generate 200+ images as required by Task A.
 */

$outputDir = __DIR__ . '/public/sample-images';

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Generate 200 images with variety
$totalImages = 200;
$imageConfigs = [];

// Create varied image configurations
for ($i = 1; $i <= $totalImages; $i++) {
    $colors = [
        ['name' => 'Blue', 'rgb' => [65, 105, 225]],
        ['name' => 'Red', 'rgb' => [220, 20, 60]],
        ['name' => 'Green', 'rgb' => [50, 205, 50]],
        ['name' => 'Orange', 'rgb' => [255, 140, 0]],
        ['name' => 'Purple', 'rgb' => [147, 112, 219]],
        ['name' => 'Teal', 'rgb' => [0, 128, 128]],
        ['name' => 'Pink', 'rgb' => [255, 105, 180]],
        ['name' => 'Brown', 'rgb' => [139, 69, 19]],
        ['name' => 'Navy', 'rgb' => [0, 0, 128]],
        ['name' => 'Maroon', 'rgb' => [128, 0, 0]],
    ];
    
    $sizes = [
        ['w' => 800, 'h' => 600],
        ['w' => 1024, 'h' => 768],
        ['w' => 1200, 'h' => 900],
        ['w' => 1600, 'h' => 1200],
        ['w' => 1920, 'h' => 1080],
        ['w' => 2400, 'h' => 1800],
    ];
    
    $color = $colors[$i % count($colors)];
    $size = $sizes[$i % count($sizes)];
    
    $imageConfigs[] = [
        'name' => sprintf('product-%03d.jpg', $i),
        'color' => $color['rgb'],
        'text' => sprintf('Product %d', $i),
        'width' => $size['w'],
        'height' => $size['h'],
    ];
}

echo "🎨 Generating Sample Product Images...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Target: {$totalImages} images\n\n";

$startTime = microtime(true);
$totalSize = 0;

foreach ($imageConfigs as $index => $img) {
    $width = $img['width'];
    $height = $img['height'];
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Allocate colors
    $bgColor = imagecolorallocate($image, $img['color'][0], $img['color'][1], $img['color'][2]);
    $patternColor = imagecolorallocate(
        $image,
        min(255, $img['color'][0] + 40),
        min(255, $img['color'][1] + 40),
        min(255, $img['color'][2] + 40)
    );
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Add diagonal pattern
    for ($i = 0; $i < $width + $height; $i += 60) {
        imageline($image, $i, 0, 0, $i, $patternColor);
        imageline($image, $width, $i - $width, $i - $width, $height, $patternColor);
    }
    
    // Add product text (centered)
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($img['text']);
    $textHeight = imagefontheight($fontSize);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Text with shadow
    imagestring($image, $fontSize, $x + 3, $y + 3, $img['text'], $black);
    imagestring($image, $fontSize, $x, $y, $img['text'], $white);
    
    // Add dimensions label
    $label = $width . 'x' . $height . ' pixels';
    imagestring($image, 3, 20, $height - 30, $label, $white);
    imagestring($image, 3, 20, $height - 30 + 1, $label, $black);
    
    // Add "SAMPLE" watermark
    imagestring($image, 2, $width - 100, 20, 'SAMPLE IMAGE', $white);
    
    // Save with high quality
    $filepath = $outputDir . '/' . $img['name'];
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    // Get file size
    $filesize = filesize($filepath);
    $filesizeKB = round($filesize / 1024, 2);
    $totalSize += $filesize;
    
    // Progress indicator
    if (($index + 1) % 20 === 0 || $index === 0) {
        echo sprintf("✅ Generated %d/%d images (%.1f%%)...\n", 
            $index + 1,
            $totalImages,
            (($index + 1) / $totalImages) * 100
        );
    }
}

$elapsed = microtime(true) - $startTime;
$totalSizeMB = round($totalSize / 1024 / 1024, 2);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ All sample images created successfully!\n\n";
echo "📊 Statistics:\n";
echo "   Images generated: {$totalImages}\n";
echo "   Total size: {$totalSizeMB} MB\n";
echo "   Time elapsed: " . round($elapsed, 2) . " seconds\n";
echo "   Generation rate: " . round($totalImages / $elapsed, 1) . " images/sec\n";
echo "   Average size: " . round($totalSize / $totalImages / 1024, 1) . " KB/image\n\n";
echo "📁 Location: public/sample-images/\n";
echo "🌐 Access via: http://127.0.0.1:8000/sample-images/\n\n";
echo "💡 Use these images to test:\n";
echo "   - Chunked upload (files > 1MB will be chunked)\n";
echo "   - Image variant generation (4 sizes)\n";
echo "   - Product image attachment\n";
echo "   - Bulk upload with hundreds of images\n\n";
