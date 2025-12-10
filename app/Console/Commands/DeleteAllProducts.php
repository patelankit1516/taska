<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Image;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAllProducts extends Command
{
    protected $signature = 'products:delete-all {--chunk-size=100 : Number of products to delete per chunk}';
    
    protected $description = 'Delete all products in chunks to avoid memory issues';

    public function handle()
    {
        $chunkSize = (int) $this->option('chunk-size');
        
        $this->info('Starting to delete all products...');
        $this->newLine();
        
        // Get total count
        $totalProducts = Product::count();
        $totalImages = Image::count();
        $totalUploads = Upload::count();
        
        $this->info("Found:");
        $this->line("  - {$totalProducts} products");
        $this->line("  - {$totalImages} images");
        $this->line("  - {$totalUploads} uploads");
        $this->newLine();
        
        if ($totalProducts === 0) {
            $this->warn('No products found to delete.');
            return 0;
        }
        
        if (!$this->confirm('Do you want to proceed with deletion?', true)) {
            $this->info('Deletion cancelled.');
            return 0;
        }
        
        $this->newLine();
        $deletedProducts = 0;
        $deletedImages = 0;
        $deletedUploads = 0;
        $deletedFiles = 0;
        
        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->start();
        
        // Process products in chunks
        Product::with(['images.upload'])->chunk($chunkSize, function ($products) use (&$deletedProducts, &$deletedImages, &$deletedUploads, &$deletedFiles, $progressBar) {
            DB::beginTransaction();
            
            try {
                foreach ($products as $product) {
                    // Delete associated images and their files
                    foreach ($product->images as $image) {
                        $upload = $image->upload;
                        
                        // Delete physical files if upload exists
                        if ($upload) {
                            // Delete all variant files
                            if ($upload->path && Storage::disk('public')->exists($upload->path)) {
                                Storage::disk('public')->delete($upload->path);
                                $deletedFiles++;
                            }
                            if ($upload->thumbnail_path && Storage::disk('public')->exists($upload->thumbnail_path)) {
                                Storage::disk('public')->delete($upload->thumbnail_path);
                                $deletedFiles++;
                            }
                            if ($upload->small_path && Storage::disk('public')->exists($upload->small_path)) {
                                Storage::disk('public')->delete($upload->small_path);
                                $deletedFiles++;
                            }
                            if ($upload->medium_path && Storage::disk('public')->exists($upload->medium_path)) {
                                Storage::disk('public')->delete($upload->medium_path);
                                $deletedFiles++;
                            }
                            
                            // Check if this upload is used by other images
                            $uploadUsageCount = Image::where('upload_id', $upload->id)->count();
                            
                            // If this is the only image using this upload, delete the upload
                            if ($uploadUsageCount === 1) {
                                $upload->delete();
                                $deletedUploads++;
                            }
                        }
                        
                        // Delete the image record
                        $image->delete();
                        $deletedImages++;
                    }
                    
                    // Delete the product
                    $product->delete();
                    $deletedProducts++;
                    
                    $progressBar->advance();
                }
                
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                $progressBar->finish();
                $this->newLine(2);
                $this->error('Error during deletion: ' . $e->getMessage());
                return false;
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Clean up orphaned uploads (uploads with no images)
        $this->info('Cleaning up orphaned uploads...');
        $orphanedUploads = Upload::whereDoesntHave('images')->get();
        
        foreach ($orphanedUploads as $upload) {
            // Delete physical files
            if ($upload->path && Storage::disk('public')->exists($upload->path)) {
                Storage::disk('public')->delete($upload->path);
                $deletedFiles++;
            }
            if ($upload->thumbnail_path && Storage::disk('public')->exists($upload->thumbnail_path)) {
                Storage::disk('public')->delete($upload->thumbnail_path);
                $deletedFiles++;
            }
            if ($upload->small_path && Storage::disk('public')->exists($upload->small_path)) {
                Storage::disk('public')->delete($upload->small_path);
                $deletedFiles++;
            }
            if ($upload->medium_path && Storage::disk('public')->exists($upload->medium_path)) {
                Storage::disk('public')->delete($upload->medium_path);
                $deletedFiles++;
            }
            
            $upload->delete();
            $deletedUploads++;
        }
        
        $this->newLine();
        $this->info('✅ Deletion completed successfully!');
        $this->newLine();
        $this->line("Deleted:");
        $this->line("  - {$deletedProducts} products");
        $this->line("  - {$deletedImages} images");
        $this->line("  - {$deletedUploads} uploads");
        $this->line("  - {$deletedFiles} physical files");
        
        return 0;
    }
}
