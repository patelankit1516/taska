# Laravel Bulk Import & Chunked Image Upload System

A comprehensive Laravel application implementing bulk CSV product import with chunked/resumable image uploads, featuring transaction safety, checksum validation, and automated image processing.

## Features

### 1. **Bulk CSV Import**
- Upsert products by SKU (create new or update existing)
- Validates each row independently
- Tracks duplicates within batch
- Continues on validation errors
- Transaction-safe for large datasets

### 2. **Chunked Image Upload**
- 1MB chunk size with resume capability
- SHA-256 checksum validation for chunks and final files
- Idempotent chunk re-upload
- Progress tracking with missing chunk detection
- Concurrent upload protection with row locking

### 3. **Automated Image Processing**
- Generates 4 variants: original, 256px, 512px, 1024px
- Maintains aspect ratio
- Uses Intervention Image library
- Polymorphic relationships for flexible attachment

### 4. **Database Schema**
- **products**: SKU-indexed product catalog
- **uploads**: Chunked upload tracking with status
- **images**: Polymorphic image storage with variants
- **upload_chunks**: Individual chunk management

## Installation

### Prerequisites
- PHP 8.2+
- MySQL 8+
- Composer
- GD or Imagick extension

### Setup Steps

1. **Install Dependencies**
```bash
composer install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taska
DB_USERNAME=root
DB_PASSWORD=
```

3. **Run Migrations**
```bash
php artisan migrate
```

4. **Create Storage Link**
```bash
php artisan storage:link
```

## API Endpoints

### Product Import

#### Import CSV
```http
POST /api/products/import
Content-Type: multipart/form-data

csv_file: file (CSV with columns: sku, name, price, description, stock, image_path)
```

**Response:**
```json
{
  "success": true,
  "message": "Import completed successfully",
  "data": {
    "total": 100,
    "imported": 80,
    "updated": 15,
    "invalid": 3,
    "duplicates": 2
  }
}
```

#### Attach Image to Product
```http
POST /api/products/{id}/attach-image
Content-Type: application/json

{
  "upload_uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Chunked Upload

#### Initialize Upload
```http
POST /api/uploads/initialize
Content-Type: application/json

{
  "filename": "product-image.jpg",
  "total_size": 5242880,
  "mime_type": "image/jpeg",
  "checksum": "a1b2c3d4...",
  "metadata": {}
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "total_chunks": 5
  }
}
```

#### Upload Chunk
```http
POST /api/uploads/{uuid}/chunk
Content-Type: application/json

{
  "chunk_number": 0,
  "chunk_data": "base64EncodedData...",
  "chunk_checksum": "sha256checksum..."
}
```

#### Get Upload Status
```http
GET /api/uploads/{uuid}/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "uploading",
    "progress": 60.0,
    "missing_chunks": [2, 4]
  }
}
```

## Testing

Run all tests:
```bash
php artisan test
```

Run specific test suite:
```bash
php artisan test --testsuite=Unit
```

### Test Coverage

The test suite includes:
- ✅ Upsert creates new products
- ✅ Upsert updates existing products
- ✅ Duplicate SKU detection in batch
- ✅ Invalid rows don't stop import
- ✅ Chunk re-upload is idempotent
- ✅ Checksum validation blocks bad uploads
- ✅ Image variants maintain aspect ratio
- ✅ Primary image attachment is idempotent
- ✅ Concurrent chunk upload safety

## Architecture

### Services

#### ProductImportService
- Reads CSV with validation
- Upserts products by SKU
- Tracks statistics
- Transaction-wrapped for safety

#### ChunkedUploadService
- Manages chunked uploads
- Validates checksums
- Assembles complete files
- Handles resume/retry

#### ImageProcessingService
- Generates image variants
- Maintains aspect ratios
- Links to polymorphic models

### Models

- **Product**: Main product entity with SKU uniqueness
- **Upload**: Tracks chunked upload progress
- **UploadChunk**: Individual chunk tracking
- **Image**: Polymorphic image storage with variants

## Key Features

1. **Idempotency**: Re-uploading same chunk doesn't corrupt data
2. **Checksum Validation**: SHA-256 for chunks and final file
3. **Transaction Safety**: Database transactions for imports
4. **Row Locking**: Prevents race conditions on concurrent uploads
5. **Polymorphic Relations**: Images can attach to any model
6. **Aspect Ratio Preservation**: Intelligent resizing

## Performance

- Efficiently handles 10,000+ row imports
- Chunk size optimized at 1MB
- Database indexes on frequently queried columns
- Transaction batching for bulk operations
