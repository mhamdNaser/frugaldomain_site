<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arabic copy for the /ar gallery. `name_ar` already existed; this adds the
     * tagline, the summary and each feature bullet. All nullable - the site
     * falls back to the English text wherever the Arabic is missing.
     */
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('tagline_ar')->nullable()->after('tagline');
            $table->longText('summary_ar')->nullable()->after('summary');
        });

        Schema::table('component_features', function (Blueprint $table) {
            $table->text('label_ar')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('component_features', function (Blueprint $table) {
            $table->dropColumn('label_ar');
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['tagline_ar', 'summary_ar']);
        });
    }
};
