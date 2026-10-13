<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One answer, extracted from the JSON so it can be indexed.
 *
 * Rows exist for the fields that are marked unique or that the owner filters
 * and sorts by - not for every answer of every field, which would triple the
 * write cost of a submission for no gain.
 */
class FormSubmissionValue extends Model
{
    protected $connection = 'forms';

    protected $fillable = [
        'form_id',
        'submission_id',
        'field_key',
        'value_text',
        'value_number',
        'value_date',
        'value_hash',
    ];

    protected $casts = [
        'form_id' => 'integer',
        'submission_id' => 'integer',
        'value_date' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }
}
