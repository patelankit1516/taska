<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadChunk extends Model
{
    protected $fillable = [
        'upload_id',
        'chunk_number',
        'chunk_size',
        'chunk_checksum',
        'uploaded',
    ];

    protected $casts = [
        'chunk_number' => 'integer',
        'chunk_size' => 'integer',
        'uploaded' => 'boolean',
    ];

    /**
     * Get the upload that owns this chunk.
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}
