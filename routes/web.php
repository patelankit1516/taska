<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SampleFileController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/test', function () {
    return view('test');
})->name('test');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Sample CSV file downloads
Route::get('/download/sample/{filename}', [SampleFileController::class, 'download'])->name('sample.download');
