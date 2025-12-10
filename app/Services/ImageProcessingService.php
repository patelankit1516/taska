<?php

namespace App\Services;

use App\Models\Upload;
use App\Models\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessingService
{
    private const VARIANTS = [
        'original' => null,  // No resizing
        '256px' => 256,
        '512px' => 512,
        '1024px' => 1024,
    ];

    private const IMAGE_STORAGE_PATH = 'public/images';

    /**
     * Process uploaded image file and generate variants.
     *
     * @param Upload $upload Completed upload
     * @return array Array of created Image models
     * @throws \Exception
     */
    public function processUpload(Upload $upload): array
    {
        if ($upload->status !== 'completed') {
            throw new \InvalidArgumentException("Upload must be in 'completed' status");
        }

        if (!$this->isImage($upload->mime_type)) {
            throw new \InvalidArgumentException("Upload is not an image file");
        }

        // Get the uploaded file path
        $sourcePath = $upload->metadata['final_path'] ?? null;
        
        if (!$sourcePath) {
            throw new \RuntimeException("Final path not found in upload metadata for: {$upload->uuid}");
        }
        
        // Use absolute path to avoid Laravel Storage facade path issues
        $sourceFullPath = storage_path('app/' . $sourcePath);
        
        if (!file_exists($sourceFullPath)) {
            Log::error("Source file not found", [
                'uuid' => $upload->uuid,
                'source_path' => $sourcePath,
                'full_path' => $sourceFullPath,
                'file_exists' => file_exists($sourceFullPath),
            ]);
            throw new \RuntimeException("Source file not found for upload: {$upload->uuid}");
        }

        $createdImages = [];

        try {
            // Create image manager instance
            $manager = new ImageManager(new Driver());
            
            // Load the original image
            $image = $manager->read($sourceFullPath);
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            foreach (self::VARIANTS as $variantName => $maxDimension) {
                $variantImage = clone $image;
                $width = $originalWidth;
                $height = $originalHeight;

                // Resize if not original
                if ($maxDimension !== null) {
                    // Maintain aspect ratio - resize to fit within max dimension
                    if ($width > $height) {
                        if ($width > $maxDimension) {
                            $height = (int) (($maxDimension / $width) * $height);
                            $width = $maxDimension;
                            $variantImage->scale(width: $width);
                        }
                    } else {
                        if ($height > $maxDimension) {
                            $width = (int) (($maxDimension / $height) * $width);
                            $height = $maxDimension;
                            $variantImage->scale(height: $height);
                        }
                    }
                }

                // Generate storage path for variant
                $variantPath = $this->getVariantPath($upload->uuid, $upload->filename, $variantName);
                $variantFullPath = storage_path('app/' . $variantPath);

                // Ensure directory exists
                $directory = dirname($variantFullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save variant
                $variantImage->save($variantFullPath);

                // Get file size
                $fileSize = filesize($variantFullPath);

                // Create Image record (not attached to any model yet)
                $imageRecord = Image::create([
                    'upload_id' => $upload->id,
                    'imageable_type' => '', // Will be set when attached
                    'imageable_id' => 0,    // Will be set when attached
                    'variant' => $variantName,
                    'path' => $variantPath,
                    'width' => $width,
                    'height' => $height,
                    'size' => $fileSize,
                ]);

                $createdImages[] = $imageRecord;

                Log::info("Created image variant", [
                    'upload_uuid' => $upload->uuid,
                    'variant' => $variantName,
                    'dimensions' => "{$width}x{$height}",
                    'size' => $fileSize,
                ]);
            }

            Log::info("Successfully processed image upload", [
                'upload_uuid' => $upload->uuid,
                'variants_created' => count($createdImages),
            ]);

            return $createdImages;

        } catch (\Exception $e) {
            Log::error("Failed to process image upload", [
                'upload_uuid' => $upload->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up any created variant files
            foreach ($createdImages as $imageRecord) {
                $variantFullPath = storage_path('app/' . $imageRecord->path);
                if (file_exists($variantFullPath)) {
                    unlink($variantFullPath);
                }
                $imageRecord->delete();
            }

            throw $e;
        }
    }

    /**
     * Attach images to a model (e.g., Product).
     *
     * @param Upload $upload
     * @param string $modelType Model class name (e.g., Product::class)
     * @param int $modelId Model ID
     * @return void
     */
    public function attachToModel(Upload $upload, string $modelType, int $modelId): void
    {
        $images = Image::where('upload_id', $upload->id)
            ->where('imageable_type', '')
            ->where('imageable_id', 0)
            ->get();

        foreach ($images as $image) {
            $image->update([
                'imageable_type' => $modelType,
                'imageable_id' => $modelId,
            ]);
        }

        Log::info("Attached images to model", [
            'upload_uuid' => $upload->uuid,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'image_count' => $images->count(),
        ]);
    }

    /**
     * Check if MIME type is an image.
     */
    private function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Generate storage path for image variant.
     */
    private function getVariantPath(string $uuid, string $filename, string $variant): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        return self::IMAGE_STORAGE_PATH . "/{$uuid}/{$basename}_{$variant}.{$extension}";
    }

    /**
     * Check if upload has already been processed.
     *
     * @param string $filename
     * @return Upload|null
     */
    public function findExistingUpload(string $filename): ?Upload
    {
        return Upload::where('filename', $filename)
            ->where('status', 'completed')
            ->whereHas('images', function ($query) {
                $query->where('variant', 'original');
            })
            ->first();
    }
}
