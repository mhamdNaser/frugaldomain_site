<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories a component can be filed under.
     *
     * A separate table rather than an enum column: the brief calls for adding
     * new category types from the dashboard, which an enum would make a
     * migration every time.
     */
    public function up(): void
    {
        Schema::create('component_categories', function (Blueprint $table) {
            $table->id();

            // The slug is what the public filter uses in the query string, so
            // it is validated as [a-z0-9-] exactly like an app slug.
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('description')->nullable();

            // Presentation only: the chip colour on the gallery.
            $table->string('accent')->default('#38bdf8');
            $table->string('icon')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('ordering')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_categories');
    }
};
