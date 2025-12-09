<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\UploadController;

// Product Import Routes
Route::post('/products/import', [ProductImportController::class, 'import']);
Route::post('/products/{id}/attach-image', [ProductImportController::class, 'attachImage']);

// Chunked Upload Routes
Route::post('/uploads/initialize', [UploadController::class, 'initialize']);
Route::post('/uploads/{uuid}/chunk', [UploadController::class, 'uploadChunk']);
Route::get('/uploads/{uuid}/status', [UploadController::class, 'status']);

// Authenticated routes (example)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
