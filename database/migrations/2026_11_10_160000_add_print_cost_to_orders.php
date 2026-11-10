<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Prodigi's quoted cost (prints + delivery to the client's address)
            // at checkout; the photographer is paid total − print_cost − platform_fee.
            $table->decimal('print_cost', 10, 2)->nullable()->after('platform_fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('print_cost'));
    }
};
