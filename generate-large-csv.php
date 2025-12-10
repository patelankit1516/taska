<?php

/**
 * Generate Large Mock CSV for Bulk Import Testing
 * 
 * Creates a realistic CSV file with 10,000+ product records
 * to test bulk import performance and edge cases.
 * 
 * Requirements: Task A requires "large mock CSV data (≥ 10,000 rows)"
 */

// Configuration
$totalRows = 10000; // Meet minimum requirement
$outputFile = __DIR__ . '/public/large_products.csv';
$batchSize = 1000; // Write in batches for memory efficiency

// Product name components for realistic names
$adjectives = ['Premium', 'Professional', 'Advanced', 'Standard', 'Deluxe', 'Ultimate', 'Elite', 'Pro', 'Basic', 'Essential'];
$categories = ['Widget', 'Gadget', 'Tool', 'Device', 'Component', 'Module', 'System', 'Unit', 'Accessory', 'Kit'];
$variants = ['A', 'B', 'C', 'X', 'Y', 'Z', 'Plus', 'Max', 'Mini', 'Lite'];

// Description templates
$descriptions = [
    'High quality {product} for professional use',
    'Reliable {product} with advanced features',
    'Perfect {product} for everyday tasks',
    'Industry-standard {product} with premium build',
    'Compact and efficient {product} solution',
    'Durable {product} designed for longevity',
    'Innovative {product} with cutting-edge technology',
    'Versatile {product} suitable for multiple applications',
    'Cost-effective {product} without compromising quality',
    'State-of-the-art {product} with modern design',
];

echo "🏭 Generating Large Mock CSV Data\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Target rows: " . number_format($totalRows) . "\n";
echo "Output file: $outputFile\n\n";

// Open file for writing
$handle = fopen($outputFile, 'w');
if ($handle === false) {
    die("❌ Error: Could not create output file\n");
}

// Write CSV header
fputcsv($handle, ['sku', 'name', 'price', 'description', 'stock', 'image_path']);

$startTime = microtime(true);
$generatedCount = 0;
$duplicatesAdded = 0;
$invalidRowsAdded = 0;

// Generate products
for ($i = 1; $i <= $totalRows; $i++) {
    $adj = $adjectives[array_rand($adjectives)];
    $cat = $categories[array_rand($categories)];
    $var = $variants[array_rand($variants)];
    
    $productName = "$adj $cat $var";
    $description = str_replace('{product}', strtolower($cat), $descriptions[array_rand($descriptions)]);
    
    // Generate SKU
    $sku = sprintf('PROD%06d', $i);
    
    // Vary prices realistically
    $priceBase = rand(999, 99999) / 100; // $9.99 to $999.99
    $price = number_format($priceBase, 2, '.', '');
    
    // Vary stock levels
    $stock = rand(0, 1000);
    
    // Random image path - reference actual sample images
    $imageNum = rand(1, 200);
    $imagePath = "sample-images/product-" . str_pad($imageNum, 3, '0', STR_PAD_LEFT) . ".jpg";
    
    // Write normal row
    fputcsv($handle, [$sku, $productName, $price, $description, $stock, $imagePath]);
    $generatedCount++;
    
    // Add some duplicate SKUs (every 500th row) to test duplicate detection
    if ($i % 500 === 0 && $i < $totalRows) {
        $dupSku = sprintf('PROD%06d', $i); // Same SKU
        $dupName = "$adj $cat Duplicate";
        fputcsv($handle, [$dupSku, $dupName, $price, $description, $stock, $imagePath]);
        $duplicatesAdded++;
        $generatedCount++;
    }
    
    // Add some invalid rows (every 1000th row) to test error handling
    if ($i % 1000 === 0 && $i < $totalRows) {
        // Missing required fields
        fputcsv($handle, [sprintf('PROD%06d', $i + 100000), $productName, 'INVALID_PRICE', $description, 'INVALID_STOCK', $imagePath]);
        $invalidRowsAdded++;
        $generatedCount++;
    }
    
    // Progress indicator
    if ($i % $batchSize === 0) {
        $elapsed = microtime(true) - $startTime;
        $percent = ($i / $totalRows) * 100;
        $rate = $i / $elapsed;
        echo sprintf("\r⏳ Progress: %d/%d (%.1f%%) - %.0f rows/sec", 
            $i, $totalRows, $percent, $rate);
    }
}

fclose($handle);

$elapsed = microtime(true) - $startTime;
$filesize = filesize($outputFile);
$filesizeMB = round($filesize / 1024 / 1024, 2);

echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ CSV Generation Complete!\n\n";

echo "📊 Statistics:\n";
echo "   Total rows generated: " . number_format($generatedCount) . "\n";
echo "   Valid products: " . number_format($totalRows) . "\n";
echo "   Duplicate SKUs added: " . number_format($duplicatesAdded) . " (for testing)\n";
echo "   Invalid rows added: " . number_format($invalidRowsAdded) . " (for testing)\n";
echo "   Time elapsed: " . round($elapsed, 2) . " seconds\n";
echo "   Generation rate: " . number_format($generatedCount / $elapsed, 0) . " rows/sec\n";
echo "   File size: {$filesizeMB} MB\n\n";

echo "📁 File location: public/large_products.csv\n";
echo "🌐 Download URL: http://127.0.0.1:8000/large_products.csv\n\n";

echo "🧪 Testing Features:\n";
echo "   ✓ {$totalRows} unique products for bulk import testing\n";
echo "   ✓ {$duplicatesAdded} duplicate SKUs to test duplicate detection\n";
echo "   ✓ {$invalidRowsAdded} invalid rows to test error handling\n";
echo "   ✓ Realistic data (names, prices, stock, descriptions)\n";
echo "   ✓ CSV properly formatted with headers\n\n";

echo "💡 Usage:\n";
echo "   1. Start server: php artisan serve\n";
echo "   2. Open test page: http://127.0.0.1:8000/test\n";
echo "   3. Download: http://127.0.0.1:8000/large_products.csv\n";
echo "   4. Upload the CSV file\n";
echo "   5. Watch import process handle 10,000+ rows!\n\n";

echo "⚡ Expected Import Results:\n";
echo "   Total: " . number_format($generatedCount) . "\n";
echo "   Imported: ~" . number_format($totalRows) . "\n";
echo "   Duplicates: ~{$duplicatesAdded}\n";
echo "   Invalid: ~{$invalidRowsAdded}\n\n";

echo "🎉 Ready for testing!\n";
