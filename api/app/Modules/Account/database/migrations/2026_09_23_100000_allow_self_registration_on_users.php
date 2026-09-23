<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a visitor create an account with a name, an email and a password.
 *
 * The users table was designed for accounts an admin creates, so it required
 * a middle name, a last name, a unique phone number and an explicit status.
 * None of those is something a person signing up to save icons should have
 * to hand over, and the public sign-up could not insert a row without them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('medium_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            // Still unique: several NULLs do not collide in a unique index.
            $table->bigInteger('phone')->nullable()->change();
            $table->boolean('status')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('medium_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->bigInteger('phone')->nullable(false)->change();
            $table->boolean('status')->default(null)->change();
        });
    }
};
