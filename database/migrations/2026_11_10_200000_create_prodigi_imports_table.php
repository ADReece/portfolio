<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One bulk add to the print catalogue (pasted SKUs, a CSV, or a
        // discovery scan), processed in the queue; the counts show progress.
        Schema::create('prodigi_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20); // paste, csv, discover
            $table->string('label')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('not_found')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('not_found_skus')->nullable();
            $table->json('failed_skus')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodigi_imports');
    }
};
