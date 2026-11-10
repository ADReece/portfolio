<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * New prices (decided 10 Nov 2026). Updates existing plan rows so production
 * picks them up on deploy; `php artisan stripe:sync-plans --apply` then
 * creates the matching Stripe prices and archives the old ones. Existing
 * subscribers stay on the price they already pay.
 */
return new class extends Migration
{
    private const PRICES = [
        'photographer' => ['price' => 3.99, 'annual_price' => 39.90, 'annual_discount_percent' => 17],
        'videographer' => ['price' => 7.99, 'annual_price' => 79.90, 'annual_discount_percent' => 17],
    ];

    private const PREVIOUS = [
        'photographer' => ['price' => 9.99, 'annual_price' => 99.99, 'annual_discount_percent' => 17],
        'videographer' => ['price' => 14.99, 'annual_price' => 139.99, 'annual_discount_percent' => 22],
    ];

    public function up(): void
    {
        foreach (self::PRICES as $slug => $values) {
            DB::table('subscription_plans')->where('slug', $slug)->update($values + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::PREVIOUS as $slug => $values) {
            DB::table('subscription_plans')->where('slug', $slug)->update($values + ['updated_at' => now()]);
        }
    }
};
