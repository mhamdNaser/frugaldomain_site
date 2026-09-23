<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Components a user has saved from the component library.
 *
 * A bookmark, not a copy: the component stays site-owned and a saved one
 * follows it when it is updated. No soft deletes - un-saving and saving again
 * is a normal thing to do, and a soft-deleted row would collide with the
 * unique index the second time (the bug icon_favorites has).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_components');
    }
};
