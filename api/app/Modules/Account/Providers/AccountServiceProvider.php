<?php

namespace App\Modules\Account\Providers;

use App\Modules\Account\Repositories\Eloquent\AccountRepository;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
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
    }
}
