<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('uploads')->onDelete('cascade');
            $table->unsignedInteger('chunk_number');
            $table->unsignedBigInteger('chunk_size');
            $table->string('chunk_checksum');
            $table->boolean('uploaded')->default(false);
            $table->timestamps();
            
            $table->unique(['upload_id', 'chunk_number']);
            $table->index('uploaded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
