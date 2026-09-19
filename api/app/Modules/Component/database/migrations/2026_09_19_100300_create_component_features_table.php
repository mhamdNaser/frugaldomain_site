<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Features - one row per bullet, mirroring app_features, so the list is
     * unbounded and reorderable without rewriting a JSON blob.
     */
    public function up(): void
    {
        Schema::create('component_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('component_id')->index();
            $table->text('label');
            $table->unsignedInteger('ordering')->default(0);

            $table->timestamps();

            $table->foreign('component_id')
                ->references('id')
                ->on('components')
                ->cascadeOnDelete();

            $table->index(['component_id', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_features');
    }
};
