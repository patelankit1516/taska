# Product Import System

Laravel application for bulk product imports with chunked image uploads.

## Features

- CSV product import with upsert (by SKU)
- Chunked file uploads with resume capability
- Automatic image variant generation
- Drag-and-drop image upload interface

## Setup

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate

# Start server
php artisan serve
```

## Testing

```bash
# Run tests
php artisan test

# Test import
curl -X POST http://localhost:8000/api/products/import \
  -F "csv_file=@public/medium_products.csv"

# Test interface
Open http://localhost:8000/test
```

## API Endpoints

- `POST /api/products/import` - Import products from CSV
- `POST /api/uploads/initialize` - Initialize chunked upload
- `POST /api/uploads/{uuid}/chunk` - Upload chunk
- `GET /api/uploads/{uuid}/status` - Get upload status

## Sample Data

```bash
# Generate test CSV files
php generate-medium-csv.php    # 100 products
php generate-large-csv.php     # 10,000+ products
php generate-sample-images.php # 200 test images
```
