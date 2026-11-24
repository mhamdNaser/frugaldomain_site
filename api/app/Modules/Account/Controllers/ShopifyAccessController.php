<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * A site account becomes a store owner ("partner") when they choose to connect a
 * Shopify store; sign-up itself stays a plain account.
 */
class ShopifyAccessController extends Controller
{
    public function enable(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('partner')) {
            $user->assignRole('partner');
        }

        return response()->json(['data' => ['is_partner' => true]]);
    }
}
