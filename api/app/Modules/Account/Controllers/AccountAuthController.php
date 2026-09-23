<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
use App\Modules\Account\Requests\RegisterAccountRequest;
use App\Modules\Account\Resources\AccountUserResource;
use App\Modules\Core\Services\AnalyticsRecorder;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sign-up and sign-out for everyone, not only the panel.
 *
 * Sign-in stays at POST /login (User module), which already accepts any role.
 * These two were missing for ordinary users: the only registration route was
 * the admin one, which could not create a row, and the only sign-out route
 * sat behind role:admin, so a user's sign-out answered 403 and left the token
 * alive on the server.
 */
class AccountAuthController extends Controller
{
    public function __construct(private AccountRepositoryInterface $accounts) {}

    public function register(RegisterAccountRequest $request)
    {
        $user = $this->accounts->register($request->validated());

        app(AnalyticsRecorder::class)->recordLogin($request, [
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
            'provider' => 'register',
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Account created.',
            'user' => new AccountUserResource($user),
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Signed out.']);
    }
}
