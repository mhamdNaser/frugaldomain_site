<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'driver', 'config', 'instructions', 'is_active', 'sort'];

    protected $hidden = ['config'];

    protected $casts = [
        'config' => 'encrypted:array',
        'instructions' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_payment_method');
    }
}
