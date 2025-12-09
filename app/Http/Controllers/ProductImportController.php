<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
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
            $file = $request->file('csv_file');
            $tempPath = $file->storeAs('temp', 'import_' . time() . '.csv');
            $fullPath = storage_path('app/' . $tempPath);

            $stats = $this->importService->importFromCsv($fullPath);

            // Clean up temporary file
            unlink($fullPath);

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
