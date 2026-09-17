<?php

namespace App\Modules\App\Providers;

use App\Modules\App\Repositories\Eloquent\AppMediaRepository;
use App\Modules\App\Repositories\Eloquent\AppRepository;
use App\Modules\App\Repositories\Interfaces\AppMediaRepositoryInterface;
use App\Modules\App\Repositories\Interfaces\AppRepositoryInterface;
use App\Modules\App\Support\HostingerClient;
use Illuminate\Support\ServiceProvider;

/**
 * Named AppCatalogueServiceProvider rather than AppServiceProvider so it does
 * not collide with Laravel's own App\Providers\AppServiceProvider when both
 * are referenced by short name.
 */
class AppCatalogueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AppRepositoryInterface::class, AppRepository::class);
        $this->app->bind(AppMediaRepositoryInterface::class, AppMediaRepository::class);

        // Stateless apart from its one-hour site cache, so a singleton keeps
        // the resolved {username, domain} pair warm within a request.
        $this->app->singleton(HostingerClient::class, fn() => new HostingerClient());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
