<?php

namespace App\Modules\Market\Services;

use App\Modules\Billing\Gateways\GatewayRegistry;
use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Market\Models\MarketItem;
use App\Modules\Market\Models\MarketPurchase;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Buying a marketplace item with the same gateways the plans use. */
class MarketPurchaseService
{
    public function __construct(private GatewayRegistry $gateways) {}

    public function checkout(User $user, MarketItem $item, ?int $paymentMethodId): array
    {
        abort_if($item->ownedBy($user), 422, 'You already own this item.');

        if ($item->isFree()) {
            $this->markPaid(MarketPurchase::create([
                'user_id' => $user->id, 'market_item_id' => $item->id, 'amount' => 0, 'status' => 'pending',
            ]));

            return ['type' => 'owned'];
        }

        $method = $paymentMethodId ? PaymentMethod::where('is_active', true)->find($paymentMethodId) : null;
        if (! $method) {
            throw ValidationException::withMessages(['payment_method_id' => 'Choose a payment method.']);
        }

        $purchase = DB::transaction(function () use ($user, $item, $method) {
            // One open checkout per user and item: a new choice replaces the old one.
            MarketPurchase::where('user_id', $user->id)->where('market_item_id', $item->id)
                ->where('status', 'pending')->update(['status' => 'canceled']);

            return MarketPurchase::create([
                'user_id' => $user->id, 'market_item_id' => $item->id, 'amount' => $item->price,
                'payment_method_id' => $method->id, 'status' => 'pending',
            ]);
        });
        $purchase->setRelation('item', $item);

        return $this->gateways->get($method->driver)->checkout($purchase, $method);
    }

    public function ownedBy(User $user, int $id): MarketPurchase
    {
        return MarketPurchase::where('user_id', $user->id)->with(['item', 'paymentMethod'])->findOrFail($id);
    }

    /** Back from PayPal or Stripe. */
    public function verify(MarketPurchase $purchase): MarketPurchase
    {
        if ($purchase->status === 'paid') {
            return $purchase;
        }
        abort_unless($purchase->status === 'pending' && $purchase->paymentMethod, 422, 'This payment is no longer open.');

        $paid = $this->gateways->get($purchase->paymentMethod->driver)->verify($purchase, $purchase->paymentMethod);
        abort_if($paid === null, 422, 'This payment is confirmed by the site admin.');
        abort_unless($paid, 422, 'The payment is not complete yet.');

        return $this->markPaid($purchase);
    }

    public function submitProof(MarketPurchase $purchase, ?string $reference, ?UploadedFile $receipt): MarketPurchase
    {
        abort_unless($purchase->status === 'pending' && $purchase->paymentMethod, 422, 'This payment is no longer open.');
        abort_if($this->gateways->get($purchase->paymentMethod->driver)->proof() === null, 422, 'This payment method does not take a receipt.');

        if ($reference === null && $receipt === null) {
            throw ValidationException::withMessages(['reference' => 'Enter the transfer number or attach the receipt.']);
        }

        $old = $purchase->receipt_path;
        $purchase->forceFill([
            'reference' => $reference ?? $purchase->reference,
            'receipt_path' => $receipt ? $receipt->store('market-receipts', 'local') : $old,
            'proof_submitted_at' => now(),
        ])->save();

        if ($receipt && $old) {
            Storage::disk('local')->delete($old);
        }

        return $purchase;
    }

    /** Idempotent: a paid purchase stays as it is. */
    public function markPaid(MarketPurchase $purchase): MarketPurchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = MarketPurchase::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'paid') {
                return $purchase;
            }
            abort_unless($purchase->status === 'pending', 422, 'Only a pending purchase can be confirmed.');
            $purchase->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

            return $purchase;
        });
    }
}
