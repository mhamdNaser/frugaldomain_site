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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
