<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('prodigi_carrier')->nullable()->after('prodigi_tracking_number');
            $table->string('prodigi_tracking_url', 1024)->nullable()->after('prodigi_carrier');
        });

        // Payouts moved to Portfolio's own Stripe platform (Express accounts).
        // Accounts linked to the old shared platform can't receive its
        // payments, so photographers set up payouts again.
        DB::table('users')->where('is_demo', false)->whereNotNull('stripe_connect_id')
            ->update(['stripe_connect_id' => null, 'stripe_connect_enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['prodigi_carrier', 'prodigi_tracking_url']);
        });
    }
};
