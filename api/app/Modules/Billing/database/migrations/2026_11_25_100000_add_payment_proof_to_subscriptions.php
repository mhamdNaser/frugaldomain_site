<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Transfer receipt a store owner uploads for a manual or Sham Cash payment (private disk path). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('receipt_path')->nullable();
            $table->timestamp('proof_submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['receipt_path', 'proof_submitted_at']);
        });
    }
};
