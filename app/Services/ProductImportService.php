<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductImportService
{
    private array $stats = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'invalid' => 0,
        'duplicates' => 0,
    ];

    private array $seenSkusInBatch = [];

    /**
     * Import products from CSV file.
     *
     * @param string $csvPath Path to the CSV file
     * @return array Import statistics
     */
    public function importFromCsv(string $csvPath): array
    {
        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            throw new \InvalidArgumentException("CSV file not found or not readable: {$csvPath}");
        }

        $this->resetStats();

        DB::transaction(function () use ($csvPath) {
            $handle = fopen($csvPath, 'r');
            
            if ($handle === false) {
                throw new \RuntimeException("Failed to open CSV file: {$csvPath}");
            }

            // Read header row
            $headers = fgetcsv($handle);
            
            if ($headers === false) {
                fclose($handle);
                throw new \RuntimeException("CSV file is empty or invalid");
            }

            // Normalize headers (trim and lowercase)
            $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

            // Process each row
            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $this->stats['total']++;

                // Create associative array from row
                if (count($row) !== count($headers)) {
                    Log::warning("Row {$rowNumber}: Column count mismatch", [
                        'expected' => count($headers),
                        'actual' => count($row),
                    ]);
                    $this->stats['invalid']++;
                    continue;
                }

                $data = array_combine($headers, $row);
                $this->processRow($data, $rowNumber);
            }

            fclose($handle);
        });

        return $this->stats;
    }

    /**
     * Process a single row from CSV.
     */
    private function processRow(array $data, int $rowNumber): void
    {
        // Validate required fields
        $validator = Validator::make($data, [
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning("Row {$rowNumber}: Validation failed", [
                'data' => $data,
                'errors' => $validator->errors()->toArray(),
            ]);
            $this->stats['invalid']++;
            return;
        }

        $sku = trim($data['sku']);

        // Check for duplicates within the same batch
        if (isset($this->seenSkusInBatch[$sku])) {
            $this->stats['duplicates']++;
            Log::info("Row {$rowNumber}: Duplicate SKU in batch: {$sku}");
            return;
        }

        $this->seenSkusInBatch[$sku] = true;

        // Check if product exists
        $existingProduct = Product::where('sku', $sku)->first();
        $isUpdate = $existingProduct !== null;

        // Prepare product data
        $productData = [
            'sku' => $sku,
            'name' => trim($data['name']),
            'price' => (float) $data['price'],
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'stock' => isset($data['stock']) ? (int) $data['stock'] : 0,
        ];

        // Upsert product
        $product = Product::updateOrCreate(
            ['sku' => $sku],
            $productData
        );

        if ($isUpdate) {
            $this->stats['updated']++;
            Log::info("Row {$rowNumber}: Updated product SKU: {$sku}");
        } else {
            $this->stats['imported']++;
            Log::info("Row {$rowNumber}: Imported new product SKU: {$sku}");
        }

        // Process image if provided
        if (!empty($data['image_path'])) {
            $this->processProductImage($product, trim($data['image_path']), $rowNumber);
        }
    }

    /**
     * Process and attach image to product.
     */
    private function processProductImage(Product $product, string $imagePath, int $rowNumber): void
    {
        try {
            // Check if an upload already exists for this filename
            $filename = basename($imagePath);
            $existingImage = Image::whereHas('upload', function ($query) use ($filename) {
                $query->where('filename', $filename)
                      ->where('status', 'completed');
            })
            ->where('imageable_type', Product::class)
            ->where('imageable_id', $product->id)
            ->where('variant', 'original')
            ->first();

            if ($existingImage) {
                // Image already exists, just ensure it's set as primary (idempotent)
                $product->setPrimaryImage($existingImage);
                Log::info("Row {$rowNumber}: Using existing image for product SKU: {$product->sku}");
            } else {
                // Store image_path in metadata for later processing
                Log::info("Row {$rowNumber}: Image path recorded for future processing: {$imagePath}");
                // Note: Actual image processing would happen via ImageProcessingService
                // after the image has been uploaded via chunked upload API
            }
        } catch (\Exception $e) {
            Log::error("Row {$rowNumber}: Failed to process image for product SKU: {$product->sku}", [
                'error' => $e->getMessage(),
                'image_path' => $imagePath,
            ]);
        }
    }

    /**
     * Reset statistics counters.
     */
    private function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'invalid' => 0,
            'duplicates' => 0,
        ];
        $this->seenSkusInBatch = [];
    }

    /**
     * Get current statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
