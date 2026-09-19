<?php

namespace App\Modules\Component\Providers;

use App\Modules\Component\Repositories\Eloquent\ComponentCategoryRepository;
use App\Modules\Component\Repositories\Eloquent\ComponentRepository;
use App\Modules\Component\Repositories\Interfaces\ComponentCategoryRepositoryInterface;
use App\Modules\Component\Repositories\Interfaces\ComponentRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ComponentRepositoryInterface::class, ComponentRepository::class);
        $this->app->bind(ComponentCategoryRepositoryInterface::class, ComponentCategoryRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
