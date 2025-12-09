<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Upload extends Model
{
    protected $fillable = [
        'uuid',
        'filename',
        'mime_type',
        'total_size',
        'uploaded_size',
        'checksum',
        'status',
        'metadata',
    ];

    protected $casts = [
        'total_size' => 'integer',
        'uploaded_size' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($upload) {
            if (empty($upload->uuid)) {
                $upload->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get all chunks for this upload.
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(UploadChunk::class);
    }

    /**
     * Get all images generated from this upload.
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Check if upload is complete.
     */
    public function isComplete(): bool
    {
        return $this->uploaded_size >= $this->total_size;
    }

    /**
     * Get missing chunk numbers.
     */
    public function getMissingChunks(): array
    {
        $totalChunks = (int) ceil($this->total_size / (1024 * 1024)); // 1MB chunks
        $uploadedChunks = $this->chunks()->where('uploaded', true)->pluck('chunk_number')->toArray();
        
        $allChunks = range(0, $totalChunks - 1);
        return array_values(array_diff($allChunks, $uploadedChunks));
    }
}
