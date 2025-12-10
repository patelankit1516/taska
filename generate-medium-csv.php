<?php

/**
 * Generate Medium-Sized Mock CSV Data
 * Creates 100 products with images for quick testing
 */

$targetRows = 100;
$outputFile = __DIR__ . '/public/medium_products.csv';

// Sample data
$adjectives = ['Essential', 'Premium', 'Pro', 'Deluxe', 'Basic', 'Advanced', 'Smart', 'Ultra', 'Super', 'Professional'];
$categories = ['Widget', 'Gadget', 'Device', 'Tool', 'Kit', 'System', 'Unit', 'Module', 'Component', 'Apparatus'];
$variants = ['X', 'Pro', 'Plus', 'Mini', 'Max', 'Ultra', 'A', 'B', 'C', 'Z'];
$descriptions = [
    'High quality {product} for professional use',
    'Reliable {product} with advanced features',
    'Perfect {product} for everyday tasks',
    'State-of-the-art {product} with modern design',
];

echo "🏭 Generating Medium Mock CSV Data\n";
echo str_repeat("━", 60) . "\n\n";
echo "Target rows: $targetRows\n";
echo "Output file: $outputFile\n\n";

$startTime = microtime(true);

// Open file for writing
$handle = fopen($outputFile, 'w');
if (!$handle) {
    die("❌ Error: Could not create file $outputFile\n");
}

// Write header
fputcsv($handle, ['sku', 'name', 'price', 'description', 'stock', 'image_path']);

$generatedCount = 0;

// Generate products
for ($i = 1; $i <= $targetRows; $i++) {
    $adj = $adjectives[array_rand($adjectives)];
    $cat = $categories[array_rand($categories)];
    $var = $variants[array_rand($variants)];
    
    $productName = "$adj $cat $var";
    $description = str_replace('{product}', strtolower($cat), $descriptions[array_rand($descriptions)]);
    
    // Generate SKU
    $sku = sprintf('MED%04d', $i);
    
    // Vary prices realistically
    $priceBase = rand(999, 99999) / 100; // $9.99 to $999.99
    $price = number_format($priceBase, 2, '.', '');
    
    // Vary stock levels
    $stock = rand(0, 1000);
    
    // Random image path - reference actual sample images
    $imageNum = rand(1, 200);
    $imagePath = "sample-images/product-" . str_pad($imageNum, 3, '0', STR_PAD_LEFT) . ".jpg";
    
    // Write row
    fputcsv($handle, [$sku, $productName, $price, $description, $stock, $imagePath]);
    $generatedCount++;
    
    // Show progress
    if ($i % 10 == 0) {
        $percent = ($i / $targetRows) * 100;
        $rate = $i / (microtime(true) - $startTime);
        echo sprintf("\r⏳ Progress: %d/%d (%.1f%%) - %.0f rows/sec", $i, $targetRows, $percent, $rate);
    }
}

fclose($handle);

$elapsed = microtime(true) - $startTime;
$fileSize = filesize($outputFile);
$fileSizeMB = $fileSize / (1024 * 1024);

echo "\n\n" . str_repeat("━", 60) . "\n";
echo "✅ CSV Generation Complete!\n\n";

echo "📊 Statistics:\n";
echo "   Total rows generated: $generatedCount\n";
echo "   Time elapsed: " . number_format($elapsed, 2) . " seconds\n";
echo "   Generation rate: " . number_format($generatedCount / $elapsed, 0) . " rows/sec\n";
echo "   File size: " . number_format($fileSizeMB, 2) . " MB\n\n";

echo "📁 File location: public/medium_products.csv\n";
echo "🌐 Download URL: http://127.0.0.1:8000/medium_products.csv\n\n";

echo "🧪 Testing Features:\n";
echo "   ✓ 100 products for quick import testing\n";
echo "   ✓ All products have images\n";
echo "   ✓ Realistic data (names, prices, stock, descriptions)\n";
echo "   ✓ CSV properly formatted with headers\n\n";

echo "💡 Usage:\n";
echo "   1. Start server: php artisan serve\n";
echo "   2. Open test page: http://127.0.0.1:8000/test\n";
echo "   3. Upload the CSV file\n";
echo "   4. Watch import process with automatic image processing!\n\n";

echo "⚡ Expected Import Time: ~1-2 minutes\n";
echo "   (100 products × ~100 unique images × 4 variants each)\n\n";

echo "🎉 Ready for testing!\n";
