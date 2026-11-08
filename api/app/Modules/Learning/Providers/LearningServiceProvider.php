<?php

namespace App\Modules\Learning\Providers;

use Illuminate\Support\ServiceProvider;

class LearningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
