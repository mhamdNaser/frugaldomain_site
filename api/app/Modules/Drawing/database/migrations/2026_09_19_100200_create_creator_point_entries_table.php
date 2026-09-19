<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * A ledger, not a balance column.
     *
     * These points are meant to turn into money later. A single
     * `users.points` integer that gets incremented has no history: when a
     * creator disputes their total, or a bug double-credits someone, there is
     * nothing to reconstruct the truth from. An append-only ledger means the
     * balance is always derivable, every entry says where it came from, and a
     * mistake is corrected by writing a reversing entry rather than by
     * silently editing a number.
     */
    public function up(): void
    {
        Schema::create('creator_point_entries', function (Blueprint $table) {
            $table->id();

            // Who earns the points - the drawing's author.
            $table->foreignId('user_id')->index();

            // Signed, so a reversal is an ordinary entry rather than a special
            // case: a correction is just a negative amount.
            $table->integer('amount');

            $table->enum('reason', [
                'usage',        // someone else used the drawing
                'publish',      // bonus for a drawing passing review
                'adjustment',   // manual correction by an admin
                'reversal',     // undoing an earlier entry
                'payout',       // points exchanged for money
            ])->index();

            // What produced this entry, kept loose so a future source (a
            // contest, a referral) does not need a schema change.
            $table->nullableMorphs('source');

            $table->foreignId('drawing_id')->nullable()->index();
            $table->foreignId('created_by')->nullable();
            $table->string('note')->nullable();

            $table->timestamps();

            // The balance query: everything for one user, newest first.
            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('drawing_id')->references('id')->on('drawings')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_point_entries');
    }
};
