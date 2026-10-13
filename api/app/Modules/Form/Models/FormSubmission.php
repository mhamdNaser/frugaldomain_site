<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One filled-in form, on the `forms` connection.
 *
 * The answers are a single JSON column: one insert per submission, which is
 * what keeps a form that has just been sent to a few thousand people from
 * turning each arrival into a fan of writes. Anything that has to be searched
 * or kept unique is mirrored into FormSubmissionValue.
 */
class FormSubmission extends Model
{
    protected $connection = 'forms';

    public const STATUS_RECEIVED = 'received';
    public const STATUS_INDEXED = 'indexed';
    public const STATUS_SPAM = 'spam';
    public const STATUS_INVALID = 'invalid';

    protected $fillable = [
        'uuid',
        'form_id',
        'form_user_id',
        'submitted_by',
        'data',
        'status',
        'ip_hash',
        'user_agent',
        'locale',
    ];

    protected $casts = [
        'data' => 'array',
        'form_id' => 'integer',
        'form_user_id' => 'integer',
        'submitted_by' => 'integer',
    ];

    public function values()
    {
        return $this->hasMany(FormSubmissionValue::class, 'submission_id');
    }

    /**
     * The owning form, which lives on the other connection.
     *
     * Eloquent runs this against the Form model's own connection, so it works
     * even when the two databases are on different servers. There is no
     * foreign key behind it - the application is what keeps them in step.
     */
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * An IP is never stored in the clear. It is only ever used to rate-limit
     * and to recognise a flood, and a salted hash does both; the salt is the
     * app key, so the hashes are useless if the answers database leaks alone.
     */
    public static function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
