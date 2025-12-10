<?php
/**
 * Generate CSV files with variable number of product records.
 * 
 * Usage: php generate-products-csv.php [records]
 * Example: php generate-products-csv.php 2000
 */

// Get number of records from command line argument
$totalProducts = isset($argv[1]) ? (int)$argv[1] : 1000;

// Validate input
if ($totalProducts < 1 || $totalProducts > 100000) {
    die("ERROR: Number of records must be between 1 and 100,000\n");
}

// Configuration
$outputFile = __DIR__ . "/public/test_products_{$totalProducts}.csv";
$imageFolder = 'sample-images';

// Available sample images (assuming 200 images available)
$availableImages = 200;

// Product name components
$adjectives = [
    'Premium', 'Professional', 'Ultra', 'Advanced', 'Smart', 'Deluxe', 
    'Essential', 'Pro', 'Super', 'Basic', 'Elite', 'Classic', 'Modern',
    'Digital', 'Wireless', 'Compact', 'Portable', 'Heavy-Duty', 'Industrial',
    'Commercial', 'Eco-Friendly', 'Innovative', 'High-Performance', 'Luxury'
];

$nouns = [
    'Widget', 'Gadget', 'Tool', 'Device', 'Component', 'Module', 'System',
    'Apparatus', 'Instrument', 'Equipment', 'Kit', 'Unit', 'Machine',
    'Controller', 'Sensor', 'Monitor', 'Adapter', 'Connector', 'Cable',
    'Panel', 'Display', 'Interface', 'Terminal', 'Hub', 'Switch'
];

$suffixes = [
    'Pro', 'Plus', 'Max', 'Ultra', 'Mini', 'Lite', 'X', 'Z', 'A', 'B', 'C',
    '2000', '3000', '5000', 'XL', 'XS', 'HD', 'Elite', 'Premium', 'Standard'
];

$descriptions = [
    'High quality %s for professional use',
    'Perfect %s for everyday tasks',
    'State-of-the-art %s with modern design',
    'Reliable %s with advanced features',
    'Durable %s built to last',
    'Innovative %s with cutting-edge technology',
    'Cost-effective %s for budget-conscious buyers',
    'Premium %s with excellent performance',
    'Versatile %s suitable for multiple applications',
    'Compact %s with powerful capabilities'
];

// Open output file
$handle = fopen($outputFile, 'w');

if ($handle === false) {
    die("ERROR: Could not open file for writing: $outputFile\n");
}

// Write CSV header
fputcsv($handle, ['sku', 'name', 'price', 'description', 'stock', 'image_path']);

// Calculate progress intervals
$progressInterval = max(100, (int)($totalProducts / 10));

echo "Generating $totalProducts products...\n";
$startTime = microtime(true);

for ($i = 1; $i <= $totalProducts; $i++) {
    // Generate SKU
    $sku = sprintf('TEST%05d', $i);
    
    // Generate product name
    $adjective = $adjectives[array_rand($adjectives)];
    $noun = $nouns[array_rand($nouns)];
    $suffix = $suffixes[array_rand($suffixes)];
    $name = "$adjective $noun $suffix";
    
    // Generate price (between $10 and $999)
    $price = number_format(mt_rand(1000, 99900) / 100, 2, '.', '');
    
    // Generate description
    $descTemplate = $descriptions[array_rand($descriptions)];
    $description = sprintf($descTemplate, strtolower($noun));
    
    // Generate stock (between 0 and 1000)
    $stock = mt_rand(0, 1000);
    
    // Assign random image from available sample images
    $imageNum = str_pad(mt_rand(1, $availableImages), 3, '0', STR_PAD_LEFT);
    $imagePath = "$imageFolder/product-$imageNum.jpg";
    
    // Write row to CSV
    fputcsv($handle, [
        $sku,
        $name,
        $price,
        $description,
        $stock,
        $imagePath
    ]);
    
    // Progress indicator
    if ($i % $progressInterval === 0) {
        $percent = round(($i / $totalProducts) * 100);
        echo "Generated $i products ($percent%)...\n";
    }
}

// Close file
fclose($handle);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Get file size
$fileSize = filesize($outputFile);
$fileSizeKB = number_format($fileSize / 1024, 2);
$fileSizeMB = number_format($fileSize / (1024 * 1024), 2);

echo "\n✅ SUCCESS!\n";
echo "========================================\n";
echo "File: $outputFile\n";
echo "Total products: $totalProducts\n";
echo "File size: $fileSizeKB KB ($fileSizeMB MB)\n";
echo "Generation time: {$duration}s\n";
echo "========================================\n";
echo "\nYou can now import this file at:\n";
echo "http://localhost:8000/test\n";
echo "\nOr via command line:\n";
echo "curl -X POST http://localhost:8000/api/products/import \\\n";
echo "  -F \"csv_file=@public/test_products_{$totalProducts}.csv\"\n";
