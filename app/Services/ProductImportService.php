<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Image;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductImportService
{
    private const BATCH_SIZE = 50; // Process 50 products at a time
    
    private array $stats = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'invalid' => 0,
        'duplicates' => 0,
    ];

    private array $seenSkusInBatch = [];
    private array $uploadCache = []; // Cache processed uploads in memory
    
    private ChunkedUploadService $chunkedUploadService;
    private ImageProcessingService $imageProcessingService;

    public function __construct(
        ChunkedUploadService $chunkedUploadService,
        ImageProcessingService $imageProcessingService
    ) {
        $this->chunkedUploadService = $chunkedUploadService;
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Import products from CSV file with optimized batch processing.
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
        
        // Pre-cache all uploads to avoid repeated DB queries
        $this->preloadUploadCache();
        
        Log::info("Starting CSV import", ['file' => basename($csvPath)]);
        
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

        // Process rows in batches
        $batch = [];
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
            $batch[] = ['data' => $data, 'row' => $rowNumber];
            
            // Process batch when it reaches BATCH_SIZE
            if (count($batch) >= self::BATCH_SIZE) {
                $this->processBatch($batch);
                $batch = [];
                
                // Log progress every 100 products
                if ($this->stats['total'] % 100 === 0) {
                    Log::info("Import progress", [
                        'processed' => $this->stats['total'],
                        'imported' => $this->stats['imported'],
                        'updated' => $this->stats['updated'],
                    ]);
                }
                
                // Clear memory
                gc_collect_cycles();
            }
        }
        
        // Process remaining rows
        if (!empty($batch)) {
            $this->processBatch($batch);
        }

        fclose($handle);
        
        // Clear upload cache
        $this->uploadCache = [];
        
        Log::info("Import completed", $this->stats);

        return $this->stats;
    }
    
    /**
     * Preload all completed uploads into memory cache for faster lookups.
     */
    private function preloadUploadCache(): void
    {
        Log::info("Preloading upload cache...");
        
        $uploads = Upload::where('status', 'completed')
            ->select('id', 'uuid', 'filename', 'checksum')
            ->get();
            
        foreach ($uploads as $upload) {
            $this->uploadCache[$upload->filename] = [
                'id' => $upload->id,
                'uuid' => $upload->uuid,
                'checksum' => $upload->checksum,
            ];
        }
        
        Log::info("Preloaded {$uploads->count()} uploads into cache");
    }
    
    /**
     * Process a batch of rows within a single transaction.
     */
    private function processBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch as $item) {
                $this->processRow($item['data'], $item['row']);
            }
        });
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
     * Process and attach image to product with optimized caching.
     * Automatically uploads the image file, generates variants, and attaches to product.
     */
    private function processProductImage(Product $product, string $imagePath, int $rowNumber): void
    {
        try {
            // Resolve full path - support both relative and absolute paths
            $fullPath = $imagePath;
            if (!file_exists($fullPath)) {
                $fullPath = public_path($imagePath);
            }
            if (!file_exists($fullPath)) {
                Log::warning("Row {$rowNumber}: Image file not found: {$imagePath}");
                return;
            }

            $filename = basename($imagePath);
            
            // Check if image already attached to this product (idempotent)
            $existingImage = Image::whereHas('upload', function ($query) use ($filename) {
                $query->where('filename', $filename)
                      ->where('status', 'completed');
            })
            ->where('imageable_type', Product::class)
            ->where('imageable_id', $product->id)
            ->where('variant', 'original')
            ->first();

            if ($existingImage) {
                // Image already exists and attached
                Log::debug("Row {$rowNumber}: Image already attached to product SKU: {$product->sku}");
                return;
            }

            // Check upload cache first (much faster than DB query)
            if (isset($this->uploadCache[$filename])) {
                $uploadId = $this->uploadCache[$filename]['id'];
                
                // Upload exists, duplicate images for this product
                Log::debug("Row {$rowNumber}: Reusing cached upload for product SKU: {$product->sku}");
                
                // Get ONE set of images from the existing upload (4 variants: original, 256px, 512px, 1024px)
                $existingImages = Image::where('upload_id', $uploadId)
                    ->whereNotExists(function ($query) use ($product) {
                        $query->select(DB::raw(1))
                              ->from('images as i2')
                              ->whereColumn('i2.upload_id', 'images.upload_id')
                              ->where('i2.imageable_type', Product::class)
                              ->where('i2.imageable_id', $product->id);
                    })
                    ->select('id', 'upload_id', 'variant', 'path', 'width', 'height', 'size')
                    ->limit(4)
                    ->get();
                
                if ($existingImages->isEmpty()) {
                    // Upload exists but no images - need to process
                    $existingUpload = Upload::find($uploadId);
                    $this->imageProcessingService->processUpload($existingUpload);
                    $existingImages = Image::where('upload_id', $uploadId)->get();
                }
                
                // Prepare batch insert data
                $imagesToInsert = [];
                $primaryImageId = null;
                
                foreach ($existingImages as $existingImg) {
                    $imagesToInsert[] = [
                        'upload_id' => $uploadId,
                        'imageable_type' => Product::class,
                        'imageable_id' => $product->id,
                        'variant' => $existingImg->variant,
                        'path' => $existingImg->path,
                        'width' => $existingImg->width,
                        'height' => $existingImg->height,
                        'size' => $existingImg->size,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    if ($existingImg->variant === 'original') {
                        // We'll set this as primary after insert
                        $primaryImageId = $existingImg->id;
                    }
                }
                
                // Bulk insert all image variants at once
                if (!empty($imagesToInsert)) {
                    DB::table('images')->insert($imagesToInsert);
                    Log::debug("Row {$rowNumber}: Bulk inserted " . count($imagesToInsert) . " image variants");
                }
                
                return;
            }

            // New image - need to upload and process
            Log::info("Row {$rowNumber}: Processing new image for product SKU: {$product->sku}", [
                'image_path' => $imagePath,
            ]);

            // Read file and calculate checksum
            $fileContent = file_get_contents($fullPath);
            $fileSize = strlen($fileContent);
            $checksum = hash('sha256', $fileContent);
            $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

            // Initialize upload
            $upload = $this->chunkedUploadService->initializeUpload(
                $filename,
                $fileSize,
                $mimeType,
                $checksum
            );

            // Add to cache immediately
            $this->uploadCache[$filename] = [
                'id' => $upload->id,
                'uuid' => $upload->uuid,
                'checksum' => $checksum,
            ];

            $chunkSize = 1024 * 1024; // 1MB chunks
            $totalChunks = (int) ceil($fileSize / $chunkSize);

            // Upload in chunks
            for ($i = 0; $i < $totalChunks; $i++) {
                $start = $i * $chunkSize;
                $chunkData = substr($fileContent, $start, $chunkSize);
                $chunkChecksum = hash('sha256', $chunkData);

                $this->chunkedUploadService->uploadChunk(
                    $upload->uuid,
                    $i,
                    $chunkData,
                    $chunkChecksum
                );
            }
            
            // Clear file content from memory
            unset($fileContent);

            // Refresh upload - it should be completed and assembled automatically
            $upload->refresh();
            
            if ($upload->status !== 'completed') {
                throw new \RuntimeException("Upload failed to complete for {$filename}");
            }

            // Process image variants
            $this->imageProcessingService->processUpload($upload);

            // Attach to product
            $this->imageProcessingService->attachToModel($upload, Product::class, $product->id);

            Log::info("Row {$rowNumber}: Successfully processed and attached image for product SKU: {$product->sku}");
            
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
        $this->uploadCache = [];
    }

    /**
     * Get current statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }
    
    /**
     * Get progress percentage.
     */
    public function getProgress(): float
    {
        if ($this->stats['total'] === 0) {
            return 0.0;
        }
        
        $processed = $this->stats['imported'] + $this->stats['updated'] + $this->stats['invalid'] + $this->stats['duplicates'];
        return ($processed / $this->stats['total']) * 100;
    }
}
