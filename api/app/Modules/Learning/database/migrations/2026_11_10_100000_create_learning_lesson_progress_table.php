<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which lessons each member has marked complete. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('learning_lessons')->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_lesson_progress');
    }
};
