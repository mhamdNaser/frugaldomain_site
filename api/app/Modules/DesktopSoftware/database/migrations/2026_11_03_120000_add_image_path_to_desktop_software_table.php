<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desktop_software', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('desktop_software', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
