<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two additions to visitor analytics.
 *
 *   download_events                - one row per program download (desktop
 *                                    software today; `item_type` leaves room
 *                                    for other kinds)
 *   analytics_excluded_sessions    - browsers that belong to the site's staff.
 *                                    A browser is added the first time a
 *                                    signed-in admin is seen on it, and from
 *                                    then on its visits are never counted,
 *                                    signed in or not.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('download_events')) {
            Schema::create('download_events', function (Blueprint $table) {
                $table->id();
                $table->string('item_type', 32)->index();
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('item_slug', 191)->nullable();
                $table->string('item_name', 255)->nullable();

                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id', 64)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('country', 2)->nullable();
                $table->string('device_type', 20)->nullable();
                $table->string('platform', 40)->nullable();
                $table->string('referrer', 512)->nullable();

                $table->timestamp('occurred_at')->useCurrent()->index();
                $table->timestamps();

                $table->index(['item_type', 'item_id', 'occurred_at']);
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('analytics_excluded_sessions')) {
            Schema::create('analytics_excluded_sessions', function (Blueprint $table) {
                $table->string('session_id', 64)->primary();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('download_events');
        Schema::dropIfExists('analytics_excluded_sessions');
    }
};
