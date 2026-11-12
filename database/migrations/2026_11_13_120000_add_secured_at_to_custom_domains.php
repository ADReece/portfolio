<?php
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When the domain was first reached over HTTPS (its certificate issued):
        // DNS verified + secured = live; DNS verified only = securing.
        Schema::table('custom_domains', function (Blueprint $table) {
            $table->timestamp('secured_at')->nullable()->after('verified_at');
        });

        // The pricing page lists "Your own domain" on the paid plans.
        foreach (['photographer', 'videographer'] as $slug) {
            $plan = DB::table('subscription_plans')->where('slug', $slug)->first();
            if (! $plan) {
                continue;
            }
            $features = json_decode($plan->features ?? '[]', true) ?: [];
            if (! in_array('Your own domain', $features, true)) {
                $features[] = 'Your own domain';
                DB::table('subscription_plans')->where('id', $plan->id)->update(['features' => json_encode($features)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('custom_domains', fn (Blueprint $table) => $table->dropColumn('secured_at'));
    }
};
