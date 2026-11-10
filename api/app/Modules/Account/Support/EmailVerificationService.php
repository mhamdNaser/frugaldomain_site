<?php

namespace App\Modules\Account\Support;

use App\Modules\Account\Mail\EmailVerificationCode;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Proving an account's email address with a six-digit code, the same rules
 * as the password reset code: stored only as a hash, valid 15 minutes and
 * once, five wrong guesses and it is gone, three codes per 15 minutes.
 * Lesson content is served only to verified accounts.
 */
class EmailVerificationService
{
    public const CODE_MINUTES = 15;
    public const MAX_ATTEMPTS = 5;

    /** Returns null when sent (or nothing to do), else the message to show. */
    public function sendCode(User $user, string $lang = 'en'): ?string
    {
        if ($user->email_verified_at) {
            return null;
        }

        $limit = 'email-code:'.$user->id;
        if (RateLimiter::tooManyAttempts($limit, 3)) {
            return 'Too many codes requested. Try again in a few minutes.';
        }
        RateLimiter::hit($limit, self::CODE_MINUTES * 60);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($user), ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(self::CODE_MINUTES));

        try {
            Mail::to($user->email)->send(new EmailVerificationCode(
                $code,
                $user->first_name ?: $user->name,
                $lang === 'ar' ? 'ar' : 'en',
                self::CODE_MINUTES,
            ));
        } catch (Throwable $exception) {
            Log::error('Email verification code could not be sent.', ['error' => $exception->getMessage()]);

            return 'The code could not be sent. Please try again later.';
        }

        return null;
    }

    /** Returns null when the address is now verified, else the message to show. */
    public function verify(User $user, string $code): ?string
    {
        if ($user->email_verified_at) {
            return null;
        }

        $entry = Cache::get($this->key($user));
        $wrong = 'The code is wrong or has expired.';
        if (! $entry) {
            return $wrong;
        }

        if (! Hash::check(trim($code), $entry['hash'])) {
            $entry['attempts']++;
            $entry['attempts'] >= self::MAX_ATTEMPTS
                ? Cache::forget($this->key($user))
                : Cache::put($this->key($user), $entry, now()->addMinutes(self::CODE_MINUTES));

            return $wrong;
        }

        Cache::forget($this->key($user));
        $user->forceFill(['email_verified_at' => now()])->save();

        return null;
    }

    private function key(User $user): string
    {
        return 'email-verification:'.$user->id;
    }
}
