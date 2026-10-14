<?php

namespace App\Modules\Convert\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ConvertServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * A conversion occupies a core on a two-core machine for several
         * seconds, so this is tighter than the site's other limits. Generous
         * enough for somebody converting a folder of documents one by one,
         * far below what a script would use.
         */
        RateLimiter::for('convert', fn (Request $request) => [
            Limit::perMinute(8)->by('convert|' . $request->ip()),
            Limit::perHour(60)->by('convert-hour|' . $request->ip()),
        ]);
    }
}
