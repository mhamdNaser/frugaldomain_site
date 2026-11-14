<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Languages beyond English and Arabic (hi, zh, tr, ...).
 *
 * Arabic keeps its `_ar` columns; every further language lives here so a new
 * one needs no schema change. One row per (model, locale):
 *   `fields` - the short texts (title, summary, objectives, description, ...)
 *   `blocks` - a lesson body; kept apart so a syllabus can read every lesson
 *              title without loading every lesson body.
 * Rows are written by LearningTranslationsSeeder from
 * database/seeders/translations/<locale>/.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_translations', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 16); // category | course | stage | unit | lesson
            $table->unsignedBigInteger('model_id');
            $table->string('locale', 8);
            $table->json('fields')->nullable();
            $table->json('blocks')->nullable();
            $table->timestamps();
            $table->unique(['model_type', 'model_id', 'locale']);
            $table->index(['locale', 'model_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_translations');
    }
};
