<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A component belongs to several categories at once - a "users table" is
     * both a Table and a Dashboard block - so this is a true many-to-many
     * rather than a category_id column.
     */
    public function up(): void
    {
        Schema::create('component_category', function (Blueprint $table) {
            $table->id();

            $table->foreignId('component_id')
                ->constrained('components')
                ->cascadeOnDelete();

            $table->foreignId('component_category_id')
                ->constrained('component_categories')
                ->cascadeOnDelete();

            // The same pair must never be stored twice, or the component
            // would appear in a filtered list more than once.
            $table->unique(['component_id', 'component_category_id'], 'component_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_category');
    }
};
