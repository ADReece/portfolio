<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Our generated mockup (App\Prints\MockupGenerator), used when no image is uploaded.
        Schema::table('prodigi_products', function (Blueprint $table) {
            $table->string('mockup_path')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('prodigi_products', fn (Blueprint $table) => $table->dropColumn('mockup_path'));
    }
};
