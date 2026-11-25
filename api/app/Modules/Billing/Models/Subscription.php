<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'plan_id',
        'payment_method_id',
        'reference',
        'status',
        'started_at',
        'ends_at',
        'trial_ends_at',
        'receipt_path',
        'proof_submitted_at',
    ];

    protected $hidden = ['receipt_path'];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'proof_submitted_at' => 'datetime',
    ];

    protected $appends = ['has_receipt'];

    public function getHasReceiptAttribute(): bool
    {
        return (bool) $this->receipt_path;
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Modules\Stores\Models\Store::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
