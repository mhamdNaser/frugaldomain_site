<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tool_uses - a visitor actually used a tool (converted a file, formatted
 * JSON, copied or downloaded a result), not just opened its page. Compared
 * with the page's visitors it gives each tool's real usage rate.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tool_uses')) {
            Schema::create('tool_uses', function (Blueprint $table) {
                $table->id();
                $table->string('tool', 120);          // the English path: /json-toolkit
                $table->string('action', 40);         // convert / format / copy / download ...
                $table->string('locale', 5)->default('en');

                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id', 64)->nullable()->index();
                $table->string('country', 2)->nullable();
                $table->string('device_type', 20)->nullable();

                $table->timestamp('occurred_at')->useCurrent()->index();
                $table->timestamps();

                $table->index(['tool', 'occurred_at']);
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_uses');
    }
};
