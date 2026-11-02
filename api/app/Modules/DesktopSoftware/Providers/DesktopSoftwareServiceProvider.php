<?php

namespace App\Modules\DesktopSoftware\Providers;

use Illuminate\Support\ServiceProvider;

class DesktopSoftwareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
