<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventes : commandes, lignes (avec la part de l'artiste figée au moment de
 * l'achat), bibliothèque de l'acheteur, versements aux artistes, factures et
 * réglages chiffrés de l'administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider', 20)->nullable();
            $table->string('reference')->nullable()->index();
            $table->unsignedInteger('total_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_name', 120)->nullable();
            $table->string('billing_email', 190)->nullable();
            $table->string('billing_company', 160)->nullable();
            $table->string('billing_address', 400)->nullable();
            $table->string('billing_vat', 40)->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 10);
            $table->foreignId('sample_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pack_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('artist_name', 80)->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedSmallInteger('commission_bp');
            $table->unsignedInteger('artist_earning_cents');
            $table->timestamps();
        });

        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_key', 40)->unique();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'sample_id']);
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('method', 20);
            $table->string('destination', 255);
            $table->string('status', 20)->default('requested')->index();
            $table->string('reference')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('invoice');
            $table->string('series', 12);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('number', 40)->unique();
            $table->timestamp('issued_at')->index();
            $table->string('access_token', 64)->unique();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('credited_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->json('seller');
            $table->string('buyer_name', 120);
            $table->string('buyer_email', 190)->nullable();
            $table->string('buyer_company', 160)->nullable();
            $table->string('buyer_address', 400)->nullable();
            $table->string('buyer_vat', 40)->nullable();

            $table->string('description', 255);
            $table->json('lines');
            $table->unsignedSmallInteger('vat_rate_bp')->default(0);
            $table->string('vat_mention', 255)->nullable();
            $table->unsignedInteger('total_ht_cents');
            $table->unsignedInteger('total_vat_cents');
            $table->unsignedInteger('total_ttc_cents');
            $table->string('currency', 3)->default('EUR');

            $table->string('payment_method', 40)->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['series', 'year', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
