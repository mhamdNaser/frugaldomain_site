<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Screenshots. `role` is 'main' (at most one, the cover) or 'secondary'
     * (exactly three are expected by the admin screen, but the table itself
     * stays permissive so a half-filled draft can be saved).
     *
     * `path` is relative to public/ — the same convention as Icon's file_svg.
     */
    public function up(): void
    {
        Schema::create('app_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')->index();
            $table->string('path');
            $table->string('role')->default('secondary');
            $table->string('alt')->nullable();
            $table->unsignedInteger('ordering')->default(0);

            $table->timestamps();

            $table->foreign('app_id')
                ->references('id')
                ->on('apps')
                ->cascadeOnDelete();

            $table->index(['app_id', 'role', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_images');
    }
};
