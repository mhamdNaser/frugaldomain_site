<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The app's name, tagline and summary in the site's other languages:
     * {"ar": {"name": ..., "tagline": ..., "summary": ...}, "hi": {...}, "zh": {...}, "tr": {...}}.
     * The English stays in its own columns; a missing field falls back to it.
     */
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
