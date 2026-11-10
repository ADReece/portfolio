<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // processing: original stored, thumbnail/watermark being made by
            // the queue worker; ready: visible; failed: shown only to the owner.
            $table->string('status', 16)->default('ready')->after('url');
            $table->string('processing_error')->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'processing_error']);
        });
    }
};
