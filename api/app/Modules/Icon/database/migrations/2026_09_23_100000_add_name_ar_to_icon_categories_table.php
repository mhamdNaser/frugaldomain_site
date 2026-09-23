<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arabic display name for a category, shown on the /ar gallery. Nullable:
     * a category without one falls back to its English name.
     */
    public function up(): void
    {
        Schema::table('icon_categories', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('icon_categories', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};
