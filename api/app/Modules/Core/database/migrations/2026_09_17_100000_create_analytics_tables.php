<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitor and engagement analytics.
 *
 * Three tables, deliberately separate rather than one generic "events" table:
 * each is queried in a different way and indexed accordingly.
 *
 *   page_visits  - one row per page view (anonymous or signed in)
 *   login_events - one row per sign-in attempt, kept for the security log
 *   icon_events  - one row per icon interaction (view / click / copy)
 *
 * Downloads already live in icon_downloads and are not duplicated here.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('page_visits')) {
            Schema::create('page_visits', function (Blueprint $table) {
                $table->id();

                // Null for anonymous visitors; the session id still groups them.
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id', 64)->nullable()->index();

                $table->string('path', 255)->index();
                $table->string('page_title', 255)->nullable();
                $table->string('referrer', 512)->nullable();

                $table->string('ip_address', 45)->nullable();
                $table->string('country', 2)->nullable()->index();
                $table->string('device_type', 20)->nullable()->index(); // mobile / tablet / desktop
                $table->string('browser', 40)->nullable();
                $table->string('platform', 40)->nullable();
                $table->text('user_agent')->nullable();

                // How long the visitor stayed, filled in by a later beacon.
                $table->unsignedInteger('duration_seconds')->nullable();

                $table->timestamp('visited_at')->useCurrent()->index();
                $table->timestamps();

                // The dashboard groups by day and by path constantly.
                $table->index(['path', 'visited_at']);

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('login_events')) {
            Schema::create('login_events', function (Blueprint $table) {
                $table->id();

                // Kept nullable so a failed attempt on an unknown email is still recorded.
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email', 255)->nullable()->index();

                $table->boolean('successful')->default(true)->index();
                $table->string('failure_reason', 120)->nullable();
                $table->string('provider', 20)->default('password'); // password / google / ...

                $table->string('ip_address', 45)->nullable();
                $table->string('country', 2)->nullable();
                $table->string('device_type', 20)->nullable();
                $table->string('browser', 40)->nullable();
                $table->string('platform', 40)->nullable();
                $table->text('user_agent')->nullable();

                $table->timestamp('logged_in_at')->useCurrent()->index();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('icon_events')) {
            Schema::create('icon_events', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('icon_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id', 64)->nullable()->index();

                // click = opened the icon, view = rendered in the grid,
                // copy = copied the markup, favorite = starred.
                $table->string('event_type', 20)->default('click')->index();

                $table->string('ip_address', 45)->nullable();
                $table->string('country', 2)->nullable();
                $table->string('device_type', 20)->nullable();

                $table->timestamp('occurred_at')->useCurrent()->index();
                $table->timestamps();

                // "Top icons in the last N days" is the main query.
                $table->index(['icon_id', 'event_type', 'occurred_at'], 'icon_events_lookup_index');

                $table->foreign('icon_id')->references('id')->on('icons')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('icon_events');
        Schema::dropIfExists('login_events');
        Schema::dropIfExists('page_visits');
    }
};
