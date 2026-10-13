<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One field of a form.
 *
 * `key` is the name the answer is stored under, and it is what makes an
 * exported spreadsheet readable. It is generated from the label once and then
 * frozen: renaming a label must not orphan every answer already collected
 * under the old key.
 *
 * The validation rules are not stored as Laravel rule strings. They are stored
 * as intent - required, unique, a length, a digit count, a date range - and
 * turned into rules by `FieldTypes` at request time. Storing the intent means
 * the same definition drives the server's validation, the browser's hints and
 * the column header in the export, and that a rule can be fixed centrally
 * without rewriting rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            $table->string('key', 64);
            $table->string('type', 32);

            $table->string('label');
            $table->string('label_ar')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('help')->nullable();
            $table->string('help_ar')->nullable();

            $table->boolean('required')->default(false);

            /*
             * A unique field is enforced by a real unique index over the
             * answers (see form_submission_values), not by a lookup that two
             * simultaneous submissions could both pass.
             */
            $table->boolean('unique')->default(false);

            // Type-specific settings: min/max, the exact digit count, the
            // options of a select, the allowed date range, a regex.
            $table->json('rules')->nullable();
            $table->json('options')->nullable();

            $table->string('default_value')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'key']);
            $table->index(['form_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
