<?php

namespace App\Modules\Form\Providers;

use App\Modules\Form\Console\SeedFormResponses;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([SeedFormResponses::class]);
        }

        /*
         * Named limiters, one counter each, so viewing a form does not spend
         * the allowance for answering it.
         *
         * The submit limiter is the one that matters. A form sent to a few
         * thousand people produces a burst that must get through, so the
         * ceiling is per address and generous enough for a household or an
         * office behind one address - while still far below what a script
         * would use to stuff a form. The second limit is the long tail: the
         * same address cannot keep that rate up for an hour.
         */
        RateLimiter::for('form-submit', fn (Request $request) => [
            Limit::perMinute(10)->by('form-submit|' . $request->ip()),
            Limit::perHour(60)->by('form-submit-hour|' . $request->ip()),
        ]);

        RateLimiter::for('form-view', fn (Request $request) => Limit::perMinute(60)->by('form-view|' . $request->ip()));

        // Keyed on the account for the owner's own actions: one person
        // rebuilding a form cannot slow another down.
        RateLimiter::for('form-write', fn (Request $request) => Limit::perMinute(40)->by(
            'form-write|' . ($request->user()?->id ?: $request->ip())
        ));

        // An export reads the whole response set, so it is deliberately the
        // tightest of the four.
        RateLimiter::for('form-export', fn (Request $request) => Limit::perMinute(6)->by(
            'form-export|' . ($request->user()?->id ?: $request->ip())
        ));
    }
}
