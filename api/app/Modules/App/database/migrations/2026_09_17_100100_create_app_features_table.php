<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Features / highlights — one row per bullet so the list is unbounded and
     * reorderable without rewriting a JSON blob.
     */
    public function up(): void
    {
        Schema::create('app_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')->index();
            $table->text('label');
            $table->unsignedInteger('ordering')->default(0);

            $table->timestamps();

            $table->foreign('app_id')
                ->references('id')
                ->on('apps')
                ->cascadeOnDelete();

            $table->index(['app_id', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_features');
    }
};
