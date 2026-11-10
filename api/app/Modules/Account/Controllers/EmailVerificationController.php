<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Support\EmailVerificationService;
use Illuminate\Http\Request;

/** Confirming the signed-in account's email with a code sent to it. */
class EmailVerificationController extends Controller
{
    public function __construct(private EmailVerificationService $verification) {}

    public function send(Request $request)
    {
        $error = $this->verification->sendCode($request->user(), $request->header('X-Language', 'en'));

        return $error
            ? response()->json(['message' => $error], 429)
            : response()->json(['message' => 'A code has been sent to your email.']);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|max:12'], ['code.required' => 'Enter the code.']);

        $error = $this->verification->verify($request->user(), $request->input('code'));

        return $error
            ? response()->json(['message' => $error], 422)
            : response()->json(['message' => 'Email confirmed.', 'email_verified' => true]);
    }
}
