<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitors' star ratings of the public pages. `path` is the English path
     * (/json-toolkit), so every language of a page shares one score.
     * `fingerprint` is an HMAC of the browser's random visitor id: one rating
     * per visitor and page, which a second rating replaces. No IP is stored.
     */
    public function up(): void
    {
        Schema::create('site_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255)->index();
            $table->unsignedTinyInteger('stars');
            $table->text('comment')->nullable();
            $table->string('locale', 5)->default('en');
            $table->string('fingerprint', 64);
            $table->timestamps();
            $table->unique(['path', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_ratings');
    }
};
