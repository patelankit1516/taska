<?php
/**
 * Generate a CSV file with 1000 product records for testing.
 * 
 * Usage: php generate-1000-products.php
 */

// Configuration
$outputFile = __DIR__ . '/public/test_products_1000.csv';
$totalProducts = 1000;
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

// Generate products
echo "Generating $totalProducts products...\n";

for ($i = 1; $i <= $totalProducts; $i++) {
    // Generate SKU
    $sku = sprintf('TEST%04d', $i);
    
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
    if ($i % 100 === 0) {
        echo "Generated $i products...\n";
    }
}

// Close file
fclose($handle);

// Get file size
$fileSize = filesize($outputFile);
$fileSizeKB = number_format($fileSize / 1024, 2);

echo "\n✅ SUCCESS!\n";
echo "========================================\n";
echo "File: $outputFile\n";
echo "Total products: $totalProducts\n";
echo "File size: $fileSizeKB KB\n";
echo "========================================\n";
echo "\nYou can now import this file at:\n";
echo "http://localhost:8000/test\n";
echo "\nOr via command line:\n";
echo "curl -X POST http://localhost:8000/api/products/import \\\n";
echo "  -F \"csv_file=@public/test_products_1000.csv\"\n";
