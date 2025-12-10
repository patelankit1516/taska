<?php

namespace App\Services;

use App\Models\Upload;
use App\Models\UploadChunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChunkedUploadService
{
    private const CHUNK_SIZE = 1024 * 1024; // 1MB
    private const TEMP_UPLOAD_PATH = 'temp_uploads';

    /**
     * Initialize a new chunked upload.
     *
     * @param string $filename Original filename
     * @param int $totalSize Total file size in bytes
     * @param string $mimeType MIME type of the file
     * @param string $expectedChecksum Expected SHA-256 checksum of complete file
     * @param array $metadata Additional metadata (optional)
     * @return Upload
     */
    public function initializeUpload(
        string $filename,
        int $totalSize,
        string $mimeType,
        string $expectedChecksum,
        array $metadata = []
    ): Upload {
        return DB::transaction(function () use ($filename, $totalSize, $mimeType, $expectedChecksum, $metadata) {
            $upload = Upload::create([
                'filename' => $filename,
                'total_size' => $totalSize,
                'mime_type' => $mimeType,
                'checksum' => $expectedChecksum,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            // Create chunk records
            $totalChunks = (int) ceil($totalSize / self::CHUNK_SIZE);
            
            for ($i = 0; $i < $totalChunks; $i++) {
                UploadChunk::create([
                    'upload_id' => $upload->id,
                    'chunk_number' => $i,
                    'chunk_size' => min(self::CHUNK_SIZE, $totalSize - ($i * self::CHUNK_SIZE)),
                    'chunk_checksum' => '', // Will be set when chunk is uploaded
                    'uploaded' => false,
                ]);
            }

            Log::info("Initialized upload", [
                'uuid' => $upload->uuid,
                'filename' => $filename,
                'total_chunks' => $totalChunks,
            ]);

            return $upload;
        });
    }

    /**
     * Upload a single chunk.
     *
     * @param string $uuid Upload UUID
     * @param int $chunkNumber Chunk number (0-indexed)
     * @param string $chunkData Base64 encoded or raw chunk data
     * @param string $chunkChecksum SHA-256 checksum of this chunk
     * @return array Status information
     * @throws \Exception
     */
    public function uploadChunk(
        string $uuid,
        int $chunkNumber,
        string $chunkData,
        string $chunkChecksum
    ): array {
        $upload = Upload::where('uuid', $uuid)->firstOrFail();

        // Validate checksum of chunk data
        $actualChecksum = hash('sha256', $chunkData);
        
        if ($actualChecksum !== $chunkChecksum) {
            Log::error("Chunk checksum mismatch", [
                'uuid' => $uuid,
                'chunk_number' => $chunkNumber,
                'expected' => $chunkChecksum,
                'actual' => $actualChecksum,
            ]);
            
            throw new \RuntimeException("Chunk checksum validation failed");
        }

        return DB::transaction(function () use ($upload, $chunkNumber, $chunkData, $chunkChecksum) {
            // Lock the upload row to prevent race conditions
            $upload = Upload::where('id', $upload->id)->lockForUpdate()->first();

            // Get or create chunk record
            $chunk = UploadChunk::where('upload_id', $upload->id)
                ->where('chunk_number', $chunkNumber)
                ->lockForUpdate()
                ->first();

            if (!$chunk) {
                throw new \RuntimeException("Invalid chunk number: {$chunkNumber}");
            }

            // Check if chunk is already uploaded (idempotent)
            if ($chunk->uploaded && $chunk->chunk_checksum === $chunkChecksum) {
                Log::info("Chunk already uploaded (idempotent)", [
                    'uuid' => $upload->uuid,
                    'chunk_number' => $chunkNumber,
                ]);
                
                return [
                    'status' => 'success',
                    'message' => 'Chunk already uploaded',
                    'chunk_number' => $chunkNumber,
                    'upload_progress' => $this->getProgress($upload),
                ];
            }

            // Store chunk data to filesystem (bypassing Laravel's Storage facade to avoid path issues)
            $chunkPath = $this->getChunkPath($upload->uuid, $chunkNumber);
            $fullChunkPath = storage_path('app/' . $chunkPath);
            
            // Ensure chunk directory exists
            $chunkDir = dirname($fullChunkPath);
            if (!is_dir($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }
            
            // Write chunk data to file
            file_put_contents($fullChunkPath, $chunkData);

            // Update chunk record
            $chunk->update([
                'chunk_checksum' => $chunkChecksum,
                'chunk_size' => strlen($chunkData),
                'uploaded' => true,
            ]);

            // Update upload status
            if ($upload->status === 'pending') {
                $upload->update(['status' => 'uploading']);
            }

            // Update uploaded size
            $uploadedSize = UploadChunk::where('upload_id', $upload->id)
                ->where('uploaded', true)
                ->sum('chunk_size');

            $upload->update(['uploaded_size' => $uploadedSize]);

            Log::info("Chunk uploaded successfully", [
                'uuid' => $upload->uuid,
                'chunk_number' => $chunkNumber,
                'uploaded_size' => $uploadedSize,
                'total_size' => $upload->total_size,
            ]);

            // Check if upload is complete
            if ($upload->isComplete()) {
                $this->assembleFile($upload);
            }

            return [
                'status' => 'success',
                'message' => 'Chunk uploaded successfully',
                'chunk_number' => $chunkNumber,
                'upload_progress' => $this->getProgress($upload),
            ];
        });
    }

    /**
     * Get upload status and progress.
     *
     * @param string $uuid Upload UUID
     * @return array Status information
     */
    public function getStatus(string $uuid): array
    {
        $upload = Upload::where('uuid', $uuid)->firstOrFail();

        return [
            'uuid' => $upload->uuid,
            'filename' => $upload->filename,
            'status' => $upload->status,
            'total_size' => $upload->total_size,
            'uploaded_size' => $upload->uploaded_size,
            'progress' => $this->getProgress($upload),
            'missing_chunks' => $upload->getMissingChunks(),
        ];
    }

    /**
     * Assemble complete file from chunks.
     */
    private function assembleFile(Upload $upload): void
    {
        $finalPath = $this->getFinalPath($upload->uuid, $upload->filename);
        $tempFile = storage_path('app/' . $finalPath);

        // Ensure directory exists
        $directory = dirname($tempFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Assemble chunks
        $outputHandle = fopen($tempFile, 'wb');
        
        if ($outputHandle === false) {
            throw new \RuntimeException("Failed to create output file");
        }

        $chunks = UploadChunk::where('upload_id', $upload->id)
            ->orderBy('chunk_number')
            ->get();

        foreach ($chunks as $chunk) {
            $chunkPath = storage_path('app/' . $this->getChunkPath($upload->uuid, $chunk->chunk_number));
            
            if (!file_exists($chunkPath)) {
                fclose($outputHandle);
                throw new \RuntimeException("Chunk file not found: {$chunk->chunk_number}");
            }

            $chunkData = file_get_contents($chunkPath);
            fwrite($outputHandle, $chunkData);
        }

        fclose($outputHandle);

        // Validate final file checksum
        $actualChecksum = hash_file('sha256', $tempFile);
        
        if ($actualChecksum !== $upload->checksum) {
            // Delete invalid file
            unlink($tempFile);
            
            $upload->update(['status' => 'failed']);
            
            Log::error("Final file checksum mismatch", [
                'uuid' => $upload->uuid,
                'expected' => $upload->checksum,
                'actual' => $actualChecksum,
            ]);
            
            throw new \RuntimeException("Final file checksum validation failed");
        }

        // Update upload status
        $upload->update([
            'status' => 'completed',
            'metadata' => array_merge($upload->metadata ?? [], [
                'final_path' => $finalPath,
                'completed_at' => now()->toIso8601String(),
            ]),
        ]);

        // Clean up chunk files
        $this->cleanupChunks($upload);

        Log::info("File assembled successfully", [
            'uuid' => $upload->uuid,
            'path' => $finalPath,
        ]);
    }

    /**
     * Clean up chunk files after successful assembly.
     */
    private function cleanupChunks(Upload $upload): void
    {
        $chunks = UploadChunk::where('upload_id', $upload->id)->get();
        
        foreach ($chunks as $chunk) {
            $chunkPath = $this->getChunkPath($upload->uuid, $chunk->chunk_number);
            $fullChunkPath = storage_path('app/' . $chunkPath);
            
            if (file_exists($fullChunkPath)) {
                unlink($fullChunkPath);
            }
        }

        // Remove chunk directory if empty
        $chunkDir = storage_path('app/' . self::TEMP_UPLOAD_PATH . '/' . $upload->uuid);
        if (is_dir($chunkDir)) {
            $files = scandir($chunkDir);
            if (count($files) === 2) { // Only . and ..
                rmdir($chunkDir);
            }
        }
    }

    /**
     * Get upload progress percentage.
     */
    private function getProgress(Upload $upload): float
    {
        if ($upload->total_size == 0) {
            return 0;
        }

        return round(($upload->uploaded_size / $upload->total_size) * 100, 2);
    }

    /**
     * Get chunk storage path.
     */
    private function getChunkPath(string $uuid, int $chunkNumber): string
    {
        return self::TEMP_UPLOAD_PATH . "/{$uuid}/chunk_{$chunkNumber}";
    }

    /**
     * Get final file storage path.
     */
    private function getFinalPath(string $uuid, string $filename): string
    {
        return 'uploads/' . $uuid . '/' . $filename;
    }
}
