<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_software', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->longText('readme');
            $table->string('platform');
            $table->string('platform_name')->nullable();
            $table->string('environment');
            $table->boolean('is_open_source')->default(false);
            $table->string('version')->nullable();
            $table->string('status')->default('draft');
            $table->string('file_path');
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['status', 'platform', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_software');
    }
};
