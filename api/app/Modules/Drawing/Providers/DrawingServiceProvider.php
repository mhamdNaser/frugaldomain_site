<?php

namespace App\Modules\Drawing\Providers;

use Illuminate\Support\ServiceProvider;

class DrawingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
