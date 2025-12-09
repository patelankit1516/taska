<?php

namespace App\Http\Controllers;

use App\Services\ChunkedUploadService;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function __construct(
        private ChunkedUploadService $uploadService
    ) {}

    /**
     * Initialize a chunked upload.
     * 
     * POST /api/uploads/initialize
     */
    public function initialize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'total_size' => 'required|integer|min:1',
            'mime_type' => 'required|string',
            'checksum' => 'required|string|size:64', // SHA-256 is 64 chars
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $upload = $this->uploadService->initializeUpload(
                $request->filename,
                $request->total_size,
                $request->mime_type,
                $request->checksum,
                $request->metadata ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Upload initialized successfully',
                'data' => [
                    'uuid' => $upload->uuid,
                    'filename' => $upload->filename,
                    'total_size' => $upload->total_size,
                    'total_chunks' => ceil($upload->total_size / (1024 * 1024)), // 1MB chunks
                    'status' => $upload->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to initialize upload', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a single chunk.
     * 
     * POST /api/uploads/{uuid}/chunk
     */
    public function uploadChunk(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chunk_number' => 'required|integer|min:0',
            'chunk_data' => 'required|string', // Base64 encoded
            'chunk_checksum' => 'required|string|size:64', // SHA-256
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Decode base64 chunk data
            $chunkData = base64_decode($request->chunk_data, true);
            
            if ($chunkData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid base64 encoded chunk data',
                ], 400);
            }

            $result = $this->uploadService->uploadChunk(
                $uuid,
                $request->chunk_number,
                $chunkData,
                $request->chunk_checksum
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'chunk_number' => $result['chunk_number'],
                    'upload_progress' => $result['upload_progress'],
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found',
            ], 404);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Failed to upload chunk', [
                'uuid' => $uuid,
                'chunk_number' => $request->chunk_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload chunk: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status.
     * 
     * GET /api/uploads/{uuid}/status
     */
    public function status(string $uuid): JsonResponse
    {
        try {
            $status = $this->uploadService->getStatus($uuid);

            return response()->json([
                'success' => true,
                'data' => $status,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get upload status', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get upload status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
