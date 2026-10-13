<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What people actually filled in.
 *
 * This table lives on the `forms` connection - a separate database - so the
 * answers can be backed up, restored and permitted independently of the site.
 * It carries no foreign key to `forms`: a cross-database constraint would tie
 * the two together again and would simply fail once they really are on two
 * servers. The link is `form_id`, checked in the application.
 *
 * The answers themselves are one JSON column. The alternative - a physical
 * table per form - needs CREATE privileges at request time (usually denied on
 * shared hosting), cannot be rolled back inside a transaction, and turns every
 * edit of a published form into an ALTER TABLE on live data. One column keeps
 * the write to a single insert, which is what matters when a form goes out to
 * a few thousand people at once; the fields that need to be searched or kept
 * unique are mirrored into form_submission_values, where real indexes apply.
 */
return new class extends Migration
{
    protected $connection = 'forms';

    public function up(): void
    {
        Schema::connection('forms')->create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('form_id')->index();
            $table->unsignedBigInteger('form_user_id')->nullable()->index();

            // Who answered, when they are signed in. Anonymous submissions
            // keep this null rather than inventing an identity.
            $table->unsignedBigInteger('submitted_by')->nullable()->index();

            $table->json('data');

            // received: written, not yet indexed. indexed: the queue has run.
            // spam / invalid: kept rather than deleted, so a false positive is
            // recoverable and a flood is measurable.
            $table->enum('status', ['received', 'indexed', 'spam', 'invalid'])->default('received');

            /*
             * The IP is stored as a salted hash, never in the clear: it is
             * only ever used to rate-limit and to spot a flood, and a plain
             * address is personal data this site has no reason to hold.
             */
            $table->char('ip_hash', 64)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->string('locale', 8)->nullable();

            $table->timestamps();

            $table->index(['form_id', 'created_at']);
            $table->index(['form_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('forms')->dropIfExists('form_submissions');
    }
};
