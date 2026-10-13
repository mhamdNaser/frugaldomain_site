<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The searchable, and unique-able, half of a submission.
 *
 * A JSON column holds every answer; this table holds the answers that have to
 * be *found* or *guarded*. Two jobs it does that JSON cannot:
 *
 * 1. **Uniqueness.** A field marked unique gets a row here with a unique index
 *    over (form_id, field_key, value_hash). Two people submitting the same
 *    national id at the same instant both pass a SELECT; only one survives a
 *    unique index. That is the difference between a rule and a guarantee.
 *
 * 2. **Search and filter.** "Everyone from Aleppo", "everyone who answered
 *    after Tuesday" - an indexed lookup rather than a scan of every JSON blob.
 *
 * Numbers and dates are kept in typed columns beside the text so that a range
 * query is a range query, not a string comparison where "9" sorts after "10".
 */
return new class extends Migration
{
    protected $connection = 'forms';

    public function up(): void
    {
        Schema::connection('forms')->create('form_submission_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('submission_id');
            $table->string('field_key', 64);

            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->dateTime('value_date')->nullable();

            /*
             * The comparison key for uniqueness: trimmed, lower-cased and
             * hashed, so "Ahmad@Mail.com " and "ahmad@mail.com" collide the
             * way a person would expect - and so a long answer still fits in
             * an index, which a TEXT column does not.
             */
            $table->char('value_hash', 64)->nullable();

            $table->timestamps();

            $table->index(['submission_id']);
            $table->index(['form_id', 'field_key']);
            $table->index(['form_id', 'field_key', 'value_number']);
            $table->index(['form_id', 'field_key', 'value_date']);

            // Partial indexes are not portable, so the uniqueness index covers
            // every row and only the rows that need guarding are written with
            // a hash; the rest leave it null, which MySQL does not collide.
            $table->unique(['form_id', 'field_key', 'value_hash'], 'form_values_unique_answer');
        });
    }

    public function down(): void
    {
        Schema::connection('forms')->dropIfExists('form_submission_values');
    }
};
