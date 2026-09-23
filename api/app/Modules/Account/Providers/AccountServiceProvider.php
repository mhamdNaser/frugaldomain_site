<?php

namespace App\Modules\Account\Providers;

use App\Modules\Account\Repositories\Eloquent\AccountRepository;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Named limiters, one counter each. The anonymous "throttle:5,10"
        // form keys every throttled route on the IP alone, so fetching a
        // proof-of-work challenge used up the allowance for sending the code
        // and a person retrying twice was locked out.
        RateLimiter::for('password-challenge', fn(Request $request) => Limit::perMinute(20)->by('pw-challenge|' . $request->ip()));
        RateLimiter::for('password-forgot', fn(Request $request) => Limit::perMinutes(10, 5)->by('pw-forgot|' . $request->ip()));
        RateLimiter::for('password-reset', fn(Request $request) => Limit::perMinutes(10, 10)->by('pw-reset|' . $request->ip()));

        RateLimiter::for('account-register', fn(Request $request) => Limit::perMinute(6)->by('acc-register|' . $request->ip()));
        // Keyed on the account, not the IP, for signed-in actions.
        RateLimiter::for('account-password', fn(Request $request) => Limit::perMinute(6)->by('acc-password|' . ($request->user()?->id ?: $request->ip())));
        RateLimiter::for('account-delete', fn(Request $request) => Limit::perMinute(3)->by('acc-delete|' . ($request->user()?->id ?: $request->ip())));
        RateLimiter::for('admin-reset-password', fn(Request $request) => Limit::perMinute(10)->by('admin-reset|' . ($request->user()?->id ?: $request->ip())));

        // Sign-in had no limit at all, so a password could be guessed at
        // whatever speed a script could manage. Keyed on the address and the
        // IP together: one attacker cannot lock a person out from elsewhere.
        RateLimiter::for('login', fn(Request $request) => Limit::perMinute(10)->by(
            'login|' . strtolower((string) $request->input('email')) . '|' . $request->ip()
        ));
    }
}
