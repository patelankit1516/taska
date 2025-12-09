<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
}
