<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChunkedUploadService;
use App\Services\ImageProcessingService;
use App\Models\Upload;
use App\Models\UploadChunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ChunkedUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChunkedUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChunkedUploadService();
        Storage::fake('local');
    }

    /**
     * Test chunk re-upload is idempotent.
     */
    public function test_chunk_reupload_is_idempotent(): void
    {
        $chunkData = 'test chunk data';
        $checksum = hash('sha256', $chunkData);
        $fileChecksum = hash('sha256', $chunkData);

        $upload = $this->service->initializeUpload(
            'test.txt',
            strlen($chunkData),
            'text/plain',
            $fileChecksum
        );

        // Upload chunk first time
        $result1 = $this->service->uploadChunk($upload->uuid, 0, $chunkData, $checksum);
        $this->assertEquals('success', $result1['status']);

        // Re-upload same chunk (should be idempotent)
        $result2 = $this->service->uploadChunk($upload->uuid, 0, $chunkData, $checksum);
        $this->assertEquals('success', $result2['status']);
        $this->assertStringContainsString('already uploaded', strtolower($result2['message']));

        // Verify chunk is still marked as uploaded
        $chunk = UploadChunk::where('upload_id', $upload->id)
            ->where('chunk_number', 0)
            ->first();

        $this->assertTrue($chunk->uploaded);
        $this->assertEquals($checksum, $chunk->chunk_checksum);
    }

    /**
     * Test checksum validation blocks bad uploads.
     */
    public function test_checksum_validation_blocks_bad_uploads(): void
    {
        $chunkData = 'test chunk data';
        $correctChecksum = hash('sha256', $chunkData);
        $wrongChecksum = hash('sha256', 'different data');
        $fileChecksum = hash('sha256', $chunkData);

        $upload = $this->service->initializeUpload(
            'test.txt',
            strlen($chunkData),
            'text/plain',
            $fileChecksum
        );

        // Try to upload with wrong checksum
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Chunk checksum validation failed');

        $this->service->uploadChunk($upload->uuid, 0, $chunkData, $wrongChecksum);
    }

    /**
     * Test upload initialization creates correct number of chunks.
     */
    public function test_upload_initialization_creates_correct_chunks(): void
    {
        $totalSize = 3 * 1024 * 1024 + 512 * 1024; // 3.5 MB
        $checksum = hash('sha256', 'test data');

        $upload = $this->service->initializeUpload(
            'large_file.bin',
            $totalSize,
            'application/octet-stream',
            $checksum
        );

        // Should create 4 chunks (3 full 1MB chunks + 1 partial 0.5MB chunk)
        $chunkCount = UploadChunk::where('upload_id', $upload->id)->count();
        $this->assertEquals(4, $chunkCount);

        // Verify each chunk has correct expected size
        $chunks = UploadChunk::where('upload_id', $upload->id)
            ->orderBy('chunk_number')
            ->get();

        $this->assertEquals(1024 * 1024, $chunks[0]->chunk_size);
        $this->assertEquals(1024 * 1024, $chunks[1]->chunk_size);
        $this->assertEquals(1024 * 1024, $chunks[2]->chunk_size);
        $this->assertEquals(512 * 1024, $chunks[3]->chunk_size); // Last chunk is smaller
    }

    /**
     * Test upload status returns missing chunks correctly.
     */
    public function test_upload_status_returns_missing_chunks(): void
    {
        $checksum = hash('sha256', 'test');
        
        $upload = $this->service->initializeUpload(
            'test.bin',
            3 * 1024 * 1024, // 3MB = 3 chunks
            'application/octet-stream',
            $checksum
        );

        // Upload only chunk 0 and chunk 2
        $chunk0Data = str_repeat('A', 1024 * 1024);
        $chunk2Data = str_repeat('C', 1024 * 1024);

        $this->service->uploadChunk($upload->uuid, 0, $chunk0Data, hash('sha256', $chunk0Data));
        $this->service->uploadChunk($upload->uuid, 2, $chunk2Data, hash('sha256', $chunk2Data));

        // Get status
        $status = $this->service->getStatus($upload->uuid);

        $this->assertEquals('uploading', $status['status']);
        $this->assertContains(1, $status['missing_chunks']); // Chunk 1 is missing
        $this->assertNotContains(0, $status['missing_chunks']);
        $this->assertNotContains(2, $status['missing_chunks']);
    }

    /**
     * Test final file checksum validation.
     */
    public function test_final_file_checksum_validation(): void
    {
        $fileContent = 'Complete file content';
        $correctChecksum = hash('sha256', $fileContent);
        $wrongChecksum = hash('sha256', 'different content');

        // Initialize with wrong expected checksum
        $upload = $this->service->initializeUpload(
            'test.txt',
            strlen($fileContent),
            'text/plain',
            $wrongChecksum
        );

        // Upload the chunk (which will trigger assembly)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Final file checksum validation failed');

        $this->service->uploadChunk($upload->uuid, 0, $fileContent, hash('sha256', $fileContent));

        // Verify upload status is marked as failed
        $upload->refresh();
        $this->assertEquals('failed', $upload->status);
    }

    /**
     * Test image variant generation maintains aspect ratio.
     */
    public function test_image_variant_generation_maintains_aspect_ratio(): void
    {
        // This test requires an actual image file
        // Create a simple test image
        $imageService = new ImageProcessingService();
        
        // Create a test image (800x600 - 4:3 ratio)
        $testImagePath = storage_path('app/test_image.jpg');
        
        // Create a simple colored image for testing
        $img = imagecreatetruecolor(800, 600);
        $bgColor = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, $bgColor);
        imagejpeg($img, $testImagePath);
        imagedestroy($img);

        // Create upload record
        $checksum = hash_file('sha256', $testImagePath);
        $upload = Upload::create([
            'filename' => 'test_image.jpg',
            'total_size' => filesize($testImagePath),
            'mime_type' => 'image/jpeg',
            'checksum' => $checksum,
            'status' => 'completed',
            'metadata' => ['final_path' => 'test_image.jpg'],
        ]);

        // Copy test image to final path
        Storage::put('test_image.jpg', file_get_contents($testImagePath));

        // Process the upload
        $images = $imageService->processUpload($upload);

        // Check that variants maintain aspect ratio (4:3)
        foreach ($images as $image) {
            if ($image->width > 0 && $image->height > 0) {
                $ratio = round($image->width / $image->height, 2);
                // Allow small rounding differences
                $this->assertEqualsWithDelta(1.33, $ratio, 0.05, 
                    "Variant {$image->variant} should maintain 4:3 aspect ratio");
            }
        }

        // Clean up
        unlink($testImagePath);
        Storage::delete('test_image.jpg');
    }

    /**
     * Test concurrent chunk uploads are handled safely.
     */
    public function test_concurrent_chunk_uploads_handled_safely(): void
    {
        $checksum = hash('sha256', 'test');
        
        $upload = $this->service->initializeUpload(
            'test.bin',
            2 * 1024 * 1024, // 2MB
            'application/octet-stream',
            $checksum
        );

        // Simulate uploading same chunk from different requests
        // (would normally be concurrent, but we'll test serially)
        $chunk0Data = str_repeat('A', 1024 * 1024);
        $chunk0Checksum = hash('sha256', $chunk0Data);

        // First upload
        $result1 = $this->service->uploadChunk($upload->uuid, 0, $chunk0Data, $chunk0Checksum);
        $this->assertEquals('success', $result1['status']);

        // Second upload of same chunk (simulating concurrent request)
        $result2 = $this->service->uploadChunk($upload->uuid, 0, $chunk0Data, $chunk0Checksum);
        $this->assertEquals('success', $result2['status']);

        // Verify no data corruption - chunk should still be valid and uploaded once
        $chunk = UploadChunk::where('upload_id', $upload->id)
            ->where('chunk_number', 0)
            ->first();

        $this->assertTrue($chunk->uploaded);
        $this->assertEquals($chunk0Checksum, $chunk->chunk_checksum);
    }
}
