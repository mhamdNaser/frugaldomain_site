<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courses become bilingual: every text field gets an Arabic twin (`_ar`).
 * English stays the primary language; the API serves Arabic where it exists
 * and falls back to English field by field.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_courses', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->string('subtitle_ar')->nullable()->after('subtitle');
            $table->longText('description_ar')->nullable()->after('description');
            $table->json('outcomes_ar')->nullable()->after('outcomes');
            $table->json('prerequisites_ar')->nullable()->after('prerequisites');
        });

        Schema::table('learning_stages', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->text('summary_ar')->nullable()->after('summary');
        });

        Schema::table('learning_units', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->text('summary_ar')->nullable()->after('summary');
        });

        Schema::table('learning_lessons', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->text('summary_ar')->nullable()->after('summary');
            $table->json('objectives_ar')->nullable()->after('objectives');
            $table->json('blocks_ar')->nullable()->after('blocks');
        });
    }

    public function down(): void
    {
        Schema::table('learning_lessons', fn (Blueprint $t) => $t->dropColumn(['title_ar', 'summary_ar', 'objectives_ar', 'blocks_ar']));
        Schema::table('learning_units', fn (Blueprint $t) => $t->dropColumn(['title_ar', 'summary_ar']));
        Schema::table('learning_stages', fn (Blueprint $t) => $t->dropColumn(['title_ar', 'summary_ar']));
        Schema::table('learning_courses', fn (Blueprint $t) => $t->dropColumn(['title_ar', 'subtitle_ar', 'description_ar', 'outcomes_ar', 'prerequisites_ar']));
    }
};
