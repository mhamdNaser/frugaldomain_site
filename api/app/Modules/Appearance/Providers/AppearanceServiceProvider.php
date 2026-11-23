<?php

namespace App\Modules\Appearance\Providers;

use Illuminate\Support\ServiceProvider;

class AppearanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
