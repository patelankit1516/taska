<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UploadController;

// Product Routes
Route::get('/products', [ProductController::class, 'list'])->name('api.products.list');
Route::post('/products', [ProductController::class, 'list'])->name('api.products.list.post'); // For AG Grid server-side model
Route::get('/products/{id}', [ProductController::class, 'show'])->name('api.products.show');

// Product Import Routes
Route::post('/products/import', [ProductImportController::class, 'import'])->name('api.products.import');
Route::post('/products/{id}/attach-image', [ProductImportController::class, 'attachImage'])->name('api.products.attach-image');

// Chunked Upload Routes
Route::post('/uploads/initialize', [UploadController::class, 'initialize'])->name('api.uploads.initialize');
Route::post('/uploads/{uuid}/chunk', [UploadController::class, 'uploadChunk'])->name('api.uploads.chunk');
Route::get('/uploads/{uuid}/status', [UploadController::class, 'status'])->name('api.uploads.status');

// Authenticated routes (example)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
