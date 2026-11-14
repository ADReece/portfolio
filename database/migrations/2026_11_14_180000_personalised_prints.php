<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // The original's size (after EXIF rotation): print templates size their text in its pixels.
            $table->unsignedInteger('width')->nullable()->after('size');
            $table->unsignedInteger('height')->nullable()->after('width');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // A print personalised with one of the photographer's templates.
            $table->foreignUuid('template_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->json('template_data')->nullable()->after('template_id');
            $table->string('print_asset_path')->nullable()->after('template_data'); // the rendered image Prodigi prints
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn(['template_data', 'print_asset_path']);
        });
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
