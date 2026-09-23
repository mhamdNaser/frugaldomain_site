<?php

namespace App\Modules\Account\Support;

use App\Modules\Account\Mail\PasswordChanged;
use App\Modules\Account\Mail\PasswordResetCode;
use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resetting a forgotten password with a code sent to the account's email.
 *
 * The code is what proves the person owns the account: only someone who can
 * read that inbox has it. Everything else here keeps that proof sound:
 *   - six random digits, stored only as a hash, valid for 15 minutes, once;
 *   - five wrong guesses and the code is gone, so it cannot be brute-forced;
 *   - at most three codes per address per 15 minutes, so an inbox cannot be
 *     flooded;
 *   - the same answer whether or not the address has an account, so the
 *     form cannot be used to find out who is registered;
 *   - on success every session is ended and the owner is told by email.
 */
class PasswordResetService
{
    public const CODE_MINUTES = 15;
    public const MAX_ATTEMPTS = 5;

    /**
     * Sends a code if the address belongs to an active account. Returns
     * nothing either way: the caller answers identically in both cases.
     */
    public function sendCode(string $email, string $lang = 'en'): void
    {
        $email = Str::lower(trim($email));

        // Per address, regardless of IP: the IP limit on the route does not
        // stop a botnet from mailing one person three hundred codes.
        $key = 'password-code:' . sha1($email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return;
        }
        RateLimiter::hit($key, 15 * 60);

        $user = User::where('email', $email)->first();
        if (!$user || !$user->status) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now(), 'attempts' => 0],
        );

        try {
            Mail::to($user->email)->send(new PasswordResetCode(
                $code,
                $user->first_name ?: $user->name,
                $lang === 'ar' ? 'ar' : 'en',
                self::CODE_MINUTES,
            ));
        } catch (Throwable $exception) {
            // Never surfaced to the requester (it would reveal the address
            // exists); the operator sees it in the log.
            Log::error('Password reset code could not be sent.', ['error' => $exception->getMessage()]);
        }
    }

    /**
     * Sets the new password if the code is right. Returns null on success,
     * or the message to show - deliberately the same for a wrong, expired or
     * unknown code, so the answer says nothing about which it was.
     */
    public function reset(string $email, string $code, string $password, string $lang = 'en', ?string $ip = null): ?string
    {
        $email = Str::lower(trim($email));
        $invalid = 'The code is invalid or has expired. Request a new one.';

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$record) {
            return $invalid;
        }

        $expired = Carbon::parse($record->created_at)->addMinutes(self::CODE_MINUTES)->isPast();
        if ($expired || (int) ($record->attempts ?? 0) >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return $invalid;
        }

        if (!Hash::check($code, $record->token)) {
            $attempts = (int) ($record->attempts ?? 0) + 1;
            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return 'Too many wrong codes. Request a new one.';
            }
            DB::table('password_reset_tokens')->where('email', $email)->update(['attempts' => $attempts]);
            return $invalid;
        }

        $user = User::where('email', $email)->first();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        if (!$user) {
            return $invalid;
        }

        $user->password = $password;
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->tokens()->delete();

        try {
            Mail::to($user->email)->send(new PasswordChanged(
                $user->first_name ?: $user->name,
                $lang === 'ar' ? 'ar' : 'en',
                $ip,
            ));
        } catch (Throwable $exception) {
            Log::error('Password changed notice could not be sent.', ['error' => $exception->getMessage()]);
        }

        return null;
    }
}
