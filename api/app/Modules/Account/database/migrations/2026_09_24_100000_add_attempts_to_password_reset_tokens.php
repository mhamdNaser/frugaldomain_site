<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Counts wrong guesses against a password reset code. A six-digit code is a
 * million possibilities; without a cap, a script could simply try them all
 * inside the fifteen minutes the code lives. After five wrong guesses the
 * code is thrown away and a new one has to be requested.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
