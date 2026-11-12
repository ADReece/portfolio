<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Films and reels in sets (Videographer plan). The file goes straight
        // from the browser to object storage (multipart, presigned); a queue
        // job reads its details and a poster frame.
        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('set_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('key');                 // videos/{user}/{id}.mp4
            $table->string('poster_key')->nullable();
            $table->string('mime', 50);
            $table->unsignedBigInteger('size');
            $table->string('upload_id', 1024)->nullable(); // S3 multipart, while uploading
            $table->string('status', 16)->default('uploading'); // uploading, processing, ready, failed
            $table->string('processing_error')->nullable();
            $table->unsignedInteger('duration')->nullable(); // seconds
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('codec', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['set_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
