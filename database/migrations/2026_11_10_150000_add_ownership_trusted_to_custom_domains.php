<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_domains', function (Blueprint $table) {
            // Set by an admin (custom-domains:connect --trusted) who vouches for
            // ownership: the TXT record is then not required. DNS must still
            // point here.
            $table->boolean('ownership_trusted')->default(false)->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('custom_domains', fn (Blueprint $table) => $table->dropColumn('ownership_trusted'));
    }
};
