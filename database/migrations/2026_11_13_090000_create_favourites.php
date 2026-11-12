<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One client's picks in one gallery, identified by a cookie on their
        // browser; submitted with an optional name, email and note.
        Schema::create('favourite_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['collection_id', 'submitted_at']);
        });

        Schema::create('favourite_list_photo', function (Blueprint $table) {
            $table->foreignUuid('favourite_list_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('photo_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['favourite_list_id', 'photo_id']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('favourites_enabled')->default(true);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_favourites')->default(true); // email when a client sends picks
        });
        // The camera's filename, for exporting picks to Lightroom.
        Schema::table('photos', function (Blueprint $table) {
            $table->string('original_filename')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('photos', fn (Blueprint $table) => $table->dropColumn('original_filename'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notify_favourites'));
        Schema::table('collections', fn (Blueprint $table) => $table->dropColumn('favourites_enabled'));
        Schema::dropIfExists('favourite_list_photo');
        Schema::dropIfExists('favourite_lists');
    }
};
