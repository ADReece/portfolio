<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete(); // the photographer
            $table->string('mode', 16); // instant (streamed) or background (built by the queue)
            $table->string('name'); // the ZIP's filename
            $table->json('files'); // [{key, name, size}]
            $table->unsignedInteger('photo_count');
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedInteger('done_count')->default(0);
            $table->string('status', 16)->default('pending'); // pending, building, ready, failed
            $table->string('path')->nullable(); // archives/… on s3, once built
            $table->string('email')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->string('requester_ip', 45)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['requester_ip', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_archives');
    }
};
