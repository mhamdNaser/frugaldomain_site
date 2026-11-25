<?php

namespace App\Modules\Market\Providers;

use Illuminate\Support\ServiceProvider;

class MarketServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
