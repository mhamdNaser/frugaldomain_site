<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The project's logo, stored as a self-contained data URI
     * (data:image/svg+xml;base64,...) so it needs no file on disk and is
     * rendered through <img>, where an SVG cannot run scripts.
     */
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->longText('logo')->nullable()->after('accent');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
