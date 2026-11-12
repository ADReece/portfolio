<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The public demo photographer (php artisan portfolio:demo-seed): can't log
        // in, never charged, left out of stats, CRM and conversions.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index();
        });

        // Credit for stock photos (the demo's Unsplash photos):
        // {"name", "profile_url", "source_url", "licence", "source_id", "category"}.
        Schema::table('photos', function (Blueprint $table) {
            $table->json('credit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('photos', fn (Blueprint $table) => $table->dropColumn('credit'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_demo'));
    }
};
