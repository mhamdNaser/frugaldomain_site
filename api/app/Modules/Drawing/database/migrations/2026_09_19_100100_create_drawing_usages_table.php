<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawing_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drawing_id')->index();

            // Who placed it in their own work. Never nullable: an anonymous
            // visitor cannot earn anyone points, because nothing stops one
            // person from being a thousand anonymous visitors.
            $table->foreignId('user_id')->index();

            // The drawing it was used in, when that is known. Kept for
            // auditing a suspicious run of usages; the credit itself does not
            // depend on it.
            $table->foreignId('used_in_drawing_id')->nullable()->index();

            // How many points this row was worth when it was recorded. Stored
            // rather than recomputed so that changing the rate later cannot
            // silently rewrite what people have already earned.
            $table->unsignedSmallInteger('points_awarded')->default(1);

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();

            /*
             * The whole anti-gaming rule, enforced by the database.
             *
             * One credit per (drawing, user) pair. Re-using the same drawing a
             * hundred times earns its author exactly one point, and the insert
             * that would break this fails at the storage layer - so a race
             * between two simultaneous requests cannot double-credit either.
             * Doing this in PHP with a check-then-insert would leave that race
             * wide open.
             */
            $table->unique(['drawing_id', 'user_id']);

            $table->foreign('drawing_id')->references('id')->on('drawings')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('used_in_drawing_id')->references('id')->on('drawings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawing_usages');
    }
};
