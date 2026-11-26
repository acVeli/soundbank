<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue : profils artistes, arborescence des catégories, packs (collections
 * du catalogue d'un artiste) et samples. Les samples et packs sont supprimés
 * « en douceur » pour que les acheteurs gardent l'accès à leurs achats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('stage_name', 80);
            $table->string('slug', 100)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('location', 80)->nullable();
            $table->string('website', 190)->nullable();
            $table->string('instagram', 80)->nullable();
            $table->string('soundcloud', 190)->nullable();
            $table->string('payout_method', 20)->default('paypal');
            $table->string('payout_email', 190)->nullable();
            $table->text('payout_iban')->nullable();
            $table->string('payout_holder', 120)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->string('icon', 30)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('genre', 60)->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sales_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pack_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->string('type', 20)->default('one_shot');
            $table->unsignedSmallInteger('bpm')->nullable();
            $table->string('musical_key', 8)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('tags', 255)->nullable();
            $table->string('file_path');
            $table->string('file_name', 190);
            $table->string('file_format', 10);
            $table->unsignedBigInteger('file_size');
            $table->string('preview_path')->nullable();
            $table->json('waveform')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('bpm');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'sample_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('samples');
        Schema::dropIfExists('packs');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('artist_profiles');
    }
};
