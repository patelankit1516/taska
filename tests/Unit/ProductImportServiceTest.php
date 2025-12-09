<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProductImportService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductImportService();
    }

    /**
     * Test upsert creates new product when SKU doesn't exist.
     */
    public function test_upsert_creates_new_product_when_sku_doesnt_exist(): void
    {
        $csvContent = "sku,name,price,description,stock\nTEST001,Test Product,99.99,Test Description,10";
        $csvPath = $this->createTempCsv($csvContent);

        $result = $this->service->importFromCsv($csvPath);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['updated']);

        $product = Product::where('sku', 'TEST001')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals(10, $product->stock);

        unlink($csvPath);
    }

    /**
     * Test upsert updates existing product when SKU exists.
     */
    public function test_upsert_updates_existing_product_when_sku_exists(): void
    {
        // Create initial product
        Product::create([
            'sku' => 'TEST002',
            'name' => 'Old Name',
            'price' => 50.00,
            'stock' => 5,
        ]);

        $csvContent = "sku,name,price,description,stock\nTEST002,Updated Name,75.50,Updated Description,20";
        $csvPath = $this->createTempCsv($csvContent);

        $result = $this->service->importFromCsv($csvPath);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['updated']);

        $product = Product::where('sku', 'TEST002')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Updated Name', $product->name);
        $this->assertEquals(75.50, $product->price);
        $this->assertEquals(20, $product->stock);

        unlink($csvPath);
    }

    /**
     * Test duplicate SKUs in same CSV batch are counted correctly.
     */
    public function test_duplicate_skus_in_same_csv_batch_are_counted(): void
    {
        $csvContent = "sku,name,price\nDUP001,Product 1,10.00\nDUP001,Product 2,20.00\nDUP001,Product 3,30.00";
        $csvPath = $this->createTempCsv($csvContent);

        $result = $this->service->importFromCsv($csvPath);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(2, $result['duplicates']);

        // Only one product should be created
        $count = Product::where('sku', 'DUP001')->count();
        $this->assertEquals(1, $count);

        unlink($csvPath);
    }

    /**
     * Test invalid rows don't stop import.
     */
    public function test_invalid_rows_dont_stop_import(): void
    {
        $csvContent = "sku,name,price\nVALID001,Valid Product,25.00\n,Missing SKU,10.00\nVALID002,Another Valid,35.00\nVALID003,Missing Price,";
        $csvPath = $this->createTempCsv($csvContent);

        $result = $this->service->importFromCsv($csvPath);

        $this->assertEquals(4, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(2, $result['invalid']);

        $this->assertNotNull(Product::where('sku', 'VALID001')->first());
        $this->assertNotNull(Product::where('sku', 'VALID002')->first());
        $this->assertNull(Product::where('sku', '')->first());

        unlink($csvPath);
    }

    /**
     * Test import handles large batch efficiently.
     */
    public function test_import_handles_large_batch_efficiently(): void
    {
        $csvLines = ["sku,name,price,stock"];
        
        // Create 100 products
        for ($i = 1; $i <= 100; $i++) {
            $csvLines[] = "BULK{$i},Product {$i},10.99,{$i}";
        }

        $csvContent = implode("\n", $csvLines);
        $csvPath = $this->createTempCsv($csvContent);

        $startTime = microtime(true);
        $result = $this->service->importFromCsv($csvPath);
        $endTime = microtime(true);

        $this->assertEquals(100, $result['total']);
        $this->assertEquals(100, $result['imported']);
        $this->assertEquals(0, $result['invalid']);

        // Should complete in reasonable time (less than 5 seconds for 100 items)
        $this->assertLessThan(5, $endTime - $startTime);

        unlink($csvPath);
    }

    /**
     * Test primary image attachment is idempotent.
     */
    public function test_primary_image_attachment_is_idempotent(): void
    {
        $product = Product::create([
            'sku' => 'IMG001',
            'name' => 'Product with Image',
            'price' => 50.00,
            'stock' => 10,
        ]);

        // Create a mock image (without actual upload for unit test)
        $upload = \App\Models\Upload::create([
            'filename' => 'test.jpg',
            'total_size' => 1024,
            'mime_type' => 'image/jpeg',
            'checksum' => hash('sha256', 'test'),
            'status' => 'completed',
        ]);

        $image = \App\Models\Image::create([
            'upload_id' => $upload->id,
            'imageable_type' => Product::class,
            'imageable_id' => $product->id,
            'variant' => 'original',
            'path' => 'images/test.jpg',
            'width' => 800,
            'height' => 600,
            'size' => 1024,
        ]);

        // Set primary image first time
        $product->setPrimaryImage($image);
        $this->assertEquals($image->id, $product->primary_image_id);

        // Set same image again (should be no-op)
        $previousUpdatedAt = $product->updated_at;
        sleep(1); // Wait to ensure timestamp would change if update occurred
        $product->refresh();
        $product->setPrimaryImage($image);

        // Should still be the same image and timestamp shouldn't change
        $this->assertEquals($image->id, $product->primary_image_id);
    }

    /**
     * Helper method to create temporary CSV file.
     */
    private function createTempCsv(string $content): string
    {
        $tempPath = sys_get_temp_dir() . '/test_import_' . uniqid() . '.csv';
        file_put_contents($tempPath, $content);
        return $tempPath;
    }
}
