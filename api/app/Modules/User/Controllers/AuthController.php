<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Requests\User\RegisterRequest;
use App\Modules\User\Requests\User\LoginRequest;
use App\Modules\User\Requests\User\ChangePasswordRequest;
use App\Modules\User\Requests\User\ForgotPasswordRequest;
use App\Modules\User\Requests\User\ResetPasswordWithCodeRequest;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Core\Services\AnalyticsRecorder;
use App\Modules\User\Models\User;
use App\Modules\User\Resources\LoginResource;
use App\Modules\Account\Support\PasswordResetService;

class AuthController extends Controller
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * ✅ Register (API)
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->users->create($request->validated());

        // إنشاء توكن مباشر بعد التسجيل
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * ✅ Login لأي مستخدم (Token-based)
     */
    public function userLogin(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // حذف التوكنات القديمة (اختياري)
        $user->tokens()->delete();

        // إنشاء توكن جديد
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * ✅ Login للأدمن فقط (Token-based)
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])
            ->with('roles')
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            app(AnalyticsRecorder::class)->recordLogin($request, [
                'user_id' => $user?->id,
                'email' => $credentials['email'],
                'successful' => false,
                'failure_reason' => 'Invalid credentials',
            ]);

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $allowedRoles = ['admin'];
        // $allowedRoles = ['admin', 'partner'];

        if (!$user->hasAnyRole($allowedRoles)) {
            app(AnalyticsRecorder::class)->recordLogin($request, [
                'user_id' => $user->id,
                'email' => $user->email,
                'successful' => false,
                'failure_reason' => 'Role not permitted',
            ]);

            return response()->json([
                'message' => 'You are not authorized to access this panel'
            ], 403);
        }

        app(AnalyticsRecorder::class)->recordLogin($request, [
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
        ]);

        $user->tokens()->delete();

        $tokenAbilities = $user->roles
            ->pluck('name')
            ->filter(fn($role) => in_array($role, $allowedRoles))
            ->map(fn($role) => 'role:' . $role)
            ->values()
            ->toArray();

        $token = $user->createToken('panel_token', $tokenAbilities)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new LoginResource($user),
            'token' => $token,
        ]);
    }

    /**
     * ✅ Logout (Token-based)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * ✅ Get Authenticated User
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * The panel's own "forgot password". It used to return the code in the
     * response whenever the server was not in production mode (so anyone who
     * knew an address could take the account), never actually sent it, and
     * had no limit on guesses. It now goes through the same service as the
     * public site: the code is only ever emailed, and the answer is the same
     * whether or not the address exists.
     */
    public function forgotPassword(ForgotPasswordRequest $request, PasswordResetService $resets)
    {
        $resets->sendCode(
            (string) $request->validated()['email'],
            $request->header('X-Language') === 'ar' ? 'ar' : 'en',
        );

        return response()->json([
            'message' => 'If an account uses this email, a code is on its way.',
        ]);
    }

    public function resetPasswordWithCode(ResetPasswordWithCodeRequest $request, PasswordResetService $resets)
    {
        $validated = $request->validated();

        $error = $resets->reset(
            (string) $validated['email'],
            (string) $validated['code'],
            (string) $validated['new_password'],
            $request->header('X-Language') === 'ar' ? 'ar' : 'en',
            $request->ip(),
        );

        if ($error) {
            return response()->json(['message' => $error, 'errors' => ['code' => [$error]]], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
