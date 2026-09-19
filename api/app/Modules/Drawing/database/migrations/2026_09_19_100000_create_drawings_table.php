<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();

            $table->string('title');
            $table->string('slug', 190)->nullable()->unique();
            $table->text('description')->nullable();

            // The editable document, exactly the payload the front end already
            // saves to a .fruga.json file. Stored as JSON rather than as a file
            // so it can be queried, migrated and versioned like any other row.
            $table->json('document');
            $table->unsignedSmallInteger('document_version')->default(1);

            // A rendered preview, kept separate from the document so a gallery
            // listing never has to parse or render every drawing it shows.
            $table->string('thumbnail_path')->nullable();

            // Denormalised from the document so the gallery can sort and filter
            // without opening the JSON.
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedInteger('element_count')->default(0);

            /*
             * Lifecycle.
             *
             * A drawing is private until its owner asks to publish it, and an
             * admin has to approve that request before anyone else can see it.
             * Publishing without review would mean copyrighted images - which
             * the editor lets anyone paste in - going public under the site's
             * name.
             */
            $table->enum('status', ['private', 'pending', 'published', 'rejected'])
                ->default('private')
                ->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Counter caches. The usage table below is the source of truth;
            // these exist so listing a gallery is one query, not N.
            $table->unsignedInteger('use_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('points')->default(0);

            $table->boolean('allow_reuse')->default(true);
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The gallery's main query: published, newest or most used first.
            $table->index(['status', 'published_at']);
            $table->index(['status', 'use_count']);
            // A user's own list.
            $table->index(['user_id', 'status']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
