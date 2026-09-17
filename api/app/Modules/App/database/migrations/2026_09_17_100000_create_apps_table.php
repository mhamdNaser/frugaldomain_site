<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The catalogue of showcased projects.
     *
     * Scalar and short-list fields live here; the two genuinely unbounded
     * collections (features and images) are separate tables so a project can
     * carry as many highlights as its author wants without a JSON rewrite on
     * every edit.
     */
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();

            // Identity. The slug doubles as the on-disk folder name under
            // public/apps/, so it is validated as [a-z0-9-] everywhere.
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->longText('summary')->nullable();

            // Presentation.
            $table->string('type')->default('react');
            $table->string('status')->default('draft');
            $table->string('version')->default('1.0.0');
            $table->date('updated_on')->nullable();
            $table->string('accent')->default('#38bdf8');
            $table->json('tags')->nullable();
            $table->json('stack')->nullable();
            $table->json('docs')->nullable();
            $table->string('repository')->nullable();

            // Hosting / live preview.
            $table->string('preview_mode')->default('subfolder');
            $table->string('preview_url')->nullable();
            $table->string('subdomain')->nullable();
            $table->boolean('preview_embed')->default(true);
            $table->boolean('preview_open_in_new_tab')->default(true);

            // Uploaded artefacts, stored as paths relative to public/ exactly
            // like Icon's file_svg / file_png columns.
            $table->string('archive_path')->nullable();
            $table->string('archive_name')->nullable();
            $table->unsignedBigInteger('archive_size')->nullable();
            $table->string('main_image')->nullable();
            $table->string('cover')->nullable();

            $table->foreignId('user_id')->index()->nullable();

            $table->unsignedInteger('ordering')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
