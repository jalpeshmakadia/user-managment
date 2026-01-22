<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            \App\Repositories\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class
        );

        // Bind services
        $this->app->singleton(\App\Services\PhoneNormalizationService::class);
        $this->app->singleton(\App\Services\AvatarStorageService::class);
        $this->app->singleton(\App\Services\UserService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        
        // Register model observers
        User::observe(UserObserver::class);
    }
}
