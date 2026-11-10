<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            // Lower-case ASCII (punycode). Also serves www.<hostname> when include_www.
            $table->string('hostname')->unique();
            $table->boolean('include_www')->default(true);
            // Which of hostname / www.hostname is the canonical one.
            $table->boolean('primary_www')->default(false);
            $table->string('verification_token', 64);
            $table->string('status', 16)->default('pending'); // pending | verified | failed
            $table->text('failure_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            // First failed check of a verified domain; taken offline after the grace period.
            $table->timestamp('failing_since')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_domains');
    }
};
