<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Requests\ForgotPasswordRequest;
use App\Modules\Account\Requests\ResetForgottenPasswordRequest;
use App\Modules\Account\Support\PasswordResetService;
use App\Modules\Account\Support\ProofOfWork;
use Illuminate\Http\Request;

/**
 * "Forgot password" for the public site. See PasswordResetService for what
 * proves the person owns the account, and ProofOfWork for what keeps
 * scripts from hammering it.
 */
class PasswordResetController extends Controller
{
    public function __construct(private PasswordResetService $resets) {}

    public function challenge()
    {
        return response()
            ->json(ProofOfWork::create())
            ->header('Cache-Control', 'no-store');
    }

    public function sendCode(ForgotPasswordRequest $request)
    {
        if (!ProofOfWork::verify($request->input('altcha'), 3)) {
            return $this->failedCheck();
        }

        $this->resets->sendCode($request->input('email'), $this->lang($request));

        // Identical whether or not the address has an account.
        return response()->json([
            'message' => 'If an account uses this email, a code is on its way.',
        ]);
    }

    public function reset(ResetForgottenPasswordRequest $request)
    {
        if (!ProofOfWork::verify($request->input('altcha'), 2)) {
            return $this->failedCheck();
        }

        $error = $this->resets->reset(
            $request->input('email'),
            $request->input('code'),
            $request->input('password'),
            $this->lang($request),
            $request->ip(),
        );

        if ($error) {
            return response()->json(['message' => $error, 'errors' => ['code' => [$error]]], 422);
        }

        return response()->json(['message' => 'Your password was changed. Sign in with the new one.']);
    }

    private function failedCheck()
    {
        return response()->json([
            'message' => 'The security check failed. Try again.',
        ], 422);
    }

    private function lang(Request $request): string
    {
        return $request->header('X-Language') === 'ar' ? 'ar' : 'en';
    }
}
