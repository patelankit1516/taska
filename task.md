# Copilot Prompt: Laravel Bulk Import & Chunked Image Upload System

I need to implement a bulk CSV import system with chunked/resumable image upload for a Laravel application with MySQL database.

## Requirements:

### Domain: Products (unique by SKU)

### 1. Database Schema
Create migrations for:
- **products** table: id, sku (unique), name, description, price, stock, primary_image_id (nullable FK to images), timestamps
- **uploads** table: id, uuid (unique), filename, mime_type, total_size, uploaded_size, checksum, status (pending/uploading/completed/failed), metadata (json), timestamps
- **images** table: id, upload_id (FK), imageable_type, imageable_id (polymorphic), variant (original/256px/512px/1024px), path, width, height, size, timestamps
- **upload_chunks** table: id, upload_id (FK), chunk_number (unique per upload), chunk_size, chunk_checksum, uploaded (boolean), timestamps

### 2. CSV Import Service
Create `ProductImportService` that:
- Reads CSV with columns: sku, name, price, description, stock, image_path
- **Upserts** products by SKU (creates new or updates existing)
- Validates each row (missing required columns = invalid, skip but don't stop import)
- Tracks duplicates within the same CSV batch
- Returns summary: `['total' => int, 'imported' => int, 'updated' => int, 'invalid' => int, 'duplicates' => int]`
- Processes image_path column to link images to products
- Must handle 10,000+ rows efficiently with transaction safety

### 3. Chunked Upload Service
Create `ChunkedUploadService` that:
- Initializes upload with: filename, total_size, mime_type, expected_checksum
- Accepts chunks with: chunk_number, chunk_data, chunk_checksum
- **Validates chunk checksum** before accepting (SHA-256)
- **Resume capability**: Re-sending same chunk is idempotent (no data corruption)
- Assembles complete file when all chunks received
- **Validates final file checksum** - blocks completion on mismatch
- Updates upload status throughout process

### 4. Image Processing Service
Create `ImageProcessingService` that:
- Takes completed Upload model
- Generates 3 variants: 256px, 512px, 1024px (maintains aspect ratio)
- Stores original + variants in storage/app/public/images
- Creates Image records for each variant linked to Upload
- Uses Intervention Image library
- Links images to products (polymorphic relationship)

### 5. Product Image Attachment
- Attach primary image to product: `$product->setPrimaryImage($image)` must be **idempotent**
- Re-attaching same image = no-op (no duplicate updates)
- Check if upload already exists for same filename before reprocessing

### 6. API Controllers
Create REST endpoints:
- `POST /api/products/import` - Upload CSV, return import summary
- `POST /api/uploads/initialize` - Start chunked upload, return upload UUID
- `POST /api/uploads/{uuid}/chunk` - Upload single chunk
- `GET /api/uploads/{uuid}/status` - Get upload progress and missing chunks
- `POST /api/products/{id}/attach-image` - Link completed upload to product

### 7. Unit Tests (Required)
Create at least one test class with:
- Test upsert creates new product when SKU doesn't exist
- Test upsert updates existing product when SKU exists
- Test duplicate SKUs in same CSV batch are counted correctly
- Test invalid rows don't stop import
- Test chunk re-upload is idempotent
- Test checksum validation blocks bad uploads
- Test image variant generation maintains aspect ratio
- Test primary image attachment is idempotent

### 8. Concurrency Safety
- Use database transactions for imports
- Use row locking for chunk uploads (`lockForUpdate()`)
- Prevent race conditions on upsert operations
- Handle simultaneous chunk uploads

## Technical Constraints:
- Laravel 10+ with MySQL 8+
- Use Eloquent ORM with proper relationships
- Store files in Laravel storage (not public directly during upload)
- Chunk size: 1MB
- Use queues for image processing (optional enhancement)
- Proper error handling and logging
- Follow Laravel best practices and PSR standards

## Expected File Structure:
```
app/
  Models/
    Product.php
    Upload.php
    Image.php
    UploadChunk.php
  Services/
    ProductImportService.php
    ChunkedUploadService.php
    ImageProcessingService.php
  Http/
    Controllers/
      ProductImportController.php
      UploadController.php
tests/
  Unit/
    ProductImportServiceTest.php
    ChunkedUploadServiceTest.php
database/
  migrations/
    xxxx_create_products_table.php
    xxxx_create_uploads_table.php
    xxxx_create_images_table.php
    xxxx_create_upload_chunks_table.php
```

## Key Implementation Notes:
1. **Upsert logic**: Use `Product::updateOrCreate(['sku' => $sku], $data)`
2. **Checksum validation**: Compare SHA-256 hashes before accepting chunks/files
3. **Idempotency**: Check existing state before making changes
4. **Image variants**: Use Intervention Image's `fit()` or `resize()` with aspect ratio constraint
5. **Polymorphic relations**: Images use `imageable_type` and `imageable_id`
6. **Resume uploads**: Return list of uploaded chunk numbers in status endpoint

Please implement this complete solution with all models, migrations, services, controllers, and unit tests.