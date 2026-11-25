<?php

namespace App\Modules\Market\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Market\Models\MarketItem;
use App\Modules\Market\Models\MarketPurchase;
use App\Modules\Market\Services\MarketPurchaseService;
use App\Modules\Market\Services\MarketReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Super admin: marketplace items and their purchases. */
class MarketAdminController extends Controller
{
    public function index()
    {
        return response()->json(['data' => MarketItem::orderBy('type')->orderBy('ordering')->orderBy('name')->get()->map(fn ($i) => $this->row($i))]);
    }

    public function store(Request $request)
    {
        $item = MarketItem::create($this->validated($request));
        $this->forget();

        return response()->json(['data' => $this->row($item)], 201);
    }

    public function update(Request $request, int $id)
    {
        $item = MarketItem::findOrFail($id);
        $data = $this->validated($request, $item);
        if (($data['github_token'] ?? '') === '') {
            unset($data['github_token']); // an empty field keeps the saved token
        }
        $item->update($data);
        $this->forget();

        return response()->json(['data' => $this->row($item->fresh())]);
    }

    public function destroy(int $id)
    {
        MarketItem::findOrFail($id)->delete();
        $this->forget();

        return response()->json(['message' => 'Deleted.']);
    }

    public function uploadFile(Request $request, int $id)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:zip', 'max:204800']]);
        $item = MarketItem::findOrFail($id);
        $item->update(['file_path' => $request->file('file')->store('market/'.$item->id, 'local')]);
        $this->forget();

        return response()->json(['data' => $this->row($item->fresh())]);
    }

    public function uploadLogo(Request $request, int $id)
    {
        $request->validate(['logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048']]);
        $item = MarketItem::findOrFail($id);
        $item->update(['logo_path' => $request->file('logo')->store('market/logos', 'local')]);
        $this->forget();

        return response()->json(['data' => $this->row($item->fresh())]);
    }

    public function sync(int $id, MarketReleaseService $releases)
    {
        try {
            $item = $releases->sync(MarketItem::findOrFail($id));
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            abort(422, 'GitHub refused the request ('.$e->response->status().'). Check the repository name and the token.');
        }
        $this->forget();

        return response()->json(['data' => $this->row($item)]);
    }

    public function purchases(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $page = MarketPurchase::with(['item:id,name,slug,type', 'user:id,name,email', 'paymentMethod:id,name,driver'])
            ->where('status', '!=', 'canceled')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                ->orWhereHas('item', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->latest('id')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn (MarketPurchase $p) => [
                'id' => $p->id, 'status' => $p->status, 'amount' => (string) $p->amount, 'reference' => $p->reference,
                'has_receipt' => (bool) $p->receipt_path, 'proof_submitted_at' => $p->proof_submitted_at,
                'paid_at' => $p->paid_at, 'created_at' => $p->created_at,
                'item' => $p->item?->only(['id', 'name', 'slug', 'type']),
                'user' => $p->user?->only(['id', 'name', 'email']),
                'payment_method' => $p->paymentMethod?->only(['id', 'name', 'driver']),
            ]),
            'meta' => ['total' => $page->total(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }

    public function confirm(int $id, MarketPurchaseService $purchases)
    {
        return response()->json(['data' => ['status' => $purchases->markPaid(MarketPurchase::findOrFail($id))->status]]);
    }

    public function receipt(int $id)
    {
        $path = MarketPurchase::findOrFail($id)->receipt_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Admin view: everything but the token and private paths. */
    private function row(MarketItem $item): array
    {
        return $item->toArray() + [
            'has_token' => (bool) $item->github_token,
            'has_file' => (bool) $item->file_path,
            'logo_url' => $item->logo_path ? url('/api/admin/market/items/'.$item->id.'/logo').'?v='.$item->updated_at?->timestamp : null,
            'file_name' => $item->file_path ? $item->downloadFilename() : null,
            'github_repo' => $item->github_repo,
        ];
    }

    public function logo(int $id)
    {
        $item = MarketItem::findOrFail($id);
        abort_unless($item->logo_path && Storage::disk('local')->exists($item->logo_path), 404);

        return response()->file(Storage::disk('local')->path($item->logo_path), ['Cache-Control' => 'private, max-age=300']);
    }

    private function forget(): void
    {
        Cache::forget(MarketController::LIST_CACHE.'theme');
        Cache::forget(MarketController::LIST_CACHE.'app');
    }

    private function validated(Request $request, ?MarketItem $item = null): array
    {
        $text = ['name' => 'string|max:255', 'summary' => 'string|max:1000', 'readme' => 'string|max:1048576',
            'seo_title' => 'string|max:255', 'seo_description' => 'string|max:500'];

        $data = $request->validate([
            'type' => ['required', Rule::in(['theme', 'app'])],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('market_items', 'slug')->ignore($item?->id)],
            'name' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'readme' => ['nullable', 'string', 'max:1048576'],
            'features' => ['nullable', 'array', 'max:30'],
            'features.*' => ['nullable', 'string', 'max:200'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array:ar'],
            'translations.ar' => ['nullable', 'array'],
            ...collect($text)->mapWithKeys(fn ($r, $k) => ["translations.ar.{$k}" => 'nullable|'.$r])->all(),
            'translations.ar.features' => ['nullable', 'array', 'max:30'],
            'translations.ar.features.*' => ['nullable', 'string', 'max:200'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'preview_url' => ['nullable', 'url:https', 'max:255'],
            'preview_password' => ['nullable', 'string', 'max:120'],
            'source' => ['required', Rule::in(['upload', 'github'])],
            'github_repo' => ['nullable', 'required_if:source,github', 'string', 'max:200', 'regex:/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/'],
            'github_token' => ['nullable', 'string', 'max:300'],
            'version' => ['nullable', 'string', 'max:60'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'ordering' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['features'] = array_values(array_filter(array_map('trim', $data['features'] ?? []), 'strlen'));
        if (isset($data['translations']['ar']['features'])) {
            $data['translations']['ar']['features'] = array_values(array_filter(array_map('trim', $data['translations']['ar']['features']), 'strlen'));
        }
        $data['ordering'] ??= 0;

        return $data;
    }
}
