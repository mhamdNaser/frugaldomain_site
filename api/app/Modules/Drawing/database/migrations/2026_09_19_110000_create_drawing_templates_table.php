<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Starter templates, seeded by the site rather than submitted by users.
     *
     * Kept in their own table instead of as rows in `drawings` with a flag:
     * a template has no author who earns points, never goes through the
     * review queue, and is managed from the admin panel rather than from the
     * editor. Sharing a table would mean every query about user drawings had
     * to remember to exclude them.
     */
    public function up(): void
    {
        Schema::create('drawing_templates', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug', 190)->unique();
            $table->string('description')->nullable();

            // Grouping for the tabs in the editor's template panel.
            $table->string('category', 60)->index();

            /*
             * The document, in exactly the shape the editor's own file format
             * uses. Storing real geometry - rects, paths, text - rather than a
             * flattened image is the whole point: a template has to arrive on
             * the canvas as ordinary editable elements that every tool,
             * effect and clip works on.
             */
            $table->json('document');

            // Rendered on demand from the document; see the seeder.
            $table->string('thumbnail_path')->nullable();

            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);

            $table->unsignedInteger('use_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The panel's query: active templates of one category, in order.
            $table->index(['is_active', 'category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawing_templates');
    }
};
