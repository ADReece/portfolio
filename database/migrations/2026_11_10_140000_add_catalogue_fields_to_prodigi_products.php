<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-curated print catalogue: display fields, offered options, prices and
 * the latest Prodigi cost quote per product. Order items remember the options
 * the client chose, which are sent to Prodigi with the order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodigi_products', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('category');
            $table->string('size_label')->nullable()->after('description');
            $table->json('allowed_attributes')->nullable()->after('size_label');
            $table->string('image_path')->nullable()->after('allowed_attributes');
            $table->decimal('default_retail_price', 10, 2)->nullable()->after('image_path');
            $table->decimal('cost_item', 10, 2)->nullable();
            $table->decimal('cost_shipping', 10, 2)->nullable();
            $table->string('cost_currency', 3)->nullable();
            $table->timestamp('cost_quoted_at')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->text('refresh_error')->nullable();
        });

        // A photographer who hasn't set a price uses the product's default.
        Schema::table('prodigi_product_user', function (Blueprint $table) {
            $table->decimal('retail_price', 10, 2)->nullable()->default(null)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('prodigi_attributes')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('prodigi_attributes'));
        Schema::table('prodigi_product_user', function (Blueprint $table) {
            $table->decimal('retail_price', 10, 2)->default(29.99)->change();
        });
        Schema::table('prodigi_products', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'size_label', 'allowed_attributes', 'image_path', 'default_retail_price',
                'cost_item', 'cost_shipping', 'cost_currency', 'cost_quoted_at', 'refreshed_at', 'refresh_error']);
        });
    }
};
