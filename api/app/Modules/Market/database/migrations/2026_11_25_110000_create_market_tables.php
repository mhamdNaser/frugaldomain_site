<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shopify themes and apps for sale, and who bought them. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->index();                 // theme | app
            $table->string('slug', 120)->unique();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->longText('readme')->nullable();
            $table->json('features')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->json('translations')->nullable();            // ar: same text fields
            $table->decimal('price', 10, 2)->default(0);
            $table->string('preview_url')->nullable();
            $table->string('preview_password')->nullable();      // shown on the page on purpose
            $table->string('logo_path')->nullable();
            $table->string('source', 10)->default('upload');     // upload | github
            $table->string('file_path')->nullable();             // private disk
            $table->string('github_repo')->nullable();
            $table->text('github_token')->nullable();            // encrypted
            $table->string('version', 60)->nullable();
            $table->text('release_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();
        });

        Schema::create('market_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();   // pending | paid | canceled
            $table->string('reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamp('proof_submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'market_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_purchases');
        Schema::dropIfExists('market_items');
    }
};
