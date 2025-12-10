<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use App\Services\ChunkedUploadService;
use App\Services\ImageProcessingService;
use App\Models\Product;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    public function __construct(
        private ProductImportService $importService,
        private ChunkedUploadService $chunkedUploadService,
        private ImageProcessingService $imageService
    ) {}

    /**
     * Import products from CSV file.
     * 
     * POST /api/products/import
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Increase execution time and memory for large imports with image processing
            // Note: For production, consider using queue jobs for async processing
            set_time_limit(900); // 15 minutes
            ini_set('max_execution_time', '900');
            ini_set('memory_limit', '1G'); // Increase memory for image processing
            
            $file = $request->file('csv_file');
            
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file upload',
                ], 422);
            }
            
            // Ensure temp directory exists with correct permissions
            $tempDir = storage_path('app/private/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
                chmod($tempDir, 0775);
            }
            
            // Use a unique filename
            $filename = 'import_' . time() . '_' . uniqid() . '.csv';
            
            // Try to store the file (will go to storage/app/private/temp because of 'local' disk config)
            $tempPath = $file->storeAs('temp', $filename);
            
            if (!$tempPath) {
                Log::error('Failed to store uploaded file', [
                    'original_name' => $file->getClientOriginalName(),
                    'temp_dir' => $tempDir,
                    'temp_dir_writable' => is_writable($tempDir),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save uploaded file',
                ], 500);
            }
            
            $fullPath = storage_path('app/private/' . $tempPath);
            
            // Verify the file was actually created
            if (!file_exists($fullPath)) {
                Log::error('File not found after storeAs', [
                    'tempPath' => $tempPath,
                    'fullPath' => $fullPath,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed - file not found',
                ], 500);
            }
            
            // Make sure the file is readable
            chmod($fullPath, 0664);

            $stats = $this->importService->importFromCsv($fullPath);

            // Clean up temporary file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach image to product.
     * 
     * POST /api/products/{id}/attach-image
     */
    public function attachImage(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_uuid' => 'required|string|exists:uploads,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $upload = Upload::where('uuid', $request->upload_uuid)->firstOrFail();

            if ($upload->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload must be completed before attaching to product',
                ], 400);
            }

            // Check if images have been processed
            if ($upload->images()->count() === 0) {
                // Process the upload to generate image variants
                $this->imageService->processUpload($upload);
            }

            // Attach images to product
            $this->imageService->attachToModel($upload, Product::class, $product->id);

            // Set primary image (original variant)
            $originalImage = $upload->images()
                ->where('variant', 'original')
                ->where('imageable_type', Product::class)
                ->where('imageable_id', $product->id)
                ->first();

            if ($originalImage) {
                $product->setPrimaryImage($originalImage);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image attached to product successfully',
                'data' => [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'upload_uuid' => $upload->uuid,
                    'images_count' => $upload->images()->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to attach image to product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to attach image: ' . $e->getMessage(),
            ], 500);
        }
    }
}
