<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photographers' default prices for full-resolution downloads.
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('digital_photo_price', 8, 2)->nullable();
            $table->json('digital_bundles')->nullable(); // [{"count": 5, "price": 20}]
        });

        // Per collection: whether downloads are for sale, and optional overrides.
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('digital_sales')->default(false);
            $table->decimal('digital_photo_price', 8, 2)->nullable();
            $table->decimal('digital_collection_price', 8, 2)->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('kind', 10)->default('print'); // print, digital
            $table->foreignUuid('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->json('pricing_breakdown')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collection_id');
            $table->dropColumn(['kind', 'stripe_checkout_session_id', 'pricing_breakdown', 'delivered_at']);
        });
        Schema::table('collections', fn (Blueprint $table) => $table->dropColumn(['digital_sales', 'digital_photo_price', 'digital_collection_price']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['digital_photo_price', 'digital_bundles']));
    }
};
