<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'billing_interval',
        'trial_days',
        'is_active',
        'max_team_members',
        'description',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Partners read a cached plans list; any plan change must show at once.
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(\App\Modules\Billing\Controllers\PaymentMethodController::PLANS_CACHE);
        static::saved($forget);
        static::deleted($forget);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'plan_payment_method');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
