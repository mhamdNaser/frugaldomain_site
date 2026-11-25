<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Super admin: store owners ("partners") with their store, and adding one by hand. */
class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = $data['search'] ?? null;

        $page = User::role('partner')
            ->with(['store' => fn ($q) => $q->select('id', 'owner_id', 'name', 'shopify_domain', 'plan_id', 'status')->with('plan:id,name')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('store', fn ($q) => $q->where('shopify_domain', 'like', "%{$search}%"))))
            ->orderByDesc('id')
            ->paginate($data['rowsPerPage'] ?? 10, ['*'], 'page', $data['page'] ?? 1);

        return response()->json([
            'data' => collect($page->items())->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => (bool) $u->status,
                'store_name' => $u->store?->name,
                'store_domain' => $u->store?->shopify_domain,
                'plan' => $u->store?->plan?->name,
                'created_at' => $u->created_at,
            ]),
            'meta' => [
                'total' => $page->total(), 'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $user = User::create([
            'first_name' => strip_tags($data['first_name']),
            'last_name' => strip_tags($data['last_name']),
            'name' => trim(strip_tags($data['first_name']).' '.strip_tags($data['last_name'])),
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'status' => 1,
        ]);
        $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('partner', 'web'));

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]], 201);
    }
}
