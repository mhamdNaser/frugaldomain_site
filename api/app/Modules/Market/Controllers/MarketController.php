<?php

namespace App\Modules\Market\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Market\Models\MarketItem;
use App\Modules\Market\Models\MarketPurchase;
use App\Modules\Market\Resources\MarketItemResource;
use App\Modules\Market\Services\MarketPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** Public marketplace, checkout and the buyer's own purchases. */
class MarketController extends Controller
{
    public const LIST_CACHE = 'market_items_';

    public function __construct(private MarketPurchaseService $purchases) {}

    public function index(Request $request)
    {
        $type = $request->query('type') === 'app' ? 'app' : 'theme';

        $data = Cache::remember(self::LIST_CACHE.$type, 600, fn () => MarketItemResource::collection(
            MarketItem::where('status', 'published')->where('type', $type)->orderBy('ordering')->orderBy('name')->get()
        )->resolve());

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, string $slug)
    {
        $item = $this->published($slug);
        $resource = new MarketItemResource($item);
        $resource->owned = $item->ownedBy(auth('sanctum')->user());

        return response()->json(['data' => $resource->resolve()]);
    }

    /** Any status: the dashboard shows drafts' logos too, and a logo is not private. */
    public function logo(string $slug)
    {
        $item = MarketItem::where('slug', $slug)->firstOrFail();
        abort_unless($item->logo_path && Storage::disk('local')->exists($item->logo_path), 404);

        return response()->file(Storage::disk('local')->path($item->logo_path), ['Cache-Control' => 'public, max-age=86400']);
    }

    /** Active payment methods offered for marketplace items. */
    public function paymentMethods()
    {
        return response()->json(['data' => PaymentMethod::where('is_active', true)->orderBy('sort')->get(['id', 'name', 'driver'])]);
    }

    public function checkout(Request $request, string $slug)
    {
        $data = $request->validate(['payment_method_id' => ['nullable', 'integer']]);

        return response()->json(['data' => $this->purchases->checkout($request->user(), $this->published($slug), $data['payment_method_id'] ?? null)]);
    }

    public function verify(Request $request, int $id)
    {
        $purchase = $this->purchases->verify($this->purchases->ownedBy($request->user(), $id));

        return response()->json(['data' => ['status' => $purchase->status, 'slug' => $purchase->item?->slug, 'type' => $purchase->item?->type]]);
    }

    public function proof(Request $request, int $id)
    {
        $purchase = $this->purchases->ownedBy($request->user(), $id);
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $reference = isset($data['reference']) ? trim(strip_tags($data['reference'])) : '';
        $this->purchases->submitProof($purchase, $reference !== '' ? $reference : null, $request->file('receipt'));

        return response()->json(['message' => 'Thanks. We will confirm the payment soon.']);
    }

    /**
     * A browser download cannot carry the API token, so a paid buyer (or the admin)
     * gets a signed link valid for five minutes instead.
     */
    public function downloadLink(Request $request, string $slug)
    {
        $item = $this->published($slug);
        $user = $request->user();
        abort_unless($user->hasRole('admin') || $item->ownedBy($user), 403, 'Buy this item to download it.');
        abort_unless($item->file_path && Storage::disk('local')->exists($item->file_path), 404, 'The file is not available yet.');

        return response()->json(['data' => [
            'url' => URL::temporarySignedRoute('market.download', now()->addMinutes(5), ['slug' => $item->slug]),
        ]]);
    }

    /** Signed link only: the latest zip, wherever it came from (upload or GitHub release). */
    public function download(string $slug)
    {
        $item = $this->published($slug);
        abort_unless($item->file_path && Storage::disk('local')->exists($item->file_path), 404, 'The file is not available yet.');

        return Storage::disk('local')->download($item->file_path, $item->downloadFilename());
    }

    public function myPurchases(Request $request)
    {
        $rows = MarketPurchase::where('user_id', $request->user()->id)
            ->where('status', '!=', 'canceled')
            ->with(['item', 'paymentMethod:id,name,driver'])
            ->latest('id')->get()
            ->map(fn (MarketPurchase $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => (string) $p->amount,
                'reference' => $p->reference,
                'proof_submitted_at' => $p->proof_submitted_at,
                'paid_at' => $p->paid_at,
                'created_at' => $p->created_at,
                'payment_method' => $p->paymentMethod?->only(['id', 'name', 'driver']),
                // Still to pay offline: where to send it (never the method's secrets).
                'pay_to' => $p->status === 'pending' && $p->paymentMethod && in_array($p->paymentMethod->driver, ['manual', 'shamcash'], true) ? [
                    'instructions' => (object) ($p->paymentMethod->instructions ?? []),
                    'account' => $p->paymentMethod->driver === 'shamcash'
                        ? ['code' => $p->paymentMethod->config['account_code'] ?? '', 'name' => $p->paymentMethod->config['account_name'] ?? '']
                        : null,
                ] : null,
                'item' => $p->item ? (new MarketItemResource($p->item))->resolve() : null,
            ]);

        return response()->json(['data' => $rows]);
    }

    private function published(string $slug): MarketItem
    {
        return MarketItem::where('status', 'published')->where('slug', $slug)->firstOrFail();
    }
}
