<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The learning platform.
 *
 *   category  (Cybersecurity, Networking, AI, …)
 *     └─ course     (a complete programme a learner enrols in)
 *          └─ stage      (a phase / certification / research track)
 *               └─ unit       (a module grouping related lessons)
 *                    └─ lesson     (one page: an ordered list of content blocks)
 *
 * A lesson's body is a JSON array of typed blocks (markdown text, code,
 * image, video, animation, terminal session, quiz, callout). Keeping it as
 * one document rather than a blocks table means a lesson is saved, versioned,
 * exported and imported as a single unit, and the editor never has to
 * reconcile partial writes of individual blocks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon', 60)->default('book');
            $table->string('color', 20)->default('#0ea5e9');
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['status', 'ordering']);
        });

        Schema::create('learning_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('level', 20)->default('beginner');
            $table->string('language', 5)->default('en');
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->json('outcomes')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('tags')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'status', 'ordering']);
        });

        Schema::create('learning_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('title');
            $table->string('code', 120)->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'ordering']);
        });

        Schema::create('learning_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('learning_stages')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
            $table->index(['stage_id', 'ordering']);
        });

        Schema::create('learning_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('learning_units')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->json('objectives')->nullable();
            $table->json('blocks')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
            $table->index(['unit_id', 'ordering']);
            $table->index(['course_id', 'status']);
        });

        Schema::create('learning_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('learning_courses')->nullOnDelete();
            $table->string('kind', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_media');
        Schema::dropIfExists('learning_lessons');
        Schema::dropIfExists('learning_units');
        Schema::dropIfExists('learning_stages');
        Schema::dropIfExists('learning_courses');
        Schema::dropIfExists('learning_categories');
    }
};
