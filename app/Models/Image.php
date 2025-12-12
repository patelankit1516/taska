<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = [
        'upload_id',
        'imageable_type',
        'imageable_id',
        'variant',
        'path',
        'width',
        'height',
        'size',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
    ];

    /**
     * Get the upload that owns this image.
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Get the parent imageable model (Product, etc.).
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL for the image
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        
        // Generate URL based on the actual server path structure
        // Server serves files from: storage/app/public/images/...
        // So we need to include 'storage/app/' prefix
        return url('storage/app/' . $this->path);
    }

    /**
     * Get URL for specific variant
     */
    protected function getVariantUrl(string $variantName): ?string
    {
        if (!$this->imageable_id || !$this->imageable_type) {
            return null;
        }

        // Get the variant image for this product
        $variant = static::where('imageable_id', $this->imageable_id)
            ->where('imageable_type', $this->imageable_type)
            ->where('variant', $variantName)
            ->where('upload_id', $this->upload_id)
            ->first();

        return $variant ? $variant->url : $this->url;
    }

    /**
     * Get thumbnail URL (256px)
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getVariantUrl('256px');
    }

    /**
     * Get small URL (256px)
     */
    public function getSmallUrlAttribute(): ?string
    {
        return $this->getVariantUrl('256px');
    }

    /**
     * Get medium URL (512px)
     */
    public function getMediumUrlAttribute(): ?string
    {
        return $this->getVariantUrl('512px');
    }

    /**
     * Get large URL (1024px)
     */
    public function getLargeUrlAttribute(): ?string
    {
        return $this->getVariantUrl('1024px');
    }
}

