<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A form, as its owner defined it.
 *
 * The definition lives in the site's own database next to the user who owns
 * it; the answers people submit live in a separate one (see the submissions
 * migration). That split is the point: the definition is small, edited by one
 * person and backed up with the site, while the answers are the volume and the
 * sensitive half.
 *
 * `slug` is what appears in the public link and `uuid` is what never changes.
 * Keeping both means an owner can rename a form without breaking a link that
 * has already been handed out - the uuid route keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();

            // draft: only the owner can open it. published: the public link
            // works. closed: the link explains that it no longer takes
            // answers, which is kinder than a 404 to someone who was sent it.
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');

            /*
             * Everything the form does beyond its fields: the success message,
             * whether one person may answer twice, a closing date, a response
             * cap, where to notify. Settings change often and are read as a
             * whole, so they are one JSON column rather than a dozen that
             * each need a migration.
             */
            $table->json('settings')->nullable();

            // Denormalised so a list of twenty forms costs one query instead
            // of twenty counts against another database.
            $table->unsignedInteger('submissions_count')->default(0);
            $table->timestamp('last_submission_at')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
