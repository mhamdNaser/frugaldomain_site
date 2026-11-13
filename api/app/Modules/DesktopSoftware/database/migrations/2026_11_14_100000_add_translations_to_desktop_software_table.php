<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The application's name, summary and README in the site's other languages:
     * {"ar": {"name": ..., "summary": ..., "readme": ...}, "hi": {...}, "zh": {...}, "tr": {...}}.
     * The English stays in its own columns; a missing field falls back to it.
     */
    public function up(): void
    {
        Schema::table('desktop_software', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('readme');
        });
    }

    public function down(): void
    {
        Schema::table('desktop_software', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
