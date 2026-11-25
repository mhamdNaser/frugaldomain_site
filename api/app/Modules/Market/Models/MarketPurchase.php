<?php

namespace App\Modules\Market\Models;

use App\Modules\Billing\Gateways\Payable;
use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One user buying one marketplace item; paid purchases unlock the download. */
class MarketPurchase extends Model implements Payable
{
    protected $fillable = [
        'user_id', 'market_item_id', 'amount', 'payment_method_id', 'status',
        'reference', 'receipt_path', 'proof_submitted_at', 'paid_at',
    ];

    protected $hidden = ['receipt_path'];

    protected $casts = [
        'amount' => 'decimal:2',
        'proof_submitted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(MarketItem::class, 'market_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payableAmount(): float
    {
        return (float) $this->amount;
    }

    public function payableTitle(): string
    {
        return (string) $this->item?->name;
    }

    public function payableReturnPath(): string
    {
        return '/shopify/purchase/return?purchase='.$this->id;
    }
}
