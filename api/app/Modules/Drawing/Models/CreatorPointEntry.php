<?php

namespace App\Modules\Drawing\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the creator points ledger.
 *
 * Entries are append-only. Nothing in the application updates or deletes a
 * row here: a mistake is corrected by writing a `reversal` entry, so the
 * history always explains how a balance was reached.
 */
class CreatorPointEntry extends Model
{
    public const REASON_USAGE = 'usage';
    public const REASON_PUBLISH = 'publish';
    public const REASON_ADJUSTMENT = 'adjustment';
    public const REASON_REVERSAL = 'reversal';
    public const REASON_PAYOUT = 'payout';

    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'source_type',
        'source_id',
        'drawing_id',
        'created_by',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function drawing()
    {
        return $this->belongsTo(Drawing::class, 'drawing_id');
    }

    /** A user's current balance, derived rather than stored. */
    public static function balanceFor(int $userId): int
    {
        return (int) static::where('user_id', $userId)->sum('amount');
    }
}
