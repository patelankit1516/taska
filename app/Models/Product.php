<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock',
        'primary_image_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Get all images for the product.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'primary_image_id');
    }

    /**
     * Set primary image for product (idempotent operation).
     */
    public function setPrimaryImage(Image $image): void
    {
        // Check if the image is already set as primary (idempotent)
        if ($this->primary_image_id === $image->id) {
            return;
        }

        $this->primary_image_id = $image->id;
        $this->save();
    }
}
