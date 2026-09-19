<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ready-made templates: one self-contained file each (a table with sample
     * data, a form, a dashboard block).
     *
     * Deliberately lighter than `apps`: a component carries a preview, a title
     * and its features, and no screenshots at all - the preview is the picture.
     */
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();

            // Doubles as the on-disk folder name under public/components/.
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('tagline')->nullable();
            $table->longText('summary')->nullable();

            // html renders in the sandboxed preview frame; jsx/vue are shown
            // as source only, since they need a build step to run.
            $table->string('file_type')->default('html');
            $table->string('status')->default('draft');
            $table->string('version')->default('1.0.0');
            $table->string('accent')->default('#38bdf8');

            // Short, unbounded lists that are only ever read as a whole.
            $table->json('tags')->nullable();
            $table->json('stack')->nullable();

            // The uploaded file, stored relative to public/ like App does.
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // Cached source for the "view code" tab, so the public endpoint
            // never has to read from disk on a request.
            $table->longText('source')->nullable();

            // A component whose markup expects a dark canvas would be
            // unreadable on the default light preview background.
            $table->string('preview_theme')->default('auto');
            $table->unsignedInteger('preview_height')->default(420);

            $table->foreignId('user_id')->index()->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->unsignedInteger('downloads')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
